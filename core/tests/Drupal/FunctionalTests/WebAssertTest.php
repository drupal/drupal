<?php

namespace Drupal\FunctionalTests;

use Drupal\Core\Url;
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
    'dblog',
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
   * @covers ::urlQueryStringEquals
   */
  public function testUrlQueryStringEquals() {
    // Insert 300 log messages.
    $logger = $this->container->get('logger.factory')->get('pager_test');
    for ($i = 0; $i < 300; $i++) {
      $logger->debug($this->randomString());
    }

    // Get to the db log report.
    $this->drupalLogin($this->drupalCreateUser([
      'access site reports',
    ]));
    $this->drupalGet('admin/reports/dblog');
    $this->assertSession()->urlQueryStringEquals('admin/reports/dblog');
    $this->assertSession()->urlQueryStringEquals('?');
    $this->assertSession()->urlQueryStringEquals('');
    $this->assertSession()->urlQueryStringEquals(Url::fromRoute('dblog.overview'));

    // Go to the second page, we expect the querystring to change to '?page=1'.
    $this->drupalGet('admin/reports/dblog', ['query' => ['page' => 1]]);
    $this->assertSession()->urlQueryStringEquals('admin/reports/dblog?page=1');
    $this->assertSession()->urlQueryStringEquals('?page=1');
    $this->assertSession()->urlQueryStringEquals(Url::fromRoute('dblog.overview', [], ['query' => ['page' => 1]]));

    $this->expectException(AssertionFailedError::class);
    $this->expectExceptionMessage("Querystring should be equal to '', found 'page=1'.");
    $this->assertSession()->urlQueryStringEquals('admin/reports/dblog');
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
