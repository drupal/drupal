<?php

/**
 * @file
 * Contains \Drupal\Core\Menu\LocalTaskInterface.
 */

namespace Drupal\Core\Menu;

use Drupal\Core\Routing\RouteMatchInterface;

/**
 * Defines an interface for menu local tasks.
 *
 * Menu local tasks are are typically rendered as navigation tabs above the
 * content region, though other presentations are possible. It is convention
 * that the titles of these tasks should be short verbs if possible.
 *
 * @see \Drupal\Core\Menu\LocalTaskManagerInterface
 */
interface LocalTaskInterface {

  /**
   * Get the route name from the settings.
   *
   * @return string
   *   The name of the route this local task links to.
   */
  public function getRouteName();

  /**
   * Returns the localized title to be shown for this tab.
   *
   * Subclasses may add optional arguments like NodeInterface $node = NULL that
   * will be supplied by the ControllerResolver.
   *
   * @return string
   *   The title of the local task.
   */
  public function getTitle();

  /**
   * Returns the route parameters needed to render a link for the local task.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   *
   * @return array
   *   An array of parameter names and values.
   */
  public function getRouteParameters(RouteMatchInterface $route_match);

  /**
   * Returns the weight of the local task.
   *
   * @return int|null
   *   The weight of the task or NULL.
   */
  public function getWeight();

  /**
   * Returns options for rendering a link to the local task.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   *
   * @return array
   *   An associative array of options.
   */
  public function getOptions(RouteMatchInterface $route_match);

  /**
   * Sets the active status.
   *
   * @param bool $active
   *   Sets whether this tab is active (e.g. a parent of the current tab).
   *
   * @return \Drupal\Core\Menu\LocalTaskInterface
   *   The called object for chaining.
   */
  public function setActive($active = TRUE);

  /**
   * Gets the active status.
   *
   * @return bool
   *   TRUE if the local task is active, FALSE otherwise.
   *
   * @see \Drupal\system\Plugin\MenuLocalTaskInterface::setActive()
   */
  public function getActive();

}
