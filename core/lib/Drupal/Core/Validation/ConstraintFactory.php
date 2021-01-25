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
    $plugin_definition = $this->discovery->getDefinition($plugin_id);
    $plugin_class = static::getPluginClass($plugin_id, $plugin_definition, $this->interface);

    // If the plugin provides a factory method, pass the container to it.
    if (is_subclass_of($plugin_class, ContainerFactoryPluginInterface::class)) {
      return $plugin_class::create(\Drupal::getContainer(), $configuration, $plugin_id, $plugin_definition);
    }

    // If the plugin is a Symfony Constraint, use the correct constructor.
    if (is_subclass_of($plugin_class, Constraint::class)) {
      return new $plugin_class($configuration);
    }

    // Otherwise, create the plugin as normal.
    return new $plugin_class($configuration, $plugin_id, $plugin_definition);
  }

}
