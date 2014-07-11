<?php

/**
 * @file
 * Contains \Drupal\views\Tests\Plugin\DisplayTest.
 */

namespace Drupal\views\Tests\Plugin;

use Drupal\views\Views;
use Drupal\views_test_data\Plugin\views\display\DisplayTest as DisplayTestPlugin;

/**
 * Tests the basic display plugin.
 *
 * @group views
 */
class DisplayTest extends PluginTestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = array('test_filter_groups', 'test_get_attach_displays', 'test_view', 'test_display_more', 'test_display_invalid', 'test_display_empty');

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('views_ui', 'node', 'block');

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
   * @see \Drupal\views_test_data\Plugin\views\display\DisplayTest
   */
  public function testDisplayPlugin() {
    $view = Views::getView('test_view');

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
      'position' => 1,
      'provider' => 'views_test_data',
    );
    $this->assertEqual($displays['display_test_1'], $options);

    // Add another one to ensure that position is counted up.
    $view->storage->addDisplay('display_test');
    $displays = $view->storage->get('display');
    $options = array(
      'display_options' => array(),
      'display_plugin' => 'display_test',
      'id' => 'display_test_2',
      'display_title' => 'Display test 2',
      'position' => 2,
      'provider' => 'views_test_data',
    );
    $this->assertEqual($displays['display_test_2'], $options);

    // Move the second display before the first one in order to test custom
    // sorting.
    $displays['display_test_1']['position'] = 2;
    $displays['display_test_2']['position'] = 1;
    $view->storage->set('display', $displays);
    $view->save();

    $view->setDisplay('display_test_1');

    $this->assertTrue($view->display_handler instanceof DisplayTestPlugin, 'The correct display handler instance is on the view object.');

    // Check the test option.
    $this->assertIdentical($view->display_handler->getOption('test_option'), '');

    $output = $view->preview();
    $output = drupal_render($output);

    $this->assertTrue(strpos($output, '<h1></h1>') !== FALSE, 'An empty value for test_option found in output.');

    // Change this option and check the title of out output.
    $view->display_handler->overrideOption('test_option', 'Test option title');
    $view->save();

    $output = $view->preview();
    $output = drupal_render($output);

    // Test we have our custom <h1> tag in the output of the view.
    $this->assertTrue(strpos($output, '<h1>Test option title</h1>') !== FALSE, 'The test_option value found in display output title.');

    // Test that the display category/summary is in the UI.
    $this->drupalGet('admin/structure/views/view/test_view/edit/display_test_1');
    $this->assertText('Display test settings');
    // Ensure that the order is as expected.
    $result = $this->xpath('//ul[@id="views-display-menu-tabs"]/li');
    $this->assertEqual((string) $result[0]->a, 'Display test 2');
    $this->assertEqual((string) $result[1]->a, 'Display test');

    $this->clickLink('Test option title');

    $this->randomString = $this->randomString();
    $this->drupalPostForm(NULL, array('test_option' => $this->randomString), t('Apply'));

    // Check the new value has been saved by checking the UI summary text.
    $this->drupalGet('admin/structure/views/view/test_view/edit/display_test_1');
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
    $view = Views::getView('test_filter_groups');
    $view->initDisplay();

    // mark is as overridden, yes FALSE, means overridden.
    $view->displayHandlers->get('page')->setOverride('filter_groups', FALSE);
    $this->assertFalse($view->displayHandlers->get('page')->isDefaulted('filter_groups'), "Make sure that 'filter_groups' is marked as overridden.");
    $this->assertFalse($view->displayHandlers->get('page')->isDefaulted('filters'), "Make sure that 'filters'' is marked as overridden.");
  }

  /**
   * Tests the getAttachedDisplays method.
   */
  public function testGetAttachedDisplays() {
    $view = Views::getView('test_get_attach_displays');

    // Both the feed_1 and the feed_2 display are attached to the page display.
    $view->setDisplay('page_1');
    $this->assertEqual($view->display_handler->getAttachedDisplays(), array('feed_1', 'feed_2'));

    $view->setDisplay('feed_1');
    $this->assertEqual($view->display_handler->getAttachedDisplays(), array());
  }

  /**
   * Tests the readmore functionality.
   */
  public function testReadMore() {
    $expected_more_text = 'custom more text';

    $view = Views::getView('test_display_more');
    $this->executeView($view);

    $output = $view->preview();
    $output = drupal_render($output);

    $this->drupalSetContent($output);
    $result = $this->xpath('//a[@class=:class]', array(':class' => 'more-link'));
    $this->assertEqual($result[0]->attributes()->href, url('test_display_more'), 'The right more link is shown.');
    $this->assertEqual(trim($result[0][0]), $expected_more_text, 'The right link text is shown.');

    // Test the renderMoreLink method directly. This could be directly unit
    // tested.
    $more_link = $view->display_handler->renderMoreLink();
    $more_link = drupal_render($more_link);
    $this->drupalSetContent($more_link);
    $result = $this->xpath('//a[@class=:class]', array(':class' => 'more-link'));
    $this->assertEqual($result[0]->attributes()->href, url('test_display_more'), 'The right more link is shown.');
    $this->assertEqual(trim($result[0][0]), $expected_more_text, 'The right link text is shown.');

    // Test the useMoreText method directly. This could be directly unit
    // tested.
    $more_text = $view->display_handler->useMoreText();
    $this->assertEqual($more_text, $expected_more_text, 'The right more text is chosen.');

    $view = Views::getView('test_display_more');
    $view->setDisplay();
    $view->display_handler->setOption('use_more', 0);
    $this->executeView($view);
    $output = $view->preview();
    $output = drupal_render($output);
    $this->drupalSetContent($output);
    $result = $this->xpath('//a[@class=:class]', array(':class' => 'more-link'));
    $this->assertTrue(empty($result), 'The more link is not shown.');

    $view = Views::getView('test_display_more');
    $view->setDisplay();
    $view->display_handler->setOption('use_more', 0);
    $view->display_handler->setOption('use_more_always', 0);
    $view->display_handler->setOption('pager', array(
      'type' => 'some',
      'options' => array(
        'items_per_page' => 1,
        'offset' => 0,
      ),
    ));
    $this->executeView($view);
    $output = $view->preview();
    $output = drupal_render($output);
    $this->drupalSetContent($output);
    $result = $this->xpath('//a[@class=:class]', array(':class' => 'more-link'));
    $this->assertTrue(empty($result), 'The more link is not shown when view has more records.');

    // Test the default value of use_more_always.
    $view = entity_create('view')->getExecutable();
    $this->assertTrue($view->getDisplay()->getOption('use_more_always'), 'Always display the more link by default.');
  }

  /**
   * Tests invalid display plugins.
   */
  public function testInvalidDisplayPlugins() {
    $this->drupalGet('test_display_invalid');
    $this->assertResponse(200);

    // Change the page plugin id to an invalid one. Bypass the entity system
    // so no menu rebuild was executed (so the path is still available).
    $config = \Drupal::config('views.view.test_display_invalid');
    $config->set('display.page_1.display_plugin', 'invalid');
    $config->save();

    $this->drupalGet('test_display_invalid');
    $this->assertResponse(200);
    $this->assertText('The "invalid" plugin does not exist.');

    // Rebuild the router, and ensure that the path is not accessible anymore.
    views_invalidate_cache();
    \Drupal::service('router.builder')->rebuildIfNeeded();

    $this->drupalGet('test_display_invalid');
    $this->assertResponse(404);

    // Change the display plugin ID back to the correct ID.
    $config = \Drupal::config('views.view.test_display_invalid');
    $config->set('display.page_1.display_plugin', 'page');
    $config->save();

    // Place the block display.
    $block = $this->drupalPlaceBlock('views_block:test_display_invalid-block_1', array(), array('title' => 'Invalid display'));

    $this->drupalGet('<front>');
    $this->assertResponse(200);
    $this->assertBlockAppears($block);

    // Change the block plugin ID to an invalid one.
    $config = \Drupal::config('views.view.test_display_invalid');
    $config->set('display.block_1.display_plugin', 'invalid');
    $config->save();

    // Test the page is still displayed, the block not present, and has the
    // plugin warning message.
    $this->drupalGet('<front>');
    $this->assertResponse(200);
    $this->assertText('The "invalid" plugin does not exist.');
    $this->assertNoBlockAppears($block);
  }

  /**
   * Tests the outputIsEmpty method on the display.
   */
  public function testOutputIsEmpty() {
    $view = Views::getView('test_display_empty');
    $this->executeView($view);
    $this->assertTrue(count($view->result) > 0, 'Ensure the result of the view is not empty.');
    $this->assertFalse($view->display_handler->outputIsEmpty(), 'Ensure the view output is marked as not empty.');
    $view->destroy();

    // Add a filter, so the view result is empty.
    $view->setDisplay('default');
    $item = array(
      'table' => 'views_test_data',
      'field' => 'id',
      'id' => 'id',
      'value' => array('value' => 7297)
    );
    $view->setHandler('default', 'filter', 'id', $item);
    $this->executeView($view);
    $this->assertFalse(count($view->result), 'Ensure the result of the view is empty.');
    $this->assertFalse($view->display_handler->outputIsEmpty(), 'Ensure the view output is marked as not empty, because the empty text still appears.');
    $view->destroy();

    // Remove the empty area, but mark the header area to still appear.
    $view->removeHandler('default', 'empty', 'area');
    $item = $view->getHandler('default', 'header', 'area');
    $item['empty'] = TRUE;
    $view->setHandler('default', 'header', 'area', $item);
    $this->executeView($view);
    $this->assertFalse(count($view->result), 'Ensure the result of the view is empty.');
    $this->assertFalse($view->display_handler->outputIsEmpty(), 'Ensure the view output is marked as not empty, because the header text still appears.');
    $view->destroy();

    // Hide the header on empty results.
    $item = $view->getHandler('default', 'header', 'area');
    $item['empty'] = FALSE;
    $view->setHandler('default', 'header', 'area', $item);
    $this->executeView($view);
    $this->assertFalse(count($view->result), 'Ensure the result of the view is empty.');
    $this->assertTrue($view->display_handler->outputIsEmpty(), 'Ensure the view output is marked as empty.');
  }

}
