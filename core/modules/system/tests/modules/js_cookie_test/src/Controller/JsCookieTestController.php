<?php

namespace Drupal\js_cookie_test\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Test controller to assert js-cookie library integration.
 */
class JsCookieTestController extends ControllerBase {

  /**
   * Provides buttons to add and remove cookies using JavaScript.
   *
   * @return array
   *   The render array.
   */
  public function jqueryCookieShimTest() {
    return [
      'add' => [
        '#type' => 'button',
        '#value' => $this->t('Add cookie'),
        '#attributes' => [
          'class' => ['js_cookie_test_add_button'],
        ],
      ],
      'add-raw' => [
        '#type' => 'button',
        '#value' => $this->t('Add raw cookie'),
        '#attributes' => [
          'class' => ['js_cookie_test_add_raw_button'],
        ],
      ],
      'add-json' => [
        '#type' => 'button',
        '#value' => $this->t('Add JSON cookie'),
        '#attributes' => [
          'class' => ['js_cookie_test_add_json_button'],
        ],
      ],
      'add-json-string' => [
        '#type' => 'button',
        '#value' => $this->t('Add JSON cookie without json option'),
        '#attributes' => [
          'class' => ['js_cookie_test_add_json_string_button'],
        ],
      ],
      'remove' => [
        '#type' => 'button',
        '#value' => $this->t('Remove cookie'),
        '#attributes' => [
          'class' => ['js_cookie_test_remove_button'],
        ],
      ],
      '#attached' => ['library' => ['js_cookie_test/with_shim_test']],
    ];
  }

}
