<?php

namespace Drupal\vipps_recurring_payments_webform\Controller;

use Drupal\Core\Messenger\Messenger;
use Drupal\vipps_recurring_payments\Service\VippsService;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\vipps_recurring_payments_webform\Repository\WebformSubmissionRepository;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\Core\Url;

class AgreementController extends ControllerBase
{
  private $request;

  private $vippsService;

  private $logger;

  private $submissionRepository;

  protected $messenger;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  public function __construct(
    RequestStack $requestStack,
    VippsService $vippsService,
    LoggerChannelFactoryInterface $loggerChannelFactory,
    ModuleHandlerInterface $module_handler,
    Messenger $messenger,
    WebformSubmissionRepository $submissionRepository
  )
  {
    $this->request = $requestStack->getCurrentRequest();
    $this->vippsService = $vippsService;
    $this->logger = $loggerChannelFactory;
    $this->moduleHandler = $module_handler;
    $this->messenger = $messenger;
    $this->submissionRepository = $submissionRepository;
  }

  public function confirm()
  {
    $submission = $this->submissionRepository->getById(intval($this->request->get('submission_id')));

    try {
      $agreementStatus = $this->vippsService->agreementStatus($submission->getElementData('agreement_id'));
      $this->submissionRepository->setStatus($submission, $agreementStatus);

      if( $this->vippsService->agreementActive($submission->getElementData('agreement_id'))) {
        $this->messenger->addMessage($this->t('Subscription has been done successfully'));
        $this->logger->get('vipps')->info(
          sprintf("Subscription %s has been done successfully", $submission->getElementData('agreement_id'))
        );
      } else {
        $this->messenger->addError($this->t('Something went wrong. Please contact to administrator'));
        $this->logger->get('vipps')->error(
          sprintf("Agreement %s has status %s", $submission->getElementData('agreement_id'), $agreementStatus)
        );
      }
    } catch (\Throwable $e) {
      $this->logger->get('vipps')->error(sprintf("Agreement %s. ", $submission->get('agreement_id')) . $e->getMessage());
    }

    return new RedirectResponse(Url::fromRoute('<front>')->toString());
  }

  public static function create(ContainerInterface $container)
  {
    /* @var RequestStack $requestStack */
    $requestStack = $container->get('request_stack');

    /* @var VippsService $vippsService */
    $vippsService = $container->get('vipps_recurring_payments:vipps_service');

    /* @var $loggerFactory LoggerChannelFactoryInterface */
    $loggerFactory = $container->get('logger.factory');

    /* @var $moduleHandler ModuleHandlerInterface */
    $moduleHandler = $container->get('module_handler');

    /* @var Messenger $messenger */
    $messenger = $container->get('messenger');

    /* @var WebformSubmissionRepository $submissionRepository */
    $submissionRepository = $container->get('vipps_recurring_payments_webform:submission_repository');

    return new static($requestStack, $vippsService, $loggerFactory, $moduleHandler, $messenger, $submissionRepository);
  }
}
