<?php

namespace Drupal\vipps_recurring_payments\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\vipps_recurring_payments\Service\VippsService;
use Drupal\vipps_recurring_payments\UseCase\Charges;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestStack;

class ChargeController extends ControllerBase
{
  private $request;

  private $vippsService;

  private $logger;

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

  public function make(){
    try {
      $chargesStorage = new Charges($this->request->getContent());

      return new JsonResponse($this->vippsService->makeCharges($chargesStorage)->toArray());

    } catch (\Throwable $exception) {
      return new JsonResponse([
        'success' => false,
        'error' => $exception->getMessage(),
      ]);
    }
  }

  public function cancel(){
    try {
      $chargesStorage = new Charges($this->request->getContent());

      return new JsonResponse($this->vippsService->cancelCharges($chargesStorage)->toArray());

    } catch (\Throwable $exception) {
      return new JsonResponse([
        'success' => false,
        'error' => $exception->getMessage(),
      ]);
    }
  }

  public function refund(){
    try {
      $chargesStorage = new Charges($this->request->getContent());

      return new JsonResponse($this->vippsService->refundCharges($chargesStorage)->toArray());

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
