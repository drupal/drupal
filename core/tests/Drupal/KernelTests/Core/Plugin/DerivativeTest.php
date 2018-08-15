<?php

namespace Drupal\KernelTests\Core\Plugin;

/**
 * Tests that derivative plugins are correctly discovered.
 *
 * @group Plugin
 */
class DerivativeTest extends PluginTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['node', 'user'];

  /**
   * Tests getDefinitions() and getDefinition() with a derivativeDecorator.
   */
  public function testDerivativeDecorator() {
    // Ensure that getDefinitions() returns the expected definitions.
    $this->assertEqual($this->mockBlockManager->getDefinitions(), $this->mockBlockExpectedDefinitions);

    // Ensure that getDefinition() returns the expected definition.
    foreach ($this->mockBlockExpectedDefinitions as $id => $definition) {
      $this->assertEqual($this->mockBlockManager->getDefinition($id), $definition);
    }

    // Ensure that NULL is returned as the definition of a non-existing base
    // plugin, a non-existing derivative plugin, or a base plugin that may not
    // be used without deriving.
    $this->assertIdentical($this->mockBlockManager->getDefinition('non_existing', FALSE), NULL, 'NULL returned as the definition of a non-existing base plugin.');
    $this->assertIdentical($this->mockBlockManager->getDefinition('menu:non_existing', FALSE), NULL, 'NULL returned as the definition of a non-existing derivative plugin.');
    $this->assertIdentical($this->mockBlockManager->getDefinition('menu', FALSE), NULL, 'NULL returned as the definition of a base plugin that may not be used without deriving.');
  }

}
