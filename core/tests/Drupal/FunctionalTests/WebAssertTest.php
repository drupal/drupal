<?php

namespace Drupal\FunctionalTests;

use Behat\Mink\Exception\ExpectationException;
use Drupal\Component\Utility\Html;
use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;
use Behat\Mink\Exception\ResponseTextException;
use PHPUnit\Framework\AssertionFailedError;

/**
 * Tests WebAssert functionality.
 *
 * @group browsertestbase
 * @group #slow
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

  /**
   * @covers ::addressEquals
   */
  public function testAddressEquals(): void {
    $this->drupalGet('test-page');
    $this->assertSession()->addressEquals('test-page');
    $this->assertSession()->addressEquals('test-page?');
    $this->assertSession()->addressNotEquals('test-page?a=b');
    $this->assertSession()->addressNotEquals('other-page');

    $this->drupalGet('test-page', ['query' => ['a' => 'b', 'c' => 'd']]);
    $this->assertSession()->addressEquals('test-page');
    $this->assertSession()->addressEquals('test-page?a=b&c=d');
    $this->assertSession()->addressEquals(Url::fromRoute('test_page_test.test_page', [], ['query' => ['a' => 'b', 'c' => 'd']]));
    $this->assertSession()->addressNotEquals('test-page?c=d&a=b');
    $this->assertSession()->addressNotEquals('test-page?a=b');
    $this->assertSession()->addressNotEquals('test-page?a=b&c=d&e=f');
    $this->assertSession()->addressNotEquals('other-page');
    $this->assertSession()->addressNotEquals('other-page?a=b&c=d');

    $this->expectException(ExpectationException::class);
    $this->expectExceptionMessage('Current page is "/test-page?a=b&c=d", but "/test-page?a=b&c=e" expected.');
    $this->assertSession()->addressEquals('test-page?a=b&c=e');
  }

  /**
   * @covers ::addressNotEquals
   */
  public function testAddressNotEqualsException(): void {
    $this->drupalGet('test-page', ['query' => ['a' => 'b', 'c' => 'd']]);
    $this->expectException(ExpectationException::class);
    $this->expectExceptionMessage('Current page is "/test-page?a=b&c=d", but should not be.');
    $this->assertSession()->addressNotEquals('test-page?a=b&c=d');
  }

  /**
   * Tests linkExists() with pipe character (|) in locator.
   *
   * @covers ::linkExists
   */
  public function testPipeCharInLocator() {
    $this->drupalGet('test-pipe-char');
    $this->assertSession()->linkExists('foo|bar|baz');
  }

  /**
   * Tests linkExistsExact() functionality.
   *
   * @covers ::linkExistsExact
   */
  public function testLinkExistsExact() {
    $this->drupalGet('test-pipe-char');
    $this->assertSession()->linkExistsExact('foo|bar|baz');
  }

  /**
   * Tests linkExistsExact() functionality fail.
   *
   * @covers ::linkExistsExact
   */
  public function testInvalidLinkExistsExact() {
    $this->drupalGet('test-pipe-char');
    $this->expectException(ExpectationException::class);
    $this->expectExceptionMessage('Link with label foo|bar not found');
    $this->assertSession()->linkExistsExact('foo|bar');
  }

  /**
   * Tests linkNotExistsExact() functionality.
   *
   * @covers ::linkNotExistsExact
   */
  public function testLinkNotExistsExact() {
    $this->drupalGet('test-pipe-char');
    $this->assertSession()->linkNotExistsExact('foo|bar');
  }

  /**
   * Tests linkNotExistsExact() functionality fail.
   *
   * @covers ::linkNotExistsExact
   */
  public function testInvalidLinkNotExistsExact() {
    $this->drupalGet('test-pipe-char');
    $this->expectException(ExpectationException::class);
    $this->expectExceptionMessage('Link with label foo|bar|baz found');
    $this->assertSession()->linkNotExistsExact('foo|bar|baz');
  }

  /**
   * Tests linkExistsByHref() functionality.
   *
   * @covers ::linkByHrefExists
   */
  public function testLinkByHrefExists(): void {
    $this->drupalGet('test-page');
    // Partial matching.
    $this->assertSession()->linkByHrefExists('/user');
    // Full matching.
    $this->assertSession()->linkByHrefExists('/user/login');
  }

  /**
   * Tests linkExistsByHref() functionality fail.
   *
   * @covers ::linkByHrefExists
   */
  public function testInvalidLinkByHrefExists(): void {
    $this->drupalGet('test-page');
    $this->expectException(ExpectationException::class);
    $this->assertSession()->linkByHrefExists('/foo');
  }

  /**
   * Tests linkByHrefNotExists() functionality.
   *
   * @covers ::linkByHrefNotExists
   */
  public function testLinkByHrefNotExists(): void {
    $this->drupalGet('test-page');
    $this->assertSession()->linkByHrefNotExists('/foo');
  }

  /**
   * Tests LinkByHrefNotExists() functionality fail partial match.
   *
   * @covers ::linkByHrefNotExists
   */
  public function testInvalidLinkByHrefNotExistsPartial(): void {
    $this->drupalGet('test-page');
    $this->expectException(ExpectationException::class);
    $this->assertSession()->linkByHrefNotExists('/user');
  }

  /**
   * Tests LinkByHrefNotExists() functionality fail full match.
   *
   * @covers ::linkByHrefNotExists
   */
  public function testInvalidLinkByHrefNotExistsFull(): void {
    $this->drupalGet('test-page');
    $this->expectException(ExpectationException::class);
    $this->assertSession()->linkByHrefNotExists('/user/login');
  }

  /**
   * Tests linkExistsByHref() functionality.
   *
   * @covers ::linkByHrefExistsExact
   */
  public function testLinkByHrefExistsExact(): void {
    $this->drupalGet('test-page');
    $this->assertSession()->linkByHrefExistsExact('/user/login');
  }

  /**
   * Tests linkByHrefExistsExact() functionality fail.
   *
   * @covers ::linkByHrefExistsExact
   */
  public function testInvalidLinkByHrefExistsExact(): void {
    $this->drupalGet('test-page');
    $this->expectException(ExpectationException::class);
    $this->assertSession()->linkByHrefExistsExact('/foo');
  }

  /**
   * Tests linkByHrefNotExistsExact() functionality.
   *
   * @covers ::linkByHrefNotExistsExact
   */
  public function testLinkByHrefNotExistsExact(): void {
    $this->drupalGet('test-page');
    $this->assertSession()->linkByHrefNotExistsExact('/foo');
  }

  /**
   * Tests linkByHrefNotExistsExact() functionality fail.
   *
   * @covers ::linkByHrefNotExistsExact
   */
  public function testInvalidLinkByHrefNotExistsExact(): void {
    $this->drupalGet('test-page');
    $this->expectException(ExpectationException::class);
    $this->assertSession()->linkByHrefNotExistsExact('/user/login');
  }

  /**
   * Tests legacy text asserts.
   *
   * @covers ::responseContains
   * @covers ::responseNotContains
   */
  public function testTextAsserts() {
    $this->drupalGet('test-encoded');
    $dangerous = 'Bad html <script>alert(123);</script>';
    $sanitized = Html::escape($dangerous);
    $this->assertSession()->responseNotContains($dangerous);
    $this->assertSession()->responseContains($sanitized);
  }

  /**
   * Tests legacy field asserts for button field type.
   *
   * @covers ::buttonExists
   * @covers ::buttonNotExists
   */
  public function testFieldAssertsForButton() {
    $this->drupalGet('test-field-xpath');

    // Verify if the test passes with button ID.
    $this->assertSession()->buttonExists('edit-save');
    // Verify if the test passes with button Value.
    $this->assertSession()->buttonExists('Save');
    // Verify if the test passes with button Name.
    $this->assertSession()->buttonExists('op');

    // Verify if the test passes with button ID.
    $this->assertSession()->buttonNotExists('i-do-not-exist');
    // Verify if the test passes with button Value.
    $this->assertSession()->buttonNotExists('I do not exist');
    // Verify if the test passes with button Name.
    $this->assertSession()->buttonNotExists('no');

    // Test that multiple fields with the same name are validated correctly.
    $this->assertSession()->buttonExists('duplicate_button');
    $this->assertSession()->buttonExists('Duplicate button 1');
    $this->assertSession()->buttonExists('Duplicate button 2');
    $this->assertSession()->buttonNotExists('Rabbit');

    try {
      $this->assertSession()->buttonNotExists('Duplicate button 2');
      $this->fail('The "duplicate_button" field with the value Duplicate button 2 was not found.');
    }
    catch (ExpectationException $e) {
      // Expected exception; just continue testing.
    }
  }

  /**
   * Tests pageContainsNoDuplicateId() functionality.
   *
   * @covers ::pageContainsNoDuplicateId
   */
  public function testPageContainsNoDuplicateId() {
    $assert_session = $this->assertSession();
    $this->drupalGet(Url::fromRoute('test_page_test.page_without_duplicate_ids'));
    $assert_session->pageContainsNoDuplicateId();

    $this->drupalGet(Url::fromRoute('test_page_test.page_with_duplicate_ids'));
    $this->expectException(ExpectationException::class);
    $this->expectExceptionMessage('The page contains a duplicate HTML ID "page-element".');
    $assert_session->pageContainsNoDuplicateId();
  }

  /**
   * Tests assertEscaped() and assertUnescaped().
   *
   * @covers ::assertNoEscaped
   * @covers ::assertEscaped
   */
  public function testEscapingAssertions() {
    $assert = $this->assertSession();

    $this->drupalGet('test-escaped-characters');
    $assert->assertNoEscaped('<div class="escaped">');
    $assert->responseContains('<div class="escaped">');
    $assert->assertEscaped('Escaped: <"\'&>');

    $this->drupalGet('test-escaped-script');
    $assert->assertNoEscaped('<div class="escaped">');
    $assert->responseContains('<div class="escaped">');
    $assert->assertEscaped("<script>alert('XSS');alert(\"XSS\");</script>");

    $this->drupalGet('test-unescaped-script');
    $assert->assertNoEscaped('<div class="unescaped">');
    $assert->responseContains('<div class="unescaped">');
    $assert->responseContains("<script>alert('Marked safe');alert(\"Marked safe\");</script>");
    $assert->assertNoEscaped("<script>alert('Marked safe');alert(\"Marked safe\");</script>");
  }

}
