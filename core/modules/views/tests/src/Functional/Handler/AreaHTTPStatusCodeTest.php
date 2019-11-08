<?php

namespace Drupal\Tests\views\Functional\Handler;

use Drupal\Tests\views\Functional\ViewTestBase;
use Drupal\views\Views;

/**
 * Tests the http_status_code area handler.
 *
 * @group views
 * @see \Drupal\views\Plugin\views\area\HTTPStatusCode
 */
class AreaHTTPStatusCodeTest extends ViewTestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_http_status_code'];

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['node'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests the area handler.
   */
  public function testHTTPStatusCodeHandler() {
    $this->drupalGet('test-http-status-code');
    $this->assertResponse(200);

    // Change the HTTP status code to 418.
    $view = Views::getView('test_http_status_code');
    $display = &$view->storage->getDisplay('default');
    $display['display_options']['empty']['http_status_code']['status_code'] = 418;
    $view->save();

    // Test that the HTTP response is "I'm a teapot".
    $this->drupalGet('test-http-status-code');
    $this->assertResponse(418);
  }

}
