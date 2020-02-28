<?php

declare(strict_types=1);

namespace Drupal\vipps_recurring_payments\UseCase;

class Charges {

  private $charges = [];

  public function __construct(string $chargesJson){
    try {
      $this->initChargesArr(\GuzzleHttp\json_decode($chargesJson));
    } catch (\Throwable $e) {
      \Drupal::service('logger.factory')->get('vipps')->error($e->getMessage());
    }
  }

  /**
   * @return ChargeItem[]
   */
  public function getCharges():array {
    return $this->charges;
  }

  private function initChargesArr(array $decodedCharges){
    foreach ($decodedCharges as $charge) {
      array_push($this->charges, new ChargeItem(
        $charge->agreement_id,
        $charge->price,
        $charge->description ?? null,
        $charge->chargeId ?? null
      ));
    }
  }

}
