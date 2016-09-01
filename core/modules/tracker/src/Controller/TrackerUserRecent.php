<?php

namespace Drupal\tracker\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\user\UserInterface;

/**
 * Controller for tracker.users_recent_content route.
 */
class TrackerUserRecent extends ControllerBase {

  /**
   * Content callback for the tracker.users_recent_content route.
   */
  public function getContent(UserInterface $user) {
    module_load_include('inc', 'tracker', 'tracker.pages');
    return tracker_page($user);
  }

  /**
   * Checks access for the users recent content tracker page.
   *
   * @param \Drupal\user\UserInterface $user
   *   The user being viewed.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account viewing the page.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   The access result.
   */
  public function checkAccess(UserInterface $user, AccountInterface $account) {
    return AccessResult::allowedIf($account->isAuthenticated() && $user->id() == $account->id())
      ->cachePerUser();
  }

}
