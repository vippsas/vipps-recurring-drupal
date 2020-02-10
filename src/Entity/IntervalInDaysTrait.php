<?php

namespace Drupal\vipps_recurring_payments\Entity;

/**
 * Trait IntervalInDaysTrait
 * @package Drupal\vipps_recurring_payments\Entity
 * @method getIntervalValue
 */
trait IntervalInDaysTrait
{
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
