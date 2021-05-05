<?php

namespace Drupal\tabbable_shim_test\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * For testing the jQuery :tabbable shim.
 */
class TabbableShimTestController extends ControllerBase {

  /**
   * Provides a page with the tabbingManager library for testing :tabbable.
   *
   * @return array
   *   The render array.
   */
  public function build() {
    return [
      'container' => [
        '#type' => 'container',
        '#attributes' => [
          'id' => 'tabbable-test-container',
        ],
      ],
      '#attached' => ['library' => ['core/jquery.ui']],
    ];
  }

}
