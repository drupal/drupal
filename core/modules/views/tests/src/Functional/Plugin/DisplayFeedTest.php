<?php

namespace Drupal\Tests\views\Functional\Plugin;

use Drupal\Core\Url;
use Drupal\Tests\Traits\Core\PathAliasTestTrait;
use Drupal\Tests\views\Functional\ViewTestBase;
use Drupal\views\Views;

/**
 * Tests the feed display plugin.
 *
 * @group views
 * @see \Drupal\views\Plugin\views\display\Feed
 */
class DisplayFeedTest extends ViewTestBase {

  use PathAliasTestTrait;

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
  protected static $modules = ['block', 'node', 'views', 'views_test_rss'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'classy';

  protected function setUp($import_test_views = TRUE, $modules = ['views_test_config']): void {
    parent::setUp($import_test_views, $modules);

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
      'body' => [
        0 => [
          'value' => 'A paragraph',
          'format' => filter_default_format(),
        ],
      ],
    ]);
    $node_link = $node->toUrl()->setAbsolute()->toString();

    // Test the site name setting.
    $site_name = $this->randomMachineName();
    $frontpage_url = Url::fromRoute('<front>')->setAbsolute()->toString();
    $this->config('system.site')->set('name', $site_name)->save();

    $this->drupalGet('test-feed-display.xml');
    $this->assertEquals($site_name, $this->getSession()->getDriver()->getText('//channel/title'));
    $this->assertEquals($frontpage_url, $this->getSession()->getDriver()->getText('//channel/link'));
    $this->assertEquals('Copyright 2019 Dries Buytaert', $this->getSession()->getDriver()->getText('//channel/copyright'));
    $this->assertEquals($node_title, $this->getSession()->getDriver()->getText('//item/title'));
    $this->assertEquals($node_link, $this->getSession()->getDriver()->getText('//item/link'));
    // Verify HTML is properly escaped in the description field.
    $this->assertSession()->responseContains('&lt;p&gt;A paragraph&lt;/p&gt;');

    $view = $this->container->get('entity_type.manager')->getStorage('view')->load('test_display_feed');
    $display = &$view->getDisplay('feed_1');
    $display['display_options']['sitename_title'] = 0;
    $view->save();

    $this->drupalGet('test-feed-display.xml');
    $this->assertEquals('test_display_feed', $this->getSession()->getDriver()->getText('//channel/title'));

    // Add a block display and attach the feed.
    $view->getExecutable()->newDisplay('block', NULL, 'test');
    $display = &$view->getDisplay('feed_1');
    $display['display_options']['displays']['test'] = 'test';
    $view->save();
    // Test the feed display adds a feed icon to the block display.
    $this->drupalPlaceBlock('views_block:test_display_feed-test');
    $this->drupalGet('<front>');
    $feed_icon = $this->cssSelect('div.view-id-test_display_feed a.feed-icon');
    $this->assertStringContainsString('test-feed-display.xml', $feed_icon[0]->getAttribute('href'), 'The feed icon was found.');

    // Test feed display attached to page display with arguments.
    $this->drupalGet('test-feed-icon/' . $node->id());
    $page_url = $this->getUrl();
    $icon_href = $this->cssSelect('a.feed-icon[href *= "test-feed-icon"]')[0]->getAttribute('href');
    $this->assertEquals($page_url . '/feed', $icon_href, 'The feed icon was found.');
    $link_href = $this->cssSelect('link[type = "application/rss+xml"][href *= "test-feed-icon"]')[0]->getAttribute('href');
    $this->assertEquals($page_url . '/feed', $link_href, 'The RSS link was found.');
    $this->drupalGet($icon_href);
    $this->assertEquals($frontpage_url, $this->getSession()->getDriver()->getText('//channel/link'));
  }

  /**
   * Tests the rendered output for fields display.
   */
  public function testFeedFieldOutput() {
    $this->drupalCreateContentType(['type' => 'page']);

    // Verify a title with HTML entities is properly escaped.
    $node_title = 'This "cool" & "neat" article\'s title';
    $node = $this->drupalCreateNode([
      'title' => $node_title,
      'body' => [
        0 => [
          'value' => 'A paragraph',
          'format' => filter_default_format(),
        ],
      ],
    ]);

    // Create an alias to verify that outbound processing runs on the link and
    // ensure that the node actually contains that.
    $this->createPathAlias('/node/' . $node->id(), '/the-article-alias');

    $node_link = $node->toUrl()->setAbsolute()->toString();
    $this->assertStringContainsString('/the-article-alias', $node_link);

    $this->drupalGet('test-feed-display-fields.xml');
    $this->assertEquals($node_title, $this->getSession()->getDriver()->getText('//item/title'));
    $this->assertEquals($node_link, $this->getSession()->getDriver()->getText('//item/link'));
    // Verify HTML is properly escaped in the description field.
    $this->assertSession()->responseContains('&lt;p&gt;A paragraph&lt;/p&gt;');

    // Change the display to use the nid field, which is rewriting output as
    // 'node/{{ nid }}' and make sure things are still working.
    $view = Views::getView('test_display_feed');
    $display = &$view->storage->getDisplay('feed_2');
    $display['display_options']['row']['options']['link_field'] = 'nid';
    $view->save();
    $this->drupalGet('test-feed-display-fields.xml');
    $this->assertEquals($node_title, $this->getSession()->getDriver()->getText('//item/title'));
    $this->assertEquals($node_link, $this->getSession()->getDriver()->getText('//item/link'));
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
    $this->assertContains('feed_1', $attached_displays, 'The feed display is attached to the page display.');

    // Check that the rss header is output on the page display.
    $this->drupalGet('/test-attached-disabled');
    $this->assertSession()->elementAttributeContains('xpath', '//link[@rel="alternate"]', 'type', 'application/rss+xml');
    $this->assertSession()->elementAttributeContains('xpath', '//link[@rel="alternate"]', 'href', 'test-attached-disabled.xml');

    // Disable the feed display.
    $view->displayHandlers->get('feed_1')->setOption('enabled', FALSE);
    $view->save();

    // Ensure there is no link rel present on the page.
    $this->drupalGet('/test-attached-disabled');
    $this->assertSession()->elementNotExists('xpath', '//link[@rel="alternate"]');

    // Ensure the feed attachment returns 'Not found'.
    $this->drupalGet('/test-attached-disabled.xml');
    $this->assertSession()->statusCodeEquals(404);
  }

  /**
   * Tests that the feed display works when the linked display is disabled.
   */
  public function testDisabledLinkedDisplay() {
    $view = Views::getView('test_attached_disabled');
    $view->setDisplay();
    // Disable the page and link the feed to the page.
    $view->displayHandlers->get('feed_1')->setOption('link_display', 'page_1');
    $view->displayHandlers->get('page_1')->setOption('enabled', FALSE);
    $view->save();

    \Drupal::service('router.builder')->rebuild();

    $this->drupalGet('test-attached-disabled');
    $this->assertSession()->statusCodeEquals(404);
    // Ensure the feed can still be reached.
    $this->drupalGet('test-attached-disabled.xml');
    $this->assertSession()->statusCodeEquals(200);
  }

}
