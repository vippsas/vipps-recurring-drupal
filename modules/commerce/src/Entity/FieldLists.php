<?php


namespace Drupal\vipps_recurring_payments_commerce\Entity;


use Drupal\Core\Field\EntityReferenceFieldItemListInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\TypedData\Exception\MissingDataException;
use Drupal\Core\TypedData\TypedDataInterface;

trait FieldLists
{
  protected function getValueFromEntityReferenceFieldItemList(EntityReferenceFieldItemListInterface $list):?TypedDataInterface {
    try {
      return $list->first();
    } catch (MissingDataException $exception) {
      return null;
    }
  }

  protected function getValueFromFieldItemList(FieldItemListInterface $list):?TypedDataInterface {
    try {
      return $list->first();
    } catch (MissingDataException $exception) {
      return null;
    }
  }

  protected function getStringFromFieldItemList(string $fieldName):?string {
    try {
      return $this->getValueFromFieldItemList($this->get($fieldName))->getString();
    } catch (\Throwable $e) {
      return null;
    }
  }
}
