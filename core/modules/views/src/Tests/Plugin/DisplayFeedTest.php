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
    $this->drupalCreateContentType(['type' => 'page']);
    $this->drupalCreateNode();

    // Test the site name setting.
    $site_name = $this->randomMachineName();
    $this->config('system.site')->set('name', $site_name)->save();

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

    // Add a block display and attach the feed.
    $view->getExecutable()->newDisplay('block', NULL, 'test');
    $display = &$view->getDisplay('feed_1');
    $display['display_options']['displays']['test'] = 'test';
    $view->save();
    // Test the feed display adds a feed icon to the block display.
    $this->drupalPlaceBlock('views_block:test_display_feed-test');
    $this->drupalGet('<front>');
    $feed_icon = $this->cssSelect('div.view-id-test_display_feed a.feed-icon');
    $this->assertTrue(strpos($feed_icon[0]['href'], 'test-feed-display.xml'), 'The feed icon was found.');
  }

}
