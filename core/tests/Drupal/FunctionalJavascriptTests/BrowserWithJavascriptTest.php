<?php

namespace Drupal\FunctionalJavascriptTests;

use Behat\Mink\Driver\GoutteDriver;

/**
 * Tests if we can execute JavaScript in the browser.
 *
 * @group javascript
 */
class BrowserWithJavascriptTest extends WebDriverTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['test_page_test'];

  public function testJavascript() {
    $this->drupalGet('<front>');
    $session = $this->getSession();

    $session->resizeWindow(400, 300);
    $javascript = <<<JS
    (function(){
        var w = window,
        d = document,
        e = d.documentElement,
        g = d.getElementsByTagName('body')[0],
        x = w.innerWidth || e.clientWidth || g.clientWidth,
        y = w.innerHeight || e.clientHeight|| g.clientHeight;
        return x == 400 && y == 300;
    }());
JS;
    $this->assertJsCondition($javascript);
  }

  public function testAssertJsCondition() {
    $this->drupalGet('<front>');
    $session = $this->getSession();

    $session->resizeWindow(500, 300);
    $javascript = <<<JS
    (function(){
        var w = window,
        d = document,
        e = d.documentElement,
        g = d.getElementsByTagName('body')[0],
        x = w.innerWidth || e.clientWidth || g.clientWidth,
        y = w.innerHeight || e.clientHeight|| g.clientHeight;
        return x == 400 && y == 300;
    }());
JS;

    // We expected the following assertion to fail because the window has been
    // re-sized to have a width of 500 not 400.
    $this->expectException(\PHPUnit_Framework_AssertionFailedError::class);
    $this->assertJsCondition($javascript, 100);
  }

  /**
   * Tests creating screenshots.
   */
  public function testCreateScreenshot() {
    $this->drupalGet('<front>');
    $this->createScreenshot('public://screenshot.jpg');
    $this->assertFileExists('public://screenshot.jpg');
  }

  /**
   * Tests assertEscaped() and assertUnescaped().
   *
   * @see \Drupal\Tests\WebAssert::assertNoEscaped()
   * @see \Drupal\Tests\WebAssert::assertEscaped()
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

    $this->drupalGetWithAlert('test-unescaped-script');
    $assert->assertNoEscaped('<div class="unescaped">');
    $assert->responseContains('<div class="unescaped">');
    $assert->responseContains("<script>alert('Marked safe');alert(\"Marked safe\");</script>");
    $assert->assertNoEscaped("<script>alert('Marked safe');alert(\"Marked safe\");</script>");
  }

  /**
   * Retrieves a Drupal path or an absolute path.
   *
   * @param string|\Drupal\Core\Url $path
   *   Drupal path or URL to load into Mink controlled browser.
   * @param array $options
   *   (optional) Options to be forwarded to the url generator.
   * @param string[] $headers
   *   An array containing additional HTTP request headers, the array keys are
   *   the header names and the array values the header values. This is useful
   *   to set for example the "Accept-Language" header for requesting the page
   *   in a different language. Note that not all headers are supported, for
   *   example the "Accept" header is always overridden by the browser. For
   *   testing REST APIs it is recommended to obtain a separate HTTP client
   *   using getHttpClient() and performing requests that way.
   *
   * @return string
   *   The retrieved HTML string, also available as $this->getRawContent()
   *
   * @see \Drupal\Tests\BrowserTestBase::getHttpClient()
   */
  protected function drupalGetWithAlert($path, array $options = [], array $headers = []) {
    $options['absolute'] = TRUE;
    $url = $this->buildUrl($path, $options);

    $session = $this->getSession();

    $this->prepareRequest();
    foreach ($headers as $header_name => $header_value) {
      $session->setRequestHeader($header_name, $header_value);
    }

    $session->visit($url);

    // There are 2 alerts to accept before we can get the content of the page.
    $session->getDriver()->getWebdriverSession()->accept_alert();
    $session->getDriver()->getWebdriverSession()->accept_alert();

    $out = $session->getPage()->getContent();

    // Ensure that any changes to variables in the other thread are picked up.
    $this->refreshVariables();

    // Replace original page output with new output from redirected page(s).
    if ($new = $this->checkForMetaRefresh()) {
      $out = $new;
      // We are finished with all meta refresh redirects, so reset the counter.
      $this->metaRefreshCount = 0;
    }

    // Log only for JavascriptTestBase tests because for Goutte we log with
    // ::getResponseLogHandler.
    if ($this->htmlOutputEnabled && !($this->getSession()->getDriver() instanceof GoutteDriver)) {
      $html_output = 'GET request to: ' . $url .
        '<hr />Ending URL: ' . $this->getSession()->getCurrentUrl();
      $html_output .= '<hr />' . $out;
      $html_output .= $this->getHtmlOutputHeaders();
      $this->htmlOutput($html_output);
    }

    return $out;
  }

}
