<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Plugin\DiscoveryTest.
 */

namespace Drupal\system\Tests\Plugin;

/**
 * Tests that plugins are correctly discovered.
 */
class DiscoveryTest extends PluginTestBase {

  public static function getInfo() {
    return array(
      'name' => 'Discovery',
      'description' => 'Tests that plugins are correctly discovered.',
      'group' => 'Plugin API',
    );
  }

  /**
   * Tests getDefinitions() and getDefinition().
   */
  function testDiscoveryInterface() {
    // Ensure that getDefinitions() returns the expected definitions.
    $this->assertIdentical($this->testPluginManager->getDefinitions(), $this->testPluginExpectedDefinitions);

    // Ensure that getDefinition() returns the expected definition.
    foreach ($this->testPluginExpectedDefinitions as $id => $definition) {
      $this->assertIdentical($this->testPluginManager->getDefinition($id), $definition);
    }

    // Ensure that NULL is returned as the definition of a non-existing plugin.
    $this->assertIdentical($this->testPluginManager->getDefinition('non_existing'), NULL, 'NULL returned as the definition of a non-existing base plugin.');
  }
}
