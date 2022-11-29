<?php

namespace Drupal\Component\Plugin\Discovery;

/**
 * Defines the minimum requirements for a plugin discovery component.
 *
 * @ingroup plugin_api
 */
interface DiscoveryInterface {

  /**
   * Gets a specific plugin definition.
   *
   * @param string $plugin_id
   *   A plugin id.
   * @param bool $exception_on_invalid
   *   (optional) If TRUE, an invalid plugin ID will throw an exception.
   *
   * @return mixed
   *   A plugin definition, or NULL if the plugin ID is invalid and
   *   $exception_on_invalid is FALSE.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   *   Thrown if $plugin_id is invalid and $exception_on_invalid is TRUE.
   */
  public function getDefinition($plugin_id, $exception_on_invalid = TRUE);

  /**
   * Gets the definition of all plugins for this type.
   *
   * @return mixed[]
   *   An array of plugin definitions (empty array if no definitions were
   *   found). Keys are plugin IDs.
   *
   * @see \Drupal\Core\Plugin\FilteredPluginManagerInterface::getFilteredDefinitions()
   */
  public function getDefinitions();

  /**
   * Indicates if a specific plugin definition exists.
   *
   * @param string $plugin_id
   *   A plugin ID.
   *
   * @return bool
   *   TRUE if the definition exists, FALSE otherwise.
   */
  public function hasDefinition($plugin_id);

}
