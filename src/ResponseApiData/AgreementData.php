<?php

declare(strict_types=1);

namespace Drupal\vipps_recurring_payments\ResponseApiData;

class AgreementData
{
  const PENDING = 'PENDING';
  const ACTIVE = 'ACTIVE';
  const STOPPED = 'STOPPED';
  const EXPIRED = 'EXPIRED';

  private $id;

  private $status;

  private $price;

  private $response;

  public function __construct(\stdClass $response)
  {
    if(!$this->statusValid($response->status)) {
      throw new \DomainException('Unsupported status');
    }
    $this->id = $response->id;
    $this->response = $response;
    $this->status = $response->status;
    $this->price = $response->price;
  }

  public function getStatuses():array {
    return [self::PENDING, self::ACTIVE, self::STOPPED, self::EXPIRED];
  }

  public function statusValid(string $status):bool {
    return  in_array($status, $this->getStatuses());
  }

  public function getId():string {
    return $this->id;
  }

  public function getStatus():string {
    return $this->status;
  }

  public function getPrice():int {
    return $this->price;
  }

  public function isActive():bool {
    return ($this->status === self::ACTIVE);
  }

  public function toArray():array {
    return (array) $this->response;
  }
}
