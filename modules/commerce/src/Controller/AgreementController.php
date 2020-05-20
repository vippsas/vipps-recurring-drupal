<?php

namespace Drupal\vipps_recurring_payments_commerce\Controller;

use Drupal\commerce_order\Entity\Order;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Messenger\Messenger;
use Drupal\Core\Url;
use Drupal\vipps_recurring_payments_commerce\Service\AgreementService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;

class AgreementController extends ControllerBase {
  private $request;

  private $logger;

  private $agreementService;

  private $submissionRepository;

  protected $messenger;

  public function __construct(
    RequestStack $requestStack,
    LoggerChannelFactoryInterface $loggerChannelFactory,
    Messenger $messenger,
    AgreementService $agreementService
  )
  {
    $this->request = $requestStack->getCurrentRequest();
    $this->logger = $loggerChannelFactory;
    $this->messenger = $messenger;
    $this->agreementService = $agreementService;
  }

  public static function create(ContainerInterface $container)
  {
    /* @var RequestStack $requestStack */
    $requestStack = $container->get('request_stack');

    /* @var $loggerFactory LoggerChannelFactoryInterface */
    $loggerFactory = $container->get('logger.factory');

    /* @var Messenger $messenger */
    $messenger = $container->get('messenger');

    /* @var AgreementService $agreementService */
    $agreementService = $container->get('vipps_recurring_payments_recurring:agreement_service');

    return new static(
      $requestStack,
      $loggerFactory,
      $messenger,
      $agreementService
    );
  }

  public function confirm() {
    $order_id = $this->request->get('commerce_order');
    $order = Order::load($order_id);

    try {
      $this->agreementService->confirmAgreementAndAddChargeToQueue($order);
      $this->messenger->addMessage($this->t('Subscription has been done successfully for order: '. $order_id));

    } catch (\Throwable $e) {
      $this->messenger->addError($this->t($e->getMessage()));
      return new RedirectResponse(Url::fromRoute('commerce_checkout.form', ['commerce_order' => $order_id, 'step' => 'review'])->toString());
    }

    return new RedirectResponse(Url::fromRoute('commerce_checkout.form', ['commerce_order' => $order_id, 'step' => 'complete'])->toString());
  }
}
