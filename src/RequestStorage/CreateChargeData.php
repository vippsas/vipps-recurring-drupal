<?php

declare(strict_types=1);

namespace Drupal\vipps_recurring_payments\RequestStorage;

use Drupal\vipps_recurring_payments\Entity\ProductSubscriptionInterface;

class CreateChargeData implements RequestStorageInterface {

  use PriceTrait;

  private $product;

  private $due;

  private $retryDays;

  public function __construct(ProductSubscriptionInterface $product, \DateTime $due, int $retryDays) {
    $this->product = $product;
    $this->due = $due;
    $this->retryDays = $retryDays;
  }

  public function getData(): array {
    $data = [
      "amount" => $this->product->getIntegerPrice(),
      "currency" => $this->product->getCurrency(),
      "description" => $this->product->getDescription(),
      "due" => $this->due->format("Y-m-d"),
      "hasPriceChanged" => false,
      "retryDays" => $this->retryDays
    ];

    if($this->product->getOrderId()) {
      $data["orderId"] = $this->product->getOrderId();
    }

    return $data;
  }

}
