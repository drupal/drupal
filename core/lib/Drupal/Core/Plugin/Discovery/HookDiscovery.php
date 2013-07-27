<?php

/**
 * @file
 * Definition of Drupal\Core\Plugin\Discovery\HookDiscovery.
 */

namespace Drupal\Core\Plugin\Discovery;

use Drupal\Component\Plugin\Discovery\DiscoveryInterface;

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
   * Constructs a Drupal\Core\Plugin\Discovery\HookDiscovery object.
   *
   * @param string $hook
   *   The Drupal hook that a module can implement in order to interface to
   *   this discovery class.
   */
  function __construct($hook) {
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
    foreach (\Drupal::moduleHandler()->getImplementations($this->hook) as $module) {
      $function = $module . '_' . $this->hook;
      foreach ($function() as $plugin_id => $definition) {
        $definition['module'] = $module;
        $definitions[$plugin_id] = $definition;
      }
    }
    return $definitions;
  }
}
