<?php

namespace Drupal\FunctionalTests;

use Drupal\Tests\BrowserTestBase;
use Behat\Mink\Exception\ResponseTextException;
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

  /**
   * @covers ::pageTextMatchesCount
   */
  public function testPageTextMatchesCount() {
    $this->drupalLogin($this->drupalCreateUser());

    // Visit a Drupal page that requires login.
    $this->drupalGet('test-page');
    $this->assertSession()->pageTextMatchesCount(1, '/Test page text\./');

    $this->expectException(AssertionFailedError::class);
    $this->expectExceptionMessage("Failed asserting that the page matches the pattern '/does-not-exist/' 1 time(s), 0 found.");
    $this->assertSession()->pageTextMatchesCount(1, '/does-not-exist/');
  }

  /**
   * @covers ::pageTextContainsOnce
   */
  public function testPageTextContainsOnce() {
    $this->drupalLogin($this->drupalCreateUser());

    // Visit a Drupal page that requires login.
    $this->drupalGet('test-page');
    $this->assertSession()->pageTextContainsOnce('Test page text.');

    $this->expectException(ResponseTextException::class);
    $this->expectExceptionMessage("Failed asserting that the page matches the pattern '/does\\-not\\-exist/ui' 1 time(s), 0 found.");
    $this->assertSession()->pageTextContainsOnce('does-not-exist');
  }

  /**
   * @covers ::elementTextEquals
   */
  public function testElementTextEquals(): void {
    $this->drupalGet('test-page');
    $this->assertSession()->elementTextEquals('xpath', '//h1', 'Test page');

    $this->expectException(AssertionFailedError::class);
    $this->expectExceptionMessage("Failed asserting that the text of the element identified by '//h1' equals 'Foo page'.");
    $this->assertSession()->elementTextEquals('xpath', '//h1', 'Foo page');
  }

}
