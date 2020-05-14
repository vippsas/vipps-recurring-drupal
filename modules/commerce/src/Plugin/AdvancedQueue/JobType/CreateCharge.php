<?php

namespace Drupal\vipps_recurring_payments_commerce\Plugin\AdvancedQueue\JobType;

use Drupal\advancedqueue\Annotation\AdvancedQueueJobType;
use Drupal\commerce_order\Entity\Order;
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

      $agreementId = $payload['agreementId'];
      $agreementNodeId = $payload['agreementNodeId'];
      $agreementNode = VippsAgreements::load($agreementNodeId);
      $agreementNode->getPrice();
      $order_id = $payload['orderId'];
      $order = Order::load($order_id);

      $message_variables = ['%aid' => $agreementId];

      try {
        $agreementIsActive = $this->vippsService->agreementActive($agreementId);
      } catch (\Exception $exception) {
        $message_variables['%m'] = $exception->getMessage();
        \Drupal::logger('vipps_recurring_commerce')->error(
          'Agreement %aid: Problem getting the agreement status. Message: %m',
          $message_variables
        );
        throw new \Exception($exception->getMessage());
      }

      if(!$agreementIsActive) {
        \Drupal::logger('vipps_recurring_commerce')->error(
          'Agreement %aid: The agreement it not ACTIVE',
          $message_variables
        );
        return JobResult::failure('The agreement it not ACTIVE');
      }

      foreach ($order->getItems() as $key => $order_item) {
        $product_variation = $order_item->getPurchasedEntity();
        $title = $product_variation->getTitle();
      }

      /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
      $payment = Payment::create([
        'payment_gateway' => $order->get('payment_gateway')->first()->entity->id(),
        'order_id' => $order->id(),
        'amount' => $order->getTotalPrice(),
        'state' => 'new',
        'payment_method' => $order->get('payment_method')->first()->entity->id(),
      ]);
      $payment->save();

      \Drupal::logger('vipps_recurring_commerce')->info(
        'Order %oid: Payment %pid.', [
          '%oid' => $order->id(),
          '%pid' => $payment->id(),
        ]
      );

      try {
        $chargeId = $this->vippsService->createChargeItem(
          new ChargeItem($agreementId, $agreementNode->getPrice()*100, $title, $order->id()),
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

      $payment->setState('completed');
      $order->getState()->applyTransitionById('place');

      $payment->save();
      $order->save();

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

        /** @var \Drupal\commerce_recurring\RecurringOrderManager $recurringOrdermanager */
        $recurringOrdermanager = \Drupal::service('commerce_recurring.order_manager');

        $nex_oder = $recurringOrdermanager->renewOrder($order);

        // Add new job to queue for the next charge
        $job = Job::create('create_charge_job_commerce', [
          'orderId' => $nex_oder->id(),
          'agreementId' => $agreementId,
          'agreementNodeId' => $agreementNodeId
        ]);

        $queue = Queue::load('vipps_recurring_payments');
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
}
