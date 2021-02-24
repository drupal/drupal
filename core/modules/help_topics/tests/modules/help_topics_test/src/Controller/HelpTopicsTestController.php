<?php

namespace Drupal\help_topics_test\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Returns the response for help_topics_test routes.
 */
class HelpTopicsTestController extends ControllerBase {

  /**
   * Displays a dummy page for testing.
   *
   * @param int $int_param
   *   Required parameter (ignored).
   *
   * @return array
   *   Render array for the dummy page.
   */
  public function testPage(int $int_param) {
    $build = [
      '#markup' => 'You have reached the help topics test routing page.',
    ];
    return $build;
  }

}
