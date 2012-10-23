<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Plugin\InspectionTest
 */

namespace Drupal\system\Tests\Plugin;

/**
 * Tests that plugins implementing PluginInspectionInterface are inspectable.
 */
class InspectionTest extends PluginTestBase {

  public static function getInfo() {
    return array(
      'name' => 'Inspection',
      'description' => 'Tests that plugins implementing PluginInspectionInterface are inspectable.',
      'group' => 'Plugin API',
    );
  }

  /**
   * Ensure the test plugins correctly implement getPluginId() and getDefinition().
   */
  function testInspection() {
    foreach (array('user_login') as $id) {
      $plugin = $this->testPluginManager->createInstance($id);
      $this->assertIdentical($plugin->getPluginId(), $id);
      $this->assertIdentical($this->testPluginManager->getDefinition($id), $this->testPluginExpectedDefinitions[$id]);
    }
    // Skip the 'menu' derived blocks, because MockMenuBlock does not implement
    // PluginInspectionInterface. The others do by extending PluginBase.
    foreach (array('user_login', 'layout') as $id) {
      $plugin = $this->mockBlockManager->createInstance($id);
      $this->assertIdentical($plugin->getPluginId(), $id);
      $this->assertIdentical($this->mockBlockManager->getDefinition($id), $this->mockBlockExpectedDefinitions[$id]);
    }
    // Test a plugin manager that provides defaults.
    foreach (array('test_block1', 'test_block2') as $id) {
      $plugin = $this->defaultsTestPluginManager->createInstance($id);
      $this->assertIdentical($plugin->getPluginId(), $id);
      $this->assertIdentical($this->defaultsTestPluginManager->getDefinition($id), $this->defaultsTestPluginExpectedDefinitions[$id]);
    }
  }

}
