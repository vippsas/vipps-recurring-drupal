<?php

declare(strict_types=1);

namespace Drupal\vipps_recurring_payments\Service;

use Drupal\vipps_recurring_payments\RequestStorage\RequestStorageInterface;
use Drupal\vipps_recurring_payments\ResponseApiData\AgreementData;
use Drupal\vipps_recurring_payments\ResponseApiData\ChargeItemResponse;
use Drupal\vipps_recurring_payments\ResponseApiData\DraftAgreementResponse;
use GuzzleHttp\ClientInterface;
use Psr\Http\Message\ResponseInterface;

class VippsHttpClient {

  private $httpClient;

  private $config;

  public function __construct(ClientInterface $httpClient, VippsApiConfig $config) {

    $this->httpClient = $httpClient;

    $this->config = $config;
  }

  public function auth():string
  {
    $response = $this->httpClient->request('POST', $this->config->getAccessTokenRequestUrl(), [
      'headers' => [
        'client_id' => $this->config->getClientId(),
        'client_secret' => $this->config->getClientSecret(),
        'Ocp-Apim-Subscription-Key' => $this->config->getSubscriptionKey(),
      ]
    ]);

    return $this->getResponseBody($response)->access_token;
  }

  public function draftAgreement(string $token, RequestStorageInterface $requestStorage):DraftAgreementResponse
  {
    $response = $this->httpClient->request('POST', $this->config->getDraftAgreementRequestUrl(), [
      'headers' => [
        'Content-Type' => 'application/json',
        'Authorization' => "Bearer {$token}",
        'Ocp-Apim-Subscription-Key' => $this->config->getSubscriptionKey(),
      ],
      'json' => $requestStorage->getData(),
    ]);

    $responseData = $this->getResponseBody($response);

    return new DraftAgreementResponse(
      $responseData->vippsConfirmationUrl,
      $responseData->agreementResource,
      $responseData->agreementId
    );
  }

  public function createCharge(string $token, string $orderId, RequestStorageInterface $requestStorage):string {

    $response = $this->httpClient->request('POST', $this->config->getCreateChargeUrl($orderId), [
      'Accept' => 'application/json',
      'headers' => [
        'Content-Type' => 'application/json',
        'Idempotent-Key' => $orderId . time(),
        'Authorization' => "Bearer {$token}",
        'Ocp-Apim-Subscription-Key' => $this->config->getSubscriptionKey(),
      ],
      'json' => $requestStorage->getData(),
    ]);

    $responseData = $this->getResponseBody($response);

    return $responseData->chargeId;
  }

  public function getRetrieveAgreement(string $token, string $agreementId):array {
    $response = $this->httpClient->request('GET', $this->config->getRetrieveAgreementUrl($agreementId), [
      'headers' => [
        'Authorization' => "Bearer {$token}",
        'Ocp-Apim-Subscription-Key' => $this->config->getSubscriptionKey(),
      ],
    ]);

    return (array) $this->getResponseBody($response);
  }

  public function getCharge(string $token, string $agreementId, string $chargeId):ChargeItemResponse{
    $response = $this->httpClient->request('GET', $this->config->getChargeUrl($agreementId, $chargeId), [
      'headers' => [
        'Authorization' => "Bearer {$token}",
        'Ocp-Apim-Subscription-Key' => $this->config->getSubscriptionKey(),
      ],
    ]);

    return new ChargeItemResponse($response);
  }

  public function getCharges(string $token, string $agreementId) {
    $response = $this->httpClient->request('GET', $this->config->getRetrieveChargesUrl($agreementId), [
      'headers' => [
        'Authorization' => "Bearer {$token}",
        'Ocp-Apim-Subscription-Key' => $this->config->getSubscriptionKey(),
      ],
    ]);

    return json_decode($response->getBody()->getContents());
  }

  public function updateAgreement(string $token, string $agreementId, RequestStorageInterface $requestStorage){
    $response = $this->httpClient->request('PATCH', $this->config->getUpdateAgreementUrl($agreementId), [
      'Accept' => 'application/json',
      'headers' => [
        'Content-Type' => 'application/json',
        'Authorization' => "Bearer {$token}",
        'Ocp-Apim-Subscription-Key' => $this->config->getSubscriptionKey(),
      ],
      'json' => $requestStorage->getData(),
    ]);

    return $this->getResponseBody($response);
  }

  public function cancelCharge(string $token, string $agreementId, string $chargeId){
    $response = $this->httpClient->request('DELETE', $this->config->getChargeUrl($agreementId, $chargeId), [
      'headers' => [
        'Content-Type' => 'application/json',
        'Authorization' => "Bearer {$token}",
        'Ocp-Apim-Subscription-Key' => $this->config->getSubscriptionKey(),
      ],
    ]);

    $statusCode = $response->getStatusCode();
    if ($statusCode == 200) {
      return array(
        'status'      => $statusCode
      );
    } else {
      return array(
        'status'      => $statusCode,
        'error'       => $this->getResponseBody($response)
      );
    }

  }

  public function refundCharge(string $token, string $agreementId, string $chargeId, RequestStorageInterface $requestStorage){
    $response = $this->httpClient->request('POST', $this->config->getRefundUrl($agreementId, $chargeId), [
      'headers' => [
        'Content-Type' => 'application/json',
        'Authorization' => "Bearer {$token}",
        'Ocp-Apim-Subscription-Key' => $this->config->getSubscriptionKey(),
        'Idempotency-Key' => "{$agreementId}_{$chargeId}_{$requestStorage->getData()['amount']}",
      ],
      'json' => $requestStorage->getData()
    ]);

    $statusCode = $response->getStatusCode();
    if ($statusCode == 200) {
      return array(
        'status'      => $statusCode
      );
    } else {
      return array(
        'status'      => $statusCode,
        'error'       => $this->getResponseBody($response)
      );
    }

  }

  public function captureCharge(string $token, string $agreementId, string $chargeId, RequestStorageInterface $requestStorage){
    $response = $this->httpClient->request('POST', $this->config->getCaptureUrl($agreementId, $chargeId), [
      'headers' => [
        'Content-Type' => 'application/json',
        'Authorization' => "Bearer {$token}",
        'Ocp-Apim-Subscription-Key' => $this->config->getSubscriptionKey(),
        'Idempotency-Key' => "{$agreementId}_{$chargeId}_{$requestStorage->getData()['amount']}",
      ],
      'json' => $requestStorage->getData()
    ]);

    $statusCode = $response->getStatusCode();
    if ($statusCode == 200) {
      return array(
        'status'      => $statusCode
      );
    } else {
      return array(
        'status'      => $statusCode,
        'error'       => $this->getResponseBody($response)
      );
    }

  }

  private function getResponseBody(ResponseInterface $response):\stdClass {
    return json_decode($response->getBody()->getContents());
  }
}
