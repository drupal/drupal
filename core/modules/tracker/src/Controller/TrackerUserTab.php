<?php

namespace Drupal\tracker\Controller;

@trigger_error(__NAMESPACE__ . '\TrackerUserTab is deprecated in drupal:8.8.0 and is removed from drupal:9.0.0. Use \Drupal\tracker\Controller\TrackerController instead. See https://www.drupal.org/node/3030645', E_USER_DEPRECATED);

use Drupal\user\UserInterface;

/**
 * Controller for tracker.user_tab route.
 */
class TrackerUserTab extends TrackerController {

  /**
   * Content callback for the tracker.user_tab route.
   */
  public function getContent(UserInterface $user) {
    return $this->buildContent($user);
  }

}
