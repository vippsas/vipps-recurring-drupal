<?php

declare(strict_types=1);

namespace Drupal\vipps_recurring_payments_commerce\Service;

use Drupal\advancedqueue\Entity\Queue;
use Drupal\advancedqueue\Job;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\vipps_recurring_payments\Service\DelayManager;
use Drupal\vipps_recurring_payments_commerce\Entity\OrderAgreement;

class QueueManager
{
  private $delayManager;
  private $logger;

  public function __construct(DelayManager $delayManager, LoggerChannelFactoryInterface $logger)
  {
    $this->logger = $logger;
    $this->delayManager = $delayManager;
  }

  public function addConfirmAgreementJobToQueue(string $agreementId, int $delay = null):void {
    $this->execute(function () use ($agreementId, $delay) {
      $job = Job::create('confirm_agreement_job', ['agreementId' => $agreementId]);
      $queue = Queue::load('confirm_agreement');
      $queue->enqueueJob($job, $delay);
    });
  }

  public function addConfirmChargeToTomorrowsQueue(int $orderItemId):void {
    $this->execute(function () use ($orderItemId) {
      $job = Job::create('confirm_charge_job', ['orderItemId' => $orderItemId]);
      $queue = Queue::load('confirm_charge');
      $queue->enqueueJob($job, $this->delayManager->getCountSecondsToTomorrow(14, 01));
    });
  }

  public function addCreateChargeJobToQueueBasedOnProductInterval(OrderAgreement $order):void {
    $this->execute(function () use ($order) {
      $job = Job::create('create_charge_job', ['orderId' => $order->getId()]);
      $queue = Queue::load('create_charge');
      $queue->enqueueJob($job, $this->delayManager->getCountSecondsToNextPayment($order->getProduct()));
    });
  }

  private function execute(callable $function):void {
    try {
      call_user_func($function);
    } catch (\Exception $exception) {
      $this->logger->get('vipps')->error($exception->getMessage());
    }
  }

}

