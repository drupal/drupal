<?php

namespace Drupal\content_moderation_test_local_task\Controller;

/**
 * A test controller.
 */
class TestLocalTaskController {

  /**
   * A method which does not hint the node parameter to avoid upcasting.
   */
  public function methodWithoutUpcastNode($node) {
    return ['#markup' => 'It works!'];
  }

}
