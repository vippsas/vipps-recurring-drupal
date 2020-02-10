<?php

declare(strict_types=1);

namespace Drupal\vipps_recurring_payments\RequestStorage;

/**
 * Trait PriceTrait
 *
 * @package Drupal\vipps_recurring_payments\RequestStorage
 *
 */
trait PriceTrait {

  protected function getIntegerPrice(?float $price):int
  {
    return ($price * 100);
  }

}
