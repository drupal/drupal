<?php

namespace Drupal\Tests\views\Kernel\Plugin;

use Drupal\views\Views;
use Drupal\Tests\views\Kernel\ViewsKernelTestBase;
use Drupal\views\Plugin\views\style\StylePluginBase;
use Drupal\views\Plugin\views\access\AccessPluginBase;
use Drupal\views\Plugin\views\exposed_form\ExposedFormPluginInterface;
use Drupal\views\Plugin\views\pager\PagerPluginBase;
use Drupal\views\Plugin\views\query\QueryPluginBase;
use Drupal\views\Plugin\views\cache\CachePluginBase;
use Drupal\views\Plugin\views\row\RowPluginBase;

/**
 * Drupal unit tests for the DisplayPluginBase class.
 *
 * @group views
 */
class DisplayKernelTest extends ViewsKernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('block', 'node', 'field', 'user');

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

  /**
   * Tests the default display options.
   */
  public function testDefaultOptions() {
    // Save the view.
    $view = Views::getView('test_display_defaults');
    $view->mergeDefaults();
    $view->save();

    // Reload to get saved storage values.
    $view = Views::getView('test_display_defaults');
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

  /**
   * Tests the \Drupal\views\Plugin\views\display\DisplayPluginBase::getPlugin() method.
   */
  public function testGetPlugin() {
    $view = Views::getView('test_display_defaults');
    $view->initDisplay();
    $display_handler = $view->display_handler;

    $this->assertTrue($display_handler->getPlugin('access') instanceof AccessPluginBase, 'An access plugin instance was returned.');
    $this->assertTrue($display_handler->getPlugin('cache') instanceof CachePluginBase, 'A cache plugin instance was returned.');
    $this->assertTrue($display_handler->getPlugin('exposed_form') instanceof ExposedFormPluginInterface, 'An exposed_form plugin instance was returned.');
    $this->assertTrue($display_handler->getPlugin('pager') instanceof PagerPluginBase, 'A pager plugin instance was returned.');
    $this->assertTrue($display_handler->getPlugin('query') instanceof QueryPluginBase, 'A query plugin instance was returned.');
    $this->assertTrue($display_handler->getPlugin('row') instanceof RowPluginBase, 'A row plugin instance was returned.');
    $this->assertTrue($display_handler->getPlugin('style') instanceof StylePluginBase, 'A style plugin instance was returned.');
    // Test that nothing is returned when an invalid type is requested.
    $this->assertNull($display_handler->getPlugin('invalid'), 'NULL was returned for an invalid instance');
    // Test that nothing was returned for an instance with no 'type' in options.
    unset($display_handler->options['access']);
    $this->assertNull($display_handler->getPlugin('access'), 'NULL was returned for a plugin type with no "type" option');

    // Get a plugin twice, and make sure the same instance is returned.
    $view->destroy();
    $view->initDisplay();
    $first = spl_object_hash($display_handler->getPlugin('style'));
    $second = spl_object_hash($display_handler->getPlugin('style'));
    $this->assertIdentical($first, $second, 'The same plugin instance was returned.');
  }

}
