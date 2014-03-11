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
   * Returns the extension of the image file.
   *
   * @return string
   *   The extension of the file, or an empty string if the file is invalid.
   */
  public function getExtension();

  /**
   * Returns the height of the image file.
   *
   * @return int
   *   The height of the file, or 0 if the file is invalid.
   */
  public function getHeight();

  /**
   * Sets the height of the image file.
   *
   * @param int $height
   *
   * @return self
   *   Returns this image file.
   */
  public function setHeight($height);

  /**
   * Returns the width of the image file.
   *
   * @return int
   *   The width of the file, or 0 if the file is invalid.
   */
  public function getWidth();

  /**
   * Sets the width of the image file.
   *
   * @param int $width
   *
   * @return self
   *   Returns this image file.
   */
  public function setWidth($width);

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
   *   The MIME type of the file, or an empty string if the file is invalid.
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
   * @return string
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
