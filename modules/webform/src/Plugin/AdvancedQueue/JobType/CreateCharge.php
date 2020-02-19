<?php

namespace Drupal\vipps_recurring_payments_webform\Plugin\AdvancedQueue\JobType;

use Drupal\advancedqueue\Annotation\AdvancedQueueJobType;
use Drupal\Core\Annotation\Translation;
use Drupal\advancedqueue\Job;
use Drupal\advancedqueue\JobResult;
use Drupal\advancedqueue\Plugin\AdvancedQueue\JobType\JobTypeBase;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
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

  public function __construct(
    LoggerChannelFactoryInterface $loggerChannelFactory,
    ProductSubscriptionRepositoryInterface $productSubscriptionRepository,
    WebformSubmissionRepository $submissionRepository,
    VippsService $vippsService,
    VippsHttpClient $httpClient
  )
  {
    $this->product = $productSubscriptionRepository->getProduct();
    $this->logger = $loggerChannelFactory->get('vipps');
    $this->vippsService = $vippsService;
    $this->submissionRepository = $submissionRepository;
    $this->httpClient = $httpClient;
  }

  /**
   * {@inheritdoc}
   */
  public function process(Job $job) {
    try {
      $payload = $job->getPayload();

      $this->vippsService->createChargeItem(
        new ChargeItem($payload['orderId'], $this->product->getIntegerPrice()),
        $this->httpClient->auth()
      );

      //TODO save charge to database

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

    return new static(
      $loggerFactory,
      $productSubscriptionRepository,
      $submissionRepository,
      $vippsService,
      $httpClient
    );
  }
}
