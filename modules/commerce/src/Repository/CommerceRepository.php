<?php

namespace Drupal\vipps_recurring_payments_commerce\Repository;

use CommerceGuys\Intl\Currency\CurrencyRepositoryInterface;
use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_order\Entity\OrderItem;
use Drupal\commerce_payment\Entity\Payment;
use Drupal\commerce_price\Entity\Currency;
use Drupal\commerce_product\Entity\Product;
use Drupal\commerce_product\Entity\ProductVariationType;
use Drupal\commerce_product\ProductAttributeFieldManagerInterface;
use Drupal\commerce_store\Entity\Store;
use Drupal\Core\Database\DatabaseNotFoundException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\profile\Entity\Profile;
use Drupal\vipps_recurring_payments_commerce\Entity\ChargeOrderItem;
use Drupal\vipps_recurring_payments_commerce\Entity\OrderAgreement;
use Drupal\vipps_recurring_payments_commerce\Entity\ProductSubscription;
use Drupal\vipps_recurring_payments_commerce\Service\CacheManager;
use League\Container\Exception\NotFoundException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CommerceRepository {

  private $cacheManager;

  private $entityTypeManager;

  private $currencyRepository;

  private $productAttributeFieldManager;

  public function __construct(
    EntityTypeManagerInterface $entityTypeManager,
    CacheManager $cacheManager,
    CurrencyRepositoryInterface $currencyRepository,
    ProductAttributeFieldManagerInterface $productAttributeFieldManager
  ) {
    $this->entityTypeManager = $entityTypeManager;

    $this->cacheManager = $cacheManager;

    $this->currencyRepository = $currencyRepository;

    $this->productAttributeFieldManager = $productAttributeFieldManager;
  }

  public function getCurrencyColumn():array {
    return $this->currencyRepository->getList();
  }

  public function getStoreByName(string $name):Store {

    $cacheId = __CLASS__ . __METHOD__;

    return $this->cacheManager->execute($cacheId, ['online'], function() use ($name) {
      $stores = $this->entityTypeManager
        ->getStorage('commerce_store')
        ->loadByProperties([
          'name' => $name,
        ]);

      $store = reset($stores);

      if(!($store instanceof Store)) {
        throw new NotFoundHttpException(sprintf("Store %s not found", $name));
      }

      return $store;
    });
  }

  public function getDefaultCurrency():Currency {
    $currencies = Currency::loadMultiple();

    if(empty($currencies)) {
      throw new DatabaseNotFoundException('Please, provide at least one currency');
    }

    if(isset($currencies['NOK'])) {
      $currencies['NOK'];
    }

    return reset($currencies);
  }

  /**
   * @return \Drupal\user\Entity\User[]
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getAdminUsers():array {
    $user_storage = $this->entityTypeManager->getStorage('user');

    $ids = $user_storage->getQuery()
      ->condition('status', 1)
      ->condition('roles', 'administrator')
      ->execute();

    return $user_storage->loadMultiple($ids);
  }

  public function getProductVariationTypeById(string $id):?ProductVariationType {
    $productVariationTypes = $this->entityTypeManager->getStorage('commerce_product_variation_type')
      ->loadByProperties([
        'id' => $id,
      ]);

    $productVariationType = reset($productVariationTypes);

    return $productVariationType ? $productVariationType : null;
  }

  /**
   * @param string $variationType
   *
   * @return FieldDefinitionInterface[]
   */
  public function getFieldDefinitionsForVariationType(string $variationType):array {
    return $this->productAttributeFieldManager->getFieldDefinitions($variationType);
  }

  /**
   * @return \Drupal\commerce_order\Entity\Order[]|\Drupal\Core\Entity\EntityInterface[]
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getDraftOrders() {
    $ordersStorage = $this->entityTypeManager->getStorage('commerce_order');

    return $ordersStorage->loadByProperties(['state' => 'draft']);
  }

  /**
   * @param array $statuses
   *
   * @return \Drupal\commerce_order\Entity\Order[]|\Drupal\Core\Entity\EntityInterface[]
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getOrders(array $statuses):array {
    $ordersStorage = $this->entityTypeManager->getStorage('commerce_order');

    return $ordersStorage->loadByProperties(['state' => $statuses]);
  }

  /**
   * @param array $statuses
   * @param int $userId
   *
   * @return OrderAgreement[]
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getOrdersForUser(array $statuses, int $userId):array {
    $ordersStorage = $this->entityTypeManager->getStorage('commerce_order');

    return $ordersStorage->loadByProperties([
      'state' => $statuses,
      'uid' => $userId,
    ]);
  }

  public function findOrder(int $id):OrderAgreement
  {
    $ordersStorage = $this->entityTypeManager->getStorage('commerce_order');

    /* @var $orders OrderAgreement[] */
    $orders = $ordersStorage->loadByProperties(['order_id' => $id]);

    if(!($orders[$id] instanceof Order)) {
      throw new NotFoundHttpException('Order not found');
    }

    return $orders[$id];
  }

  public function findActiveOrderAgreement(int $id):OrderAgreement{
    $ordersStorage = $this->entityTypeManager->getStorage('commerce_order');

    /* @var $orders OrderAgreement[] */
    $orders = $ordersStorage->loadByProperties([
      'order_id' => $id,
      'state' => OrderAgreement::STATE_COMPLETED,
    ]);

    if(!($orders[$id] instanceof Order)) {
      throw new NotFoundHttpException('Order not found');
    }

    return $orders[$id];
  }

  public function getDraftOrderByAgreementId(string $agreementId):OrderAgreement{
    /* @var $orders Order[] */
    $orders = $this->entityTypeManager->getStorage('commerce_order')->loadByProperties([
      'state' => 'draft',
      'field_subscription_id' => $agreementId
    ]);

    if(empty($orders)) {
      throw new NotFoundException('Order not found');
    }

    /* @var $order Order */
    $order = reset($orders);

    if(!($order instanceof OrderAgreement)) {
      throw new NotFoundException('Order not found');
    }

    return $order;
  }

  /**
   * @param array $properties
   *
   * @return \Drupal\commerce_order\Entity\Order[]|\Drupal\Core\Entity\EntityInterface[]
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getOrderByProperties(array $properties):array
  {
    $ordersStorage = $this->entityTypeManager->getStorage('commerce_order');

    return $ordersStorage->loadByProperties($properties);
  }

  public function updateOrderStateStatus(Order $order, string $status):void {
    if(!in_array($status, ['draft', 'completed', 'canceled'])) {
      throw new \DomainException('Unsupported status');
    }

    $order->set('state', $status);
    $order->save();
  }

  /**
   * @return ProductSubscription[]
   */
  public function loadVippsProducts():array {
    $commerceStorage = $this->entityTypeManager->getStorage('commerce_product');

    $ids = $commerceStorage->getQuery()
      ->condition('status', 1)
      ->condition('type', 'vipps')
      ->execute();

    return $commerceStorage->loadMultiple($ids);
  }

  public function getProduct(int $productId):ProductSubscription{
    /* @var $product ProductSubscription */
    $product = $this->entityTypeManager->getStorage('commerce_product')->load($productId);

    if(empty($product)) {
      throw new \Exception('Product not found');
    }

    return $product;
  }

  public function getOrCreateProfile(int $userId):Profile{
    $profileStorage = $this->entityTypeManager->getStorage('profile');

    $id = $profileStorage->getQuery()
      ->condition('type', 'customer')
      ->condition('uid', $userId)
      ->execute();

    /* @var Profile $profile */
    $profile = $profileStorage->load($id);

    if(!($profile instanceof Profile)) {
      $profile = Profile::create([
        'type' => 'customer',
        'uid' => $userId,
      ]);
      $profile->save();
    }

    return  $profile;
  }

  public function saveOrderAgreement(OrderAgreement $order):OrderAgreement {
    $order->save();

    return $order;
  }

  public function saveChargeOrderItem(ChargeOrderItem $orderItem):OrderItem {
    $orderItem->save();

    return $orderItem;
  }

  public function savePayment(Payment $payment):Payment {
    $payment->save();

    return $payment;
  }

  public function findChargeOrderItem(int $id):ChargeOrderItem {
    $orderItem = $this->entityTypeManager->getStorage('commerce_order_item')->load($id);

    if(!($orderItem instanceof ChargeOrderItem)) {
      throw new NotFoundException();
    }

    return $orderItem;
  }

}
