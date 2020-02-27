<?php

declare(strict_types=1);

namespace Drupal\vipps_recurring_payments_webform\Service;

use Drupal\advancedqueue\Job;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\vipps_recurring_payments\Repository\ProductSubscriptionRepositoryInterface;
use Drupal\vipps_recurring_payments\Service\DelayManager;
use Drupal\vipps_recurring_payments\Service\VippsHttpClient;
use Drupal\vipps_recurring_payments_webform\Repository\WebformSubmissionRepository;
use Drupal\webform\WebformSubmissionInterface;
use Drupal\advancedqueue\Entity\Queue;

class AgreementService
{
  private $httpClient;

  private $logger;

  private $submissionRepository;

  private $productSubscriptionRepository;

  private $delayManager;

  /**
   * The module handler.
   *
   * @var ModuleHandlerInterface
   */
  protected $moduleHandler;

  public function __construct(
    VippsHttpClient $httpClient,
    LoggerChannelFactoryInterface $loggerChannelFactory,
    WebformSubmissionRepository $submissionRepository,
    ProductSubscriptionRepositoryInterface $productSubscriptionRepository,
    DelayManager $delayManager,
    ModuleHandlerInterface $module_handler
  )
  {
    $this->httpClient = $httpClient;
    $this->logger = $loggerChannelFactory;
    $this->submissionRepository = $submissionRepository;
    $this->productSubscriptionRepository = $productSubscriptionRepository;
    $this->delayManager = $delayManager;
    $this->moduleHandler = $module_handler;
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

    /**
     * Invoke hook_vipps_recurring_payment_done and pass $submission
     * This is useful so third party modules can do what ever they want after form submission and payment
     */
    $this->moduleHandler->invokeAll('vipps_recurring_payment_done', ['submission' => $submission]);

    $product = $this->productSubscriptionRepository->getProduct();
    $product->setPrice($this->getSubmissionAmount($submission));

    $job = Job::create('create_charge_job', ['orderId' => $submission->getElementData('agreement_id')]);
    $queue = Queue::load('default');//TODO use custom queue
    $queue->enqueueJob($job, $this->delayManager->getCountSecondsToNextPayment($product));

    $this->logger->get('vipps')->info(
      sprintf("Subscription %s has been done successfully", $submission->getElementData('agreement_id'))
    );
  }

  private function getSubmissionAmount(WebformSubmissionInterface $webformSubmission):float {
    $amount = !empty($webformSubmission->getElementData('amount_select')) ?
      $webformSubmission->getElementData('amount_select') :
      $webformSubmission->getElementData('amount');

    return floatval($amount);
  }
}
