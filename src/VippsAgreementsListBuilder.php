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
  public function buildHeader() {
    $header['id'] = $this->t('Vipps agreements ID');
    $header['name'] = $this->t('Name');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var \Drupal\vipps_recurring_payments\Entity\VippsAgreements $entity */
    $row['id'] = $entity->id();
    $row['name'] = Link::createFromRoute(
      $entity->label(),
      'entity.vipps_agreements.edit_form',
      ['vipps_agreements' => $entity->id()]
    );
    return $row + parent::buildRow($entity);
  }

}
