<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Test;

use Behat\Mink\Driver\BrowserKitDriver;
use Behat\Mink\Exception\ExpectationException;
use Behat\Mink\Exception\ResponseTextException;
use Behat\Mink\Session;
use Drupal\Component\Utility\Html;
use Drupal\Core\Url;
use Drupal\Tests\UnitTestCase;
use Drupal\Tests\WebAssert;
use PHPUnit\Framework\AssertionFailedError;
use Symfony\Component\BrowserKit\AbstractBrowser;
use Symfony\Component\BrowserKit\Response;

/**
 * Tests WebAssert functionality.
 *
 * @group browsertestbase
 * @coversDefaultClass \Drupal\Tests\WebAssert
 */
class WebAssertTest extends UnitTestCase {

  /**
   * Session mock.
   */
  protected Session $session;

  /**
   * Client mock.
   */
  protected AbstractBrowser $client;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();
    $this->client = new MockClient();
    $driver = new BrowserKitDriver($this->client);
    $this->session = new Session($driver);
  }

  /**
   * Get the mocked session.
   */
  protected function assertSession(): WebAssert {
    return new WebAssert($this->session);
  }

  /**
   * Simulate a page visit and expect a response.
   *
   * @param string $uri
   *   The URI to visit. This is only required if assertions are made about the
   *   URL, otherwise it can be left empty.
   * @param string $content
   *   The expected response content.
   * @param array $responseHeaders
   *   The expected response headers.
   */
  protected function visit(string $uri = '', string $content = '', array $responseHeaders = []): void {
    $this->client->setExpectedResponse(new Response($content, 200, $responseHeaders));
    $this->session->visit($uri);
  }

  /**
   * Tests WebAssert::responseHeaderExists().
   *
   * @covers ::responseHeaderExists
   */
  public function testResponseHeaderExists(): void {
    $this->visit('', '', ['Null-Header' => '']);
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
  public function testResponseHeaderDoesNotExist(): void {
    $this->visit('', '', ['Null-Header' => '']);
    $this->assertSession()->responseHeaderDoesNotExist('does-not-exist');

    $this->expectException(AssertionFailedError::class);
    $this->expectExceptionMessage("Failed asserting that the response does not have a 'Null-Header' header.");
    $this->assertSession()->responseHeaderDoesNotExist('Null-Header');
  }

  /**
   * @covers ::pageTextMatchesCount
   */
  public function testPageTextMatchesCount(): void {
    $this->visit('', 'Test page text. <a href="#">Foo</a>');
    $this->assertSession()->pageTextMatchesCount(1, '/Test page text\./');

    $this->expectException(AssertionFailedError::class);
    $this->expectExceptionMessage("Failed asserting that the page matches the pattern '/does-not-exist/' 1 time(s), 0 found.");
    $this->assertSession()->pageTextMatchesCount(1, '/does-not-exist/');
  }

  /**
   * @covers ::pageTextContainsOnce
   */
  public function testPageTextContainsOnce(): void {
    $this->visit('', 'Test page text. <a href="#">Foo</a>');
    $this->assertSession()->pageTextContainsOnce('Test page text.');

    $this->expectException(ResponseTextException::class);
    $this->expectExceptionMessage("Failed asserting that the page matches the pattern '/does\\-not\\-exist/ui' 1 time(s), 0 found.");
    $this->assertSession()->pageTextContainsOnce('does-not-exist');
  }

  /**
   * @covers ::elementTextEquals
   */
  public function testElementTextEquals(): void {
    $this->visit('', '<h1>Test page</h1>');
    $this->assertSession()->elementTextEquals('xpath', '//h1', 'Test page');

    $this->expectException(AssertionFailedError::class);
    $this->expectExceptionMessage("Failed asserting that the text of the element identified by '//h1' equals 'Foo page'.");
    $this->assertSession()->elementTextEquals('xpath', '//h1', 'Foo page');
  }

  /**
   * @covers ::addressEquals
   */
  public function testAddressEquals(): void {
    $this->visit('http://localhost/test-page');
    $this->assertSession()->addressEquals('test-page');
    $this->assertSession()->addressEquals('test-page?');
    $this->assertSession()->addressNotEquals('test-page?a=b');
    $this->assertSession()->addressNotEquals('other-page');

    $this->visit('http://localhost/test-page?a=b&c=d');
    $this->assertSession()->addressEquals('test-page');
    $this->assertSession()->addressEquals('test-page?a=b&c=d');
    $url = $this->createMock(Url::class);
    $url->expects($this->any())
      ->method('setAbsolute')
      ->willReturn($url);
    $url->expects($this->any())
      ->method('toString')
      ->willReturn('test-page?a=b&c=d');
    $this->assertSession()->addressEquals($url);
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
    $this->visit('http://localhost/test-page?a=b&c=d');

    $this->expectException(ExpectationException::class);
    $this->expectExceptionMessage('Current page is "/test-page?a=b&c=d", but should not be.');
    $this->assertSession()->addressNotEquals('test-page?a=b&c=d');
  }

  /**
   * Tests linkExists() with pipe character (|) in locator.
   *
   * @covers ::linkExists
   */
  public function testPipeCharInLocator(): void {
    $this->visit('', '<a href="http://example.com">foo|bar|baz</a>');
    $this->assertSession()->linkExists('foo|bar|baz');
    $this->addToAssertionCount(1);
  }

  /**
   * Tests linkExistsExact() functionality.
   *
   * @covers ::linkExistsExact
   */
  public function testLinkExistsExact(): void {
    $this->visit('', '<a href="http://example.com">foo|bar|baz</a>');
    $this->assertSession()->linkExistsExact('foo|bar|baz');
    $this->addToAssertionCount(1);
  }

  /**
   * Tests linkExistsExact() functionality fail.
   *
   * @covers ::linkExistsExact
   */
  public function testInvalidLinkExistsExact(): void {
    $this->visit('', '<a href="http://example.com">foo|bar|baz</a>');
    $this->expectException(ExpectationException::class);
    $this->expectExceptionMessage('Link with label foo|bar not found');
    $this->assertSession()->linkExistsExact('foo|bar');
  }

  /**
   * Tests linkNotExistsExact() functionality.
   *
   * @covers ::linkNotExistsExact
   */
  public function testLinkNotExistsExact(): void {
    $this->visit('', '<a href="http://example.com">foo|bar|baz</a>');
    $this->assertSession()->linkNotExistsExact('foo|bar');
    $this->addToAssertionCount(1);
  }

  /**
   * Tests linkNotExistsExact() functionality fail.
   *
   * @covers ::linkNotExistsExact
   */
  public function testInvalidLinkNotExistsExact(): void {
    $this->visit('', '<a href="http://example.com">foo|bar|baz</a>');
    $this->expectException(ExpectationException::class);
    $this->expectExceptionMessage('Link with label foo|bar|baz found');
    $this->assertSession()->linkNotExistsExact('foo|bar|baz');
    $this->addToAssertionCount(1);
  }

  /**
   * Tests linkExistsByHref() functionality.
   *
   * @covers ::linkByHrefExists
   */
  public function testLinkByHrefExists(): void {
    $this->visit('', '<a href="/user/login">Log in</a><a href="/user/register">Register</a>');
    // Partial matching.
    $this->assertSession()->linkByHrefExists('/user');
    // Full matching.
    $this->assertSession()->linkByHrefExists('/user/login');
    $this->addToAssertionCount(1);
  }

  /**
   * Tests linkExistsByHref() functionality fail.
   *
   * @covers ::linkByHrefExists
   */
  public function testInvalidLinkByHrefExists(): void {
    $this->visit('', '<a href="/user/login">Log in</a><a href="/user/register">Register</a>');
    $this->expectException(ExpectationException::class);
    $this->assertSession()->linkByHrefExists('/foo');
    $this->addToAssertionCount(1);
  }

  /**
   * Tests linkByHrefNotExists() functionality.
   *
   * @covers ::linkByHrefNotExists
   */
  public function testLinkByHrefNotExists(): void {
    $this->visit('', '<a href="/user/login">Log in</a><a href="/user/register">Register</a>');
    $this->assertSession()->linkByHrefNotExists('/foo');
    $this->addToAssertionCount(1);
  }

  /**
   * Tests LinkByHrefNotExists() functionality fail partial match.
   *
   * @covers ::linkByHrefNotExists
   */
  public function testInvalidLinkByHrefNotExistsPartial(): void {
    $this->visit('', '<a href="/user/login">Log in</a><a href="/user/register">Register</a>');
    $this->expectException(ExpectationException::class);
    $this->assertSession()->linkByHrefNotExists('/user');
    $this->addToAssertionCount(1);
  }

  /**
   * Tests LinkByHrefNotExists() functionality fail full match.
   *
   * @covers ::linkByHrefNotExists
   */
  public function testInvalidLinkByHrefNotExistsFull(): void {
    $this->visit('', '<a href="/user/login">Log in</a><a href="/user/register">Register</a>');
    $this->expectException(ExpectationException::class);
    $this->assertSession()->linkByHrefNotExists('/user/login');
  }

  /**
   * Tests linkExistsByHref() functionality.
   *
   * @covers ::linkByHrefExistsExact
   */
  public function testLinkByHrefExistsExact(): void {
    $this->visit('', '<a href="/user/login">Log in</a><a href="/user/register">Register</a>');
    $this->assertSession()->linkByHrefExistsExact('/user/login');
    $this->addToAssertionCount(1);
  }

  /**
   * Tests linkByHrefExistsExact() functionality fail.
   *
   * @covers ::linkByHrefExistsExact
   */
  public function testInvalidLinkByHrefExistsExact(): void {
    $this->visit('', '<a href="/user/login">Log in</a><a href="/user/register">Register</a>');
    $this->expectException(ExpectationException::class);
    $this->assertSession()->linkByHrefExistsExact('/foo');
  }

  /**
   * Tests linkByHrefNotExistsExact() functionality.
   *
   * @covers ::linkByHrefNotExistsExact
   */
  public function testLinkByHrefNotExistsExact(): void {
    $this->visit('', '<a href="/user/login">Log in</a><a href="/user/register">Register</a>');
    $this->assertSession()->linkByHrefNotExistsExact('/foo');
    $this->addToAssertionCount(1);
  }

  /**
   * Tests linkByHrefNotExistsExact() functionality fail.
   *
   * @covers ::linkByHrefNotExistsExact
   */
  public function testInvalidLinkByHrefNotExistsExact(): void {
    $this->visit('', '<a href="/user/login">Log in</a><a href="/user/register">Register</a>');
    $this->expectException(ExpectationException::class);
    $this->assertSession()->linkByHrefNotExistsExact('/user/login');
  }

  /**
   * Tests legacy text asserts.
   *
   * @covers ::responseContains
   * @covers ::responseNotContains
   */
  public function testTextAsserts(): void {
    $this->visit('', 'Bad html &lt;script&gt;alert(123);&lt;/script&gt;');
    $dangerous = 'Bad html <script>alert(123);</script>';
    $sanitized = Html::escape($dangerous);
    $this->assertSession()->responseNotContains($dangerous);
    $this->assertSession()->responseContains($sanitized);
    $this->addToAssertionCount(2);
  }

  /**
   * Tests legacy field asserts for button field type.
   *
   * @covers ::buttonExists
   * @covers ::buttonNotExists
   */
  public function testFieldAssertsForButton(): void {
    $this->visit('', <<<HTML
      <input type="submit" id="edit-save" value="Save" name="op">
      <input type="submit" id="duplicate_button" value="Duplicate button 1" name="duplicate_button">
      <input type="submit" id="duplicate_button" value="Duplicate button 2" name="duplicate_button">
HTML);

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
    catch (ExpectationException) {
      // Expected exception; just continue testing.
    }
    $this->addToAssertionCount(11);
  }

  /**
   * Tests pageContainsNoDuplicateId() functionality.
   *
   * @covers ::pageContainsNoDuplicateId
   */
  public function testPageContainsNoDuplicateId(): void {
    $this->visit('', <<<HTML
      <h1 id="page-element-title">Hello</h1>
      <h2 id="page-element-description">World</h2>
HTML);
    $assert_session = $this->assertSession();
    $assert_session->pageContainsNoDuplicateId();

    $this->visit('', <<<HTML
      <h1 id="page-element">Hello</h1>
      <h2 id="page-element">World</h2>
HTML);
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
  public function testEscapingAssertions(): void {
    $assert = $this->assertSession();

    $this->visit('', '<div class="escaped">Escaped: &lt;&quot;&#039;&amp;&gt;</div>');
    $assert->assertNoEscaped('<div class="escaped">');
    $assert->responseContains('<div class="escaped">');
    $assert->assertEscaped('Escaped: <"\'&>');

    $this->visit('', '<div class="escaped">&lt;script&gt;alert(&#039;XSS&#039;);alert(&quot;XSS&quot;);&lt;/script&gt;</div>');
    $assert->assertNoEscaped('<div class="escaped">');
    $assert->responseContains('<div class="escaped">');
    $assert->assertEscaped("<script>alert('XSS');alert(\"XSS\");</script>");

    $this->visit('', <<<HTML
        <div class="unescaped"><script>alert('Marked safe');alert("Marked safe");</script></div>
HTML);
    $this->session->visit('');
    $assert->assertNoEscaped('<div class="unescaped">');
    $assert->responseContains('<div class="unescaped">');
    $assert->responseContains("<script>alert('Marked safe');alert(\"Marked safe\");</script>");
    $assert->assertNoEscaped("<script>alert('Marked safe');alert(\"Marked safe\");</script>");
    $this->addToAssertionCount(10);
  }

}

/**
 * A mock client.
 */
class MockClient extends AbstractBrowser {

  public function setExpectedResponse(Response $response): void {
    $this->response = $response;
  }

  protected function doRequest(object $request): object {
    return $this->response ?? new Response();
  }

}
