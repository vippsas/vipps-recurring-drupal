<?php

declare(strict_types=1);

namespace Drupal\vipps_recurring_payments\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\vipps_recurring_payments\Service\VippsService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\JsonResponse;

class AgreementController extends ControllerBase
{
  private $logger;

  private $vippsService;

  private $request;

  public function __construct(
    RequestStack $requestStack,
    VippsService $vippsService,
    LoggerChannelFactoryInterface $loggerChannelFactory
  )
  {
    $this->request = $requestStack->getCurrentRequest();
    $this->vippsService = $vippsService;
    $this->logger = $loggerChannelFactory;
  }

  public function merchantAgreement()
  {
    $this->logger->get('vipps')->debug(json_encode($_POST, $_GET));
  }

  public function confirmAgreement()
  {
    $this->logger->get('vipps')->debug(json_encode($_POST, $_GET));
  }

  public function get(){
    try {
      $requestContent = \GuzzleHttp\json_decode($this->request->getContent());

      return new JsonResponse($this->vippsService->getAgreement($requestContent));

    } catch (\Throwable $exception) {
      return new JsonResponse([
        'success' => false,
        'error' => $exception->getMessage(),
      ]);
    }
  }

  public function cancel(){
    try {
      $requestContent = \GuzzleHttp\json_decode($this->request->getContent());

      return new JsonResponse($this->vippsService->cancelAgreement($requestContent)->toArray());

    } catch (\Throwable $exception) {
      return new JsonResponse([
        'success' => false,
        'error' => $exception->getMessage(),
      ]);
    }
  }

  public static function create(ContainerInterface $container)
  {
    /* @var RequestStack $requestStack */
    $requestStack = $container->get('request_stack');

    /* @var VippsService $vippsService */
    $vippsService = $container->get('vipps_recurring_payments:vipps_service');

    /* @var $loggerFactory LoggerChannelFactoryInterface */
    $loggerFactory = $container->get('logger.factory');

    return new static($requestStack, $vippsService, $loggerFactory);
  }
}
