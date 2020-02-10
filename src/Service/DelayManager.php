<?php

declare(strict_types=1);

namespace Drupal\vipps_recurring_payments\Service;

use Drupal\vipps_recurring_payments\Entity\ProductSubscriptionInterface;

class DelayManager
{
  public function getCountSecondsToTomorrow(int $hour = null, int $minute = null):int {
    $tomorrow = $this->getTomorrow();

    if(!is_null($hour) && !is_null($minute)) {//TODO check 0 value
      $tomorrow->setTime($hour, $minute);
    }

    return ($tomorrow->getTimestamp() - $this->getNow()->getTimestamp());
  }

  public function getCountSecondsToNextPayment(ProductSubscriptionInterface $product):int {
    return (86400 * $product->getIntervalInDays() * $product->getIntervalCount());
  }

  public function getDayAfterTomorrow():\DateTime{
    return (new \DateTime())->modify("+2 days");
  }

  private function getNow():\DateTime{
    return new \DateTime();
  }

  private function getTomorrow():\DateTime{
    return (new \DateTime())->modify("+1 day");
  }

}
