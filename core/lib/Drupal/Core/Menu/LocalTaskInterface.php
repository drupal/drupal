<?php

/**
 * @file
 * Contains \DrupalCore\Menu\LocalTaskInterface.
 */

namespace Drupal\Core\Menu;

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
   * Returns an internal Drupal path to use when creating the link for the tab.
   *
   * Subclasses may add optional arguments like NodeInterface $node = NULL that
   * will be supplied by the ControllerResolver.
   *
   * @return string
   *   The path of this local task.
   */
  public function getPath();

  /**
   * Returns the weight of the local task.
   *
   * @return int|null
   *   The weight of the task or NULL.
   */
  public function getWeight();

  /**
   * Returns an array of options suitable to pass to l().
   *
   * @return array
   *   Associative array of options.
   *
   * @see l()
   */
  public function getOptions();

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
