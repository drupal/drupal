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
   * Checks if the image is valid.
   *
   * @return bool
   *   TRUE if the image object contains a valid image, FALSE otherwise.
   */
  public function isValid();

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
   * @return int|null
   *   The size of the file in bytes, or NULL if the image is invalid.
   */
  public function getFileSize();

  /**
   * Returns the MIME type of the image file.
   *
   * @return string
   *   The MIME type of the image file, or an empty string if the image is
   *   invalid.
   */
  public function getMimeType();

  /**
   * Retrieves the source path of the image file.
   *
   * @return string
   *   The source path of the image file. An empty string if the source is
   *   not set.
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
   * Applies a toolkit operation to the image.
   *
   * The operation is deferred to the active toolkit.
   *
   * @param string $operation
   *   The operation to be performed against the image.
   * @param array $arguments
   *   An associative array of arguments to be passed to the toolkit
   *   operation, e.g. array('width' => 50, 'height' => 100,
   *   'upscale' => TRUE).
   *
   * @return bool
   *   TRUE on success, FALSE on failure.
   */
  public function apply($operation, array $arguments = array());

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

  /**
   * Scales an image while maintaining aspect ratio.
   *
   * The resulting image can be smaller for one or both target dimensions.
   *
   * @param int|null $width
   *   The target width, in pixels. If this value is null then the scaling will
   *   be based only on the height value.
   * @param int|null $height
   *   (optional) The target height, in pixels. If this value is null then the
   *   scaling will be based only on the width value.
   * @param bool $upscale
   *   (optional) Boolean indicating that files smaller than the dimensions will
   *   be scaled up. This generally results in a low quality image.
   *
   * @return bool
   *   TRUE on success, FALSE on failure.
   */
  public function scale($width, $height = NULL, $upscale = FALSE);

  /**
   * Scales an image to the exact width and height given.
   *
   * This function achieves the target aspect ratio by cropping the original
   * image equally on both sides, or equally on the top and bottom. This
   * function is useful to create uniform sized avatars from larger images.
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
   */
  public function crop($x, $y, $width, $height = NULL);

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
   */
  public function resize($width, $height);

  /**
   * Converts an image to grayscale.
   *
   * @return bool
   *   TRUE on success, FALSE on failure.
   */
  public function desaturate();

  /**
   * Rotates an image by the given number of degrees.
   *
   * @param float $degrees
   *   The number of (clockwise) degrees to rotate the image.
   * @param string|null $background
   *   (optional) An hexadecimal integer specifying the background color to use
   *   for the uncovered area of the image after the rotation. E.g. 0x000000 for
   *   black, 0xff00ff for magenta, and 0xffffff for white. For images that
   *   support transparency, this will default to transparent. Otherwise it will
   *   be white.
   *
   * @return bool
   *   TRUE on success, FALSE on failure.
   */
  public function rotate($degrees, $background = NULL);
}
