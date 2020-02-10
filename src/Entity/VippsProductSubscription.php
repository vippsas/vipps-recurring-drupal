<?php

declare(strict_types=1);

namespace Drupal\vipps_recurring_payments\Entity;

use Drupal\vipps_recurring_payments\Entity\IntervalInDaysTrait;
use Drupal\vipps_recurring_payments\Entity\ProductSubscriptionInterface;

class VippsProductSubscription implements ProductSubscriptionInterface
{
  use IntervalInDaysTrait;

  private $price;

  private $description = 'Testing Vipps recurring payment';//TODO set real description;

  public function getId(): int
  {
    throw new \DomainException('id not supported');
  }

  public function getTitle(): string
  {
    return "Vipps bkf"; //TODO real name
  }

  public function getIntervalValue(): string
  {
    return 'MONTH';
  }

  public function getIntervalCount(): int
  {
    return 1;
  }

  public function getPrice(): ?float
  {
    return round($this->price / 100, 2);
  }

  public function getIntegerPrice(): int
  {
    return intval(round($this->price, 0));
  }

  public function getPriceAsString(): string
  {
    return strval($this->getPrice());
  }

  public function getCurrency(): string
  {
    return 'NOK';
  }

  public function getDescription(): string
  {
    return $this->description;
  }

  public function setPrice(?float $price):void{
    $this->price = round($price, 2) * 100;
  }

  public function setDescription(string $description)
  {
    $this->description = $description;
  }
}
