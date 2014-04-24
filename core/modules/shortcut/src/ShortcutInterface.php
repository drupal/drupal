<?php

/**
 * @file
 * Contains \Drupal\shortcut\ShortcutInterface.
 */

namespace Drupal\shortcut;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Provides an interface defining a shortcut entity.
 */
interface ShortcutInterface extends ContentEntityInterface {

  /**
   * Returns the title of this shortcut.
   *
   * @return string
   *   The title of this shortcut.
   */
  public function getTitle();

  /**
   * Sets the title of this shortcut.
   *
   * @param string $title
   *   The title of this shortcut.
   *
   * @return \Drupal\shortcut\ShortcutInterface
   *   The called shortcut entity.
   */
  public function setTitle($title);

  /**
   * Returns the weight among shortcuts with the same depth.
   *
   * @return int
   *   The shortcut weight.
   */
  public function getWeight();

  /**
   * Sets the weight among shortcuts with the same depth.
   *
   * @param int $weight
   *   The shortcut weight.
   *
   * @return \Drupal\shortcut\ShortcutInterface
   *   The called shortcut entity.
   */
  public function setWeight($weight);

  /**
   * Returns the route name associated with this shortcut, if any.
   *
   * @return string|null
   *   The route name of this shortcut.
   */
  public function getRouteName();

  /**
   * Sets the route name associated with this shortcut.
   *
   * @param string|null $route_name
   *   The route name associated with this shortcut.
   *
   * @return \Drupal\shortcut\ShortcutInterface
   *   The called shortcut entity.
   */
  public function setRouteName($route_name);

  /**
   * Returns the route parameters associated with this shortcut, if any.
   *
   * @return array
   *   The route parameters of this shortcut.
   */
  public function getRouteParams();

  /**
   * Sets the route parameters associated with this shortcut.
   *
   * @param array $route_parameters
   *   The route parameters associated with this shortcut.
   *
   * @return \Drupal\shortcut\ShortcutInterface
   *   The called shortcut entity.
   */
  public function setRouteParams($route_parameters);

}
