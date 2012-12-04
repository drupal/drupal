<?php

/**
 * @file
 * Definition of Drupal\views\Tests\Plugin\DisplayPageTest.
 */

namespace Drupal\views\Tests\Plugin;

use Drupal\views\Tests\Plugin\PluginTestBase;

/**
 * Tests the page display plugin.
 *
 * @see Drupal\views\Plugin\display\Page
 */
class DisplayPageTest extends PluginTestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = array('test_page_display');

  public static function getInfo() {
    return array(
      'name' => 'Display: Page plugin',
      'description' => 'Tests the page display plugin.',
      'group' => 'Views Plugins',
    );
  }

  protected function setUp() {
    parent::setUp();

    $this->enableViewsTestModule();
  }

  /**
   * Checks the behavior of the page for access denied/not found behaviours.
   */
  public function testPageResponses() {
    $view = views_get_view('test_page_display');
    $this->drupalGet('test_page_display_403');
    $this->assertResponse(403);
    $this->drupalGet('test_page_display_404');
    $this->assertResponse(404);
  }

}
