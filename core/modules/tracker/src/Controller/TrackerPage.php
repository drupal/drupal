<?php

namespace Drupal\tracker\Controller;

@trigger_error(__NAMESPACE__ . '\TrackerPage is deprecated in drupal:8.8.0 and is removed from drupal:9.0.0. Use \Drupal\tracker\Controller\TrackerController instead. See https://www.drupal.org/node/3030645', E_USER_DEPRECATED);

/**
 * Controller for tracker.page route.
 */
class TrackerPage extends TrackerController {

  /**
   * Content callback for the tracker.page route.
   */
  public function getContent() {
    return $this->buildContent();
  }

}
