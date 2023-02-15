<?php

namespace Drupal\js_errors_test\Controller;

/**
 * Test Controller loading js_errors_test/errors_test library.
 */
class JsErrorsTestController {

  /**
   * Renders page that has js_errors_test/errors_test library attached.
   *
   * @return string[][]
   *   Render array.
   */
  public function jsErrorsTest(): array {
    return [
      '#attached' => ['library' => ['js_errors_test/errors_test']],
    ];
  }

  /**
   * Renders page that has js_errors_test/errors_async_test library attached.
   *
   * @return string[][]
   *   Render array.
   */
  public function jsErrorsAsyncTest(): array {
    return [
      '#attached' => ['library' => ['js_errors_test/errors_async_test']],
    ];
  }

}
