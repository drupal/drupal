<?php

/**
 * @file
 * Contains \Drupal\Component\Plugin\ContextAwarePluginInterface.
 */

namespace Drupal\Component\Plugin;

use Drupal\Component\Plugin\Exception\PluginException;

/**
 * Interface for defining context aware plugins.
 *
 * Context aware plugins can specify an array of context definitions keyed by
 * context name at the plugin definition under the "context" key.
 */
interface ContextAwarePluginInterface extends PluginInspectionInterface {

  /**
   * Gets the context definitions of the plugin.
   *
   * @return array
   *   The array of context definitions, keyed by context name.
   */
  public function getContextDefinitions();

  /**
   * Gets a specific context definition of the plugin.
   *
   * @param string $name
   *   The name of the context in the plugin definition.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   *   If the requested context is not defined.
   *
   * @return array
   *   The definition against which the context value must validate.
   */
  public function getContextDefinition($name);

  /**
   * Gets the defined contexts.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   *   If contexts are defined but not set.
   *
   * @return array
   *   The set context objects.
   */
  public function getContexts();

  /**
   * Gets a defined context.
   *
   * @param string $name
   *   The name of the context in the plugin definition.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   *   If the requested context is not set.
   *
   * @return \Drupal\Component\Plugin\Context\ContextInterface
   *   The context object.
   */
  public function getContext($name);

  /**
   * Gets the values for all defined contexts.
   *
   * @return array
   *   An array of set context values, keyed by context name. If a context is
   *   unset its value is returned as NULL.
   */
  public function getContextValues();

  /**
   * Gets the value for a defined context.
   *
   * @param string $name
   *   The name of the context in the plugin configuration.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   *   If the requested context is not set.
   *
   * @return mixed
   *   The currently set context value.
   */
  public function getContextValue($name);

  /**
   * Sets the value for a defined context.
   *
   * @param string $name
   *   The name of the context in the plugin definition.
   * @param mixed $value
   *   The value to set the context to. The value has to validate against the
   *   provided context definition.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   *   If the value does not pass validation.
   *
   * @return \Drupal\Component\Plugin\ContextAwarePluginInterface.
   *   A context aware plugin object for chaining.
   */
  public function setContextValue($name, $value);

  /**
   * Validates the set values for the defined contexts.
   *
   * @return \Symfony\Component\Validator\ConstraintViolationListInterface
   *   A list of constraint violations. If the list is empty, validation
   *   succeeded.
   */
  public function validateContexts();

}
