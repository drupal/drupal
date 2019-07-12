<?php

namespace Drupal\FunctionalTests;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests BrowserTestBase legacy functionality.
 *
 * @group browsertestbase
 * @group legacy
 */
class BrowserTestBaseLegacyTest extends BrowserTestBase {

  /**
   * Test ::drupalGetHeaders().
   *
   * @expectedDeprecation Drupal\Tests\BrowserTestBase::drupalGetHeaders() is deprecated in drupal:8.8.0 and is removed from drupal:9.0.0. Use $this->getSession()->getResponseHeaders() instead. See https://www.drupal.org/node/3067207
   */
  public function testDrupalGetHeaders() {
    $this->assertSame(
      $this->getSession()->getResponseHeaders(),
      $this->drupalGetHeaders()
    );
  }

}
