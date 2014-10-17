<?php

/**
 * @file
 * Contains \Drupal\views_ui\Tests\DisplayFeedTest.
 */

namespace Drupal\views_ui\Tests;

/**
 * Tests the UI for feed display plugin.
 *
 * @group views_ui
 * @see \Drupal\views\Plugin\views\display\Feed
 */
class DisplayFeedTest extends UITestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = array('test_display_feed', 'test_style_opml');

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('views_ui', 'aggregator');

  /**
   * Tests feed display admin UI.
   */
  public function testFeedUI() {
    // Test both RSS and OPML feeds.
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
    $this->assertText($view_name);

    // Check the attach TO interface.
    $this->drupalGet('admin/structure/views/nojs/display/' . $view_name . '/feed_1/displays');

    // Load all the options of the checkbox.
    $result = $this->xpath('//div[@id="edit-displays"]/div');
    $options = array();
    foreach ($result as $item) {
      foreach ($item->input->attributes() as $attribute => $value) {
        if ($attribute == 'value') {
          $options[] = (string) $value;
        }
      }
    }

    $this->assertEqual($options, array('default', 'page'), 'Make sure all displays appears as expected.');

    // Post and save this and check the output.
    $this->drupalPostForm('admin/structure/views/nojs/display/' . $view_name . '/feed_1/displays', array('displays[page]' => 'page'), t('Apply'));
    $this->drupalGet('admin/structure/views/view/' . $view_name . '/edit/feed_1');
    $this->assertFieldByXpath('//*[@id="views-feed-1-displays"]', 'Page');

    // Add the default display, so there should now be multiple displays.
    $this->drupalPostForm('admin/structure/views/nojs/display/' . $view_name . '/feed_1/displays', array('displays[default]' => 'default'), t('Apply'));
    $this->drupalGet('admin/structure/views/view/' . $view_name . '/edit/feed_1');
    $this->assertFieldByXpath('//*[@id="views-feed-1-displays"]', 'Multiple displays');
  }

}
