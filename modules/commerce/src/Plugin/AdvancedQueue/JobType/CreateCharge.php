<?php

namespace Drupal\vipps_recurring_payments_commerce\Plugin\AdvancedQueue\JobType;

use Drupal\advancedqueue\Annotation\AdvancedQueueJobType;
use Drupal\commerce_payment\Entity\Payment;
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
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\vipps_recurring_payments\UseCase\ChargeItem;

/**
 * @AdvancedQueueJobType(
 *   id = "create_charge_job_commerce",
 *   label = @Translation("Create charge queue - Commerce"),
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
    VippsService $vippsService,
    VippsHttpClient $httpClient,
    DelayManager $delayManager
  )
  {
    $this->product = $productSubscriptionRepository->getProduct();
    $this->logger = $loggerChannelFactory->get('vipps');
    $this->vippsService = $vippsService;
    $this->httpClient = $httpClient;
    $this->delayManager = $delayManager;
  }

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition)
  {
    /* @var $loggerFactory LoggerChannelFactoryInterface */
    $loggerFactory = $container->get('logger.factory');

    /* @var ProductSubscriptionRepositoryInterface $productSubscriptionRepository */
    $productSubscriptionRepository = $container->get('vipps_recurring_payments:product_subscription_repository');

    /* @var VippsService $vippsService */
    $vippsService = $container->get('vipps_recurring_payments:vipps_service');

    /* @var VippsHttpClient $httpClient */
    $httpClient = $container->get('vipps_recurring_payments:http_client');

    /* @var DelayManager $delayManager */
    $delayManager = $container->get('vipps_recurring_payments:delay_manager');

    return new static(
      $loggerFactory,
      $productSubscriptionRepository,
      $vippsService,
      $httpClient,
      $delayManager
    );
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

      $message_variables = ['%aid' => $agreementId];

      try {
        $chargeId = $this->vippsService->createChargeItem(
          new ChargeItem($agreementId, $agreementNode->getPrice()*100, 'Recurring charge'),
          $this->httpClient->auth()
        );
      } catch (\Exception $exception) {
        $message_variables['%m'] = $exception->getMessage();
        \Drupal::logger('vipps_recurring_commerce')->error(
          'Agreement %aid: Problem creating charge. Message: %m',
          $message_variables
        );
        throw new \Exception($exception->getMessage());
      }

      $message_variables['%cid'] = $chargeId;

      try {
        // Get charge
        $charge = $this->httpClient->getCharge($this->httpClient->auth(), $agreementId, $chargeId);
      } catch (\Exception $exception) {
        $message_variables['%m'] = $exception->getMessage();
        \Drupal::logger('vipps_recurring_commerce')->error(
          'Agreement %aid: Problem getting charge %cid. Message: %m',
          $message_variables
        );
        throw new \Exception($exception->getMessage());
      }

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
        $job = Job::create('create_charge_job_commerce', [
          'orderId' => $agreementId,
          'agreementNodeId' => $agreementNodeId
        ]);
        $queue = Queue::load('vipps_recurring_payments');
        $queue->enqueueJob($job, $this->delayManager->getCountSecondsToNextPayment($this->product));

        try {
          // Get charges
          $charges = $this->httpClient->getCharges($this->httpClient->auth(), $agreementId);
        } catch (\Exception $exception) {
          $message_variables['%m'] = $exception->getMessage();
          \Drupal::logger('vipps_recurring_commerce')->error(
            'Agreement %aid: Problem getting charges. Message: %m',
            $message_variables
          );
          throw new \Exception($exception->getMessage());
        }

        //Get first charge
        $first_charge = $charges[0];

        // Get payment associated to charge
        /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
        $payment = \Drupal::entityTypeManager()
          ->getStorage('commerce_payment')
          ->loadByRemoteId($first_charge->id);

        $order = $payment->getOrder();

        $payment_new = Payment::create([
          'payment_gateway' => $payment->getPaymentGateway()->id(),
          'order_id' => $order->id(),
          'amount' => $charge->getAmount(),
          'state' => 'completed',
        ]);
        $payment_new->save();

        \Drupal::logger('vipps_recurring_commerce')->info(
          'Order %oid: Payment %pid.', [
            '%oid' => $order->id(),
            '%pid' => $payment_new->id(),
          ]
        );

        $this->logger->info(
          sprintf("Charge for %s has been done successfully", $agreementId)
        );
      }

      return JobResult::success();
    } catch (\Throwable $e) {
      return JobResult::failure($e->getMessage());
    }
  }
}
