<?php

namespace Drupal\image;

/**
 * Interface for storage controller for "image style" configuration entities.
 */
interface ImageStyleStorageInterface {

  /**
   * Stores a replacement ID for an image style being deleted.
   *
   * The method stores a replacement style to be used by the configuration
   * dependency system when a image style is deleted. The replacement style is
   * replacing the deleted style in other configuration entities that are
   * depending on the image style being deleted.
   *
   * @param string $name
   *   The ID of the image style to be deleted.
   * @param string $replacement
   *   The ID of the image style used as replacement.
   */
  public function setReplacementId($name, $replacement);

  /**
   * Retrieves the replacement ID of a deleted image style.
   *
   * The method is retrieving the value stored by ::setReplacementId().
   *
   * @param string $name
   *   The ID of the image style to be replaced.
   *
   * @return string|null
   *   The ID of the image style used as replacement, if there's any, or NULL.
   *
   * @see \Drupal\image\ImageStyleStorageInterface::setReplacementId()
   */
  public function getReplacementId($name);

  /**
   * Clears a replacement ID from the storage.
   *
   * The method clears the value previously stored with ::setReplacementId().
   *
   * @param string $name
   *   The ID of the image style to be replaced.
   *
   * @see \Drupal\image\ImageStyleStorageInterface::setReplacementId()
   */
  public function clearReplacementId($name);

}
