<?php

namespace Drupal\vipps_recurring_payments\Repository;

use Drupal\vipps_recurring_payments\Entity\ProductSubscriptionInterface;
use Drupal\vipps_recurring_payments\Entity\VippsProductSubscription;

class ProductSubscriptionRepository implements ProductSubscriptionRepositoryInterface
{
  public function getProduct():ProductSubscriptionInterface {
    return new VippsProductSubscription();
  }
}
