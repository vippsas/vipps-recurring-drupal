<?php

namespace Drupal\vipps_recurring_payments\Repository;

use Drupal\vipps_recurring_payments\Entity\ProductSubscriptionInterface;
use Drupal\vipps_recurring_payments\Entity\VippsProductSubscription;

class WebFormProductSubscriptionRepository implements ProductSubscriptionRepositoryInterface
{
  public function getProduct(string $agreementId = null): ProductSubscriptionInterface
  {
    return new VippsProductSubscription();
  }
}
