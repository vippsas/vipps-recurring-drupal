<?php

namespace Drupal\vipps_recurring_payments_webform\Controller;

use Drupal\Core\Messenger\Messenger;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\vipps_recurring_payments_webform\Repository\WebformSubmissionRepository;
use Drupal\vipps_recurring_payments_webform\Service\AgreementService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\Core\Url;

class AgreementController extends ControllerBase
{
  private $request;

  private $logger;

  private $agreementService;

  private $submissionRepository;

  protected $messenger;

  public function __construct(
    RequestStack $requestStack,
    LoggerChannelFactoryInterface $loggerChannelFactory,
    Messenger $messenger,
    WebformSubmissionRepository $submissionRepository,
    AgreementService $agreementService
  )
  {
    $this->request = $requestStack->getCurrentRequest();
    $this->logger = $loggerChannelFactory;
    $this->messenger = $messenger;
    $this->submissionRepository = $submissionRepository;
    $this->agreementService = $agreementService;
  }

  public function confirm()
  {
    $submission = $this->submissionRepository->getById(intval($this->request->get('submission_id')));

    try {
      $this->agreementService->confirmAgreementAndAddChargeTQueue($submission);
      $this->messenger->addMessage($this->t('Subscription has been done successfully: '. $submission->id()));

    } catch (\Throwable $e) {
      $this->messenger->addError($this->t($e->getMessage()));
    }

    return new RedirectResponse(Url::fromRoute('<front>')->toString());
  }

  public static function create(ContainerInterface $container)
  {
    /* @var RequestStack $requestStack */
    $requestStack = $container->get('request_stack');

    /* @var $loggerFactory LoggerChannelFactoryInterface */
    $loggerFactory = $container->get('logger.factory');

    /* @var Messenger $messenger */
    $messenger = $container->get('messenger');

    /* @var WebformSubmissionRepository $submissionRepository */
    $submissionRepository = $container->get('vipps_recurring_payments_webform:submission_repository');

    /* @var AgreementService $agreementService */
    $agreementService = $container->get('vipps_recurring_payments_webform:agreement_service');

    return new static(
      $requestStack,
      $loggerFactory,
      $messenger,
      $submissionRepository,
      $agreementService
    );
  }
}
