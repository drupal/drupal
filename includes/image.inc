<?php

/**
 * @file
 * API for manipulating images.
 */

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
 * Image toolkits are discovered based on the associated module's
 * hook_image_toolkits. Additionally the image toolkit include file
 * must be identified in the files array in the module.info file. The
 * toolkit must then be enabled using the admin/config/media/image-toolkit
 * form.
 *
 * Only one toolkit may be selected at a time. If a module author wishes to call
 * a specific toolkit they can check that it is installed by calling
 * image_get_available_toolkits(), and then calling its functions directly.
 */

/**
 * Gets a list of available toolkits.
 *
 * @return
 *   An array with the toolkit names as keys and the descriptions as values.
 */
function image_get_available_toolkits() {
  // hook_image_toolkits returns an array of toolkit names.
  $toolkits = module_invoke_all('image_toolkits');

  $output = array();
  foreach ($toolkits as $name => $info) {
    // Only allow modules that aren't marked as unavailable.
    if ($info['available']) {
      $output[$name] = $info['title'];
    }
  }

  return $output;
}

/**
 * Gets the name of the currently used toolkit.
 *
 * @return
 *   String containing the name of the selected toolkit, or FALSE on error.
 */
function image_get_toolkit() {
  static $toolkit;

  if (!isset($toolkit)) {
    $toolkits = image_get_available_toolkits();
    $toolkit = variable_get('image_toolkit', 'gd');
    if (!isset($toolkits[$toolkit]) || !function_exists('image_' . $toolkit . '_load')) {
      // The selected toolkit isn't available so return the first one found. If
      // none are available this will return FALSE.
      reset($toolkits);
      $toolkit = key($toolkits);
    }
  }

  return $toolkit;
}

/**
 * Invokes the given method using the currently selected toolkit.
 *
 * @param $method
 *   A string containing the method to invoke.
 * @param $image
 *   An image object returned by image_load().
 * @param $params
 *   An optional array of parameters to pass to the toolkit method.
 *
 * @return
 *   Mixed values (typically Boolean indicating successful operation).
 */
function image_toolkit_invoke($method, stdClass $image, array $params = array()) {
  $function = 'image_' . $image->toolkit . '_' . $method;
  if (function_exists($function)) {
    array_unshift($params, $image);
    return call_user_func_array($function, $params);
  }
  watchdog('image', 'The selected image handling toolkit %toolkit can not correctly process %function.', array('%toolkit' => $image->toolkit, '%function' => $function), WATCHDOG_ERROR);
  return FALSE;
}

/**
 * Gets details about an image.
 *
 * Drupal supports GIF, JPG and PNG file formats when used with the GD
 * toolkit, and may support others, depending on which toolkits are
 * installed.
 *
 * @param $filepath
 *   String specifying the path of the image file.
 * @param $toolkit
 *   An optional image toolkit name to override the default.
 *
 * @return
 *   FALSE, if the file could not be found or is not an image. Otherwise, a
 *   keyed array containing information about the image:
 *   - "width": Width, in pixels.
 *   - "height": Height, in pixels.
 *   - "extension": Commonly used file extension for the image.
 *   - "mime_type": MIME type ('image/jpeg', 'image/gif', 'image/png').
 *   - "file_size": File size in bytes.
 */
function image_get_info($filepath, $toolkit = FALSE) {
  $details = FALSE;
  if (!is_file($filepath) && !is_uploaded_file($filepath)) {
    return $details;
  }

  if (!$toolkit) {
    $toolkit = image_get_toolkit();
  }
  if ($toolkit) {
    $image = new stdClass();
    $image->source = $filepath;
    $image->toolkit = $toolkit;
    $details = image_toolkit_invoke('get_info', $image);
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
 * @param $image
 *   An image object returned by image_load().
 * @param $width
 *   The target width, in pixels.
 * @param $height
 *   The target height, in pixels.
 *
 * @return
 *   TRUE on success, FALSE on failure.
 *
 * @see image_load()
 * @see image_resize()
 * @see image_crop()
 */
function image_scale_and_crop(stdClass $image, $width, $height) {
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
 * The resulting dimensions can be smaller for one or both target dimensions.
 *
 * @param $dimensions
 *   Dimensions to be modified - an array with components width and height, in
 *   pixels.
 * @param $width
 *   The target width, in pixels. If this value is NULL then the scaling will be
 *   based only on the height value.
 * @param $height
 *   The target height, in pixels. If this value is NULL then the scaling will
 *   be based only on the width value.
 * @param $upscale
 *   Boolean indicating that images smaller than the target dimensions will be
 *   scaled up. This generally results in a low quality image.
 *
 * @return
 *   TRUE if $dimensions was modified, FALSE otherwise.
 *
 * @see image_scale()
 */
function image_dimensions_scale(array &$dimensions, $width = NULL, $height = NULL, $upscale = FALSE) {
  $aspect = $dimensions['height'] / $dimensions['width'];

  // Calculate one of the dimensions from the other target dimension,
  // ensuring the same aspect ratio as the source dimensions. If one of the
  // target dimensions is missing, that is the one that is calculated. If both
  // are specified then the dimension calculated is the one that would not be
  // calculated to be bigger than its target.
  if (($width && !$height) || ($width && $height && $aspect < $height / $width)) {
    $height = (int) round($width * $aspect);
  }
  else {
    $width = (int) round($height / $aspect);
  }

  // Don't upscale if the option isn't enabled.
  if (!$upscale && ($width >= $dimensions['width'] || $height >= $dimensions['height'])) {
    return FALSE;
  }

  $dimensions['width'] = $width;
  $dimensions['height'] = $height;
  return TRUE;
}

/**
 * Scales an image while maintaining aspect ratio.
 *
 * The resulting image can be smaller for one or both target dimensions.
 *
 * @param $image
 *   An image object returned by image_load().
 * @param $width
 *   The target width, in pixels. This value is omitted then the scaling will
 *   based only on the height value.
 * @param $height
 *   The target height, in pixels. This value is omitted then the scaling will
 *   based only on the width value.
 * @param $upscale
 *   Boolean indicating that files smaller than the dimensions will be scaled
 *   up. This generally results in a low quality image.
 *
 * @return
 *   TRUE on success, FALSE on failure.
 *
 * @see image_dimensions_scale()
 * @see image_load()
 * @see image_scale_and_crop()
 */
function image_scale(stdClass $image, $width = NULL, $height = NULL, $upscale = FALSE) {
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
 * @param $image
 *   An image object returned by image_load().
 * @param $width
 *   The target width, in pixels.
 * @param $height
 *   The target height, in pixels.
 *
 * @return
 *   TRUE on success, FALSE on failure.
 *
 * @see image_load()
 * @see image_gd_resize()
 */
function image_resize(stdClass $image, $width, $height) {
  $width = (int) round($width);
  $height = (int) round($height);

  return image_toolkit_invoke('resize', $image, array($width, $height));
}

/**
 * Rotates an image by the given number of degrees.
 *
 * @param $image
 *   An image object returned by image_load().
 * @param $degrees
 *   The number of (clockwise) degrees to rotate the image.
 * @param $background
 *   An hexadecimal integer specifying the background color to use for the
 *   uncovered area of the image after the rotation. E.g. 0x000000 for black,
 *   0xff00ff for magenta, and 0xffffff for white. For images that support
 *   transparency, this will default to transparent. Otherwise it will
 *   be white.
 *
 * @return
 *   TRUE on success, FALSE on failure.
 *
 * @see image_load()
 * @see image_gd_rotate()
 */
function image_rotate(stdClass $image, $degrees, $background = NULL) {
  return image_toolkit_invoke('rotate', $image, array($degrees, $background));
}

/**
 * Crops an image to a rectangle specified by the given dimensions.
 *
 * @param $image
 *   An image object returned by image_load().
 * @param $x
 *   The top left coordinate, in pixels, of the crop area (x axis value).
 * @param $y
 *   The top left coordinate, in pixels, of the crop area (y axis value).
 * @param $width
 *   The target width, in pixels.
 * @param $height
 *   The target height, in pixels.
 *
 * @return
 *   TRUE on success, FALSE on failure.
 *
 * @see image_load()
 * @see image_scale_and_crop()
 * @see image_gd_crop()
 */
function image_crop(stdClass $image, $x, $y, $width, $height) {
  $aspect = $image->info['height'] / $image->info['width'];
  if (empty($height)) $height = $width / $aspect;
  if (empty($width)) $width = $height * $aspect;

  $width = (int) round($width);
  $height = (int) round($height);

  return image_toolkit_invoke('crop', $image, array($x, $y, $width, $height));
}

/**
 * Converts an image to grayscale.
 *
 * @param $image
 *   An image object returned by image_load().
 *
 * @return
 *   TRUE on success, FALSE on failure.
 *
 * @see image_load()
 * @see image_gd_desaturate()
 */
function image_desaturate(stdClass $image) {
  return image_toolkit_invoke('desaturate', $image);
}

/**
 * Loads an image file and returns an image object.
 *
 * Any changes to the file are not saved until image_save() is called.
 *
 * @param $file
 *   Path to an image file.
 * @param $toolkit
 *   An optional, image toolkit name to override the default.
 *
 * @return
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
 * @see image_get_available_toolkits()
 * @see image_gd_load()
 */
function image_load($file, $toolkit = FALSE) {
  if (!$toolkit) {
    $toolkit = image_get_toolkit();
  }
  if ($toolkit) {
    $image = new stdClass();
    $image->source = $file;
    $image->info = image_get_info($file, $toolkit);
    if (isset($image->info) && is_array($image->info)) {
      $image->toolkit = $toolkit;
      if (image_toolkit_invoke('load', $image)) {
        return $image;
      }
    }
  }
  return FALSE;
}

/**
 * Closes the image and saves the changes to a file.
 *
 * @param $image
 *   An image object returned by image_load(). The object's 'info' property
 *   will be updated if the file is saved successfully.
 * @param $destination
 *   Destination path where the image should be saved. If it is empty the
 *   original image file will be overwritten.
 *
 * @return
 *   TRUE on success, FALSE on failure.
 *
 * @see image_load()
 * @see image_gd_save()
 */
function image_save(stdClass $image, $destination = NULL) {
  if (empty($destination)) {
    $destination = $image->source;
  }
  if ($return = image_toolkit_invoke('save', $image, array($destination))) {
    // Clear the cached file size and refresh the image information.
    clearstatcache();
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
