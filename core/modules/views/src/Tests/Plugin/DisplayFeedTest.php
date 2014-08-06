<?php

/**
 * @file
 * Contains \Drupal\views\Tests\Plugin\DisplayFeedTest.
 */

namespace Drupal\views\Tests\Plugin;

/**
 * Tests the feed display plugin.
 *
 * @group views
 * @see \Drupal\views\Plugin\views\display\Feed
 */
class DisplayFeedTest extends PluginTestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = array('test_display_feed');

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('block', 'node', 'views');

  protected function setUp() {
    parent::setUp();

    $this->enableViewsTestModule();

    $admin_user = $this->drupalCreateUser(array('administer site configuration'));
    $this->drupalLogin($admin_user);
  }

  /**
   * Tests the rendered output.
   */
  public function testFeedOutput() {
    $this->drupalCreateNode();

    // Test the site name setting.
    $site_name = $this->randomMachineName();
    $this->container->get('config.factory')->get('system.site')->set('name', $site_name)->save();

    $this->drupalGet('test-feed-display.xml');
    $result = $this->xpath('//title');
    $this->assertEqual($result[0], $site_name, 'The site title is used for the feed title.');

    $view = $this->container->get('entity.manager')->getStorage('view')->load('test_display_feed');
    $display = &$view->getDisplay('feed_1');
    $display['display_options']['sitename_title'] = 0;
    $view->save();

    $this->drupalGet('test-feed-display.xml');
    $result = $this->xpath('//title');
    $this->assertEqual($result[0], 'test_display_feed', 'The display title is used for the feed title.');
  }

}
