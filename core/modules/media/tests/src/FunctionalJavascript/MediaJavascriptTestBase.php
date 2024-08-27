<?php

declare(strict_types=1);

namespace Drupal\Tests\media\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\Tests\media\Functional\MediaFunctionalTestTrait;
use Drupal\Tests\media\Traits\MediaTypeCreationTrait;

/**
 * Base class for Media functional JavaScript tests.
 */
abstract class MediaJavascriptTestBase extends WebDriverTestBase {

  use MediaFunctionalTestTrait;
  use MediaTypeCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
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

  /**
   * Asserts that a link to a new media item is displayed in the messages area.
   *
   * @return string
   *   The link URL.
   */
  protected function assertLinkToCreatedMedia() {
    $assert_session = $this->assertSession();
    $selector = 'div[aria-label="Status message"] a';

    // Get the canonical media entity URL from the creation message.
    $link = $assert_session->elementExists('css', $selector);
    $assert_session->elementAttributeExists('css', $selector, 'href');

    return $link->getAttribute('href');
  }

}
