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

    $provider = NULL;
    $config_dependencies = [];
    if ($definition instanceof PluginDefinitionInterface) {
      $provider = $definition->getProvider();

      if ($definition instanceof DependentPluginDefinitionInterface) {
        $config_dependencies = $definition->getConfigDependencies();
      }
    }
    elseif (is_array($definition)) {
      $provider = $definition['provider'];

      if (isset($definition['config_dependencies'])) {
        $config_dependencies = $definition['config_dependencies'];
      }
    }

    // Add the provider as a dependency, taking into account if it's a module or
    // a theme.
    if ($provider) {
      if ($provider === 'core' || $this->moduleHandler()->moduleExists($provider)) {
        $dependencies['module'][] = $provider;
      }
      elseif ($this->themeHandler()->themeExists($provider)) {
        $dependencies['theme'][] = $provider;
      }
    }

    // Add the config dependencies.
    if ($config_dependencies) {
      $dependencies = NestedArray::mergeDeep($dependencies, $config_dependencies);
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

  /**
   * Wraps the module handler.
   *
   * @return \Drupal\Core\Extension\ModuleHandlerInterface
   *   The module handler.
   */
  protected function moduleHandler() {
    return \Drupal::moduleHandler();
  }

  /**
   * Wraps the theme handler.
   *
   * @return \Drupal\Core\Extension\ThemeHandlerInterface
   *   The theme handler.
   */
  protected function themeHandler() {
    return \Drupal::service('theme_handler');
  }

}
