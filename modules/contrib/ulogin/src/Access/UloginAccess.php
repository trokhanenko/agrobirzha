<?php

namespace Drupal\ulogin\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\Routing\Route;

class UloginAccess implements AccessInterface {

  /**
   * Check if user can access specified group
   * @param \Symfony\Component\Routing\Route $route
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   * @param \Drupal\Core\Session\AccountInterface $account
   * @return \Drupal\Core\Access\AccessResult
   */
  public function access(Route $route, RouteMatchInterface $route_match, AccountInterface $account) {
    $access = AccessResult::neutral();
    $user = $account->getAccount();
//dsm($user);
    if (($route_match->getRawParameter('uid') == $user->id() || $user->hasPermission('administer users')) && $user->isAuthenticated()) {
      return $access->allowed();
    }
    return $access->forbidden();
  }
}