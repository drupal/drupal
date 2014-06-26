<?php

/**
 * @file
 * Contains \Drupal\Core\ImageToolkit\ImageToolkitInterface.
 */

namespace Drupal\Core\ImageToolkit;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Image\ImageInterface;

/**
 * @defgroup image Image toolkits
 * @{
 * Functions for image file manipulations.
 *
 * Drupal's image toolkits provide an abstraction layer for common image file
 * manipulations like scaling, cropping, and rotating. The abstraction frees
 * module authors from the need to support multiple image libraries, and it
 * allows site administrators to choose the library that's best for them.
 *
 * PHP includes the GD library by default so a GD toolkit is installed with
 * Drupal. Other toolkits like ImageMagick are available from contrib modules.
 * GD works well for small images, but using it with larger files may cause PHP
 * to run out of memory. In contrast the ImageMagick library does not suffer
 * from this problem, but it requires the ISP to have installed additional
 * software.
 *
 * Image toolkits are discovered using the Plugin system using
 * \Drupal\Core\ImageToolkit\ImageToolkitManager. The toolkit must then be
 * enabled using the admin/config/media/image-toolkit form.
 *
 * Only one toolkit may be selected at a time. If a module author wishes to call
 * a specific toolkit they can check that it is installed by calling
 * \Drupal\Core\ImageToolkit\ImageToolkitManager::getAvailableToolkits(), and
 * then calling its functions directly.
 */

/**
 * Defines an interface for image toolkits.
 *
 * An image toolkit provides common image file manipulations like scaling,
 * cropping, and rotating.
 */
interface ImageToolkitInterface extends PluginInspectionInterface {

  /**
   * Retrieves the toolkit's settings form.
   *
   * @see system_image_toolkit_settings()
   */
  public function settingsForm();

  /**
   * Handles submissions for toolkit's settings form.
   *
   * @see system_image_toolkit_settings_submit()
   */
  public function settingsFormSubmit($form, &$form_state);

  /**
   * Sets the image object that this toolkit instance is tied to.
   *
   * @throws \BadMethodCallException
   *   When called twice.
   *
   * @param \Drupal\Core\Image\ImageInterface $image
   *   The image that this toolkit instance will be tied to.
   */
  public function setImage(ImageInterface $image);

  /**
   * Gets the image object that this toolkit instance is tied to.
   *
   * @return \Drupal\Core\Image\ImageInterface
   *   The image object that this toolkit instance is tied to.
   */
  public function getImage();

  /**
   * Scales an image to the specified size.
   *
   * @param int $width
   *   The new width of the resized image, in pixels.
   * @param int $height
   *   The new height of the resized image, in pixels.
   *
   * @return bool
   *   TRUE on success, FALSE on failure.
   */
  public function resize($width, $height);

  /**
   * Rotates an image the given number of degrees.
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
   */
  public function rotate($degrees, $background = NULL);

  /**
   * Crops an image.
   *
   * @param int $x
   *   The starting x offset at which to start the crop, in pixels.
   * @param int $y
   *   The starting y offset at which to start the crop, in pixels.
   * @param int $width
   *   The width of the cropped area, in pixels.
   * @param int $height
   *   The height of the cropped area, in pixels.
   *
   * @return bool
   *   TRUE on success, FALSE on failure.
   *
   * @see image_crop()
   */
  public function crop($x, $y, $width, $height);

  /**
   * Converts an image resource to grayscale.
   *
   * Note that transparent GIFs loose transparency when desaturated.
   *
   * @return bool
   *   TRUE on success, FALSE on failure.
   */
  public function desaturate();

  /**
   * Writes an image resource to a destination file.
   *
   * @param string $destination
   *   A string file URI or path where the image should be saved.
   *
   * @return bool
   *   TRUE on success, FALSE on failure.
   */
  public function save($destination);

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
   * Determines if a file contains a valid image.
   *
   * @return bool
   *   TRUE if the file could be found and is an image, FALSE otherwise.
   */
  public function parseFile();

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
   * Returns the MIME type of the image file.
   *
   * @return string
   *   The MIME type of the image file, or an empty string if the image is
   *   invalid.
   */
  public function getMimeType();

  /**
   * Gets toolkit requirements in a format suitable for hook_requirements().
   *
   * @return array
   *   An associative requirements array as is returned by hook_requirements().
   *   If the toolkit claims no requirements to the system, returns an empty
   *   array. The array can have arbitrary keys and they do not have to be
   *   prefixed by e.g. the module name or toolkit ID, as the system will make
   *   the keys globally unique.
   *
   * @see hook_requirements()
   */
  public function getRequirements();

  /**
   * Verifies that the Image Toolkit is set up correctly.
   *
   * @return bool
   *   TRUE if the toolkit is available on this machine, FALSE otherwise.
   */
  public static function isAvailable();

  /**
   * Returns a list of image file extensions supported by the toolkit.
   *
   * @return array
   *   An array of supported image file extensions (e.g. png/jpeg/gif).
   */
  public static function getSupportedExtensions();

}
