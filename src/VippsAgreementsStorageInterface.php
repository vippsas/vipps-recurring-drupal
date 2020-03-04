<?php

namespace Drupal\vipps_recurring_payments;

use Drupal\Core\Entity\ContentEntityStorageInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\vipps_recurring_payments\Entity\VippsAgreementsInterface;

/**
 * Defines the storage handler class for Vipps agreements entities.
 *
 * This extends the base storage class, adding required special handling for
 * Vipps agreements entities.
 *
 * @ingroup vipps_recurring_payments
 */
interface VippsAgreementsStorageInterface extends ContentEntityStorageInterface {

  /**
   * Gets a list of Vipps agreements revision IDs for a specific Vipps agreements.
   *
   * @param \Drupal\vipps_recurring_payments\Entity\VippsAgreementsInterface $entity
   *   The Vipps agreements entity.
   *
   * @return int[]
   *   Vipps agreements revision IDs (in ascending order).
   */
  public function revisionIds(VippsAgreementsInterface $entity);

  /**
   * Gets a list of revision IDs having a given user as Vipps agreements author.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user entity.
   *
   * @return int[]
   *   Vipps agreements revision IDs (in ascending order).
   */
  public function userRevisionIds(AccountInterface $account);

  /**
   * Counts the number of revisions in the default language.
   *
   * @param \Drupal\vipps_recurring_payments\Entity\VippsAgreementsInterface $entity
   *   The Vipps agreements entity.
   *
   * @return int
   *   The number of revisions in the default language.
   */
  public function countDefaultLanguageRevisions(VippsAgreementsInterface $entity);

  /**
   * Unsets the language for all Vipps agreements with the given language.
   *
   * @param \Drupal\Core\Language\LanguageInterface $language
   *   The language object.
   */
  public function clearRevisionsLanguage(LanguageInterface $language);

}
