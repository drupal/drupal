<?php

/**
 * @file
 * API for manipulating images.
 */

use Drupal\system\Plugin\ImageToolkitInterface;
use Drupal\Component\Image\Image;

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
 * \Drupal\system\Plugin\ImageToolkitManager. The toolkit must then be enabled
 * using the admin/config/media/image-toolkit form.
 *
 * Only one toolkit may be selected at a time. If a module author wishes to call
 * a specific toolkit they can check that it is installed by calling
 * \Drupal\system\Plugin\ImageToolkitManager::getAvailableToolkits(), and then
 * calling its functions directly.
 */

/**
 * Gets details about an image.
 *
 * Drupal supports GIF, JPG and PNG file formats when used with the GD
 * toolkit, and may support others, depending on which toolkits are
 * installed.
 *
 * @param string $filepath
 *   String specifying the path of the image file.
 * @param \Drupal\system\Plugin\ImageToolkitInterface $toolkit
 *   (optional) An image toolkit object to override the default.
 *
 * @return array
 *   FALSE, if the file could not be found or is not an image. Otherwise, a
 *   keyed array containing information about the image:
 *   - "width": Width, in pixels.
 *   - "height": Height, in pixels.
 *   - "extension": Commonly used file extension for the image.
 *   - "mime_type": MIME type ('image/jpeg', 'image/gif', 'image/png').
 *   - "file_size": File size in bytes.
 */
function image_get_info($filepath, ImageToolkitInterface $toolkit = NULL) {
  $details = FALSE;
  if (!is_file($filepath) && !is_uploaded_file($filepath)) {
    return $details;
  }

  if ($toolkit === NULL) {
    $toolkit = Drupal::service('image.toolkit');
  }
  if ($toolkit) {
    $image = new stdClass();
    $image->source = $filepath;
    $image->toolkit = $toolkit;
    $details = $toolkit->getInfo($image);
    if (isset($details) && is_array($details)) {
      $details['file_size'] = filesize($filepath);
    }
  }

  return $details;
}

/**
 * Scales an image to the exact width and height given.
 *
 * This function achieves the target aspect ratio by cropping the original image
 * equally on both sides, or equally on the top and bottom. This function is
 * useful to create uniform sized avatars from larger images.
 *
 * The resulting image always has the exact target dimensions.
 *
 * @param object $image
 *   An image object returned by image_load().
 * @param int $width
 *   The target width, in pixels.
 * @param int $height
 *   The target height, in pixels.
 *
 * @return bool
 *   TRUE on success, FALSE on failure.
 *
 * @see image_load()
 * @see image_resize()
 * @see image_crop()
 */
function image_scale_and_crop($image, $width, $height) {
  $scale = max($width / $image->info['width'], $height / $image->info['height']);
  $x = ($image->info['width'] * $scale - $width) / 2;
  $y = ($image->info['height'] * $scale - $height) / 2;

  if (image_resize($image, $image->info['width'] * $scale, $image->info['height'] * $scale)) {
    return image_crop($image, $x, $y, $width, $height);
  }
  return FALSE;
}

/**
 * Scales image dimensions while maintaining aspect ratio.
 *
 * @deprecated as of Drupal 8.0. Use
 *   \Drupal\Component\Image\Image::scaleDimensions() directly instead.
 *
 * @see image_scale()
 */
function image_dimensions_scale(array &$dimensions, $width = NULL, $height = NULL, $upscale = FALSE) {
  return Image::scaleDimensions($dimensions, $width, $height, $upscale);
}

/**
 * Scales an image while maintaining aspect ratio.
 *
 * The resulting image can be smaller for one or both target dimensions.
 *
 * @param object $image
 *   An image object returned by image_load().
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
 *
 * @see image_dimensions_scale()
 * @see image_load()
 * @see image_scale_and_crop()
 */
function image_scale($image, $width = NULL, $height = NULL, $upscale = FALSE) {
  $dimensions = $image->info;

  // Scale the dimensions - if they don't change then just return success.
  if (!image_dimensions_scale($dimensions, $width, $height, $upscale)) {
    return TRUE;
  }

  return image_resize($image, $dimensions['width'], $dimensions['height']);
}

/**
 * Resizes an image to the given dimensions (ignoring aspect ratio).
 *
 * @param object $image
 *   An image object returned by image_load().
 * @param int $width
 *   The target width, in pixels.
 * @param int $height
 *   The target height, in pixels.
 *
 * @return bool
 *   TRUE on success, FALSE on failure.
 *
 * @see image_load()
 * @see \Drupal\system\Plugin\ImageToolkitInterface::resize()
 */
function image_resize($image, $width, $height) {
  $width = (int) round($width);
  $height = (int) round($height);

  return $image->toolkit->resize($image, $width, $height);
}

/**
 * Rotates an image by the given number of degrees.
 *
 * @param $image
 *   An image object returned by image_load().
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
 * @see image_load()
 * @see \Drupal\system\Plugin\ImageToolkitInterface::rotate()
 */
function image_rotate($image, $degrees, $background = NULL) {
  return $image->toolkit->rotate($image, $degrees, $background);
}

/**
 * Crops an image to a rectangle specified by the given dimensions.
 *
 * @param $image
 *   An image object returned by image_load().
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
 * @see image_load()
 * @see image_scale_and_crop()
 * @see \Drupal\system\Plugin\ImageToolkitInterface::crop()
 */
function image_crop($image, $x, $y, $width, $height) {
  $aspect = $image->info['height'] / $image->info['width'];
  if (empty($height)) $height = $width / $aspect;
  if (empty($width)) $width = $height * $aspect;

  $width = (int) round($width);
  $height = (int) round($height);

  return $image->toolkit->crop($image, $x, $y, $width, $height);
}

/**
 * Converts an image to grayscale.
 *
 * @param $image
 *   An image object returned by image_load().
 *
 * @return bool
 *   TRUE on success, FALSE on failure.
 *
 * @see image_load()
 * @see \Drupal\system\Plugin\ImageToolkitInterface::desaturate()
 */
function image_desaturate($image) {
  return $image->toolkit->desaturate($image);
}

/**
 * Loads an image file and returns an image object.
 *
 * Any changes to the file are not saved until image_save() is called.
 *
 * @param string $file
 *   Path to an image file.
 * @param \Drupal\system\Plugin\ImageToolkitInterface $toolkit
 *   (optional) Image toolkit object to override the default.
 *
 * @return object
 *   An image object or FALSE if there was a problem loading the file. The
 *   image object has the following properties:
 *    - 'source' - The original file path.
 *    - 'info' - The array of information returned by image_get_info()
 *    - 'toolkit' - The name of the image toolkit requested when the image was
 *      loaded.
 *   Image toolkits may add additional properties. The caller is advised not to
 *   monkey about with them.
 *
 * @see image_save()
 * @see image_get_info()
 */
function image_load($file, ImageToolkitInterface $toolkit = NULL) {
  if ($toolkit === NULL) {
    $toolkit = Drupal::service('image.toolkit');
  }
  if ($toolkit) {
    $image = new stdClass();
    $image->source = $file;
    $image->info = image_get_info($file, $toolkit);
    if (isset($image->info) && is_array($image->info)) {
      $image->toolkit = $toolkit;
      if ($toolkit->load($image)) {
        return $image;
      }
    }
  }
  return FALSE;
}

/**
 * Closes the image and saves the changes to a file.
 *
 * @param object $image
 *   An image object returned by image_load(). The object's 'info' property
 *   will be updated if the file is saved successfully.
 * @param $destination
 *   (optional) Destination path where the image should be saved. If it is empty
 *   the original image file will be overwritten.
 *
 * @return bool
 *   TRUE on success, FALSE on failure.
 *
 * @see image_load()
 * @see \Drupal\system\Plugin\ImageToolkitInterface::save()
 */
function image_save($image, $destination = NULL) {
  if (empty($destination)) {
    $destination = $image->source;
  }
  if ($return = $image->toolkit->save($image, $destination)) {
    // Clear the cached file size and refresh the image information.
    clearstatcache(TRUE, $destination);
    $image->info = image_get_info($destination, $image->toolkit);

    if (drupal_chmod($destination)) {
      return $return;
    }
  }
  return FALSE;
}

/**
 * @} End of "defgroup image".
 */
