<?php

namespace Drupal\vipps_recurring_payments;

use Drupal\Core\Entity\ContentEntityStorageInterface;
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
interface PeriodicChargesStorageInterface extends ContentEntityStorageInterface {

  /**
   * Gets a list of Periodic charges revision IDs for a specific Periodic charges.
   *
   * @param \Drupal\vipps_recurring_payments\Entity\PeriodicChargesInterface $entity
   *   The Periodic charges entity.
   *
   * @return int[]
   *   Periodic charges revision IDs (in ascending order).
   */
  public function revisionIds(PeriodicChargesInterface $entity);

  /**
   * Gets a list of revision IDs having a given user as Periodic charges author.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user entity.
   *
   * @return int[]
   *   Periodic charges revision IDs (in ascending order).
   */
  public function userRevisionIds(AccountInterface $account);

}
