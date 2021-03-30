<?php

namespace Drupal\tabbable_shim_test\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * For testing the jQuery :tabbable shim as used in a dialog.
 */
class TabbableShimDialogIntegrationTestController extends ControllerBase {

  /**
   * Provides a page with the jQuery UI dialog library for testing .
   *
   * @return array
   *   The render array.
   */
  public function build() {
    return [
      'container' => [
        '#type' => 'container',
        '#attributes' => [
          'id' => 'tabbable-dialog-test-container',
        ],
      ],
      '#attached' => [
        'library' => ['core/jquery.ui.dialog'],
      ],
    ];
  }

}
