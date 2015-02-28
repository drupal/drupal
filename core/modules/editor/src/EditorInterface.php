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
   * Returns whether this text editor has an associated filter format.
   *
   * A text editor may be created at the same time as the filter format it's
   * going to be associated with; in that case, no filter format object is
   * available yet.
   *
   * @return bool
   */
  public function hasAssociatedFilterFormat();

  /**
   * Returns the filter format this text editor is associated with.
   *
   * This could be NULL if the associated filter format is still being created.
   * @see hasAssociatedFilterFormat()
   *
   * @return \Drupal\filter\FilterFormatInterface|null
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
   * Set the text editor plugin ID.
   *
   * @param string $editor
   *   The text editor plugin ID to set.
   */
  public function setEditor($editor);

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
