<?php

namespace Drupal\vipps_recurring_payments\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Checks access for Vipps Agreements routes.
 *
 * @see \Drupal\Core\Access\CustomAccessCheck
 */
class VippsAgreementAccessCheck {
  /**
   * Checks access.
   *
   * Confirms that the user either has the 'administer vipps agreements entities'
   * permission, or the 'manage own vipps agreements entities' permission while
   * visiting their own agreements pages.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The current user account.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   The access result.
   */
  public function checkAccess(RouteMatchInterface $route_match, AccountInterface $account) {
    $result = AccessResult::allowedIfHasPermissions($account, [
      'administer vipps agreements entities',
    ]);

    $current_user = $route_match->getParameter('user');
    if ($result->isNeutral() && $current_user->id() == $account->id()) {
      $result = AccessResult::allowedIfHasPermissions($account, [
        'manage own vipps agreements entities',
      ])->cachePerUser();
    }

    return $result;
  }
}
