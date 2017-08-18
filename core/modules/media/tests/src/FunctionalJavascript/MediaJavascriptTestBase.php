<?php

namespace Drupal\Tests\media\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\JavascriptTestBase;
use Drupal\Tests\media\Functional\MediaFunctionalTestCreateMediaTypeTrait;
use Drupal\Tests\media\Functional\MediaFunctionalTestTrait;

/**
 * Base class for Media functional JavaScript tests.
 */
abstract class MediaJavascriptTestBase extends JavascriptTestBase {

  use MediaFunctionalTestTrait;
  use MediaFunctionalTestCreateMediaTypeTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'system',
    'node',
    'field_ui',
    'views_ui',
    'media',
    'media_test_source',
  ];

  /**
   * Waits and asserts that a given element is visible.
   *
   * @param string $selector
   *   The CSS selector.
   * @param int $timeout
   *   (Optional) Timeout in milliseconds, defaults to 1000.
   * @param string $message
   *   (Optional) Message to pass to assertJsCondition().
   */
  protected function waitUntilVisible($selector, $timeout = 1000, $message = '') {
    $condition = "jQuery('" . $selector . ":visible').length > 0";
    $this->assertJsCondition($condition, $timeout, $message);
  }

}
