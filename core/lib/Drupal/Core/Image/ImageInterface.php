<?php

/**
 * @file
 * Contains Drupal\Core\Image\ImageInterface.
 */

namespace Drupal\Core\Image;

/**
 * Provides an interface for image objects.
 */
interface ImageInterface {

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
   * Returns the MIME type of the image file.
   *
   * @return string
   *   The MIME type of the file, or an empty string if the file is invalid.
   */
  public function getMimeType();

  /**
   * Sets the image file resource.
   *
   * @param resource $resource
   *   The image file handle.
   *
   * @return self
   *   Returns this image file.
   */
  public function setResource($resource);

  /**
   * Determines if this image file has a resource set.
   *
   * @return bool
   *   TRUE if this image file has a resource set, FALSE otherwise.
   */
  public function hasResource();

  /**
   * Retrieves the image file resource.
   *
   * @return resource
   *   The image file handle.
   */
  public function getResource();

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
   * @see \Drupal\system\Plugin\ImageToolkitInterface::save()
   */
  public function save($destination = NULL);

  /**
   * Scales an image while maintaining aspect ratio.
   *
   * The resulting image can be smaller for one or both target dimensions.
   *
   * @param int $width
   *   (optional) The target width, in pixels. This value is omitted then the
   *   scaling will based only on the height value.
   * @param int $height
   *   (optional) The target height, in pixels. This value is omitted then the
   *   scaling will based only on the width value.
   * @param bool $upscale
   *   (optional) Boolean indicating that files smaller than the dimensions will
   *   be scaled up. This generally results in a low quality image.
   *
   * @return bool
   *   TRUE on success, FALSE on failure.
   */
  public function scale($width = NULL, $height = NULL, $upscale = FALSE);

  /**
   * Scales an image to the exact width and height given.
   *
   * This function achieves the target aspect ratio by cropping the original image
   * equally on both sides, or equally on the top and bottom. This function is
   * useful to create uniform sized avatars from larger images.
   *
   * The resulting image always has the exact target dimensions.
   *
   * @param int $width
   *   The target width, in pixels.
   * @param int $height
   *   The target height, in pixels.
   *
   * @return bool
   *   TRUE on success, FALSE on failure.
   */
  public function scaleAndCrop($width, $height);

  /**
   * Crops an image to a rectangle specified by the given dimensions.
   *
   * @param int $x
   *   The top left coordinate, in pixels, of the crop area (x axis value).
   * @param int $y
   *   The top left coordinate, in pixels, of the crop area (y axis value).
   * @param int $width
   *   The target width, in pixels.
   * @param int $height
   *   The target height, in pixels.
   *
   * @return bool
   *   TRUE on success, FALSE on failure.
   *
   * @see \Drupal\system\Plugin\ImageToolkitInterface::crop()
   */
  public function crop($x, $y, $width, $height);

  /**
   * Resizes an image to the given dimensions (ignoring aspect ratio).
   *
   * @param int $width
   *   The target width, in pixels.
   * @param int $height
   *   The target height, in pixels.
   *
   * @return bool
   *   TRUE on success, FALSE on failure.
   *
   * @see \Drupal\system\Plugin\ImageToolkitInterface::resize()
   */
  public function resize($width, $height);

  /**
   * Converts an image to grayscale.
   *
   * @return bool
   *   TRUE on success, FALSE on failure.
   *
   * @see \Drupal\system\Plugin\ImageToolkitInterface::desaturate()
   */
  public function desaturate();

  /**
   * Rotates an image by the given number of degrees.
   *
   * @param int $degrees
   *   The number of (clockwise) degrees to rotate the image.
   * @param string $background
   *   (optional) An hexadecimal integer specifying the background color to use
   *   for the uncovered area of the image after the rotation. E.g. 0x000000 for
   *   black, 0xff00ff for magenta, and 0xffffff for white. For images that
   *   support transparency, this will default to transparent. Otherwise it will
   *   be white.
   *
   * @return bool
   *   TRUE on success, FALSE on failure.
   *
   * @see \Drupal\system\Plugin\ImageToolkitInterface::rotate()
   */
  public function rotate($degrees, $background = NULL);

}
