<?php
/**
 * @file
 * Contains \Drupal\views\Tests\Plugin\DisplayUnitTest.
 */

namespace Drupal\views\Tests\Plugin;

use Drupal\views\Tests\ViewUnitTestBase;
use Drupal\views\ViewExecutable;

/**
 * Drupal unit tests for the DisplayPluginBase class.
 */
class DisplayUnitTest extends ViewUnitTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('block', 'node');

  /**
   * Views plugin types to test.
   *
   * @var array
   */
  protected $pluginTypes = array(
    'access',
    'cache',
    'query',
    'exposed_form',
    'pager',
    'style',
    'row',
  );

  /**
   * Views handler types to test.
   *
   * @var array
   */
  protected $handlerTypes = array(
    'fields',
    'sorts',
  );

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = array('test_display_defaults');

  public static function getInfo() {
    return array(
      'name' => 'Display unit tests',
      'description' => 'Unit tests for the DisplayPluginBase class.',
      'group' => 'Views Plugins'
    );
  }

  /**
   * Tests the default display options.
   */
  public function testDefaultOptions() {
    // Save the view.
    $view = views_get_view('test_display_defaults');
    $view->mergeDefaults();
    $view->save();

    // Reload to get saved storage values.
    $view = views_get_view('test_display_defaults');
    $view->initDisplay();
    $display_data = $view->storage->get('display');

    foreach ($view->displayHandlers as $id => $display) {
      // Test the view plugin options against the storage.
      foreach ($this->pluginTypes as $type) {
        $options = $display->getOption($type);
        $this->assertIdentical($display_data[$id]['display_options'][$type]['options'], $options['options']);
      }
      // Test the view handler options against the storage.
      foreach ($this->handlerTypes as $type) {
        $options = $display->getOption($type);
        $this->assertIdentical($display_data[$id]['display_options'][$type], $options);
      }
    }

  }

}
