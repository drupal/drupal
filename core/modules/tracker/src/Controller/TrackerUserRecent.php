<?php

namespace Drupal\tracker\Controller;

use Drupal\Core\Controller\ControllerBase;
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
}
