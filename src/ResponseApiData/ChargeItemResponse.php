<?php

declare(strict_types=1);

namespace Drupal\vipps_recurring_payments\ResponseApiData;

use Psr\Http\Message\ResponseInterface;

class ChargeItemResponse
{
  const STATUS_PENDING = 'PENDING';
  const STATUS_DUE = 'DUE';
  const STATUS_CHARGED = 'CHARGED';
  const STATUS_FAILED = 'FAILED';
  const STATUS_REFUNDED = 'REFUNDED';
  const STATUS_PARTIALLY_REFUNDED = 'PARTIALLY_REFUNDED';
  const STATUS_RESERVED = 'RESERVED';

  private $id;
  private $status;
  private $due;
  private $amount;
  private $description;
  private $type;

  public function __construct(ResponseInterface $response)
  {
    try {
      /* @var $responseStd \stdClass */
      $responseStd = json_decode($response->getBody());

      $this->id = $responseStd->id;
      $this->status = $responseStd->status;
      $this->due = $responseStd->due;
      $this->amount = $responseStd->amount;
      $this->description = $responseStd->description;
      $this->type = $responseStd->type;
    } catch (\Throwable $exception) {

    }
  }

  public function charged():bool {
    return ($this->status === self::STATUS_CHARGED);
  }

  public function canContinue():bool {//TODO check with which statuses need to wait
    return in_array($this->status, [self::STATUS_PENDING, self::STATUS_DUE]);
  }

  public function failed():bool {//TODO check with which statuses need to close agreement
    return in_array($this->status, [self::STATUS_FAILED, self::STATUS_REFUNDED, self::STATUS_PARTIALLY_REFUNDED, self::STATUS_RESERVED]);
  }

  public function getId():string
  {
    return $this->id;
  }

  public function getDue():\DateTime
  {
    return new \DateTime($this->due);
  }

  public function getAmount():int
  {
    return intval($this->amount);
  }

  public function getDescription():string
  {
    return $this->description;
  }

  public function getType():string
  {
    return $this->type;
  }

}
