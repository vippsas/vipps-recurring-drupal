<?php

declare(strict_types=1);

namespace Drupal\vipps_recurring_payments\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\vipps_recurring_payments\Form\SettingsForm;

class VippsApiConfig {

  private const access_token_path = '/accessToken/get';

  private const draft_agreement_path = '/recurring/v2/agreements';

  protected $configFactory;

  protected $msn;

  protected $access_token;

  protected $subscription_key;

  protected $client_id;

  protected $client_secret;

  protected $base_url;

  protected $merchant_redirect_url;

  protected $merchant_agreement_url;

  public function __construct(ConfigFactoryInterface $configFactory) {

    $this->configFactory = $configFactory->getEditable(SettingsForm::SETTINGS);
    $this->initializeAttributes();
  }

  public function getMsn():string {
    return $this->msn;
  }

  public function getAccessToken():string {
    return $this->access_token;
  }

  public function getSubscriptionKey():string {
    return $this->subscription_key;
  }

  public function getClientId():string {
    return $this->client_id;
  }

  public function getClientSecret():string {
    return $this->client_secret;
  }

  public function getBaseUrl():string {
    return $this->base_url;
  }

  public function getMerchantRedirectUrl():string {
    return $this->merchant_redirect_url;
  }

  public function getMerchantAgreementUrl():string {
    return $this->merchant_agreement_url;
  }

  public function getAccessTokenRequestUrl():string {
    return $this->generateUrl(self::access_token_path);
  }

  public function getDraftAgreementRequestUrl():string {
    return $this->generateUrl(self::draft_agreement_path);
  }

  public function getCreateChargeUrl(string $orderId):string {
    return $this->generateUrl(sprintf("/recurring/v2/agreements/%s/charges", $orderId));
  }

  public function getRetrieveAgreementUtl(string $agreementId):string {
    return $this->generateUrl(sprintf("/recurring/v2/agreements/%s", $agreementId));
  }

  public function getUpdateAgreementUrl(string $agreementId):string {
    return $this->generateUrl(sprintf("/recurring/v2/agreements/%s", $agreementId));
  }

  public function getChargeUrl(string $agreementId, string $chargeId):string {
    return $this->generateUrl(sprintf("/recurring/v2/agreements/%s/charges/%s", $agreementId, $chargeId));
  }

  private function initializeAttributes():void
  {
    foreach ($this->configFactory->getRawData() as $attributeName => $value) {
      $this->$attributeName = $value;
    }
  }

  private function generateUrl(string $path):string
  {
    return $this->getBaseUrl() . $path;
  }
}
