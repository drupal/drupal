<?php

/**
 * @file
 * Contains \Drupal\Core\Plugin\PluginDependencyTrait.
 */

namespace Drupal\Core\Plugin;

use Drupal\Component\Plugin\ConfigurablePluginInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Entity\DependencyTrait;

/**
 * Provides a trait for calculating the dependencies of a plugin.
 */
trait PluginDependencyTrait {

  use DependencyTrait;

  /**
   * Calculates the dependencies of a specific plugin instance.
   *
   * @param \Drupal\Component\Plugin\PluginInspectionInterface $instance
   *   The plugin instance.
   */
  protected function calculatePluginDependencies(PluginInspectionInterface $instance) {
    $definition = $instance->getPluginDefinition();
    $this->addDependency('module', $definition['provider']);
    // Plugins can declare additional dependencies in their definition.
    if (isset($definition['config_dependencies'])) {
      $this->addDependencies($definition['config_dependencies']);
    }
    // If a plugin is configurable, calculate its dependencies.
    if ($instance instanceof ConfigurablePluginInterface && $plugin_dependencies = $instance->calculateDependencies()) {
      $this->addDependencies($plugin_dependencies);
    }
  }

}
