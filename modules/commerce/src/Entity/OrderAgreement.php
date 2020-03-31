<?php

declare(strict_types=1);

namespace Drupal\vipps_recurring_payments_commerce\Entity;

use Drupal\commerce_order\Entity\Order;
use Drupal\Core\Entity\Plugin\DataType\EntityReference;
use Drupal\vipps_recurring_payments_commerce\Entity\Timestamps;

class OrderAgreement extends Order
{
  use Timestamps;
  use FieldLists;

  CONST RETRY_LIMIT = 5;

  const TYPE = 'subscription';

  const STATE_DRAFT = 'draft';
  const STATE_COMPLETED = 'completed';
  const STATE_CANCELED = 'canceled';

  protected $product;

  public function getId():int {
    return intval($this->id());
  }

  public function getSubscriptionId():?string
  {
    return $this->getStringFromFieldItemList('field_subscription_id');
  }

  public function getProduct():?ProductSubscription
  {
    try {
      if(!is_null($this->product)) {
        return  $this->product;
      }

      /* @var $entityReferenceItem EntityReference */
      $entityReferenceItem = $this->get('field_subscription_product_id')->first()->get('entity');
      return $entityReferenceItem->getTarget()->getValue();
    } catch (\Throwable $exception) {
      return null;
    }
  }

  public function setState(string $state):void {
    if(!in_array($state, $this->getStates())) {
      throw new \DomainException('Unsupported state');
    }
    $this->set('state', $state);
  }

  public function complete():void {
    $this->setState(self::STATE_COMPLETED);
  }

  public function cancel():void {
    $this->setState(self::STATE_CANCELED);
  }

  public function canBeRetried():bool{
    return ($this->getRetries() < self::RETRY_LIMIT);
  }

  public function incrementRetries(){
    $this->set('field_retries', ($this->getRetries() + 1));
  }

  public function getRetries():int {
    return intval($this->getStringFromFieldItemList('field_retries'));
  }

  public function getStateValue():string {
    return $this->getState()->getString();
  }

  public function getOwnerId():int {
    return intval($this->getStringFromFieldItemList('uid'));
  }

  public function getCanBeCancelled():bool {
    return in_array($this->getStateValue(), [self::STATE_DRAFT, self::STATE_COMPLETED]);
  }

  public function getStates():array {
    return [self::STATE_DRAFT, self::STATE_COMPLETED, self::STATE_CANCELED];
  }

}
