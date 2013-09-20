<?php

/**
 * @file
 * Contains \DrupalCore\Menu\LocalTaskInterface.
 */

namespace Drupal\Core\Menu;

use Symfony\Component\HttpFoundation\Request;

/**
 * Defines an interface for menu local tasks.
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
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The HttpRequest object representing the current request.
   *
   * @return array
   *   An array of parameter names and values.
   *
   * @see \Drupal\Core\Utility\LinkGeneratorInterface::generate()
   */
  public function getRouteParameters(Request $request);

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
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The HttpRequest object representing the current request.
   *
   * @return array
   *   An array of options.
   *
   * @see \Drupal\Core\Utility\LinkGeneratorInterface::generate()
   */
  public function getOptions(Request $request);

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
