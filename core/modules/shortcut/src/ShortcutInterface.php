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
   * Returns the URL object pointing to the configured route.
   *
   * @return \Drupal\Core\Url
   *   The URL object.
   */
  public function getUrl();

}
