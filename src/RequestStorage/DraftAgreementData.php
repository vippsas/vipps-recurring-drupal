<?php

declare(strict_types=1);

namespace Drupal\vipps_recurring_payments\RequestStorage;

use Drupal\vipps_recurring_payments\Entity\ProductSubscriptionInterface;

class DraftAgreementData implements RequestStorageInterface {

  private $isApp;

  private $merchantRedirectUrl;

  private $merchantAgreementUrl;

  private $customerPhoneNumber;

  private $product;

  private $initialCharge;

  public function __construct(
    ProductSubscriptionInterface $product,
    string $merchantRedirectUrl,
    string $merchantAgreementUrl,
    string $customerPhoneNumber,
    bool $isApp
  ) {
    $this->product = $product;
    $this->merchantRedirectUrl = $merchantRedirectUrl;
    $this->merchantAgreementUrl = $merchantAgreementUrl;
    $this->customerPhoneNumber = $customerPhoneNumber;
    $this->isApp = $isApp;
    $this->initialCharge = $this->product->getInitialCharge();
  }

  public function getProduct():ProductSubscriptionInterface{
    return $this->product;
  }

  public function getData(): array {
    $data = [
      "currency" => $this->product->getCurrency(),
      "interval" => $this->product->getIntervalValue(),
      "intervalCount" => $this->product->getIntervalCount(),
      "isApp" => $this->isApp,
      "merchantRedirectUrl" => $this->merchantRedirectUrl,
      "merchantAgreementUrl" => $this->merchantAgreementUrl,
      "customerPhoneNumber" => $this->customerPhoneNumber,
      "price" => $this->product->getIntegerPrice(),
      "productDescription" => $this->product->getDescription(),
      "productName" => $this->product->getTitle(),
    ];

    if($this->initialCharge) {
      $data = array_merge($data, [
        "initialCharge" => [
          "amount" => $this->product->getIntegerPrice(),
          "currency" => $this->product->getCurrency(),
          "description" => $this->product->getDescription(),
          "transactionType" => "DIRECT_CAPTURE"
        ],
      ]);
      if($this->product->getOrderId()) {
        $data["initialCharge"]['orderId'] = $this->product->getOrderId();
      }
    }

    return $data;
  }

}
