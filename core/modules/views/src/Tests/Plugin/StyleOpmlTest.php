<?php

namespace Drupal\views\Tests\Plugin;

/**
 * Tests the OPML feed style plugin.
 *
 * @group views
 * @see \Drupal\views\Plugin\views\style\Opml
 */
class StyleOpmlTest extends PluginTestBase {

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
  protected function setUp() {
    parent::setUp();

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
    $feed = $this->container->get('entity.manager')
      ->getStorage('aggregator_feed')
      ->create($values);
    $feed->save();

    $this->drupalGet('test-feed-opml-style');
    $outline = $this->xpath('//outline[1]');
    $this->assertEqual($outline[0]['type'], 'rss', 'The correct type attribute is used for rss OPML.');
    $this->assertEqual($outline[0]['text'], $feed->label(), 'The correct text attribute is used for rss OPML.');
    $this->assertEqual($outline[0]['xmlurl'], $feed->getUrl(), 'The correct xmlUrl attribute is used for rss OPML.');

    $view = $this->container->get('entity.manager')
      ->getStorage('view')
      ->load('test_style_opml');
    $display = &$view->getDisplay('feed_1');
    $display['display_options']['row']['options']['type_field'] = 'link';
    $display['display_options']['row']['options']['url_field'] = 'url';
    $view->save();

    $this->drupalGet('test-feed-opml-style');
    $outline = $this->xpath('//outline[1]');
    $this->assertEqual($outline[0]['type'], 'link', 'The correct type attribute is used for link OPML.');
    $this->assertEqual($outline[0]['text'], $feed->label(), 'The correct text attribute is used for link OPML.');
    $this->assertEqual($outline[0]['url'], $feed->getUrl(), 'The correct URL attribute is used for link OPML.');
    // xmlUrl should not be present when type is link.
    $this->assertNull($outline[0]['xmlUrl'], 'The xmlUrl attribute is not used for link OPML.');
  }

}
