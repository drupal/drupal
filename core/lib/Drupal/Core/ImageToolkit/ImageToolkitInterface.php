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
   * Retrieves toolkit's settings form.
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
   * Scales an image to the specified size.
   *
   * @param \Drupal\Core\Image\ImageInterface $image
   *   An image object. The $image->resource, $image->info['width'], and
   *   $image->info['height'] values will be modified by this call.
   * @param int $width
   *   The new width of the resized image, in pixels.
   * @param int $height
   *   The new height of the resized image, in pixels.
   *
   * @return bool
   *   TRUE or FALSE, based on success.
   */
  public function resize(ImageInterface $image, $width, $height);

  /**
   * Rotates an image the given number of degrees.
   *
   * @param \Drupal\Core\Image\ImageInterface $image
   *   An image object. The $image->resource, $image->info['width'], and
   *   $image->info['height'] values will be modified by this call.
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
   *   TRUE or FALSE, based on success.
   */
  public function rotate(ImageInterface $image, $degrees, $background = NULL);

  /**
   * Crops an image.
   *
   * @param \Drupal\Core\Image\ImageInterface $image
   *   An image object. The $image->resource, $image->info['width'], and
   *   $image->info['height'] values will be modified by this call.
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
   *   TRUE or FALSE, based on success.
   *
   * @see image_crop()
   */
  public function crop(ImageInterface $image, $x, $y, $width, $height);

  /**
   * Converts an image resource to grayscale.
   *
   * Note that transparent GIFs loose transparency when desaturated.
   *
   * @param \Drupal\Core\Image\ImageInterface $image
   *   An image object. The $image->resource value will be modified by this
   *   call.
   *
   * @return bool
   *   TRUE or FALSE, based on success.
   */
  public function desaturate(ImageInterface $image);

  /**
   * Writes an image resource to a destination file.
   *
   * @param \Drupal\Core\Image\ImageInterface $image
   *   An image object.
   * @param string $destination
   *   A string file URI or path where the image should be saved.
   *
   * @return bool
   *   TRUE or FALSE, based on success.
   */
  public function save(ImageInterface $image, $destination);

  /**
   * Scales an image while maintaining aspect ratio.
   *
   * The resulting image can be smaller for one or both target dimensions.
   *
   * @param \Drupal\Core\Image\ImageInterface $image
   *   An image object.
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
  public function scale(ImageInterface $image, $width = NULL, $height = NULL, $upscale = FALSE);

  /**
   * Scales an image to the exact width and height given.
   *
   * This function achieves the target aspect ratio by cropping the original
   * image equally on both sides, or equally on the top and bottom. This
   * function is useful to create uniform sized avatars from larger images.
   *
   * The resulting image always has the exact target dimensions.
   *
   * @param \Drupal\Core\Image\ImageInterface $image
   *   An image object.
   * @param int $width
   *   The target width, in pixels.
   * @param int $height
   *   The target height, in pixels.
   *
   * @return bool
   *   TRUE on success, FALSE on failure.
   */
  public function scaleAndCrop(ImageInterface $image, $width, $height);

  /**
   * Gets details about an image.
   *
   * @param \Drupal\Core\Image\ImageInterface $image
   *   An image object.
   *
   * @return array
   *   FALSE, if the file could not be found or is not an image. Otherwise, a
   *   keyed array containing information about the image:
   *   - "width": Width, in pixels.
   *   - "height": Height, in pixels.
   *   - "type": Image type represented as an IMAGETYPE_* constant.
   *   - "mime_type": MIME type (e.g. 'image/jpeg', 'image/gif', 'image/png').
   *
   * @see \Drupal\Core\Image\ImageInterface::processInfo()
   */
  public function getInfo(ImageInterface $image);

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
   * Verifies Image Toolkit is set up correctly.
   *
   * @return bool
   *   True if the GD toolkit is available on this machine.
   */
  public static function isAvailable();

  /**
   * Returns a list of image types supported by the toolkit.
   *
   * @return array
   *   An array of available image types. An image type is represented by a PHP
   *   IMAGETYPE_* constant (e.g. IMAGETYPE_JPEG, IMAGETYPE_PNG, etc.).
   */
  public static function supportedTypes();

}
