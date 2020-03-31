<?php

namespace Drupal\vipps_recurring_payments;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Periodic charges entity.
 *
 * @see \Drupal\vipps_recurring_payments\Entity\PeriodicCharges.
 */
class PeriodicChargesAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\vipps_recurring_payments\Entity\PeriodicChargesInterface $entity */

    switch ($operation) {

      case 'view':

        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished periodic charges entities');
        }


        return AccessResult::allowedIfHasPermission($account, 'view published periodic charges entities');

      case 'update':

        return AccessResult::allowedIfHasPermission($account, 'edit periodic charges entities');

      case 'delete':

        return AccessResult::allowedIfHasPermission($account, 'delete periodic charges entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add periodic charges entities');
  }


}
