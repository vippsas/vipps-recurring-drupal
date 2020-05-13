<?php

namespace Drupal\vipps_recurring_payments;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;

/**
 * Defines a class to build a listing of Vipps agreements entities.
 *
 * @ingroup vipps_recurring_payments
 */
class VippsAgreementsListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  protected function getEntityIds() {
    /** @var \Drupal\Core\Routing\RouteMatchInterface $route */
    $route = \Drupal::service('current_route_match');
    $user = $route->getParameter('user');

    $query = $this->getStorage()->getQuery();

    if(isset($user)) {
      $query->condition('uid', $user->id())
        ->sort('id');
    }

    // Only add the pager if a limit is specified.
    if ($this->limit) {
      $query->pager($this->limit);
    }

    return $query->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    /** @var \Drupal\Core\Routing\RouteMatchInterface $route */
    $route = \Drupal::service('current_route_match');
    $user = $route->getParameter('user');

    if(isset($user)) {
      $header['id'] = $this->t('Vipps agreement');
    } else {
      $header['id'] = $this->t('Vipps agreements ID');
    }
    $header['name'] = $this->t('Mobile');
    $header['price'] = $this->t('Price');
    $header['status'] = $this->t('Agreement Status');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\Core\Routing\RouteMatchInterface $route */
    $route = \Drupal::service('current_route_match');
    $user = $route->getParameter('user');

    if(isset($user)) {
      /* @var \Drupal\vipps_recurring_payments\Entity\VippsAgreements $entity */
      $row['id'] = $entity->agreement_id->value;
    } else {
      /* @var \Drupal\vipps_recurring_payments\Entity\VippsAgreements $entity */
      $row['id'] = $entity->id();
    }

    $row['name'] = Link::createFromRoute(
      $entity->label(),
      'entity.vipps_agreements.edit_form',
      ['vipps_agreements' => $entity->id()]
    );

    $row['price'] =$entity->price->value;
    $row['status'] =$entity->agreement_status->value;
    return $row + parent::buildRow($entity);
  }

}
