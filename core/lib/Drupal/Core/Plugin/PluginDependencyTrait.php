<?php

namespace Drupal\Core\Plugin;

use Drupal\Component\Plugin\Definition\PluginDefinitionInterface;
use Drupal\Component\Plugin\DependentPluginInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\DependencyTrait;
use Drupal\Core\Plugin\Definition\DependentPluginDefinitionInterface;

/**
 * Provides a trait for calculating the dependencies of a plugin.
 */
trait PluginDependencyTrait {

  use DependencyTrait;

  /**
   * Calculates and returns dependencies of a specific plugin instance.
   *
   * Dependencies are added for the module that provides the plugin, as well
   * as any dependencies declared by the instance's calculateDependencies()
   * method, if it implements
   * \Drupal\Component\Plugin\DependentPluginInterface.
   *
   * @param \Drupal\Component\Plugin\PluginInspectionInterface $instance
   *   The plugin instance.
   *
   * @return array
   *   An array of dependencies keyed by the type of dependency.
   */
  protected function getPluginDependencies(PluginInspectionInterface $instance) {
    $dependencies = [];
    $definition = $instance->getPluginDefinition();
    if ($definition instanceof PluginDefinitionInterface) {
      $dependencies['module'][] = $definition->getProvider();
      if ($definition instanceof DependentPluginDefinitionInterface && $config_dependencies = $definition->getConfigDependencies()) {
        $dependencies = NestedArray::mergeDeep($dependencies, $config_dependencies);
      }
    }
    elseif (is_array($definition)) {
      $dependencies['module'][] = $definition['provider'];
      // Plugins can declare additional dependencies in their definition.
      if (isset($definition['config_dependencies'])) {
        $dependencies = NestedArray::mergeDeep($dependencies, $definition['config_dependencies']);
      }
    }

    // If a plugin is dependent, calculate its dependencies.
    if ($instance instanceof DependentPluginInterface && $plugin_dependencies = $instance->calculateDependencies()) {
      $dependencies = NestedArray::mergeDeep($dependencies, $plugin_dependencies);
    }
    return $dependencies;
  }

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
    $this->addDependencies($this->getPluginDependencies($instance));
  }

}
