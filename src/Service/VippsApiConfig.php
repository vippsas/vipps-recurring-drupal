<?php

declare(strict_types=1);

namespace Drupal\vipps_recurring_payments\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\vipps_recurring_payments\Form\SettingsForm;
use Drupal\Core\Url;

class VippsApiConfig {

  private const access_token_path = '/accessToken/get';

  private const draft_agreement_path = '/recurring/v2/agreements';

  protected $configFactory;

  protected $msn;

  protected $access_token;

  protected $subscription_key;

  protected $client_id;

  protected $client_secret;

  protected $test_mode;

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
    return $this->isTest() ? 'https://apitest.vipps.no' : 'https://api.vipps.no';
  }

  public function getMerchantRedirectUrl(array $params = []):string {
    $urlObject = Url::fromRoute('vipps_recurring_payments_webform.confirm_agreement', $params, ['absolute' => TRUE]);
    return $urlObject->toString();
  }

  public function getMerchantAgreementUrl(array $params = []):string {
    $urlObject = Url::fromRoute('vipps_recurring_payments.merchant_agreement', $params, ['absolute' => TRUE]);
    return $urlObject->toString();
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

  public function getRefundUrl(string $agreementId, string $chargeId):string {
    return $this->generateUrl(sprintf("/recurring/v2/agreements/%s/charges/%s/refund", $agreementId, $chargeId));
  }

  private function initializeAttributes():void
  {
    $rowData = $this->configFactory->getRawData();

    foreach ($rowData as $attributeName => $value) {
      $this->$attributeName = $value;
    }

    if ($this->isTest()) {
      $this->msn = $rowData['test_msn'];
      $this->access_token = $rowData['test_access_token'];
      $this->subscription_key = $rowData['test_subscription_key'];
      $this->client_id = $rowData['test_client_id'];
      $this->client_secret = $rowData['test_client_secret'];
    }
  }

  private function generateUrl(string $path):string
  {
    return $this->getBaseUrl() . $path;
  }

  private function isTest():bool
  {
    return boolval($this->test_mode);
  }
}
