<?php
// $Id: image.inc,v 1.24 2008/01/28 16:05:17 goba Exp $

/**
 * @file
 * API for manipulating images.
 */

/**
 * @defgroup image Image toolkits
 * @{
 * Drupal's image toolkits provide an abstraction layer for common image file
 * manipulations like scaling, cropping, and rotating. The abstraction frees
 * module authors from the need to support multiple image libraries, and it
 * allows site administrators to choose the library that's best for them.
 *
 * PHP includes the GD library by default so a GD toolkit is installed with
 * Drupal. Other toolkits like ImageMagic are available from contrib modules.
 * GD works well for small images, but using it with larger files may cause PHP
 * to run out of memory. In contrast the ImageMagick library does not suffer
 * from this problem, but it requires the ISP to have installed additional
 * software.
 *
 * Image toolkits are installed by copying the image.ToolkitName.inc file into
 * Drupal's includes directory. The toolkit must then be enabled using the
 * admin/settings/image-toolkit form.
 *
 * Only one toolkit maybe selected at a time. If a module author wishes to call
 * a specific toolkit they can check that it is installed by calling
 * image_get_available_toolkits(), and then calling its functions directly.
 */

/**
 * Return a list of available toolkits.
 *
 * @return
 *   An array of toolkit name => descriptive title.
 */
function image_get_available_toolkits() {
  $toolkits = file_scan_directory('includes', 'image\..*\.inc$');

  $output = array();
  foreach ($toolkits as $file => $toolkit) {
    include_once "./$file";
    $function = str_replace('.', '_', $toolkit->name) .'_info';
    if (function_exists($function)) {
      $info = $function();
      $output[$info['name']] = $info['title'];
    }
  }

  return $output;
}

/**
 * Retrieve the name of the currently used toolkit.
 *
 * @return
 *   String containing the name of the selected toolkit, or FALSE on error.
 */
function image_get_toolkit() {
  static $toolkit;

  if (!$toolkit) {
    $toolkit = variable_get('image_toolkit', 'gd');
    $toolkit_file = './includes/image.'. $toolkit .'.inc';
    if (isset($toolkit) && file_exists($toolkit_file)) {
      include_once $toolkit_file;
    }
    elseif (!image_gd_check_settings()) {
      $toolkit = FALSE;
    }
  }

  return $toolkit;
}

/**
 * Invokes the given method using the currently selected toolkit.
 *
 * @param $method
 *   A string containing the method to invoke.
 * @param $params
 *   An optional array of parameters to pass to the toolkit method.
 * @return
 *   Mixed values (typically Boolean indicating successful operation).
 */
function image_toolkit_invoke($method, $params = array()) {
  if ($toolkit = image_get_toolkit()) {
    $function = 'image_'. $toolkit .'_'. $method;
    if (function_exists($function)) {
      return call_user_func_array($function, $params);
    }
    else {
      watchdog('php', 'The selected image handling toolkit %toolkit can not correctly process %function.', array('%toolkit' => $toolkit, '%function' => $function), WATCHDOG_ERROR);
      return FALSE;
    }
  }
}


/**
 * Get details about an image.
 *
 * Drupal only supports GIF, JPG and PNG file formats.
 *
 * @return
 *   FALSE, if the file could not be found or is not an image. Otherwise, a
 *   keyed array containing information about the image:
 *    'width'     - Width in pixels.
 *    'height'    - Height in pixels.
 *    'extension' - Commonly used file extension for the image.
 *    'mime_type' - MIME type ('image/jpeg', 'image/gif', 'image/png').
 *    'file_size' - File size in bytes.
 */
function image_get_info($file) {
  if (!is_file($file)) {
    return FALSE;
  }

  $details = FALSE;
  $data = @getimagesize($file);
  $file_size = @filesize($file);

  if (isset($data) && is_array($data)) {
    $extensions = array('1' => 'gif', '2' => 'jpg', '3' => 'png');
    $extension = array_key_exists($data[2], $extensions) ?  $extensions[$data[2]] : '';
    $details = array('width'     => $data[0],
                     'height'    => $data[1],
                     'extension' => $extension,
                     'file_size' => $file_size,
                     'mime_type' => $data['mime']);
  }

  return $details;
}

/**
 * Scales an image to the exact width and height given. Achieves the
 * target aspect ratio by cropping the original image equally on both
 * sides, or equally on the top and bottom.  This function is, for
 * example, useful to create uniform sized avatars from larger images.
 *
 * The resulting image always has the exact target dimensions.
 *
 * @param $source
 *   The file path of the source image.
 * @param $destination
 *   The file path of the destination image.
 * @param $width
 *   The target width, in pixels.
 * @param $height
 *   The target height, in pixels.
 * @return
 *   TRUE or FALSE, based on success.
 */
function image_scale_and_crop($source, $destination, $width, $height) {
  $info = image_get_info($source);

  $scale = max($width / $info['width'], $height / $info['height']);
  $x = round(($info['width'] * $scale - $width) / 2);
  $y = round(($info['height'] * $scale - $height) / 2);

  if (image_toolkit_invoke('resize', array($source, $destination, $info['width'] * $scale, $info['height'] * $scale))) {
    return image_toolkit_invoke('crop', array($destination, $destination, $x, $y, $width, $height));
  }
  return FALSE;
}

/**
 * Scales an image to the given width and height while maintaining aspect
 * ratio.
 *
 * The resulting image can be smaller for one or both target dimensions.
 *
 * @param $source
 *   The file path of the source image.
 * @param $destination
 *   The file path of the destination image.
 * @param $width
 *   The target width, in pixels.
 * @param $height
 *   The target height, in pixels.
 * @return
 *   TRUE or FALSE, based on success.
 */
function image_scale($source, $destination, $width, $height) {
  $info = image_get_info($source);

  // Don't scale up.
  if ($width >= $info['width'] && $height >= $info['height']) {
    return FALSE;
  }

  $aspect = $info['height'] / $info['width'];
  if ($aspect < $height / $width) {
    $width = (int)min($width, $info['width']);
    $height = (int)round($width * $aspect);
  }
  else {
    $height = (int)min($height, $info['height']);
    $width = (int)round($height / $aspect);
  }

  return image_toolkit_invoke('resize', array($source, $destination, $width, $height));
}

/**
 * Resize an image to the given dimensions (ignoring aspect ratio).
 *
 * @param $source
 *   The file path of the source image.
 * @param $destination
 *   The file path of the destination image.
 * @param $width
 *   The target width, in pixels.
 * @param $height
 *   The target height, in pixels.
  * @return
 *   TRUE or FALSE, based on success.
 */
function image_resize($source, $destination, $width, $height) {
  return image_toolkit_invoke('resize', array($source, $destination, $width, $height));
}

/**
 * Rotate an image by the given number of degrees.
 *
 * @param $source
 *   The file path of the source image.
 * @param $destination
 *   The file path of the destination image.
 * @param $degrees
 *   The number of (clockwise) degrees to rotate the image.
 * @param $background
 *   An hexidecimal integer specifying the background color to use for the
 *   uncovered area of the image after the rotation. E.g. 0x000000 for black,
 *   0xff00ff for magenta, and 0xffffff for white.
 * @return
 *   TRUE or FALSE, based on success.
 */
function image_rotate($source, $destination, $degrees, $background = 0x000000) {
  return image_toolkit_invoke('rotate', array($source, $destination, $degrees, $background));
}

/**
 * Crop an image to the rectangle specified by the given rectangle.
 *
 * @param $source
 *   The file path of the source image.
 * @param $destination
 *   The file path of the destination image.
 * @param $x
 *   The top left co-ordinate, in pixels, of the crop area (x axis value).
 * @param $y
 *   The top left co-ordinate, in pixels, of the crop area (y axis value).
 * @param $width
 *   The target width, in pixels.
 * @param $height
 *   The target height, in pixels.
 * @return
 *   TRUE or FALSE, based on success.
 */
function image_crop($source, $destination, $x, $y, $width, $height) {
  return image_toolkit_invoke('crop', array($source, $destination, $x, $y, $width, $height));
}

/**
 * @} End of "defgroup image".
 */
