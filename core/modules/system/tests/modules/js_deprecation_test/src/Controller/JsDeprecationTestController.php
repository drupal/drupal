<?php

namespace Drupal\js_deprecation_test\Controller;

/**
 * Test Controller to show message links.
 */
class JsDeprecationTestController {

  /**
   * Renders page that has js_deprecation_test/deprecation library attached.
   *
   * @return array
   *   Render array.
   */
  public function jsDeprecationTest() {
    return [
      '#attached' => ['library' => ['js_deprecation_test/deprecation_test']],
    ];
  }

}
