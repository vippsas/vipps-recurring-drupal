<?php

declare(strict_types=1);

namespace Drupal\vipps_recurring_payments\ResponseApiData;

class CancelAgreementResponse
{
  private $errors = [];

  private $successes = [];

  public function addSuccessCancel(string $id) {
    array_push($this->successes, $id);
  }

  public function addError(ResponseErrorItem $errorItem) {
    array_push($this->errors, $errorItem->toString());
  }

  public function toArray():array {
    return [
      'successes' => $this->successes,
      'errors' => $this->errors,
    ];
  }
}
