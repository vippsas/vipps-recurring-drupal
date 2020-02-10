<?php

declare(strict_types=1);

namespace Drupal\vipps_recurring_payments\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\vipps_recurring_payments\Service\VippsHttpClient;
use Symfony\Component\HttpFoundation\Response;

class TestController extends ControllerBase
{
  private $httpClient;

  public function __construct(VippsHttpClient $httpClient){
    $this->httpClient = $httpClient;
  }

  public function auth(){
    return new Response($this->httpClient->auth());
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {

    /* @var VippsHttpClient $httpClient */
    $httpClient = $container->get('vipps_recurring_payments:http_client');

    return new static($httpClient);
  }
}
