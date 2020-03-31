<?php


namespace Drupal\vipps_recurring_payments_commerce\Resolver;


interface OrderIdResolverInterface {

  /**
   * Resolves the remote order id.
   *
   * @return string
   */
  public function resolve();

}
