<?php

namespace Drupal\vipps_recurring_payments_commerce\Entity;

/**
 * Trait Timestamps
 * @package Drupal\vipps_recurring_payments\Entity
 *
 * @method getCreatedTime
 * @method getChangedTime
 */
trait Timestamps
{
  public function getCreated():\DateTime{
    $dateTime = new \DateTime();
    $dateTime->setTimestamp(intval($this->getCreatedTime()));
    return $dateTime;
  }

  public function getChanged():\DateTime{
    $dateTime = new \DateTime();
    $dateTime->setTimestamp(intval($this->getChangedTime()));
    return $dateTime;
  }

}

