<?php

namespace Drupal\Tests\views\Functional\Plugin;

use Drupal\Tests\views\Functional\ViewTestBase;

/**
 * Tests the OPML feed style plugin.
 *
 * @group views
 * @see \Drupal\views\Plugin\views\style\Opml
 */
class StyleOpmlTest extends ViewTestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_style_opml'];

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['aggregator'];

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE) {
    parent::setUp($import_test_views);

    $this->enableViewsTestModule();

    $admin_user = $this->drupalCreateUser(['administer news feeds']);
    $this->drupalLogin($admin_user);
  }

  /**
   * Tests the rendered output.
   */
  public function testOpmlOutput() {
    // Create a test feed.
    $values = [
      'title' => $this->randomMachineName(10),
      'url' => 'http://example.com/rss.xml',
      'refresh' => '900',
    ];
    $feed = $this->container->get('entity_type.manager')
      ->getStorage('aggregator_feed')
      ->create($values);
    $feed->save();

    $this->drupalGet('test-feed-opml-style');
    $outline = $this->getSession()->getDriver()->find('//outline[1]')[0];
    $this->assertEquals('rss', $outline->getAttribute('type'));
    $this->assertEquals($feed->label(), $outline->getAttribute('text'));
    $this->assertEquals($feed->getUrl(), $outline->getAttribute('xmlUrl'));

    $view = $this->container->get('entity_type.manager')
      ->getStorage('view')
      ->load('test_style_opml');
    $display = &$view->getDisplay('feed_1');
    $display['display_options']['row']['options']['type_field'] = 'link';
    $display['display_options']['row']['options']['url_field'] = 'url';
    $view->save();

    $this->drupalGet('test-feed-opml-style');
    $outline = $this->getSession()->getDriver()->find('//outline[1]')[0];
    $this->assertEquals('link', $outline->getAttribute('type'));
    $this->assertEquals($feed->label(), $outline->getAttribute('text'));
    $this->assertEquals($feed->getUrl(), $outline->getAttribute('url'));
    // xmlUrl should not be present when type is link.
    $this->assertNull($outline->getAttribute('xmlUrl'));
  }

}
