<?php

declare(strict_types=1);

namespace Drupal\vipps_recurring_payments\ResponseApiData;

class ResponseErrorItem
{
  private $id;

  private $message;

  public function __construct(string $id, string $message)
  {
    $this->message = $message;
    $this->id = $id;
  }

  public function getId(): string
  {
    return $this->id;
  }

  public function getMessage(): string
  {
    return $this->message;
  }

  public function toString():string {
    return sprintf("%s: %s", $this->getId(), $this->getMessage());
  }
}

