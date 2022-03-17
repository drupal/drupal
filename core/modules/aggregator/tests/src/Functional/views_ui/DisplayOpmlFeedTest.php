<?php

namespace Drupal\Tests\aggregator\Functional\views_ui;

use Drupal\Tests\views_ui\Functional\UITestBase;

/**
 * Tests the views UI for feed display plugin.
 *
 * @group aggregator
 * @see \Drupal\views\Plugin\views\display\Feed
 */
class DisplayOpmlFeedTest extends UITestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_aggregator_style_opml'];

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'views_ui',
    'aggregator',
    'aggregator_test_views',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE, $modules = ['aggregator_test_views']): void {
    parent::setUp($import_test_views, $modules);
  }

  /**
   * Tests feed display admin UI.
   */
  public function testFeedUI() {
    // Test the OPML feed.
    foreach (self::$testViews as $view_name) {
      $this->checkFeedViewUi($view_name);
    }
  }

  /**
   * Checks views UI for a specific feed view.
   *
   * @param string $view_name
   *   The view name to check against.
   */
  protected function checkFeedViewUi($view_name) {
    $this->drupalGet('admin/structure/views');
    // Verify that the page lists the $view_name view.
    // Regression test: ViewListBuilder::getDisplayPaths() did not properly
    // check whether a DisplayPluginCollection was returned in iterating over
    // all displays.
    $this->assertSession()->pageTextContains($view_name);

    // Check the attach TO interface.
    $this->drupalGet('admin/structure/views/nojs/display/' . $view_name . '/feed_1/displays');
    // Display labels should be escaped.
    $this->assertSession()->assertEscaped('<em>Page</em>');

    // Load all the options of the checkbox.
    $result = $this->xpath('//div[@id="edit-displays"]/div');
    $options = [];
    foreach ($result as $item) {
      $input_node = $item->find('css', 'input');
      if ($input_node->hasAttribute('value')) {
        $options[] = $input_node->getAttribute('value');
      }
    }

    $this->assertEquals(['default', 'page'], $options, 'Make sure all displays appears as expected.');

    // Post and save this and check the output.
    $this->drupalGet('admin/structure/views/nojs/display/' . $view_name . '/feed_1/displays');
    $this->submitForm(['displays[page]' => 'page'], 'Apply');
    // Options summary should be escaped.
    $this->assertSession()->assertEscaped('<em>Page</em>');
    $this->assertSession()->responseNotContains('<em>Page</em>');

    $this->drupalGet('admin/structure/views/view/' . $view_name . '/edit/feed_1');
    $this->assertSession()->elementTextContains('xpath', '//*[@id="views-feed-1-displays"]', 'Page');

    // Add the default display, so there should now be multiple displays.
    $this->drupalGet('admin/structure/views/nojs/display/' . $view_name . '/feed_1/displays');
    $this->submitForm(['displays[default]' => 'default'], 'Apply');
    $this->drupalGet('admin/structure/views/view/' . $view_name . '/edit/feed_1');
    $this->assertSession()->elementTextContains('xpath', '//*[@id="views-feed-1-displays"]', 'Multiple displays');
  }

}
