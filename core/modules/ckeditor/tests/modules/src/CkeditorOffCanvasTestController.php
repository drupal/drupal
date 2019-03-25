<?php

namespace Drupal\ckeditor_test;

use Drupal\Core\Url;

/**
 * Provides controller for testing CKEditor in off-canvas dialogs.
 */
class CkeditorOffCanvasTestController {

  /**
   * Returns a link that can open a node add form in an off-canvas dialog.
   *
   * @return array
   *   A render array.
   */
  public function testOffCanvas() {
    $build['link'] = [
      '#type' => 'link',
      '#title' => 'Add Node',
      '#url' => Url::fromRoute('node.add', ['node_type' => 'page']),
      '#attributes' => [
        'class' => ['use-ajax'],
        'data-dialog-type' => 'dialog',
        'data-dialog-renderer' => 'off_canvas',
      ],
    ];
    $build['#attached']['library'][] = 'core/drupal.dialog.off_canvas';
    return $build;
  }

}
