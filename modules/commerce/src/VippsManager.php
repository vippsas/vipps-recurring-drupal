<?php

namespace Drupal\vipps_recurring_payments_commerce;

use Drupal\vipps_recurring_payments\Service\VippsHttpClient;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\PaymentGatewayInterface;
use Drupal\vipps_recurring_payments_commerce\Plugin\Commerce\PaymentGateway\VippsForm;

/**
 * Vipps Manager.
 */
class VippsManager {

  /**
   * @var VippsHttpClient
   */
  protected $httpClient;

  /**
   * VippsManager constructor.
   *
   * @param VippsHttpClient $httpClient
   */
  public function __construct(VippsHttpClient $httpClient) {
    $this->httpClient = $httpClient;
  }

  /**
   * Get Payment Manager.
   */
  public function getPaymentManager(PaymentGatewayInterface $paymentGateway) {
    $settings = $paymentGateway->getConfiguration();
    $vipps = $this->getVippsClient($paymentGateway);

    // Authorize.
    $vipps
      ->authorization($settings['subscription_key_authorization'])
      ->getToken($settings['client_secret']);

    return $vipps->payment($settings['subscription_key_payment'], $settings['serial_number']);
    return $this->httpClient->auth();
  }

  /**
   * Get Vipps Client.
   */
  protected function getVippsClient(PaymentGatewayInterface $paymentGateway) {
    $settings = $paymentGateway->getConfiguration();
    $client = new VippsHttpClient($settings['client_id'], [
      'http_client' => new GuzzleClient($this->httpClient),
      'endpoint' => $settings['mode'] === 'live' ? 'live' : 'test',
    ]);
    return new VippsForm($client);
  }

}
