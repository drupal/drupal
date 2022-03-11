<?php

namespace Drupal\js_once_test\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Controller for testing the @drupal/once library integration.
 */
class JsOnceTestController extends ControllerBase {

  /**
   * Provides elements for testing @drupal/once.
   *
   * @return array
   *   The render array.
   */
  public function onceTest() {
    $output = [
      '#attached' => ['library' => ['core/once']],
    ];
    foreach (range(1, 5) as $item) {
      $output['item' . $item] = [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#value' => 'Item ' . $item,
        '#attributes' => [
          'data-drupal-item' => $item,
        ],
      ];
    }
    return $output;
  }

}
