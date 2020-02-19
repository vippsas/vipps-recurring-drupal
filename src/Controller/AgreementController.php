<?php

declare(strict_types=1);

namespace Drupal\vipps_recurring_payments\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class AgreementController extends ControllerBase
{
  private $logger;

  public function __construct(LoggerChannelFactoryInterface $loggerChannelFactory)
  {
    $this->logger = $loggerChannelFactory;
  }

  public function merchantAgreement()
  {
    $this->logger->get('vipps')->debug(json_encode($_POST, $_GET));
  }

  public static function create(ContainerInterface $container)
  {
    /* @var $loggerFactory LoggerChannelFactoryInterface */
    $loggerFactory = $container->get('logger.factory');

    return new static($loggerFactory);
  }
}
