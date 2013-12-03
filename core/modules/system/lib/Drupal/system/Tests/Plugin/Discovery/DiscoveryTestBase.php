<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Plugin\Discovery\DiscoveryTestBase.
 */

namespace Drupal\system\Tests\Plugin\Discovery;

use Drupal\simpletest\UnitTestBase;

/**
 * Tests that plugins are correctly discovered.
 */
class DiscoveryTestBase extends UnitTestBase {

  /**
   * The discovery component to test.
   *
   * @var \Drupal\Component\Plugin\Discovery\DiscoveryInterface
   */
  protected $discovery;

  /**
   * The plugin definitions the discovery component is expected to discover.
   *
   * @var array
   */
  protected $expectedDefinitions;

  /**
   * An empty discovery component.
   *
   * This will be tested to ensure that the case where no plugin information is
   * found, is handled correctly.
   *
   * @var \Drupal\Component\Plugin\Discovery\DiscoveryInterface
   */
  protected $emptyDiscovery;

  /**
   * Tests getDefinitions() and getDefinition().
   */
  function testDiscoveryInterface() {
    // Ensure that getDefinitions() returns the expected definitions.
    // For the arrays to be identical (instead of only equal), they must be
    // sorted equally, which seems unnecessary here.
    $this->assertEqual($this->discovery->getDefinitions(), $this->expectedDefinitions);

    // Ensure that getDefinition() returns the expected definition.
    foreach ($this->expectedDefinitions as $id => $definition) {
      $this->assertIdentical($this->discovery->getDefinition($id), $definition);
    }

    // Ensure that an empty array is returned if no plugin definitions are found.
    $this->assertIdentical($this->emptyDiscovery->getDefinitions(), array(), 'array() returned if no plugin definitions are found.');

    // Ensure that NULL is returned as the definition of a non-existing plugin.
    $this->assertIdentical($this->emptyDiscovery->getDefinition('non_existing'), NULL, 'NULL returned as the definition of a non-existing plugin.');
  }
}

