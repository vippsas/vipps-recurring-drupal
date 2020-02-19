<?php

declare(strict_types=1);

namespace Drupal\vipps_recurring_payments\Service;

use Drupal\vipps_recurring_payments\Repository\ProductSubscriptionRepositoryInterface;
use Drupal\vipps_recurring_payments\ResponseApiData\CreateChargesResponse;
use Drupal\vipps_recurring_payments\ResponseApiData\ResponseErrorItem;
use Drupal\vipps_recurring_payments\UseCase\ChargeItem;
use Drupal\vipps_recurring_payments\UseCase\Charges;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\vipps_recurring_payments\Factory\RequestStorageFactory;
use Drupal\vipps_recurring_payments_webform\Repository\WebformSubmissionRepository;
use Drupal\webform\WebformSubmissionInterface;

class VippsService
{
  private $httpClient;

  private $requestStorageFactory;

  private $logger;

  private $productSubscriptionRepository;

  public function __construct(
    VippsHttpClient $httpClient,
    RequestStorageFactory $requestStorageFactory,
    LoggerChannelFactoryInterface $loggerChannelFactory,
    ProductSubscriptionRepositoryInterface $productSubscriptionRepository
  )
  {
    $this->httpClient = $httpClient;
    $this->requestStorageFactory = $requestStorageFactory;
    $this->logger = $loggerChannelFactory;
    $this->productSubscriptionRepository = $productSubscriptionRepository;
  }

  public function makeCharges(Charges $chargesStorage):CreateChargesResponse {
    $token = $this->httpClient->auth();

    $response = new CreateChargesResponse();

    foreach ($chargesStorage->getCharges() as $charge) {
      try {
        $response->addSuccessCharge($this->createChargeItem($charge, $token));
      } catch (\Throwable $e) {
        $response->addError(new ResponseErrorItem($charge->getAgreementId(), $e->getMessage()));
      }
    }

    return $response;
  }

  public function agreementActive(string $agreementId):bool {
    return $this->httpClient->getRetrieveAgreement($this->httpClient->auth(), $agreementId)->isActive();
  }

  public function agreementStatus(string $agreementId):string {
    return $this->httpClient->getRetrieveAgreement($this->httpClient->auth(), $agreementId)->getStatus();
  }

  public function createChargeItem(ChargeItem $chargeItem, string $token):string {
    $product = $this->productSubscriptionRepository->getProduct();
    $product->setPrice($chargeItem->getPrice());

    if($chargeItem->hasDescription()) {
      $product->setDescription($chargeItem->getDescription());
    }

    $request = $this->requestStorageFactory->buildCreateChargeData(
      $product,
      new \DateTime()
    );
    return $this->httpClient->createCharge($token, $chargeItem->getAgreementId(), $request);
  }
}
