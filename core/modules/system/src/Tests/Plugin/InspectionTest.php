<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Plugin\InspectionTest
 */

namespace Drupal\system\Tests\Plugin;

/**
 * Tests that plugins implementing PluginInspectionInterface are inspectable.
 *
 * @group Plugin
 */
class InspectionTest extends PluginTestBase {

  /**
   * Ensure the test plugins correctly implement getPluginId() and getPluginDefinition().
   */
  function testInspection() {
    foreach (array('user_login') as $id) {
      $plugin = $this->testPluginManager->createInstance($id);
      $expected_definition = $this->testPluginExpectedDefinitions[$id];
      $this->assertIdentical($plugin->getPluginId(), $id);
      $this->assertIdentical($this->testPluginManager->getDefinition($id), $expected_definition);
      $this->assertIdentical($plugin->getPluginDefinition(), $expected_definition);
    }
    // Skip the 'menu' derived blocks, because MockMenuBlock does not implement
    // PluginInspectionInterface. The others do by extending PluginBase.
    foreach (array('user_login', 'layout') as $id) {
      $plugin = $this->mockBlockManager->createInstance($id);
      $expected_definition = $this->mockBlockExpectedDefinitions[$id];
      $this->assertIdentical($plugin->getPluginId(), $id);
      $this->assertIdentical($this->mockBlockManager->getDefinition($id), $expected_definition);
      $this->assertIdentical($plugin->getPluginDefinition(), $expected_definition);
    }
    // Test a plugin manager that provides defaults.
    foreach (array('test_block1', 'test_block2') as $id) {
      $plugin = $this->defaultsTestPluginManager->createInstance($id);
      $expected_definition = $this->defaultsTestPluginExpectedDefinitions[$id];
      $this->assertIdentical($plugin->getPluginId(), $id);
      $this->assertIdentical($this->defaultsTestPluginManager->getDefinition($id), $expected_definition);
      $this->assertIdentical($plugin->getPluginDefinition(), $expected_definition);
    }
  }

}
