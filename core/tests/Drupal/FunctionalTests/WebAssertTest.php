<?php

namespace Drupal\FunctionalTests;

use Drupal\Tests\BrowserTestBase;
use PHPUnit\Framework\AssertionFailedError;

/**
 * Tests WebAssert functionality.
 *
 * @group browsertestbase
 * @coversDefaultClass \Drupal\Tests\WebAssert
 */
class WebAssertTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'test_page_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests WebAssert::responseHeaderExists().
   *
   * @covers ::responseHeaderExists
   */
  public function testResponseHeaderExists() {
    $this->drupalGet('test-null-header');
    $this->assertSession()->responseHeaderExists('Null-Header');

    $this->expectException(AssertionFailedError::class);
    $this->expectExceptionMessage("Failed asserting that the response has a 'does-not-exist' header.");
    $this->assertSession()->responseHeaderExists('does-not-exist');
  }

  /**
   * Tests WebAssert::responseHeaderDoesNotExist().
   *
   * @covers ::responseHeaderDoesNotExist
   */
  public function testResponseHeaderDoesNotExist() {
    $this->drupalGet('test-null-header');
    $this->assertSession()->responseHeaderDoesNotExist('does-not-exist');

    $this->expectException(AssertionFailedError::class);
    $this->expectExceptionMessage("Failed asserting that the response does not have a 'Null-Header' header.");
    $this->assertSession()->responseHeaderDoesNotExist('Null-Header');
  }

}
