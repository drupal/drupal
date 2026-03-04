<?php

namespace Drupal\Core\Validation;

use Drupal\Core\Plugin\Factory\ContainerFactory;
use Symfony\Component\Validator\Constraint;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

/**
 * Constraint plugin factory.
 *
 * Symfony Constraint plugins are created as Drupal plugins, but the default
 * plugin constructor is not compatible.
 */
class ConstraintFactory extends ContainerFactory {

  /**
   * {@inheritdoc}
   */
  public function createInstance($plugin_id, array $configuration = []) {
    if ($configuration && array_is_list($configuration)) {
      throw new \InvalidArgumentException('$configuration must be an associative array.');
    }
    $plugin_definition = $this->discovery->getDefinition($plugin_id);
    $plugin_class = static::getPluginClass($plugin_id, $plugin_definition, $this->interface);

    if (is_subclass_of($plugin_class, CompositeConstraintInterface::class)) {
      $composite_constraint_options = (array) $plugin_class::getCompositeOptionStatic();
      foreach ($composite_constraint_options as $option) {
        // Skip if no constraints are set in the configuration.
        if (!isset($configuration[$option])) {
          continue;
        }
        foreach ($configuration[$option] as $key => $value) {
          foreach ($value as $nested_constraint_id => $nested_constraint_configuration) {
            $configuration[$option][$key] = $this->createInstance($nested_constraint_id, $nested_constraint_configuration);
          }
        }
      }
    }

    // If the plugin provides a factory method, pass the container to it.
    if (is_subclass_of($plugin_class, ContainerFactoryPluginInterface::class)) {
      return $plugin_class::create(\Drupal::getContainer(), $configuration, $plugin_id, $plugin_definition);
    }

    // If the plugin is a Symfony Constraint, use the correct constructor.
    if (is_subclass_of($plugin_class, Constraint::class)) {
      return new $plugin_class(...$configuration);
    }

    // Otherwise, create the plugin as normal.
    return new $plugin_class($configuration, $plugin_id, $plugin_definition);
  }

}
