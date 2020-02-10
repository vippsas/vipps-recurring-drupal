<?php

declare(strict_types=1);

namespace Drupal\vipps_recurring_payments\UseCase;

class ChargeItem
{
  private $id;

  private $price;

  private $description;

  public function __construct(string $id, int $price, string $description = null)
  {
    $this->id = $id;
    $this->price = $price;
    $this->description = $description;
  }

  public function getAgreementId():string {
    return $this->id;
  }

  public function getPrice():?float {
    return round($this->price/ 100, 2);
  }

  public function getDescription():string {
    return $this->description;
  }

  public function hasDescription():bool {
    return !is_null($this->description);
  }
}
