<?php

namespace Drupal\KernelTests\Core\Plugin\Discovery;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\KernelTests\KernelTestBase;

/**
 * Base class for plugin discovery tests.
 */
abstract class DiscoveryTestBase extends KernelTestBase {

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
  public function testDiscoveryInterface() {
    // Ensure that getDefinitions() returns the expected definitions.
    // For the arrays to be identical (instead of only equal), they must be
    // sorted equally, which seems unnecessary here.
    // The discovered definitions may contain circular references; use a custom
    // assertion message to prevent var_export() from getting called.
    $this->assertEquals($this->expectedDefinitions, $this->discovery->getDefinitions(), 'Expected definitions found.');

    // Ensure that getDefinition() returns the expected definition.
    foreach ($this->expectedDefinitions as $id => $definition) {
      $this->assertDefinitionIdentical($this->discovery->getDefinition($id), $definition);
    }

    // Ensure that an empty array is returned if no plugin definitions are found.
    $this->assertSame([], $this->emptyDiscovery->getDefinitions(), 'array() returned if no plugin definitions are found.');

    // Ensure that NULL is returned as the definition of a non-existing plugin.
    $this->assertNull($this->emptyDiscovery->getDefinition('non_existing', FALSE), 'NULL returned as the definition of a non-existing plugin.');
  }

  /**
   * Asserts a definition against an expected definition.
   *
   * Converts any instances of \Drupal\Core\Annotation\Translation to a string.
   *
   * @param array $definition
   *   The definition to test.
   * @param array $expected_definition
   *   The expected definition to test against.
   */
  protected function assertDefinitionIdentical(array $definition, array $expected_definition) {
    $func = function (&$item) {
      if ($item instanceof TranslatableMarkup) {
        $item = (string) $item;
      }
    };
    array_walk_recursive($definition, $func);
    array_walk_recursive($expected_definition, $func);
    $this->assertSame($expected_definition, $definition);
  }

}
