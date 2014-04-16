<?php

/**
 * @file
 * Contains \Drupal\Core\Image\ImageInterface.
 */

namespace Drupal\Core\Image;

/**
 * Provides an interface for image objects.
 */
interface ImageInterface {

  /**
   * Checks if the image format is supported.
   *
   * @return bool
   *   Returns TRUE if the image format is supported by the toolkit.
   */
  public function isSupported();

  /**
   * Checks if the image is existing.
   *
   * @return bool
   *   TRUE if the image exists and is a valid image, FALSE otherwise.
   */
  public function isExisting();

  /**
   * Returns the height of the image.
   *
   * @return int|null
   *   The height of the image, or NULL if the image is invalid.
   */
  public function getHeight();

  /**
   * Returns the width of the image.
   *
   * @return int|null
   *   The width of the image, or NULL if the image is invalid.
   */
  public function getWidth();

  /**
   * Returns the size of the image file.
   *
   * @return int
   *   The size of the file in bytes, or 0 if the file is invalid.
   */
  public function getFileSize();

  /**
   * Returns the type of the image.
   *
   * @return int
   *   The image type represented by a PHP IMAGETYPE_* constant (e.g.
   *   IMAGETYPE_JPEG).
   */
  public function getType();

  /**
   * Returns the MIME type of the image file.
   *
   * @return string
   *   The MIME type of the image file, or an empty string if the image is
   *   invalid.
   */
  public function getMimeType();

  /**
   * Sets the source path of the image file.
   *
   * @param string $source
   *   A string specifying the path of the image file.
   *
   * @return self
   *   Returns this image file.
   */
  public function setSource($source);

  /**
   * Retrieves the source path of the image file.
   *
   * @return string
   *   The source path of the image file.
   */
  public function getSource();

  /**
   * Returns the image toolkit used for this image file.
   *
   * @return \Drupal\Core\ImageToolkit\ImageToolkitInterface
   *   The image toolkit.
   */
  public function getToolkit();

  /**
   * Returns the ID of the image toolkit used for this image file.
   *
   * @return string
   *   The ID of the image toolkit.
   */
  public function getToolkitId();

  /**
   * Closes the image and saves the changes to a file.
   *
   * @param string|null $destination
   *   (optional) Destination path where the image should be saved. If it is empty
   *   the original image file will be overwritten.
   *
   * @return bool
   *   TRUE on success, FALSE on failure.
   *
   * @see \Drupal\Core\ImageToolkit\ImageToolkitInterface::save()
   */
  public function save($destination = NULL);

}
