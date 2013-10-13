<?php

/**
 * @file
 * Definition of Drupal\Core\Plugin\Discovery\HookDiscovery.
 */

namespace Drupal\Core\Plugin\Discovery;

use Drupal\Component\Plugin\Discovery\DiscoveryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Provides a hook-based plugin discovery class.
 */
class HookDiscovery implements DiscoveryInterface {

  /**
   * The name of the hook that will be implemented by this discovery instance.
   *
   * @var string
   */
  protected $hook;

  /**
   * The module handler used to find and execute the plugin hook.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs a Drupal\Core\Plugin\Discovery\HookDiscovery object.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param string $hook
   *   The Drupal hook that a module can implement in order to interface to
   *   this discovery class.
   */
  function __construct(ModuleHandlerInterface $module_handler, $hook) {
    $this->moduleHandler = $module_handler;
    $this->hook = $hook;
  }

  /**
   * Implements Drupal\Component\Plugin\Discovery\DicoveryInterface::getDefinition().
   */
  public function getDefinition($plugin_id) {
    $plugins = $this->getDefinitions();
    return isset($plugins[$plugin_id]) ? $plugins[$plugin_id] : NULL;
  }

  /**
   * Implements Drupal\Component\Plugin\Discovery\DicoveryInterface::getDefinitions().
   */
  public function getDefinitions() {
    $definitions = array();
    foreach ($this->moduleHandler->getImplementations($this->hook) as $module) {
      $result = $this->moduleHandler->invoke($module, $this->hook);
      foreach ($result as $plugin_id => $definition) {
        $definition['module'] = $module;
        $definitions[$plugin_id] = $definition;
      }
    }
    return $definitions;
  }
}
