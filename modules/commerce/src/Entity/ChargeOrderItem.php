<?php

declare(strict_types=1);

namespace Drupal\vipps_recurring_payments_commerce\Entity;

use Drupal\commerce_order\Entity\OrderItem;
use Drupal\vipps_recurring_payments_commerce\Entity\FieldLists;
use Drupal\vipps_recurring_payments_commerce\Entity\Timestamps;

class ChargeOrderItem extends OrderItem
{
  use Timestamps;
  use FieldLists;

  CONST RETRY_LIMIT = 5;

  public function getChargeId():?string {
    return $this->getStringFromFieldItemList('field_charge_id');
  }

  public function getRetries():?int {
    return intval($this->getStringFromFieldItemList('field_retries'));
  }

  public function canBeRetried():bool{
    return ($this->getRetries() < self::RETRY_LIMIT);
  }

  public function incrementRetries(){
    $this->set('field_retries', ($this->getRetries() + 1));
  }

  public function getOrderAgreement():OrderAgreement
  {
    /* @var OrderAgreement $order */
    $order = parent::getOrder();

    return $order;
  }

}
