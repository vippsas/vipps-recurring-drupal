<?php

namespace Drupal\vipps_recurring_payments_commerce\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_payment\Entity\PaymentInterface;
use Drupal\commerce_payment\Exception\DeclineException;
use Drupal\commerce_payment\Exception\PaymentGatewayException;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\SupportsAuthorizationsInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\SupportsRefundsInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\SupportsVoidsInterface;
use Drupal\commerce_price\Price;
use Drupal\vipps_recurring_payments\Entity\VippsProductSubscription;
use Drupal\vipps_recurring_payments\Form\SettingsForm;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Provides the Nets payment gateway.
 *
 * @CommercePaymentGateway(
 *   id = "vipps_recurring_checkout",
 *   label = "Vipps Recurring Checkout",
 *   display_label = "Vipps Recurring Checkout",
 *   requires_billing_information = TRUE,
 *   forms = {
 *     "add-payment-method" = "Drupal\vipps_recurring_payments_commerce\PluginForm\VippsPaymentMethodAddForm",
 *   },
 *   payment_method_types = {"vipps_payment_method"},
 * )
 */
class VippsPaymentGateway extends BaseVippsPaymentGateway implements SupportsRefundsInterface, SupportsVoidsInterface, SupportsAuthorizationsInterface {

  /**
   * @inheritDoc
   */
  public function createPayment(\Drupal\commerce_payment\Entity\PaymentInterface $payment, $capture = TRUE) {
    $this->assertPaymentState($payment, ['new']);
    $payment_method = $payment->getPaymentMethod();
    $this->assertPaymentMethod($payment_method);
    $requestStorageFactory = $this->getRequestStorageFactory();

    /** @var SettingsForm $plugin */
    $configFactory = $this->configFactory->getEditable(SettingsForm::SETTINGS);
    $settings = $configFactory->getRawData();

    if($settings['msn'] == '' || $settings['test_msn'] == '') {
      \Drupal::logger('vipps_recurring_commerce')->error(
        'There is no Settings for Vipps recurring'
      );
      throw new PaymentGatewayException('There is no Settings for Vipps recurring');
    }


    $token = $this->httpClient->auth();
    $order = $payment->getOrder();
    $order->setOrderNumber($order->id());
    $order->save();
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

    if(!isset($billing_schedule)) {
      \Drupal::logger('vipps_recurring_commerce')->error(
        'Order %oid: there is no Billing schedule defined for this order', ['%oid' => $order->id()]
      );
      throw new PaymentGatewayException('There is no Billing schedule defined for this order');
    }

    if(!isset($frequency) || in_array($frequency, ['hourly'])) {
      \Drupal::logger('vipps_recurring_commerce')->error(
        'Order %oid: unsupported schedule frequency %fre', ['%oid' => $order->id(), '%fre' => $frequency]
      );
      throw new PaymentGatewayException('Unsupported schedule frequency');
    }

    if ($order->getData('vipps_auth_key') === NULL) {
      $order->setData('vipps_auth_key', $token);
    }

    $intervalService = \Drupal::service('vipps_recurring_payments:charge_intervals');
    $intervals = $intervalService->getIntervals($frequency);

    $product = new VippsProductSubscription(
      $intervals['base_interval'],
      intval($intervals['base_interval_count']),
      $title,
      $title,
      $initial_charge,
      $order->id()
    );
    $product->setPrice($order->total_price->getValue()[0]['number']);

    try {
      $draftAgreementResponse = $this->httpClient->draftAgreement(
        $token,
        $requestStorageFactory->buildDefaultDraftAgreement(
          $product,
          $payment_method->phone_number->value,
          [
            'commerce_order' => $order->id()
          ]
        )
      );
    }
    catch (\Exception $exception) {
      \Drupal::logger('vipps_recurring_commerce')->error(
        'Order %oid: problems drafting the agreement', ['%oid' => $order->id()]
      );
      throw new PaymentGatewayException($exception->getMessage());
    }

    $payment->setRemoteId($draftAgreementResponse->getAgreementId());
    $payment->save();

    $order->setData('vipps_current_transaction', $draftAgreementResponse->getAgreementId());
    $order->getState()->applyTransitionById('place');
    $order->save();

    $redirect = new RedirectResponse($draftAgreementResponse->getVippsConfirmationUrl(), Response::HTTP_SEE_OTHER);
    $redirect->send();
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
  public function capturePayment(PaymentInterface $payment, Price $amount = NULL) {
    $this->assertPaymentState($payment, ['authorization']);

    // Assert things.
    $this->assertPaymentState($payment, ['authorization']);
    // If not specified, capture the entire amount.
    $amount = $amount ?: $payment->getAmount();

    if ($amount->lessThan($payment->getAmount())) {
      /** @var \Drupal\commerce_payment\Entity\PaymentInterface $parent_payment */
      $parent_payment = $payment;
      $payment = $parent_payment->createDuplicate();
    }

    $order = $payment->getOrder();
    $agreementId = $order->getData('vipps_current_transaction');
    $requestStorageFactory = \Drupal::service('vipps_recurring_payments:request_storage_factory');

    // Get charge
    $charge = $this->httpClient->getCharge($this->httpClient->auth(), $agreementId, $payment->getRemoteId());
    $token = $this->httpClient->auth();

    if($charge->getStatus() == 'RESERVED') {
      $product_repo = \Drupal::service('vipps_recurring_payments:product_subscription_repository');
      $product = $product_repo->getProduct();

      $product->setDescription(t('Capture ') . $amount->getNumber() . t(' for Agreement ') . $agreementId);
      $product->setPrice($amount->getNumber());

      $request = $requestStorageFactory->buildCreateChargeData(
        $product,
        new \DateTime()
      );

      try {
        $this->httpClient->captureCharge($token, $agreementId, $charge->getId(), $request);
      } catch (\Exception $exception) {
        \Drupal::logger('vipps_recurring_commerce')->error(
          'Order %oid: Capture operation failed.', [
            '%oid' => $order->id()
          ]
        );
        throw new DeclineException($exception->getMessage());
      }

      $payment->setState('completed');
      $payment->setAmount($amount);
      $payment->save();

      // Update parent payment if one exists.
      if (isset($parent_payment)) {
        $parent_payment->setAmount($parent_payment->getAmount()->subtract($amount));
        if ($parent_payment->getAmount()->isZero()) {
          $parent_payment->setState('authorization_voided');
        }
        $parent_payment->save();
      }

      \Drupal::logger('vipps_recurring_commerce')->info(
        'Order %oid: Payment Captured.', [
          '%oid' => $order->id()
        ]
      );
    }

  }

  /**
   * @inheritDoc
   */
  public function voidPayment(PaymentInterface $payment) {
    $this->assertPaymentState($payment, ['authorization']);

    $order = $payment->getOrder();
    $agreementId = $order->getData('vipps_current_transaction');
    $token = $this->httpClient->auth();
    $chargeId = $payment->getRemoteId();

    try {
      $this->httpClient->cancelCharge($token, $agreementId, $chargeId);
    }
    catch (\Exception $exception) {
      \Drupal::logger('vipps_recurring_commerce')->error(
        'Order %oid: Void operation failed.', [
          '%oid' => $order->id()
        ]
      );
      throw new DeclineException($exception->getMessage());
    }

    $payment->setState('authorization_voided');
    $payment->save();
    \Drupal::logger('vipps_recurring_commerce')->info(
      'Order %oid: Payment voided.', [
        '%oid' => $order->id(),
      ]
    );

  }

  /**
   * @inheritDoc
   */
  public function refundPayment(PaymentInterface $payment, Price $amount = NULL) {
    $this->assertPaymentState($payment, ['authorization']);

    // Validate.
    $this->assertPaymentState($payment, ['completed', 'partially_refunded']);

    // Let's do some refunds.
    parent::assertRefundAmount($payment, $amount);

    $order = $payment->getOrder();
    $agreementId = $order->getData('vipps_current_transaction');
    $requestStorageFactory = \Drupal::service('vipps_recurring_payments:request_storage_factory');

    // Get charge
    $charge = $this->httpClient->getCharge($this->httpClient->auth(), $agreementId, $payment->getRemoteId());
    $token = $this->httpClient->auth();

    $product_repo = \Drupal::service('vipps_recurring_payments:product_subscription_repository');
    $product = $product_repo->getProduct();

    $product->setDescription(t('Refund ') . $amount->getNumber() . t(' for Agreement ') . $agreementId);
    $product->setPrice($amount->getNumber());

    $request = $requestStorageFactory->buildCreateChargeData(
      $product,
      new \DateTime()
    );

    try {
      $this->httpClient->refundCharge($token, $agreementId, $charge->getId(), $request);
    } catch (\Exception $exception) {
      \Drupal::logger('vipps_recurring_commerce')->error(
        'Order %oid: Refund operation failed.', [
          '%oid' => $order->id()
        ]
      );
      throw new DeclineException($exception->getMessage());
    }

    $old_refunded_amount = $payment->getRefundedAmount();
    $new_refunded_amount = $old_refunded_amount->add($amount);
    if ($new_refunded_amount->lessThan($payment->getAmount())) {
      $payment->setState('partially_refunded');
    }
    else {
      $payment->setState('refunded');
    }

    $payment->setRefundedAmount($new_refunded_amount);
    $payment->save();

    \Drupal::logger('vipps_recurring_commerce')->error(
      'Order %oid: Payment refunded.', [
        '%oid' => $order->id()
      ]
    );
  }
}
