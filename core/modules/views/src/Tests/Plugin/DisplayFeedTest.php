<?php

namespace Drupal\views\Tests\Plugin;

use Drupal\views\Views;

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
  public static $testViews = ['test_display_feed', 'test_attached_disabled', 'test_feed_icon'];

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['block', 'node', 'views'];

  protected function setUp() {
    parent::setUp();

    $this->enableViewsTestModule();

    $admin_user = $this->drupalCreateUser(['administer site configuration']);
    $this->drupalLogin($admin_user);
  }

  /**
   * Tests the rendered output.
   */
  public function testFeedOutput() {
    $this->drupalCreateContentType(['type' => 'page']);

    // Verify a title with HTML entities is properly escaped.
    $node_title = 'This "cool" & "neat" article\'s title';
    $node = $this->drupalCreateNode([
      'title' => $node_title,
      'body' => [0 => [
        'value' => 'A paragraph',
        'format' => filter_default_format(),
      ]],
    ]);

    // Test the site name setting.
    $site_name = $this->randomMachineName();
    $this->config('system.site')->set('name', $site_name)->save();

    $this->drupalGet('test-feed-display.xml');
    $result = $this->xpath('//title');
    $this->assertEqual($result[0], $site_name, 'The site title is used for the feed title.');
    $this->assertEqual($result[1], $node_title, 'Node title with HTML entities displays correctly.');
    // Verify HTML is properly escaped in the description field.
    $this->assertRaw('&lt;p&gt;A paragraph&lt;/p&gt;');

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

    // Test feed display attached to page display with arguments.
    $this->drupalGet('test-feed-icon/' . $node->id());
    $page_url = $this->getUrl();
    $icon_href = $this->cssSelect('a.feed-icon[href *= "test-feed-icon"]')[0]['href'];
    $this->assertEqual($icon_href, $page_url . '/feed', 'The feed icon was found.');
    $link_href = $this->cssSelect('link[type = "application/rss+xml"][href *= "test-feed-icon"]')[0]['href'];
    $this->assertEqual($link_href, $page_url . '/feed', 'The RSS link was found.');
    $feed_link = simplexml_load_string($this->drupalGet($icon_href))->channel->link;
    $this->assertEqual($feed_link, $page_url, 'The channel link was found.');
  }

  /**
   * Tests the rendered output for fields display.
   */
  public function testFeedFieldOutput() {
    $this->drupalCreateContentType(['type' => 'page']);

    // Verify a title with HTML entities is properly escaped.
    $node_title = 'This "cool" & "neat" article\'s title';
    $this->drupalCreateNode([
      'title' => $node_title,
      'body' => [0 => [
        'value' => 'A paragraph',
        'format' => filter_default_format(),
      ]],
    ]);

    $this->drupalGet('test-feed-display-fields.xml');
    $result = $this->xpath('//title/a');
    $this->assertEqual($result[0], $node_title, 'Node title with HTML entities displays correctly.');
    // Verify HTML is properly escaped in the description field.
    $this->assertRaw('&lt;p&gt;A paragraph&lt;/p&gt;');
  }

  /**
   * Tests that nothing is output when the feed display is disabled.
   */
  public function testDisabledFeed() {
    $this->drupalCreateContentType(['type' => 'page']);
    $this->drupalCreateNode();

    // Ensure that the feed_1 display is attached to the page_1 display.
    $view = Views::getView('test_attached_disabled');
    $view->setDisplay('page_1');
    $attached_displays = $view->display_handler->getAttachedDisplays();
    $this->assertTrue(in_array('feed_1', $attached_displays), 'The feed display is attached to the page display.');

    // Check that the rss header is output on the page display.
    $this->drupalGet('/test-attached-disabled');
    $feed_header = $this->xpath('//link[@rel="alternate"]');
    $this->assertEqual($feed_header[0]['type'], 'application/rss+xml', 'The feed link has the type application/rss+xml.');
    $this->assertTrue(strpos($feed_header[0]['href'], 'test-attached-disabled.xml'), 'Page display contains the correct feed URL.');

    // Disable the feed display.
    $view->displayHandlers->get('feed_1')->setOption('enabled', FALSE);
    $view->save();

    // Ensure there is no link rel present on the page.
    $this->drupalGet('/test-attached-disabled');
    $result = $this->xpath('//link[@rel="alternate"]');
    $this->assertTrue(empty($result), 'Page display does not contain a feed header.');

    // Ensure the feed attachment returns 'Not found'.
    $this->drupalGet('/test-attached-disabled.xml');
    $this->assertResponse(404);
  }

}
