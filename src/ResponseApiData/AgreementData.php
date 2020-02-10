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

  public function __construct(string $id, string $status)
  {
    if(!$this->statusValid($status)) {
      throw new \DomainException('Unsupported status');
    }
    $this->id = $id;
    $this->status = $status;
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

  public function isActive():bool {
    return ($this->status === self::ACTIVE);
  }
}
