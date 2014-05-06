<?php

/**
 * @file
 * Contains \Drupal\editor\EditorInterface.
 */

namespace Drupal\editor;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface defining a text editor entity.
 */
interface EditorInterface extends ConfigEntityInterface {

  /**
   * Returns the filter format this text editor is associated with.
   *
   * @return \Drupal\filter\FilterFormatInterface
   */
  public function getFilterFormat();

  /**
   * Returns the associated text editor plugin ID.
   *
   * @return string
   *   The text editor plugin ID.
   */
  public function getEditor();

  /**
   * Returns the text editor plugin-specific settings.
   *
   * @return array
   *   A structured array containing all text editor settings.
   */
  public function getSettings();

  /**
   * Sets the text editor plugin-specific settings.
   *
   * @param array $settings
   *   The structured array containing all text editor settings.
   *
   * @return $this
   */
  public function setSettings(array $settings);

  /**
   * Returns the image upload settings.
   *
   * @return array
   *   A structured array containing image upload settings.
   */
  public function getImageUploadSettings();

  /**
   * Sets the image upload settings.
   *
   * @param array $image_upload
   *   The structured array containing image upload settings.
   *
   * @return $this
   */
  public function setImageUploadSettings(array $image_upload);

}
