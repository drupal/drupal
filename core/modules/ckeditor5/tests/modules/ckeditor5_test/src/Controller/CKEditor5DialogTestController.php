<?php

declare(strict_types = 1);

namespace Drupal\ckeditor5_test\Controller;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Url;

/**
 * Provides controller for testing CKEditor in off-canvas dialogs.
 */
class CKEditor5DialogTestController {

  /**
   * Returns a link that can open a node add form in an modal dialog.
   *
   * @return array
   *   A render array.
   */
  public function testDialog() {
    $build['link'] = [
      '#type' => 'link',
      '#title' => 'Add Node',
      '#url' => Url::fromRoute('node.add', ['node_type' => 'page']),
      '#attributes' => [
        'class' => ['use-ajax'],
        'data-dialog-type' => 'dialog',
        'data-dialog-options' => Json::encode([
          'width' => 700,
          'modal' => TRUE,
          'autoResize' => TRUE,
        ]),
      ],
    ];
    $build['#attached']['library'][] = 'core/drupal.dialog.ajax';
    return $build;
  }

}
