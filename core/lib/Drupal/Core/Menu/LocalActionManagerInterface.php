<?php

/**
 * @file
 * Contains \Drupal\Core\Menu\LocalActionManagerInterface.
 */
namespace Drupal\Core\Menu;

use Drupal\Component\Plugin\PluginManagerInterface;

/**
 * Manages discovery and instantiation of menu local action plugins.
 *
 * Menu local actions are links that lead to actions like "add new". The plugin
 * format allows them (if needed) to dynamically generate a title or the path
 * they link to. The annotation on the plugin provides the default title,
 * and the list of routes where the action should be rendered.
 */
interface LocalActionManagerInterface extends PluginManagerInterface {

  /**
   * Gets the title for a local action.
   *
   * @param \Drupal\Core\Menu\LocalActionInterface $local_action
   *   An object to get the title from.
   *
   * @return string
   *   The title (already localized).
   *
   * @throws \BadMethodCallException
   *   If the plugin does not implement the getTitle() method.
   */
  public function getTitle(LocalActionInterface $local_action);

  /**
   * Finds all local actions that appear on a named route.
   *
   * @param string $route_appears
   *   The route name for which to find local actions.
   *
   * @return array
   *   An array of link render arrays.
   */
  public function getActionsForRoute($route_appears);

}
