<?php

namespace Drupal\vipps_recurring_payments_commerce\Resolver;

use Drupal\vipps_recurring_payments_commerce\Resolver\ChainOrderIdResolverInterface;
use Drupal\vipps_recurring_payments_commerce\Resolver\OrderIdResolverInterface;

class ChainOrderIdResolver implements ChainOrderIdResolverInterface {

  /**
   * The resolvers.
   *
   * @var \Drupal\vipps_recurring_payments_commerce\Resolver\OrderIdResolverInterface[]
   */
  protected $resolvers = [];

  /**
   * Constructs a new ChainOrderIdResolver object.
   *
   * @param \Drupal\vipps_recurring_payments_commerce\Resolver\OrderIdResolverInterface[] $resolvers
   *   The resolvers.
   */
  public function __construct(array $resolvers = []) {
    $this->resolvers = $resolvers;
  }

  /**
   * {@inheritdoc}
   */
  public function addResolver(OrderIdResolverInterface $resolver) {
    $this->resolvers[] = $resolver;
  }

  /**
   * {@inheritdoc}
   */
  public function getResolvers() {
    return $this->resolvers;
  }

  /**
   * {@inheritdoc}
   */
  public function resolve() {
    foreach ($this->resolvers as $resolver) {
      $result = $resolver->resolve();
      if ($result) {
        return $result;
      }
    }
  }

}
