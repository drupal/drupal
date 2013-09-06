<?php

/**
 * @file
 * Contains \Drupal\overlay\Tests\OverlayRenderTest.
 */

namespace Drupal\overlay\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests the rendering of a page in an overlay.
 */
class OverlayRenderTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('test_page_test', 'overlay');

  public static function getInfo() {
    return array(
      'name' => 'Overlay child page rendering',
      'description' => 'Tests the rendering of a page in an overlay.',
      'group' => 'Overlay',
    );
  }

  /**
   * Tests the title of a page in an overlay.
   */
  public function testOverlayTitle() {
    $account = $this->drupalCreateUser(array('access overlay'));
    $this->drupalLogin($account);

    $this->drupalGet('admin/test-render-title', array('query' => array('render' => 'overlay')));
    $result = $this->xpath('//h1[@id = "overlay-title"]');
    $this->assertEqual((string) $result[0], 'Foo');
  }

}
