<?php

declare(strict_types=1);

namespace Drupal\vipps_recurring_payments_webform\Service;

use Drupal\advancedqueue\Job;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\vipps_recurring_payments\Service\VippsHttpClient;
use Drupal\vipps_recurring_payments_webform\Repository\WebformSubmissionRepository;
use Drupal\webform\WebformSubmissionInterface;
use Drupal\advancedqueue\Entity\Queue;

class AgreementService
{
  private $httpClient;

  private $logger;

  private $submissionRepository;

  public function __construct(
    VippsHttpClient $httpClient,
    LoggerChannelFactoryInterface $loggerChannelFactory,
    WebformSubmissionRepository $submissionRepository
  )
  {
    $this->httpClient = $httpClient;
    $this->logger = $loggerChannelFactory;
    $this->submissionRepository = $submissionRepository;
  }

  public function confirmAgreementAndAddChargeTQueue(WebformSubmissionInterface $submission):void
  {
    $agreementData = $this->httpClient->getRetrieveAgreement(
      $this->httpClient->auth(),
      $submission->getElementData('agreement_id')
    );

    $this->submissionRepository->setStatus($submission, $agreementData->getStatus());

    if(!$agreementData->isActive()) {
      $this->logger->get('vipps')->error(
        sprintf("Agreement %s has status %s",
          $submission->getElementData('agreement_id'),
          $agreementData->getStatus()
        )
      );

      throw new \DomainException('Something went wrong. Please contact to administrator');
    }

    $job = Job::create('create_charge_job', ['orderId' => $submission->getElementData('agreement_id')]);
    $queue = Queue::load('default');//TODO use custom queue
    $queue->enqueueJob($job, 300);//TODO 300 test value
    // $this->delayManager->getCountSecondsToNextPayment($order->getProduct())

    $this->logger->get('vipps')->info(
      sprintf("Subscription %s has been done successfully", $submission->getElementData('agreement_id'))
    );
  }
}
