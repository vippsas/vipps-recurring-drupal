<?php

namespace Drupal\vipps_recurring_payments;

use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
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
class VippsAgreementsStorage extends SqlContentEntityStorage implements VippsAgreementsStorageInterface {

  /**
   * {@inheritdoc}
   */
  public function revisionIds(VippsAgreementsInterface $entity) {
    return $this->database->query(
      'SELECT vid FROM {vipps_agreements_revision} WHERE id=:id ORDER BY vid',
      [':id' => $entity->id()]
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function userRevisionIds(AccountInterface $account) {
    return $this->database->query(
      'SELECT vid FROM {vipps_agreements_field_revision} WHERE uid = :uid ORDER BY vid',
      [':uid' => $account->id()]
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function countDefaultLanguageRevisions(VippsAgreementsInterface $entity) {
    return $this->database->query('SELECT COUNT(*) FROM {vipps_agreements_field_revision} WHERE id = :id AND default_langcode = 1', [':id' => $entity->id()])
      ->fetchField();
  }

  /**
   * {@inheritdoc}
   */
  public function clearRevisionsLanguage(LanguageInterface $language) {
    return $this->database->update('vipps_agreements_revision')
      ->fields(['langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED])
      ->condition('langcode', $language->getId())
      ->execute();
  }

}
