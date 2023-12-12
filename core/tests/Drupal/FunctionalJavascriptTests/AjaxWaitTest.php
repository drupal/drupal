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
   * Tests that an unnecessary wait triggers a deprecation error.
   */
  public function testUnnecessaryWait(): void {
    $this->drupalGet('user');

    $this->expectDeprecation("Drupal\FunctionalJavascriptTests\JSWebAssert::assertExpectedAjaxRequest called unnecessarily in a test is deprecated in drupal:10.2.0 and will throw an exception in drupal:11.0.0. See https://www.drupal.org/node/3401201");
    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage('Unable to complete AJAX request.');

    $this->assertSession()->assertWaitOnAjaxRequest(500);
  }

  /**
   * Tests that an untracked XHR triggers a deprecation error.
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
