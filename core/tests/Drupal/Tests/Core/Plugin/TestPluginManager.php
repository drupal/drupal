<?php

/**
 * @file
 * Contains \Drupal\Tests\Plugin\Core\TestDefaultPluginManager.
 */

namespace Drupal\Tests\Core\Plugin;

use Drupal\Component\Plugin\Discovery\StaticDiscovery;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * A plugin manager for condition plugins.
 */
class TestPluginManager extends DefaultPluginManager {

  /**
   * Constructs a TestPluginManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param array $definitions
   *   An array of static definitions.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   (optional) The module handler to invoke the alter hook with.
   * @param string $alter_hook
   *   (optional) Name of the alter hook.
   */
  public function __construct(\Traversable $namespaces, array $definitions, ModuleHandlerInterface $module_handler = NULL, $alter_hook = NULL) {
    // Create the object that can be used to return definitions for all the
    // plugins available for this type. Most real plugin managers use a richer
    // discovery implementation, but StaticDiscovery lets us add some simple
    // mock plugins for unit testing.
    $this->discovery = new StaticDiscovery();

    // Add the static definitions.
    foreach ($definitions as $key => $definition) {
      $this->discovery->setDefinition($key, $definition);
    }

    $this->moduleHandler = $module_handler;

    if ($alter_hook) {
      $this->alterInfo($alter_hook);
    }
  }

}
