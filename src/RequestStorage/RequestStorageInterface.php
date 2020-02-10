<?php

declare(strict_types=1);

namespace Drupal\vipps_recurring_payments\RequestStorage;

interface RequestStorageInterface {
  public function getData():array ;
}
