<?php


namespace Drupal\vipps_recurring_payments_commerce\Resolver;

interface ChainOrderIdResolverInterface extends OrderIdResolverInterface {

  /**
   * Adds a resolver.
   *
   * @param OrderIdResolverInterface $resolver
   *   The resolver.
   */
  public function addResolver(OrderIdResolverInterface $resolver);

  /**
   * Gets all added resolvers.
   *
   * @return OrderIdResolverInterface[]
   *   The resolvers.
   */
  public function getResolvers();

}