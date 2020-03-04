<?php

namespace Drupal\vipps_recurring_payments;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Vipps agreements entity.
 *
 * @see \Drupal\vipps_recurring_payments\Entity\VippsAgreements.
 */
class VippsAgreementsAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\vipps_recurring_payments\Entity\VippsAgreementsInterface $entity */

    switch ($operation) {

      case 'view':

        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished vipps agreements entities');
        }


        return AccessResult::allowedIfHasPermission($account, 'view published vipps agreements entities');

      case 'update':

        return AccessResult::allowedIfHasPermission($account, 'edit vipps agreements entities');

      case 'delete':

        return AccessResult::allowedIfHasPermission($account, 'delete vipps agreements entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add vipps agreements entities');
  }


}
