<?php

/**
 * @file
 * Definition of Drupal\views\Tests\Plugin\DisplayFeedTest.
 */

namespace Drupal\views\Tests\Plugin;

/**
 * Tests the feed display plugin.
 *
 * @see Drupal\views\Plugin\views\display\Feed
 */
class DisplayFeedTest extends PluginTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('views_ui');

  public static function getInfo() {
    return array(
      'name' => 'Display: Feed plugin',
      'description' => 'Tests the feed display plugin.',
      'group' => 'Views Plugins',
    );
  }

  protected function setUp() {
    parent::setUp();

    $this->enableViewsTestModule();

    $admin_user = $this->drupalCreateUser(array('administer views', 'administer site configuration'));
    $this->drupalLogin($admin_user);
  }

  /**
   * Tests feed display admin ui.
   */
  public function testFeedUI() {
    $this->drupalGet('admin/structure/views');

    // Check the attach TO interface.
    $this->drupalGet('admin/structure/views/nojs/display/test_feed_display/feed/displays');

    // Load all the options of the checkbox.
    $result = $this->xpath('//div[@id="edit-displays"]/div');
    $options = array();
    foreach ($result as $value) {
      foreach ($value->input->attributes() as $attribute => $value) {
        if ($attribute == 'value') {
          $options[] = (string) $value;
        }
      }
    }

    $this->assertEqual($options, array('default', 'page'), 'Make sure all displays appears as expected.');

    // Post and save this and check the output.
    $this->drupalPost('admin/structure/views/nojs/display/test_feed_display/feed/displays', array('displays[page]' => 'page'), t('Apply'));
    $this->drupalGet('admin/structure/views/view/test_feed_display/edit/feed');
    $this->assertFieldByXpath('//*[@id="views-feed-displays"]', 'Page');

    // Add the default display, so there should now be multiple displays.
    $this->drupalPost('admin/structure/views/nojs/display/test_feed_display/feed/displays', array('displays[default]' => 'default'), t('Apply'));
    $this->drupalGet('admin/structure/views/view/test_feed_display/edit/feed');
    $this->assertFieldByXpath('//*[@id="views-feed-displays"]', 'Multiple displays');
  }

}
