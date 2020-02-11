<?php

namespace Drupal\vipps_recurring_payments;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceModifierInterface;
use Drupal\vipps_recurring_payments\Repository\WebFormProductSubscriptionRepository;

class VippsRecurringPaymentsServiceProvider implements ServiceModifierInterface
{
  /**
   * Modifies existing service definitions.
   *
   * @param ContainerBuilder $container
   *   The ContainerBuilder whose service definitions can be altered.
   */
  public function alter(ContainerBuilder $container) {

    $this->setBasicAuthAsGlobal($container);

    $container->getDefinition('vipps_recurring_payments:product_subscription_repository')
      ->setClass(WebFormProductSubscriptionRepository::class);
  }

  private function setBasicAuthAsGlobal(ContainerBuilder $container){
//    $basicAuthDefinition = $container->getDefinition('basic_auth.authentication.basic_auth');
//    $tags = $basicAuthDefinition->getTags();
//    $tags['authentication_provider'][0]['global'] = TRUE;
//    $basicAuthDefinition->setTags($tags);
    //TODO solve basic_auth.authentication problem
  }
}
