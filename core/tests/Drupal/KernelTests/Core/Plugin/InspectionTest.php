<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Plugin;

/**
 * Tests that plugins implementing PluginInspectionInterface can be inspected.
 *
 * @group Plugin
 */
class InspectionTest extends PluginTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['node', 'user'];

  /**
   * Tests getPluginId() and getPluginDefinition().
   */
  public function testInspection(): void {
    foreach (['user_login'] as $id) {
      $plugin = $this->testPluginManager->createInstance($id);
      $expected_definition = $this->testPluginExpectedDefinitions[$id];
      $this->assertSame($id, $plugin->getPluginId());
      $this->assertSame($expected_definition, $this->testPluginManager->getDefinition($id));
      $this->assertSame($expected_definition, $plugin->getPluginDefinition());
    }
    // Skip the 'menu' derived blocks, because MockMenuBlock does not implement
    // PluginInspectionInterface. The others do by extending PluginBase.
    foreach (['user_login', 'layout'] as $id) {
      $plugin = $this->mockBlockManager->createInstance($id);
      $expected_definition = $this->mockBlockExpectedDefinitions[$id];
      $this->assertSame($id, $plugin->getPluginId());
      $this->assertEquals($expected_definition, $this->mockBlockManager->getDefinition($id));
      $this->assertEquals($expected_definition, $plugin->getPluginDefinition());
    }
    // Test a plugin manager that provides defaults.
    foreach (['test_block1', 'test_block2'] as $id) {
      $plugin = $this->defaultsTestPluginManager->createInstance($id);
      $expected_definition = $this->defaultsTestPluginExpectedDefinitions[$id];
      $this->assertSame($id, $plugin->getPluginId());
      $this->assertSame($expected_definition, $this->defaultsTestPluginManager->getDefinition($id));
      $this->assertEquals($expected_definition, $plugin->getPluginDefinition());
    }
  }

}
