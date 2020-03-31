<?php

namespace Drupal\vipps_recurring_payments_webform\Plugin\AdvancedQueue\JobType;

use Drupal\advancedqueue\Annotation\AdvancedQueueJobType;
use Drupal\Core\Annotation\Translation;
use Drupal\vipps_recurring_payments\Service\DelayManager;
use Drupal\advancedqueue\Job;
use Drupal\advancedqueue\JobResult;
use Drupal\advancedqueue\Entity\Queue;
use Drupal\advancedqueue\Plugin\AdvancedQueue\JobType\JobTypeBase;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\vipps_recurring_payments\Entity\PeriodicCharges;
use Drupal\vipps_recurring_payments\Entity\VippsAgreements;
use Drupal\vipps_recurring_payments\Repository\ProductSubscriptionRepositoryInterface;
use Drupal\vipps_recurring_payments\Service\VippsHttpClient;
use Drupal\vipps_recurring_payments\Service\VippsService;
use Drupal\vipps_recurring_payments_webform\Repository\WebformSubmissionRepository;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\vipps_recurring_payments\UseCase\ChargeItem;

/**
 * @AdvancedQueueJobType(
 *   id = "create_charge_job",
 *   label = @Translation("Confirm charge queue"),
 * )
 */
class CreateCharge extends JobTypeBase implements ContainerFactoryPluginInterface
{
  private $logger;

  private $product;

  private $vippsService;

  private $submissionRepository;

  private $httpClient;

  private $delayManager;

  public function __construct(
    LoggerChannelFactoryInterface $loggerChannelFactory,
    ProductSubscriptionRepositoryInterface $productSubscriptionRepository,
    WebformSubmissionRepository $submissionRepository,
    VippsService $vippsService,
    VippsHttpClient $httpClient,
    DelayManager $delayManager
  )
  {
    $this->product = $productSubscriptionRepository->getProduct();
    $this->logger = $loggerChannelFactory->get('vipps');
    $this->vippsService = $vippsService;
    $this->submissionRepository = $submissionRepository;
    $this->httpClient = $httpClient;
    $this->delayManager = $delayManager;
  }

  /**
   * {@inheritdoc}
   */
  public function process(Job $job) {
    try {
      $payload = $job->getPayload();

      $agreementId = $payload['orderId'];
      $agreementNodeId = $payload['agreementNodeId'];
      $agreementNode = VippsAgreements::load($agreementNodeId);
      $agreementNode->getPrice();

      $chargeId = $this->vippsService->createChargeItem(
        new ChargeItem($agreementId, $this->product->getIntegerPrice()),
        $this->httpClient->auth()
      );

      // Get charge
      $charge = $this->httpClient->getCharge($this->httpClient->auth(), $agreementId, $chargeId);
      // Store charge in periodic_charges entity
      if (isset($charge)) {
        $chargeNode = new PeriodicCharges([
          'type' => 'periodic_charges',
        ], 'periodic_charges');
        $chargeNode->set('status', 1);
        $chargeNode->setChargeId($chargeId);
        $chargeNode->setPrice($charge->getAmount());
        $chargeNode->setParentId($agreementId);
        $chargeNode->setStatus($charge->getStatus());
        $chargeNode->setDescription($charge->getDescription());
        $chargeNode->save();

        // Add new job to queue for the next charge
        $job = Job::create('create_charge_job', [
          'orderId' => $agreementId,
          'agreementNodeId' => $agreementNodeId
        ]);
        $queue = Queue::load('default'); //TODO use custom queue
        $queue->enqueueJob($job, $this->delayManager->getCountSecondsToNextPayment($this->product));

        $this->logger->info(
          sprintf("Charge for %s has been done successfully", $agreementId)
        );
      }

      return JobResult::success();
    } catch (\Throwable $e) {
      return JobResult::failure($e->getMessage());
    }
  }

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition)
  {
    /* @var $loggerFactory LoggerChannelFactoryInterface */
    $loggerFactory = $container->get('logger.factory');

    /* @var ProductSubscriptionRepositoryInterface $productSubscriptionRepository */
    $productSubscriptionRepository = $container->get('vipps_recurring_payments:product_subscription_repository');

    /* @var WebformSubmissionRepository $submissionRepository */
    $submissionRepository = $container->get('vipps_recurring_payments_webform:submission_repository');

    /* @var VippsService $vippsService */
    $vippsService = $container->get('vipps_recurring_payments:vipps_service');

    /* @var VippsHttpClient $httpClient */
    $httpClient = $container->get('vipps_recurring_payments:http_client');

    /* @var DelayManager $delayManager */
    $delayManager = $container->get('vipps_recurring_payments:delay_manager');

    return new static(
      $loggerFactory,
      $productSubscriptionRepository,
      $submissionRepository,
      $vippsService,
      $httpClient,
      $delayManager
    );
  }
}
