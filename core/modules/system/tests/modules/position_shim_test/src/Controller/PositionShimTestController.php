<?php

namespace Drupal\position_shim_test\Controller;

use Drupal\Core\Controller\ControllerBase;

class PositionShimTestController extends ControllerBase {

  /**
   * Provides a page with the jQuery UI position library for testing.
   *
   * @return array
   *   The render array.
   */
  public function build() {
    return [
      'reference1' => [
        '#type' => 'container',
        '#attributes' => [
          'id' => 'position-reference-1',
        ],
      ],
      '#attached' => [
        'library' => [
          'core/jquery.ui.position',
          'position_shim_test/position.shim.test',
        ],
      ],
    ];
  }

}
