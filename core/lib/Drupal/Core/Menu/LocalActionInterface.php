<?php

/**
 * @file
 * Contains \Drupal\Core\Menu\LocalActionInterface.
 */

namespace Drupal\Core\Menu;

/**
 * Defines an interface for menu local actions.
 */
interface LocalActionInterface {

  /**
   * Get the route name from the settings.
   *
   * @return string
   *   The name of the route this action links to.
   */
  public function getRouteName();

  /**
   * Returns the localized title to be shown for this action.
   *
   * Subclasses may add optional arguments like NodeInterface $node = NULL that
   * will be supplied by the ControllerResolver.
   *
   * @return string
   *   The title to be shown for this action.
   *
   * @see \Drupal\Core\Menu\LocalActionManager::getTitle()
   */
  public function getTitle();

  /**
   * Return an internal Drupal path to use when linking to the action.
   *
   * Subclasses may add arguments for request attributes which will then be
   * automatically supplied by the controller resolver.
   *
   * @return string
   *   The path to use when linking to the action.
   *
   * @see \Drupal\Core\Menu\LocalActionManager::getPath()
   */
  public function getPath();

}
