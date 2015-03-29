<?php

/**
 * @file
 * Contains \Drupal\tracker\Controller\TrackerUserTab.
 */

namespace Drupal\tracker\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\user\UserInterface;
use Drupal\Component\Utility\SafeMarkup;

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
    return SafeMarkup::checkPlain($user->getUsername());
  }
}
