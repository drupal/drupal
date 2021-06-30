<?php

namespace Drupal\js_once_test\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Test controller to assert js-cookie library integration.
 */
class JsOnceTestController extends ControllerBase {

  /**
   * Provides buttons to add and remove cookies using JavaScript.
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

  /**
   * Provides buttons to add and remove cookies using JavaScript.
   *
   * @return array
   *   The render array.
   */
  public function onceBcTest() {
    $output = [
      '#attached' => ['library' => ['core/jquery.once']],
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
