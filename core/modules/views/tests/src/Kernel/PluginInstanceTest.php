<?php

namespace Drupal\Tests\views\Kernel;

use Drupal\views\Views;
use Drupal\views\Plugin\views\PluginBase;

/**
 * Tests that an instance of all views plugins can be created.
 *
 * @group views
 */
class PluginInstanceTest extends ViewsKernelTestBase {

  /**
   * All views plugin types.
   *
   * @var array
   */
  protected $pluginTypes = [
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
    'pager',
    'query',
    'relationship',
    'row',
    'sort',
    'style',
    'wizard',
  ];

  /**
   * List of deprecated plugin classes.
   *
   * @var string[]
   */
  protected $deprecatedPlugins = [];

  /**
   * An array of plugin definitions, keyed by plugin type.
   *
   * @var array
   */
  protected $definitions;

  protected function setUp($import_test_views = TRUE): void {
    parent::setUp();

    $this->definitions = Views::getPluginDefinitions();
  }

  /**
   * Confirms that there is plugin data for all views plugin types.
   */
  public function testPluginData() {
    // Check that we have an array of data.
    $this->assertIsArray($this->definitions);

    // Check all plugin types.
    foreach ($this->pluginTypes as $type) {
      $this->assertArrayHasKey($type, $this->definitions);
      $this->assertIsArray($this->definitions[$type]);
      $this->assertNotEmpty($this->definitions[$type], "Plugin type '$type' should contain plugins.");
    }

    // Tests that the plugin list has not missed any types.
    $diff = array_diff(array_keys($this->definitions), $this->pluginTypes);
    $this->assertTrue(empty($diff), 'All plugins were found and matched.');
  }

  /**
   * Tests creating instances of every views plugin.
   *
   * This will iterate through all plugins from _views_fetch_plugin_data(),
   * filtering out deprecated plugins.
   */
  public function testPluginInstances() {
    $this->assertPluginInstances(FALSE);
  }

  /**
   * Asserts that instances of every views plugin can be created.
   *
   * @param bool $test_deprecated
   *   Indicates if deprecated plugins should be tested or skipped.
   */
  protected function assertPluginInstances($test_deprecated) {
    foreach ($this->definitions as $type => $plugins) {
      // Get a plugin manager for this type.
      $manager = $this->container->get("plugin.manager.views.$type");
      foreach ($plugins as $id => $definition) {
        if ($test_deprecated !== in_array($definition['class'], $this->deprecatedPlugins)) {
          continue;
        }
        // Get a reflection class for this plugin.
        // We only want to test true plugins, i.e. They extend PluginBase.
        $reflection = new \ReflectionClass($definition['class']);
        if ($reflection->isSubclassOf(PluginBase::class)) {
          // Create a plugin instance and check what it is. This is not just
          // good to check they can be created but for throwing any notices for
          // method signatures etc. too.
          $instance = $manager->createInstance($id);
          $this->assertInstanceOf($definition['class'], $instance);
        }
      }
    }
  }

}
