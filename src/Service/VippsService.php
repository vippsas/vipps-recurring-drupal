<?php

declare(strict_types=1);

namespace Drupal\vipps_recurring_payments\Service;

use Drupal\vipps_recurring_payments\Repository\ProductSubscriptionRepositoryInterface;
use Drupal\vipps_recurring_payments\RequestStorage\RequestStorageInterface;
use Drupal\vipps_recurring_payments\ResponseApiData\CancelAgreementResponse;
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

  public function cancelCharges(Charges $chargesStorage):CreateChargesResponse {
    $token = $this->httpClient->auth();

    $response = new CreateChargesResponse();

    foreach ($chargesStorage->getCharges() as $charge) {

      try {
        $cancelResponse = $this->httpClient->cancelCharge($token, $charge->getAgreementId(), $charge->getChargeId());
        if ($cancelResponse['status'] == 200 ) {
          $response->addSuccessCharge($charge->getChargeId());
        } else {
           $response->addError(new ResponseErrorItem($charge->getChargeId(), \GuzzleHttp\json_encode($cancelResponse)));
        }
      } catch (\Throwable $e) {
         $response->addError(new ResponseErrorItem($charge->getChargeId(), $e->getMessage()));
      }


    }

    return $response;
  }

  public function refundCharges(Charges $chargesStorage):CreateChargesResponse {
    $token = $this->httpClient->auth();

    $response = new CreateChargesResponse();

    foreach ($chargesStorage->getCharges() as $charge) {
      try {
        $product = $this->productSubscriptionRepository->getProduct();
        $product->setPrice($charge->getPrice());
        $request = $this->requestStorageFactory->buildCreateChargeData(
          $product,
          new \DateTime()
        );

        $refundResponse = $this->httpClient->refundCharge($token, $charge->getAgreementId(), $charge->getChargeId(), $request);
        if ($refundResponse['status'] == 200 ) {
          $response->addSuccessCharge($charge->getChargeId());
        } else {
           $response->addError(new ResponseErrorItem($charge->getChargeId(), \GuzzleHttp\json_encode($refundResponse)));
        }
      } catch (\Throwable $e) {
         $response->addError(new ResponseErrorItem($charge->getChargeId(), $e->getMessage()));
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

  public function cancelAgreement(array $agreementIds):CancelAgreementResponse {
    $token = $this->httpClient->auth();

    $response = new CancelAgreementResponse();

    $product = $this->productSubscriptionRepository->getProduct();
    $request = $this->requestStorageFactory->buildCreateCancelData($product);

    foreach ($agreementIds as $agreementId) {
      $update = $this->updateAgreement($agreementId, $token, $request);
      if ($update['success']) {
        $response->addSuccessCancel($agreementId);
      } else {
        $response->addError($update[$agreementId]);
      }
    }
    return $response;
  }


  public function updateAgreement(string $agreementId, string $token, RequestStorageInterface $request)
  {
    try {
      $response = $this->httpClient->updateAgreement($token, $agreementId, $request);
      if ($response->agreementId ) {
        return ['success' => TRUE, $agreementId => TRUE];
      }
    } catch (\Throwable $e) {
      return ['success' => FALSE, $agreementId => new ResponseErrorItem($agreementId, $e->getMessage())];
    }
  }
}
