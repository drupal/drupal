<?php

declare(strict_types=1);

namespace Drupal\FunctionalJavascriptTests;

/**
 * Tests that unnecessary or untracked XHRs will cause a test failure.
 *
 * @group javascript
 * @group legacy
 */
class AjaxWaitTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'starterkit_theme';

  /**
   * Tests that an unnecessary wait triggers an error.
   */
  public function testUnnecessaryWait(): void {
    $this->drupalGet('user');

    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage('There are no AJAX requests to wait for.');

    $this->assertSession()->assertWaitOnAjaxRequest(500);
  }

  /**
   * Tests that an untracked XHR triggers an error.
   */
  public function testUntrackedXhr(): void {
    $this->getSession()->executeScript(<<<JS
let xhr = new XMLHttpRequest();
xhr.open('GET', '/foobar');
xhr.send();
JS);
    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage('0 XHR requests through jQuery, but 1 observed in the browser — this requires js_testing_ajax_request_test.js to be updated.');

    $this->assertSession()->assertExpectedAjaxRequest(1, 500);
  }

}
