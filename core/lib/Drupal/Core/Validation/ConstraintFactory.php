<?php

namespace Drupal\Core\Validation;

use Drupal\Core\Plugin\Factory\ContainerFactory;
use Symfony\Component\Validator\Attribute\HasNamedArguments;
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
    $options_not_passed_as_array = !empty($configuration['_options_not_passed_as_array']);
    unset($configuration['_options_not_passed_as_array']);

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
      $configuration_is_list = !empty($configuration) && array_is_list($configuration);
      if ($options_not_passed_as_array || $configuration_is_list) {
        @trigger_error(sprintf('Passing any non-associative-array options to configure constraint plugin "%s" is deprecated in drupal:11.4.0 and will not be supported in drupal:12.0.0. See https://www.drupal.org/node/3554746', $plugin_id), E_USER_DEPRECATED);
        return new $plugin_class($configuration);
      }

      $reflection_class = new \ReflectionClass($plugin_class);
      $reflection_constructor = $reflection_class->getConstructor();
      // If configuration is empty, an empty first parameter is passed to any
      // plugin class constructor that has a required parameter. Otherwise,
      // create a new plugin class instance without any constructor parameters.
      // For an example of a constraint that has a required parameter:
      // @see Drupal\Core\Extension\Plugin\Validation\Constraint\ExtensionNameConstraint
      if (empty($configuration)) {
        return ($reflection_constructor?->getNumberOfRequiredParameters() > 0) ? new $plugin_class($configuration) : new $plugin_class();
      }

      // If the plugin class has the HasNamedArguments attribute on its
      // constructor, then passing named parameters to the constructor will be
      // required.
      $has_named_arguments = (bool) $reflection_constructor->getAttributes(HasNamedArguments::class);
      if ($has_named_arguments) {
        // If the configuration array is associative, use the spread operator to
        // pass the values as named parameters.
        return array_is_list($configuration) ? new $plugin_class($configuration) : new $plugin_class(...$configuration);
      }

      return new $plugin_class($configuration);
    }

    // Otherwise, create the plugin as normal.
    return new $plugin_class($configuration, $plugin_id, $plugin_definition);
  }

}
