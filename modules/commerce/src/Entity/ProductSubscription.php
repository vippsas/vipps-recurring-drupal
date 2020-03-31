<?php

namespace Drupal\vipps_recurring_payments_commerce\Entity;

use Drupal\commerce_product\Entity\Product;
use Drupal\commerce_store\Entity\Store;
use Drupal\Core\Field\EntityReferenceFieldItemListInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\TypedData\Exception\MissingDataException;
use Drupal\Core\TypedData\TypedDataInterface;
use Drupal\taxonomy\Entity\Term;
use Drupal\vipps_recurring_payments_commerce\Entity\Timestamps;

class ProductSubscription extends Product
{
  use Timestamps;
  use FieldLists;

  public function getId():int
  {
    return intval($this->id());
  }

  public function getType():?string
  {
    return $this->getStringFromFieldItemList('type');
  }

  public function isPublished():bool
  {
    return boolval($this->getStringFromFieldItemList('status'));
  }

  public function getInterval():Term
  {
    try {
      return $this->getValueFromFieldItemList($this->get('field_interval'))->get('entity')->getTarget()->getValue();
    } catch (\Throwable $exception) {
      return new Term([], 'taxonomy_term');
    }
  }

  public function getIntervalValue():?string {
    try {
      return $this->getInterval()->get('field_machine_name_')->first()->getString();
    } catch (\Throwable $exception) {
      return null;
    }
  }

  public function getIntervalCount():?int
  {
    return intval($this->getStringFromFieldItemList('field_interval_count'));
  }

  public function getPrice():?float
  {
    try {
      return floatval($this->getValueFromFieldItemList($this->get('field_price'))->getValue()['number']);
    } catch (\Throwable $exception) {
      return null;
    }
  }

  public function getIntegerPrice():?int {
    return ($this->getPrice() * 100);
  }

  public function getPriceAsString():?string {
    return  strval($this->getPrice());
  }

  public function getCurrency():?string
  {
    try {
      return $this->getValueFromFieldItemList($this->get('field_price'))->getValue()['currency_code'] ?? null;
    } catch (\Throwable $exception) {
      return null;
    }
  }

  public function getStore():?Store
  {
    try {
      return  $this->getValueFromEntityReferenceFieldItemList($this->get('store'))->getTarget()->getValue();
    } catch (\Throwable $exception) {
      return null;
    }
  }

  public function getDescription():string {//TODO set real data
    return 'temp description';
  }

  public function getIntervalInDays():int {
    $interval = $this->getIntervalValue();

    switch ($interval) {
      case "DAY":
        return  1;
      case "WEEK":
        return 7;
      case "MONTH":
        return 30;//TODO check count days
      default:
        throw new \Exception("Interval isn't supported");
    }
  }
}
