<?php

namespace Drupal\vipps_recurring_payments;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceModifierInterface;
use Drupal\vipps_recurring_payments\Repository\WebFormProductSubscriptionRepository;
use Symfony\Component\DependencyInjection\Reference;

class VippsRecurringPaymentsServiceProvider implements ServiceModifierInterface
{
  /**
   * Modifies existing service definitions.
   *
   * @param ContainerBuilder $container
   *   The ContainerBuilder whose service definitions can be altered.
   */
  public function alter(ContainerBuilder $container) {
    $container->getDefinition('vipps_recurring_payments:product_subscription_repository')
      ->setClass(WebFormProductSubscriptionRepository::class)
      ->addArgument(new Reference('config.factory'));
  }
}
