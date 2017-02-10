<?php

namespace Drupal\Core\Plugin;

use Drupal\Component\Plugin\Definition\PluginDefinitionInterface;
use Drupal\Component\Plugin\DependentPluginInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Entity\DependencyTrait;
use Drupal\Core\Plugin\Definition\DependentPluginDefinitionInterface;

/**
 * Provides a trait for calculating the dependencies of a plugin.
 */
trait PluginDependencyTrait {

  use DependencyTrait;

  /**
   * Calculates and adds dependencies of a specific plugin instance.
   *
   * Dependencies are added for the module that provides the plugin, as well
   * as any dependencies declared by the instance's calculateDependencies()
   * method, if it implements
   * \Drupal\Component\Plugin\DependentPluginInterface.
   *
   * @param \Drupal\Component\Plugin\PluginInspectionInterface $instance
   *   The plugin instance.
   */
  protected function calculatePluginDependencies(PluginInspectionInterface $instance) {
    $definition = $instance->getPluginDefinition();

    if ($definition instanceof PluginDefinitionInterface) {
      $this->addDependency('module', $definition->getProvider());
      if ($definition instanceof DependentPluginDefinitionInterface && $config_dependencies = $definition->getConfigDependencies()) {
        $this->addDependencies($config_dependencies);
      }
    }
    elseif (is_array($definition)) {
      $this->addDependency('module', $definition['provider']);
      // Plugins can declare additional dependencies in their definition.
      if (isset($definition['config_dependencies'])) {
        $this->addDependencies($definition['config_dependencies']);
      }
    }

    // If a plugin is dependent, calculate its dependencies.
    if ($instance instanceof DependentPluginInterface && $plugin_dependencies = $instance->calculateDependencies()) {
      $this->addDependencies($plugin_dependencies);
    }
  }

}
