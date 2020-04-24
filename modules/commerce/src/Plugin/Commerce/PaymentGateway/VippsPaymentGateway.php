<?php

namespace Drupal\vipps_recurring_payments_commerce\Plugin\Commerce\PaymentGateway;

use Drupal\advancedqueue\Entity\Queue;
use Drupal\advancedqueue\Job;
use Drupal\commerce_payment\Entity\PaymentInterface;
use Drupal\commerce_payment\Exception\PaymentGatewayException;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\SupportsRefundsInterface;
use Drupal\commerce_price\Price;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\vipps_recurring_payments\Entity\PeriodicCharges;
use Drupal\vipps_recurring_payments\Entity\VippsAgreements;
use Drupal\vipps_recurring_payments\Entity\VippsProductSubscription;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Provides the Nets payment gateway.
 *
 * @CommercePaymentGateway(
 *   id = "vipps_recurring_checkout",
 *   label = "Vipps Recurring Checkout",
 *   display_label = "Vipps Recurring Checkout",
 *   requires_billing_information = FALSE,
 *   forms = {
 *     "add-payment-method" = "Drupal\vipps_recurring_payments_commerce\PluginForm\VippsPaymentMethodAddForm",
 *   },
 *   payment_method_types = {"vipps_payment_method"},
 * )
 */
class VippsPaymentGateway extends BaseVippsPaymentGateway implements SupportsRefundsInterface {

  /**
   * @inheritDoc
   */
  public function createPayment(\Drupal\commerce_payment\Entity\PaymentInterface $payment, $capture = TRUE) {
    $this->assertPaymentState($payment, ['new']);
    $payment_method = $payment->getPaymentMethod();
    $this->assertPaymentMethod($payment_method);
    $httpClient = $this->getVippsHttpClient();
    $requestStorageFactory = $this->getRequestStorageFactory();
    $vippsService = $this->getVippsService();

    $token = $httpClient->auth();

    $order = $payment->getOrder();
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
    }


    if ($order->getData('vipps_auth_key') === NULL) {
      $order->setData('vipps_auth_key', $token);
    }

    $order->setData('vipps_current_transaction', $payment->getRemoteId());

    $intervalService = \Drupal::service('vipps_recurring_payments:charge_intervals');
    $intervals = $intervalService->getIntervals($frequency);

    $product = new VippsProductSubscription(
      $intervals['base_interval'],
      intval($intervals['base_interval_count']),
      $payment_method->agreement_title->value,
      $payment_method->agreement_description->value,
      $initial_charge
    );
    $product->setPrice($order->total_price->getValue()[0]['number']);

    try {
      $draftAgreementResponse = $httpClient->draftAgreement(
        $token,
        $requestStorageFactory->buildDefaultDraftAgreement(
          $product,
          $payment_method->phone_number->value,
          [
            'commerce_order' => $order->id(),
            'step' => 'payment'
          ]
        )
      );
      new TrustedRedirectResponse($draftAgreementResponse->getVippsConfirmationUrl(), 302);
      $redirect = new RedirectResponse($draftAgreementResponse->getVippsConfirmationUrl(), 302);
      $redirect->send();
    }
    catch (Exception $exception) {
      \Drupal::logger('vipps_recurring_commerce')->error(
        'Order %oid: problems drafting the agreement', ['%oid' => $order->id()]
      );
      throw new PaymentGatewayException($exception->getMessage());
    }

    try {
      $charges = $httpClient->getCharges(
        $httpClient->auth(),
        $draftAgreementResponse->getAgreementId()
      );
    } catch (Exception $exception) {
      \Drupal::logger('vipps_recurring_commerce')->error(
        'Order %oid: problems getting the charges', ['%oid' => $order->id()]
      );
      throw new PaymentGatewayException($exception->getMessage());
    }

    // If the payment was successfully created at remote host.
    $payment->setRemoteId($charges[0]->id);
    $payment->save();
    $order->setData('vipps_current_transaction', $draftAgreementResponse->getAgreementId());
    $order->save();

    // Get agreement status
    $agreementStatus = $vippsService->agreementStatus($draftAgreementResponse->getAgreementId());
    $message_variables['%os'] = $agreementStatus;
    \Drupal::logger('vipps_recurring_commerce')->info(
      'Order %oid status: %os', $message_variables
    );

    switch ($agreementStatus) {
      case 'PENDING':
        $payment->setState('authorization');
        break;

      case 'ACTIVE':
        $payment->setState('completed');
        break;

      case 'STOPPED':
      case 'EXPIRED':
      $payment->setState('failed');
        $order->getState()->applyTransitionById('cancel');
        \Drupal::logger('vipps_recurring_commerce')->error(
          'Order %oid: Oooops, something went wrong.', $message_variables
        );
        throw new PaymentGatewayException("Oooops, something went wrong.");
        break;

      default:
        \Drupal::logger('vipps_recurring_commerce')->error(
          'Order %oid: Oooops, something went wrong.', $message_variables
        );
        throw new PaymentGatewayException("Oooops, something went wrong.");
        break;
    }

    $order->getState()->applyTransitionById('place');
    $payment->save();
    $order->save();

    $this->confirmAgreement($draftAgreementResponse->getAgreementId());
  }

  /**
   * @inheritDoc
   */
  public function createPaymentMethod(\Drupal\commerce_payment\Entity\PaymentMethodInterface $payment_method, array $payment_details) {
    $required_keys = [
      'phone_number'
    ];

    foreach ($required_keys as $required_key) {
      if (empty($payment_details[$required_key])) {
        throw new \InvalidArgumentException(sprintf('$payment_details must contain the %s key.', $required_key));
      }
    }

    $payment_method->setReusable(FALSE);
    $payment_method->phone_number = $payment_details['phone_number'];
    $payment_method->agreement_title = $payment_details['agreement_title'];
    $payment_method->agreement_description = $payment_details['agreement_description'];
    $payment_method->save();
  }

  /**
   * @inheritDoc
   */
  public function deletePaymentMethod(\Drupal\commerce_payment\Entity\PaymentMethodInterface $payment_method) {
    // Delete the record here, throw an exception if it fails.
    // See \Drupal\commerce_payment\Exception for the available exceptions.
    // Delete the local entity.
    $payment_method->delete();
  }

  /**
   * @inheritDoc
   */
  public function refundPayment(PaymentInterface $payment, Price $amount = NULL) {
    // TODO: Implement refundPayment() method.
  }
  /**
   * @param $agreementId
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  private function confirmAgreement($agreementId) {
    $httpClient = $this->getVippsHttpClient();
    $requestStorageFactory = $this->getRequestStorageFactory();
    $vippsService = $this->getVippsService();
    // Add agreement to job queue
    $agreementData = $httpClient->getRetrieveAgreement(
      $httpClient->auth(),
      $agreementId
    );

    $message_variables = [
      '%aid' => $agreementId,
      '%as' => $agreementData->getStatus(),
    ];

    if(!$agreementData->isActive()) {
      \Drupal::logger('vipps_recurring_commerce')->error(
        'Order %oid: Agreement %aid has status %as', $message_variables
      );
      return;
    }

    $delayManager = \Drupal::service('vipps_recurring_payments:delay_manager');

    /**
     * Create a Node of vipps_agreement type
     */
    $agreementNode = new VippsAgreements([
      'type' => 'vipps_agreements',
    ], 'vipps_agreements');
    $agreementNode->set('status', 1);
    $agreementNode->setStatus($agreementData->getStatus());
    $agreementNode->setIntervals($this->configuration['charge_interval'] ?? 'MONTHLY');
    $agreementNode->setAgreementId($agreementId);
    $agreementNode->setMobile('');
    $agreementNode->setPrice($agreementData->getPrice()/100);

    $agreementNode->save();
    $agreementNodeId = $agreementNode->id();

    /**
     * Store first charge as periodic_charges entity
     */
    $charges = $httpClient->getCharges(
      $httpClient->auth(),
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
    $intervals = $intervalService->getIntervals($this->configuration['charge_interval']);

    /**
     * @todo update descriptions
     */
    $product = new VippsProductSubscription(
      $intervals['base_interval'],
      intval($intervals['base_interval_count']),
      t('Recurring payment: ') . $agreementData->getPrice(),
      t('Initial Charge'),
      boolval(TRUE)
    );
    $product->setPrice($agreementData->getPrice());

    $job = Job::create('create_charge_job_commerce', [
      'orderId' => $agreementId,
      'agreementNodeId' => $agreementNodeId
    ]);

    $queue = Queue::load('vipps_recurring_payments');
    $queue->enqueueJob($job, $delayManager->getCountSecondsToNextPayment($product));

    $message_variables['%aid'] = $agreementId;
    \Drupal::logger('vipps_recurring_commerce')->info(
      'Order %oid: Subscription %aid has been done successfully', $message_variables
    );
  }

}
