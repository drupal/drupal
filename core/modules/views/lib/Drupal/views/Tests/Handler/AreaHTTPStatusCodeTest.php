<?php

/**
 * @file
 * Contains \Drupal\views\Tests\Handler\AreaHTTPStatusCodeTest.
 */

namespace Drupal\views\Tests\Handler;

/**
 * Tests the http_status_code area handler.
 *
 * @see \Drupal\views\Plugin\views\area\HTTPStatusCode
 */
class AreaHTTPStatusCodeTest extends HandlerTestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = array('test_http_status_code');

  public static function getInfo() {
    return array(
      'name' => 'Area: HTTP Status Code',
      'description' => 'Tests the http_status_code area handler.',
      'group' => 'Views Handlers',
    );
  }

  /**
   * Tests the area handler.
   */
  public function testHTTPStatusCodeHandler() {
    $this->drupalGet('test-http-status-code');
    $this->assertResponse(200);

    // Change the HTTP status code to 418.
    $view = views_get_view('test_http_status_code');
    $display = &$view->storage->getDisplay('default');
    $display['display_options']['empty']['http_status_code']['status_code'] = 418;
    $view->save();

    // Test that the HTTP response is "I'm a teapot".
    $this->drupalGet('test-http-status-code');
    $this->assertResponse(418);
  }

}
