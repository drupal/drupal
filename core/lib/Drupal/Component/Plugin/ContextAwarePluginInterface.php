<?php

/**
 * @file
 * Contains \Drupal\Component\Plugin\ContextAwarePluginInterface.
 */

namespace Drupal\Component\Plugin;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Component\Plugin\Context\Context;

/**
 * Interface for defining context aware plugins.
 */
interface ContextAwarePluginInterface extends PluginInspectionInterface {

  /**
   * Gets the context definitions of the plugin.
   *
   * @return array|null
   *   The context definitions if set, otherwise NULL.
   */
  public function getContextDefinitions();

  /**
   * Gets the a specific context definition of the plugin.
   *
   * @param string $key
   *   The name of the context in the plugin definition.
   *
   * @return mixed
   *   The definition against which the context value must validate.
   */
  public function getContextDefinition($key);

  /**
   * Gets the defined contexts.
   *
   * @return array
   *   The set context objects.
   */
  public function getContexts();

  /**
   * Gets a defined context.
   *
   * @param string $key
   *   The name of the context in the plugin configuration. This string is
   *   usually identical to the representative string in the plugin definition.
   *
   * @return \Drupal\Component\Plugin\Context\ContextInterface
   *   The context object.
   */
  public function getContext($key);

  /**
   * Gets the values for all defined contexts.
   *
   * @return array
   *   The set context object values.
   */
  public function getContextValues();

  /**
   * Gets the value for a defined context.
   *
   * @param string $key
   *   The name of the context in the plugin configuration. This string is
   *   usually identical to the representative string in the plugin definition.
   *
   * @return mixed
   *   The currently set context value.
   */
  public function getContextValue($key);

  /**
   * Sets the value for a defined context.
   *
   * @param string $key
   *   The name of the context in the plugin definition.
   * @param mixed $value
   *   The variable to set the context to. This should validate against the
   *   provided context definition.
   *
   * @return \Drupal\Component\Plugin\ContextAwarePluginInterface.
   *   A context aware plugin object for chaining.
   */
  public function setContextValue($key, $value);

}
