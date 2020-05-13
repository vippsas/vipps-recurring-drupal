<?php

declare(strict_types=1);

namespace Drupal\vipps_recurring_payments\Entity;

use Drupal\vipps_recurring_payments\Entity\IntervalInDaysTrait;

class VippsProductSubscription implements ProductSubscriptionInterface
{
  use IntervalInDaysTrait;

  private $price;

  private $interval;

  private $intervalCount;

  private $title;

  private $description;

  private $initialCharge;

  private $orderId;

  public function __construct(
    string $baseInterval = 'MONTH',
    int $baseIntervalCount = 1,
    string $title = '',
    string $description = '',
    bool $initialCharge = true,
    string $oderId = ''
  )
  {
    $this->setIntervalValue($baseInterval);
    $this->setIntervalCount($baseIntervalCount);
    $this->setDescription($description);
    $this->setTitle($title);
    $this->setInitialCharge($initialCharge);
    $this->setOrderId($oderId);
  }

  public function getId(): int
  {
    throw new \DomainException('id not supported');
  }

  public function getTitle(): string
  {
    return $this->title;
  }

  public function setTitle(string $title):void
  {
    $this->title = $title;
  }

  public function setIntervalValue(string $interval): void
  {
    if(!in_array($interval, ['MONTH', 'WEEK', 'DAY'])) {
      throw new \DomainException();
    }

    $this->interval = $interval;
  }

  public function getIntervalValue(): string
  {
    return $this->interval;
  }

  public function setIntervalCount(int $intervalCount): void
  {
    $this->intervalCount = $intervalCount;
  }

  public function getIntervalCount(): int
  {
    return $this->intervalCount;
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

  public function setDescription(string $description): void
  {
    $this->description = $description;
  }

  public function getDescription(): string
  {
    return $this->description;
  }

  public function setPrice(?float $price):void{
    $this->price = round($price, 2) * 100;
  }

  public function getIntervalInDays():int {
    switch ($this->getIntervalValue()) {
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

  public function setInitialCharge(bool $initialCharge): void
  {
    $this->initialCharge = $initialCharge;
  }

  public function getInitialCharge(): bool
  {
    return $this->initialCharge;
  }

  public function setOrderId(string $orderId): void
  {
    $this->orderId = $orderId;
  }

  public function getOrderId(): string
  {
    return $this->orderId;
  }
}
