<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Plugin\PluginBagTest.
 */

namespace Drupal\system\Tests\Plugin;

use Drupal\plugin_test\Plugin\TestPluginBag;
use Drupal\plugin_test\Plugin\plugin_test\mock_block\MockTestPluginInterface;

/**
 * Tests the generic plugin bag.
 *
 * @see \Drupal\Component\Plugin\PluginBag
 * @see \Drupal\plugin_test\Plugin\TestPluginBag
 */
class PluginBagTest extends PluginTestBase {

  public static function getInfo() {
    return array(
      'name' => 'Plugin Bag',
      'description' => 'Tests the generic plugin bag.',
      'group' => 'Plugin API',
    );
  }

  /**
   * Tests the generic plugin bag.
   */
  protected function testPluginBag() {
    // Setup the plugin bag as well as the available plugin definitions.
    $plugin_bag = new TestPluginBag($this->mockBlockManager);
    $definitions = $this->mockBlockManager->getDefinitions();
    $first_instance_id = key($definitions);

    foreach ($definitions as $instance_id => $definition) {
      $this->assertTrue($plugin_bag->has($instance_id), format_string('Plugin instance @instance_id exits on the bag', array('@instance_id' => $instance_id)));
      $this->assertTrue($plugin_bag->get($instance_id) instanceof $definition['class'], 'Getting the plugin from the bag worked.');
    }

    // A non existing instance_id shouldn't exist on the bag.
    $random_name = $this->randomName();
    $this->assertFalse($plugin_bag->has($random_name), 'A random instance_id should not exist on the plugin bag.');

    // Set a new plugin instance to the bag, to test offsetSet.
    $plugin_bag->set($random_name, $this->mockBlockManager->createInstance($first_instance_id, array()));
    $this->assertTrue($plugin_bag->has($random_name), 'A random instance_id should exist after manual setting on the plugin bag.');

    // Remove the previous added element and check whether it still exists.
    $plugin_bag->remove($random_name);
    $this->assertFalse($plugin_bag->has($random_name), 'A random instance_id should not exist on the plugin bag after removing.');

    // Test that iterating over the plugins work.
    $expected_instance_ids = array_keys($definitions);
    $counter = 0;
    foreach ($plugin_bag as $instance_id => $plugin) {
      $this->assertEqual($expected_instance_ids[$counter], $instance_id, format_string('The iteration works as expected for plugin instance @instance_id', array('@instance_id' => $instance_id)));
      $counter++;
    }

    $this->assertEqual(count($plugin_bag), count($expected_instance_ids), 'The amount of items in plugin bag is as expected.');
  }

}
