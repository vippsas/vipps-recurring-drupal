<?php

declare(strict_types=1);

namespace Drupal\vipps_recurring_payments\Entity;

interface ProductSubscriptionInterface
{
  public function getId():int;

  public function getTitle():string;

  public function setTitle(string $title):void;

  public function setIntervalValue(string $interval): void;

  public function setIntervalCount(int $intervalCount): void;

  public function getIntervalValue():string;

  public function getIntervalCount():int;

  public function setPrice(?float $price):void;

  public function getPrice():?float;

  public function getIntegerPrice():int;

  public function getPriceAsString():string;

  public function getCurrency():string;

  public function getDescription():string;

  public function getIntervalInDays():int;

  public function setDescription(string $description): void;

  public function setInitialCharge(bool $initialCharge): void;

  public function getInitialCharge(): bool;
}
