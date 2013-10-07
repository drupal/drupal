<?php

/**
 * @file
 * Contains \Drupal\tracker\Controller\TrackerPage.
 */

namespace Drupal\tracker\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Controller for tracker.page route.
 */
class TrackerPage extends ControllerBase {

  /**
   * Content callback for the tracker.page route.
   */
  public function getContent() {
    module_load_include('inc', 'tracker', 'tracker.pages');
    return tracker_page();
  }
}
