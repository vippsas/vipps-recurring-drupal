<?php

declare(strict_types=1);

namespace Drupal\vipps_recurring_payments\ResponseApiData;

class DraftAgreementResponse {

  private $vippsConfirmationUrl;

  private $agreementResource;

  private $agreementId;

  public function __construct(string $vippsConfirmationUrl, string $agreementResource, string $agreementId) {
    $this->vippsConfirmationUrl = $vippsConfirmationUrl;
    $this->agreementResource = $agreementResource;
    $this->agreementId = $agreementId;
  }

  public function getVippsConfirmationUrl():string {
    return $this->vippsConfirmationUrl;
  }

  public function getAgreementResource():string {
    return $this->agreementResource;
  }

  public function getAgreementId():string {
    return $this->agreementId;
  }

}
