<?php

namespace Drupal\vipps_recurring_payments_commerce\Factory;

use Drupal\commerce_payment\Entity\Payment;
use Drupal\commerce_price\Price;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\vipps_recurring_payments_commerce\Entity\ChargeOrderItem;
use Drupal\vipps_recurring_payments_commerce\Entity\OrderAgreement;
use Drupal\vipps_recurring_payments_commerce\Entity\ProductSubscription;
use Drupal\vipps_recurring_payments_commerce\Repository\CommerceRepository;
use Symfony\Component\HttpFoundation\RequestStack;

class CommerceEntitiesFactory
{
  private $currentUser;

  private $commerceRepository;

  private $requestStack;

  public function __construct(
    AccountProxyInterface $currentUser,
    RequestStack $requestStack,
    CommerceRepository $commerceRepository
  ) {
    $this->currentUser = $currentUser;
    $this->requestStack = $requestStack;
    $this->commerceRepository = $commerceRepository;
  }

  public function createChargeOrderItem(ProductSubscription $product, string $chargeId):ChargeOrderItem{

    /* @var ChargeOrderItem $orderItem */
    $orderItem = ChargeOrderItem::create([
      'type' => 'subscription',
      'purchased_entity' => $product->getDefaultVariation(),
      'quantity' => 1,
      'unit_price' => new Price($product->getPriceAsString(), $product->getCurrency()),
      'total_price' => new Price($product->getPriceAsString(), $product->getCurrency()),
      'title' => 'Subscription order item',
      'field_charge_id‎' => $chargeId,
      'field_retries' => 1,
    ]);

    return  $orderItem;
  }

  public function createDraftAgreement(ProductSubscription $product, string $agreementId):OrderAgreement {
    /* @var OrderAgreement $orderAgreement */
    $orderAgreement =  OrderAgreement::create([
      'type' => OrderAgreement::TYPE,
      'state' => OrderAgreement::STATE_DRAFT,
      'mail' => $this->currentUser->getEmail(),
      'uid' => $this->currentUser->id(),
      'ip_address' => $this->requestStack->getCurrentRequest()->getClientIp(),
      'billing_profile' => $this->commerceRepository->getOrCreateProfile(intval($this->currentUser->id())),
      'store_id' => $this->commerceRepository->getStoreByName('viips')->id(),
      'placed' => time(),
      'field_subscription_id' => $agreementId,
      'field_subscription_product_id' => $product->id(),
    ]);

    return $orderAgreement;
  }

  public function createSubscriptionCompletedPayment(ChargeOrderItem $chargeOrderItem):Payment{
    /* @var $payment Payment */
    $payment = Payment::create([
      'type' => 'payment_default',
      'payment_gateway' => 'vipps',
      'order_id' => strval($chargeOrderItem->getOrderId()),
      'amount' => $chargeOrderItem->getTotalPrice(),
      'state' => 'completed',
    ]);

    return $payment;
  }
}
