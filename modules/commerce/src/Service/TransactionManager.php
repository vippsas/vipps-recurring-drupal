<?php

namespace Drupal\vipps_recurring_payments_commerce\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;

class TransactionManager {

  private $connection;

  private $logger;

  public function __construct(Connection $connection, LoggerChannelFactoryInterface $logger) {
    $this->connection = $connection;
    $this->logger = $logger;
  }

  public function execute(callable $function):void {
    $transaction = $this->connection->startTransaction();

    try {
      call_user_func($function);
    }
    catch (\Exception $e) {
      $transaction->rollBack();

      $this->logger->get('error')->error($e->getMessage());
    }
  }

}
