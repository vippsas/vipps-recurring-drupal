<?php

namespace Drupal\vipps_recurring_payments;

use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
use Drupal\Core\Session\AccountInterface;
use Drupal\vipps_recurring_payments\Entity\PeriodicChargesInterface;

/**
 * Defines the storage handler class for Periodic charges entities.
 *
 * This extends the base storage class, adding required special handling for
 * Periodic charges entities.
 *
 * @ingroup vipps_recurring_payments
 */
class PeriodicChargesStorage extends SqlContentEntityStorage implements PeriodicChargesStorageInterface {

  /**
   * {@inheritdoc}
   */
  public function revisionIds(PeriodicChargesInterface $entity) {
    return $this->database->query(
      'SELECT vid FROM {periodic_charges_revision} WHERE id=:id ORDER BY vid',
      [':id' => $entity->id()]
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function userRevisionIds(AccountInterface $account) {
    return $this->database->query(
      'SELECT vid FROM {periodic_charges_field_revision} WHERE uid = :uid ORDER BY vid',
      [':uid' => $account->id()]
    )->fetchCol();
  }

}
