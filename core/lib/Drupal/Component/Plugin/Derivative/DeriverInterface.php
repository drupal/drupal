<?php

namespace Drupal\Component\Plugin\Derivative;

/**
 * Provides additional plugin definitions based on an existing definition.
 *
 * @ingroup plugin_api
 */
interface DeriverInterface {

  /**
   * Gets the definition of a derivative plugin.
   *
   * @param string $derivative_id
   *   The derivative id. The id must uniquely identify the derivative within a
   *   given base plugin, but derivative ids can be reused across base plugins.
   * @param mixed $base_plugin_definition
   *   The definition of the base plugin from which the derivative plugin
   *   is derived. It is maybe an entire object or just some array, depending
   *   on the discovery mechanism.
   *
   * @return array
   *   The full definition array of the derivative plugin, typically a merge of
   *   $base_plugin_definition with extra derivative-specific information. NULL
   *   if the derivative doesn't exist.
   */
  public function getDerivativeDefinition($derivative_id, $base_plugin_definition);

  /**
   * Gets the definition of all derivatives of a base plugin.
   *
   * @param array $base_plugin_definition
   *   The definition array of the base plugin.
   * @return array
   *   An array of full derivative definitions keyed on derivative id.
   *
   * @see getDerivativeDefinition()
   */
  public function getDerivativeDefinitions($base_plugin_definition);

}
