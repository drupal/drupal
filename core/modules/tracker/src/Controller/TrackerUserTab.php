<?php

namespace Drupal\tracker\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\user\UserInterface;

/**
 * Controller for tracker.user_tab route.
 */
class TrackerUserTab extends ControllerBase {

  /**
   * Content callback for the tracker.user_tab route.
   */
  public function getContent(UserInterface $user) {
    module_load_include('inc', 'tracker', 'tracker.pages');
    return tracker_page($user);
  }

  /**
   * Title callback for the tracker.user_tab route.
   */
  public function getTitle(UserInterface $user) {
    return $user->getUsername();
  }

}
