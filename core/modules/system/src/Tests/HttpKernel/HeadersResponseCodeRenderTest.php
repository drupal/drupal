<?php

/**
 * @file
 * Contains \Drupal\system\Tests\HttpKernel\HeadersResponseCodeRenderTest.
 */

namespace Drupal\system\Tests\HttpKernel;

use Drupal\simpletest\WebTestBase;

/**
 * Tests rendering headers and response codes.
 *
 * @group Routing
 */
class HeadersResponseCodeRenderTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('httpkernel_test');

  /**
   * Tests the rendering of an array-based header and response code.
   */
  public function testHeaderResponseCode() {
    $this->drupalGet('/httpkernel-test/teapot');
    $this->assertResponse(418);
    $this->assertHeader('X-Test-Teapot', 'Teapot Mode Active');
    $this->assertHeader('X-Test-Teapot-Replace', 'Teapot replaced');
    $this->assertHeader('X-Test-Teapot-No-Replace', 'This value is not replaced,This one is added');
  }

}
