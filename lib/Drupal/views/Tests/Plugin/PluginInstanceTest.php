<?php

/**
 * @file
 * Definition of Drupal\views\Tests\Plugin\PluginInstanceTest.
 */

namespace Drupal\views\Tests\Plugin;

use Drupal\simpletest\UnitTestBase;
use Drupal\views\Plugin\Type\ViewsPluginManager;

/**
 * Checks general plugin data and instances for all plugin types.
 */
 class PluginInstanceTest extends UnitTestBase {

  /**
   * All views plugin types.
   *
   * @var array
   */
  protected $plugin_types = array(
    'access',
    'area',
    'argument',
    'argument_default',
    'argument_validator',
    'cache',
    'display_extender',
    'display',
    'exposed_form',
    'field',
    'filter',
    'join',
    'localization',
    'pager',
    'query',
    'relationship',
    'row',
    'sort',
    'style',
    'wizard',
  );

  public static function getInfo() {
    return array(
      'name' => 'Plugin instance unit tests',
      'description' => 'Tests that an instance of all views plugins can be created.',
      'group' => 'Views Plugins',
    );
  }

  /**
   * Confirms that there is plugin data for all views plugin types.
   */
  public function testPluginData() {
    $plugin_data = $this->getViewsPluginData();

    // Check we have an array of data.
    $this->assertTrue(is_array($plugin_data), 'Plugin data is an array.');

    // Check all plugin types.
    foreach ($this->plugin_types as $type) {
      $this->assertTrue(array_key_exists($type, $plugin_data), format_string('Key for plugin type @type found.', array('@type' => $type)));
      $this->assertTrue(is_array($plugin_data[$type]) && !empty($plugin_data[$type]), format_string('Plugin type @type has an array of plugins.', array('@type' => $type)));
    }

    // Tests that the plugin list has not missed any types.
    $diff = array_diff(array_keys($plugin_data), $this->plugin_types);
    $this->assertTrue(empty($diff), 'All plugins were found and matched.');
  }

  /**
   * Tests creating instances of every views plugin.
   *
   * This will iterate through all plugins from _views_fetch_plugin_data().
   */
  public function testPluginInstances() {
    foreach ($this->getViewsPluginData() as $type => $plugins) {
      // Get a plugin manager for this type.
      $manager = new ViewsPluginManager($type);
      foreach ($plugins as $definition) {
        // Get a reflection class for this plugin.
        // We only want to test true plugins, i.e. They extend PluginBase.
        $reflection = new \ReflectionClass($definition['class']);
        if ($reflection->isSubclassOf('Drupal\views\Plugin\views\PluginBase')) {
          // Create a plugin instance and check what it is. This is not just
          // good to check they can be created but for throwing any notices for
          // method signatures etc... too.
          $instance = $manager->createInstance($definition['id']);
          $this->assertTrue($instance instanceof $definition['class'], format_string('Instance of @type:@id created', array('@type' => $type, '@id' => $definition['id'])));
        }
      }
    }
  }

  /**
   * Gets all views plugin definition data.
   *
   * @return array
   *   A nested array of plugin data, keyed by plugin type.
   */
  public function getViewsPluginData() {
    include_once drupal_get_path('module', 'views') . '/views.module';
    return views_get_plugin_definitions();
  }

}
