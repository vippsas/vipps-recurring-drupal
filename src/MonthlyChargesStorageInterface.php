<?php

namespace Drupal\vipps_recurring_payments;

use Drupal\Core\Entity\ContentEntityStorageInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\vipps_recurring_payments\Entity\MonthlyChargesInterface;

/**
 * Defines the storage handler class for Monthly charges entities.
 *
 * This extends the base storage class, adding required special handling for
 * Monthly charges entities.
 *
 * @ingroup vipps_recurring_payments
 */
interface MonthlyChargesStorageInterface extends ContentEntityStorageInterface {

  /**
   * Gets a list of Monthly charges revision IDs for a specific Monthly charges.
   *
   * @param \Drupal\vipps_recurring_payments\Entity\MonthlyChargesInterface $entity
   *   The Monthly charges entity.
   *
   * @return int[]
   *   Monthly charges revision IDs (in ascending order).
   */
  public function revisionIds(MonthlyChargesInterface $entity);

  /**
   * Gets a list of revision IDs having a given user as Monthly charges author.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user entity.
   *
   * @return int[]
   *   Monthly charges revision IDs (in ascending order).
   */
  public function userRevisionIds(AccountInterface $account);

}
