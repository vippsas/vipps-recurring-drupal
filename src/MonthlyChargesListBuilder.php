<?php

namespace Drupal\vipps_recurring_payments;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;

/**
 * Defines a class to build a listing of Monthly charges entities.
 *
 * @ingroup vipps_recurring_payments
 */
class MonthlyChargesListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('Monthly charges ID');
    $header['name'] = $this->t('Name');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var \Drupal\vipps_recurring_payments\Entity\MonthlyCharges $entity */
    $row['id'] = $entity->id();
    $row['name'] = Link::createFromRoute(
      $entity->label(),
      'entity.monthly_charges.edit_form',
      ['monthly_charges' => $entity->id()]
    );
    return $row + parent::buildRow($entity);
  }

}
