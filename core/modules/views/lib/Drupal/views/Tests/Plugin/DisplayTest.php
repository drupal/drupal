<?php

/**
 * @file
 * Definition of Drupal\views\Tests\Plugin\DisplayTest.
 */

namespace Drupal\views\Tests\Plugin;

use Drupal\views_test_data\Plugin\views\display\DisplayTest as DisplayTestPlugin;

/**
 * Tests the basic display plugin.
 */
class DisplayTest extends PluginTestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = array('test_filter_groups', 'test_get_attach_displays');

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('views_ui');

  public static function getInfo() {
    return array(
      'name' => 'Display',
      'description' => 'Tests the basic display plugin.',
      'group' => 'Views Plugins',
    );
  }

  public function setUp() {
    parent::setUp();

    $this->enableViewsTestModule();

    $this->adminUser = $this->drupalCreateUser(array('administer views'));
    $this->drupalLogin($this->adminUser);

    // Create 10 nodes.
    for ($i = 0; $i <= 10; $i++) {
      $this->drupalCreateNode(array('promote' => TRUE));
    }
  }

  /**
   * Tests the display test plugin.
   *
   * @see Drupal\views_test_data\Plugin\views\display\DisplayTest
   */
  function testDisplayPlugin() {
    $view = views_get_view('frontpage');

    // Add a new 'display_test' display and test it's there.
    $view->storage->addDisplay('display_test');
    $displays = $view->storage->get('display');

    $this->assertTrue(isset($displays['display_test_1']), 'Added display has been assigned to "display_test_1"');

    // Check the the display options are like expected.
    $options = array(
      'display_options' => array(),
      'display_plugin' => 'display_test',
      'id' => 'display_test_1',
      'display_title' => 'Display test',
      'position' => NULL,
    );
    $this->assertEqual($displays['display_test_1'], $options);

    $view->setDisplay('display_test_1');

    $this->assertTrue($view->display_handler instanceof DisplayTestPlugin, 'The correct display handler instance is on the view object.');

    // Check the test option.
    $this->assertIdentical($view->display_handler->getOption('test_option'), '');

    $output = $view->preview();

    $this->assertTrue(strpos($output, '<h1></h1>') !== FALSE, 'An empty value for test_option found in output.');

    // Change this option and check the title of out output.
    $view->display_handler->overrideOption('test_option', 'Test option title');

    $view->save();
    $output = $view->preview();

    // Test we have our custom <h1> tag in the output of the view.
    $this->assertTrue(strpos($output, '<h1>Test option title</h1>') !== FALSE, 'The test_option value found in display output title.');

    // Test that the display category/summary is in the UI.
    $this->drupalGet('admin/structure/views/view/frontpage/edit/display_test_1');
    $this->assertText('Display test settings');

    $this->clickLink('Test option title');

    $this->randomString = $this->randomString();
    $this->drupalPost(NULL, array('test_option' => $this->randomString), t('Apply'));

    // Check the new value has been saved by checking the UI summary text.
    $this->drupalGet('admin/structure/views/view/frontpage/edit/display_test_1');
    $this->assertRaw($this->randomString);

    // Test the enable/disable status of a display.
    $view->display_handler->setOption('enabled', FALSE);
    $this->assertFalse($view->display_handler->isEnabled(), 'Make sure that isEnabled returns FALSE on a disabled display.');
    $view->display_handler->setOption('enabled', TRUE);
    $this->assertTrue($view->display_handler->isEnabled(), 'Make sure that isEnabled returns TRUE on a disabled display.');
  }

  /**
   * Tests the overriding of filter_groups.
   */
  public function testFilterGroupsOverriding() {
    $view = views_get_view('test_filter_groups');
    $view->initDisplay();

    // mark is as overridden, yes FALSE, means overridden.
    $view->displayHandlers->get('page')->setOverride('filter_groups', FALSE);
    $this->assertFalse($view->displayHandlers->get('page')->isDefaulted('filter_groups'), "Take sure that 'filter_groups' is marked as overridden.");
    $this->assertFalse($view->displayHandlers->get('page')->isDefaulted('filters'), "Take sure that 'filters'' is marked as overridden.");
  }

  /**
   * Tests the getAttachedDisplays method.
   */
  public function testGetAttachedDisplays() {
    $view = views_get_view('test_get_attach_displays');

    // Both the feed_1 and the feed_2 display are attached to the page display.
    $view->setDisplay('page_1');
    $this->assertEqual($view->display_handler->getAttachedDisplays(), array('feed_1', 'feed_2'));

    $view->setDisplay('feed_1');
    $this->assertEqual($view->display_handler->getAttachedDisplays(), array());
  }

}
