<?php

/**
 * @file
 * Contains \Drupal\system\Plugin\ImageToolkitInterface.
 */

namespace Drupal\system\Plugin;

/**
 * Defines an interface for image toolkits.
 *
 * An image toolkit provides common image file manipulations like scaling,
 * cropping, and rotating.
 */
interface ImageToolkitInterface {

  /**
   * Retrieves toolkit's settings form.
   *
   * @see system_image_toolkit_settings()
   */
  function settingsForm();

  /**
   * Handles submissions for toolkit's settings form.
   *
   * @see system_image_toolkit_settings_submit()
   */
  function settingsFormSubmit($form, &$form_state);

  /**
   * Scales an image to the specified size.
   *
   * @param object $image
   *   An image object. The $image->resource, $image->info['width'], and
   *   $image->info['height'] values will be modified by this call.
   * @param int $width
   *   The new width of the resized image, in pixels.
   * @param int $height
   *   The new height of the resized image, in pixels.
   *
   * @return bool
   *   TRUE or FALSE, based on success.
   *
   * @see image_resize()
   */
  function resize($image, $width, $height);

  /**
   * Rotates an image the given number of degrees.
   *
   * @param object $image
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
   *
   * @see image_rotate()
   */
  function rotate($image, $degrees, $background = NULL);

  /**
   * Crops an image.
   *
   * @param object $image
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
  function crop($image, $x, $y, $width, $height);

  /**
   * Converts an image resource to grayscale.
   *
   * Note that transparent GIFs loose transparency when desaturated.
   *
   * @param object $image
   *   An image object. The $image->resource value will be modified by this
   *   call.
   *
   * @return bool
   *   TRUE or FALSE, based on success.
   *
   * @see image_desaturate()
   */
  function desaturate($image);

  /**
   * Creates an image resource from a file.
   *
   * @param object $image
   *   An image object. The $image->resource value will populated by this call.
   *
   * @return bool
   *   TRUE or FALSE, based on success.
   *
   * @see image_load()
   */
  function load($image);

  /**
   * Writes an image resource to a destination file.
   *
   * @param object $image
   *   An image object.
   * @param string $destination
   *   A string file URI or path where the image should be saved.
   *
   * @return bool
   *   TRUE or FALSE, based on success.
   *
   * @see image_save()
   */
  function save($image, $destination);

  /**
   * Gets details about an image.
   *
   * @param object $image
   *   An image object.
   *
   * @return array
   *   FALSE, if the file could not be found or is not an image. Otherwise, a
   *   keyed array containing information about the image:
   *   - "width": Width, in pixels.
   *   - "height": Height, in pixels.
   *   - "extension": Commonly used file extension for the image.
   *   - "mime_type": MIME type ('image/jpeg', 'image/gif', 'image/png').
   *
   * @see image_get_info()
   */
  function getInfo($image);

  /**
   * Verifies Image Toolkit is set up correctly.
   *
   * @return bool
   *   True if the GD toolkit is available on this machine.
   */
  static function isAvailable();
}
