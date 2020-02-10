<?php

namespace Drupal\vipps_recurring_payments\Repository;

use Drupal\vipps_recurring_payments\Entity\ProductSubscriptionInterface;

interface ProductSubscriptionRepositoryInterface
{
  public function getProduct(string $agreementId = null):ProductSubscriptionInterface;
}
