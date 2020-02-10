<?php

declare(strict_types=1);

namespace Drupal\vipps_recurring_payments\RequestStorage;

use Drupal\vipps_recurring_payments\Entity\ProductSubscriptionInterface;

class CancelAgreement implements RequestStorageInterface
{
  const STATUS = 'STOPPED';

  private $product;

  public function __construct(ProductSubscriptionInterface $product) {
    $this->product = $product;
  }

  public function getData(): array
  {
    return [
      'productName' => $this->product->getTitle(),
      'price' => $this->product->getIntegerPrice(),
      'productDescription' => $this->product->getDescription(),
      'status' => self::STATUS,
    ];
  }
}
