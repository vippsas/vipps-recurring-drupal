<?php

namespace Drupal\vipps_recurring_payments_commerce\Service;

use DateTime;
use Drupal\advancedqueue\Entity\Queue;
use Drupal\advancedqueue\Job;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Url;
use Drupal\vipps_recurring_payments\Entity\PeriodicCharges;
use Drupal\vipps_recurring_payments\Entity\VippsAgreements;
use Drupal\vipps_recurring_payments\Entity\VippsProductSubscription;
use Drupal\vipps_recurring_payments\Service\DelayManager;
use Drupal\vipps_recurring_payments\Service\VippsHttpClient;
use Symfony\Component\HttpFoundation\RedirectResponse;

class AgreementService {
  private $httpClient;

  private $logger;

  private $delayManager;

  /**
   * The module handler.
   *
   * @var ModuleHandlerInterface
   */
  protected $moduleHandler;

  public function __construct(
    VippsHttpClient $httpClient,
    LoggerChannelFactoryInterface $loggerChannelFactory,
    DelayManager $delayManager,
    ModuleHandlerInterface $module_handler
  )
  {
    $this->httpClient = $httpClient;
    $this->logger = $loggerChannelFactory;
    $this->delayManager = $delayManager;
    $this->moduleHandler = $module_handler;
  }

  public function confirmAgreementAndAddChargeToQueue(\Drupal\commerce_order\Entity\Order $order):void
  {
    $agreementId = $order->getData('vipps_current_transaction');

    $agreementData = $this->httpClient->getRetrieveAgreement(
      $this->httpClient->auth(),
      $agreementId
    );

    $message_variables = [
      '%aid' => $agreementId,
      '%as' => $agreementData->getStatus(),
      '%oid' => $order->id(),
    ];

    $payments = \Drupal::entityTypeManager()->getStorage('commerce_payment')->loadByProperties(['order_id' => $order->id()]);
    /** @var \Drupal\commerce_payment\Entity\PaymentInterface $matching_payment */
    $payment = reset($payments);

    switch ($agreementData->getStatus()) {
      case 'PENDING':
        $payment->setState('authorization');
        break;

      case 'ACTIVE':
        $payment->setState('completed');
        break;

      case 'STOPPED':
      case 'EXPIRED':
        $payment->setState('failed');
        $order->set('state', 'draft');
        $order->save();
        \Drupal::logger('vipps_recurring_commerce')->error(
          'Order %oid: Oooops, something went wrong.', $message_variables
        );
        throw new \DomainException("Oooops, something went wrong.");
        break;

      default:
        \Drupal::logger('vipps_recurring_commerce')->error(
          'Order %oid: Oooops, something went wrong.', $message_variables
        );
        throw new \DomainException("Oooops, something went wrong.");
        break;
    }

    $payment->save();
    $order->save();

    if(!$agreementData->isActive()) {
      \Drupal::logger('vipps_recurring_commerce')->error(
        'Order %oid: Agreement %aid has status %as', $message_variables
      );
      return;
    }

    $title = ' ';

    // Can be considered an initial subscription order if it has at least one
    // product which has subscription enabled.
    foreach ($order->getItems() as $order_item) {
      $purchased_entity = $order_item->getPurchasedEntity();
      if (!$purchased_entity->hasField('subscription_type')) {
        continue;
      }
      /** @var \Drupal\commerce_recurring\Entity\BillingScheduleInterface $billing_schedule */
      $billing_schedule = $purchased_entity->get('billing_schedule')->entity;
      $initial_charge = $billing_schedule->getBillingType() == 'prepaid' ?? 'false';
      $frequency = $billing_schedule->getPluginConfiguration()["interval"]["unit"] . 'ly';
      $frequency = $frequency == 'dayly' ? 'daily' : $frequency;
      $title = $purchased_entity->getTitle();
    }

    $payment_method = $payment->getPaymentMethod();
    $date = strtotime("now");

    /**
     * Create a Node of vipps_agreement type
     */
    $agreementNode = new VippsAgreements([
      'type' => 'vipps_agreements',
    ], 'vipps_agreements');
    $agreementNode->set('status', 1);
    $agreementNode->setStatus($agreementData->getStatus());
    $agreementNode->setIntervals($frequency ?? 'MONTHLY');
    $agreementNode->setAgreementId($agreementId);
    $agreementNode->setMobile($payment_method->phone_number->value);
    $agreementNode->setPrice($agreementData->getPrice());
    $agreementNode->setCreatedTime($date);
    $agreementNode->setChangedTime($date);
    $agreementNode->setOwnerId(\Drupal::currentUser()->id());

    $agreementNode->save();
    $agreementNodeId = $agreementNode->id();

    /**
     * Store first charge as periodic_charges entity
     */
    $charges = $this->httpClient->getCharges(
      $this->httpClient->auth(),
      $agreementId
    );

    if (isset($charges)) {
      $chargeNode = new PeriodicCharges([
        'type' => 'periodic_charges',
      ], 'periodic_charges');
      $chargeNode->set('status', 1);
      $chargeNode->setChargeId($charges[0]->id);
      $chargeNode->setPrice($charges[0]->amount);
      $chargeNode->setParentId($agreementNodeId);
      $chargeNode->setStatus($charges[0]->status);
      $chargeNode->setDescription($charges[0]->description);
      $chargeNode->save();
    }

    $intervalService = \Drupal::service('vipps_recurring_payments:charge_intervals');
    $intervals = $intervalService->getIntervals($frequency);

    /** @var \Drupal\commerce_recurring\SubscriptionStorageInterface $subscription_storage */
    $subscription_storage = \Drupal::entityTypeManager()->getStorage('commerce_subscription');
    /** @var \Drupal\commerce_recurring\Entity\SubscriptionInterface[] $subscriptions */
    $query = $subscription_storage->getQuery();
    $query
      ->condition('initial_order', $order->id())
      ->condition('state', ['trial', 'active'], 'IN');
    $result = $query->execute();

    if ($result) {
      $subscriptions = $subscription_storage->loadMultiple($result);
      $subscription = reset($subscriptions);
      /** @var \Drupal\commerce_order\Entity\Order $next_order */
      $next_order = $subscription->getCurrentOrder();
    }

    if(!isset($next_order)) {
      \Drupal::logger('vipps_recurring_commerce')->info(
        'Order %oid: No subscription found', $message_variables
      );
      return;
    }

    $next_order->setOrderNumber($next_order->id());
    $order->save();


    $product = new VippsProductSubscription(
      $intervals['base_interval'],
      intval($intervals['base_interval_count']),
      $title,
      $title,
      $initial_charge
    );
    $product->setPrice($agreementData->getPrice());

    $job = Job::create('create_charge_job_commerce', [
      'orderId' => $next_order->id(),
      'agreementId' => $agreementId,
      'agreementNodeId' => $agreementNodeId
    ]);

    $queue = Queue::load('vipps_recurring_payments');
    $queue->enqueueJob($job, $this->delayManager->getCountSecondsToNextPayment($product));

    $message_variables['%aid'] = $agreementId;
    \Drupal::logger('vipps_recurring_commerce')->info(
      'Order %oid: Subscription %aid has been done successfully', $message_variables
    );
  }
}
