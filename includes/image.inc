<?php
// $Id$

/**
 * Return a list of available toolkits.
 *
 * @return An array of toolkit name => descriptive title
 */
function image_get_available_toolkits() {
  $toolkits = file_scan_directory('includes', 'image\..*\.inc$');

  $output = array();
  foreach ($toolkits as $file => $toolkit) {
    include_once($file);
    $function = str_replace('.', '_', $toolkit->name) . '_info';
    if (function_exists($function)) {
      $info = $function();
      $output[$info['name']] = $info['title'];
    }
  }
  $output['gd'] = t('Built-in GD Toolkit');
  return $output;
}

/**
 * Retrieve the name of the currently used toolkit.
 *
 * @return String containing the name of the toolkit.
 */
function image_get_toolkit() {
  static $toolkit;
  if (!$toolkit) {
    $toolkit = variable_get('image_toolkit', 'gd');
    if ($toolkit != 'gd') {
      include_once 'includes/image.'.$toolkit.'.inc';
    }
  }

  return $toolkit;
}

/**
 * Invokes the given method using the currently selected toolkit.
 *
 * @param $method A string containing the method to invoke.
 * @param $params An optional array of parameters to pass to the toolkit method
 *
 * @return Mixed values (typically Boolean for successful operation)
 */
function image_toolkit_invoke($method, $params = array()) {
  $toolkit = image_get_toolkit();

  $function = 'image_' . $toolkit . '_'. $method;
  if (function_exists($function)) {
    return call_user_func_array($function, $params);
  }
  else {
    drupal_set_message(t('%method is not supported by %toolkit.', array('%method' => "<em>$method</em>", '%toolkit' => "<em>$toolkit</em>")));
  }
}


/**
 * Get details about an image.
 *
 * @return array containing information about the image
 *      'width': image's width in pixels
 *      'height': image's height in pixels
 *      'extension': commonly used extension for the image
 *      'mime_type': image's MIME type ('image/jpeg', 'image/gif', etc.)
 */
function image_get_info($file) {
  if (!file_exists($file)) {
    return false;
  }

  $details = false;
  $data = @getimagesize($file);

  if (is_array($data)) {
    $extensions = array('1' => 'gif', '2' => 'jpg', '3' => 'png');
    $extension = array_key_exists($data[2], $extensions) ?  $extensions[$data[2]] : '';
    $details = array('width'     => $data[0],
        'height'    => $data[1],
        'extension' => $extension,
        'mime_type' => $data['mime']);
  }

  return $details;
}

/**
 * Scales an image to the given width and height while maintaining aspect
 * ratio.
 *
 * @param $source         The filepath of the source image
 * @param $destination    The file path of the destination image
 * @param $width          The target width
 * @param $height         The target height
 *
 * @return True or false, based on success
 */
function image_scale($source, $destination, $width, $height) {
  $info = image_get_info($source);

  // don't scale up
  if ($width > $info['width'] && $height > $info['height']) {
    return false;
  }

  $aspect = $info['height'] / $info['width'];
  if ($aspect < $height / $width) {
    $width = (int)min($width, $info['width']);
    $height = (int)round($width * $aspect);
  } else {
    $height = (int)min($height, $info['height']);
    $width = (int)round($height / $aspect);
  }

  return image_toolkit_invoke('resize', array($source, $destination, $width, $height));
}

/**
 * Resize an image to the given dimensions (ignoring aspect ratio).
 *
 * @param $source        The filepath of the source image.
 * @param $destination   The file path of the destination image.
 * @param $width         The target width.
 * @param $height        The target height.
 */
function image_resize($source, $destination, $width, $height) {
  return image_toolkit_invoke('resize', array($source, $destination, $width, $height));
}

/**
 * Rotate an image by the given number of degrees.
 *
 * @param $source  The filepath of the source image
 * @param $destination    The file path of the destination image
 * @param $degrees The number of (clockwise) degrees to rotate the image
 */
function image_rotate($source, $destination, $degrees) {
  return image_toolkit_invoke('rotate', array($source, $destination, $degrees));
}

/**
 * Crop an image to the rectangle specified by the given rectangle.
 *
 * @param $source        The filepath of the source image
 * @param $destination   The file path of the destination image
 * @param $x             The top left co-ordinate of the crop area (x axis value)
 * @param $y             The top left co-ordinate of the crop area (y axis value)
 * @param $width         The target width
 * @param $height        The target height
 */
function image_crop($source, $destination, $x, $y, $width, $height) {
  return image_toolkit_invoke('crop', array($source, $destination, $x, $y, $width, $height));
}

/**
 * GD Toolkit functions
 */

/**
 * Verify GD settings (that the extension is actually installed).
 */
function image_gd_settings() {
  if (!extension_loaded('gd')) {
    drupal_set_message(t('Unable to load the GD toolkit'), 'error');
  }
}

/**
 * Scale an image to the specified size using GD.
 */
function image_gd_resize($source, $destination, $width, $height) {
  if (!file_exists($source)) {
    return false;
  }

  $info = image_get_info($source);
  if (!$info) {
    return false;
  }

  $im = image_gd_open($source, $info['extension']);
  if (!$im) {
    return false;
  }

  // GD1 doesn't have true color
  if (function_exists('imageCreateTrueColor')) {
    $res = imageCreateTrueColor($width, $height);
  }
  else {
    $res = imageCreate($width, $height);
  }

  // GD1 doesn't have copyResampled
  if (function_exists('imageCopyResampled')) {
    imageCopyResampled($res, $im, 0, 0, 0, 0, $width, $height, $info['width'], $info['height']);
  }
  else {
    imageCopyResized($res, $im, 0, 0, 0, 0, $width, $height, $info['width'], $info['height']);
  }
  $result = image_gd_close($res, $destination, $info['extension']);

  imageDestroy($res);
  imageDestroy($im);

  return $result;
}

/**
 * Rotate an image the given number of degrees.
 */
function image_gd_rotate($source, $destination, $degrees, $bg_color = 0) {
  if (!function_exists('imageRotate')) {
    return false;
  }

  $info = image_get_info($source);
  if (!$info) {
    return false;
  }

  $im = image_gd_open($source, $info['extension']);
  if (!$im) {
    return false;
  }

  $res = imageRotate($im, $degrees, $bg_color);

  $result = image_gd_close($res, $destination, $info['extension']);

  return $result;
}

/**
 * Crop an image using the GD toolkit.
 */
function image_gd_crop($source, $destination, $x, $y, $width, $height) {
  $info = image_get_info($source);
  if (!$info) {
    return false;
  }

  $im = image_gd_open($source, $info['extension']);

  // GD1 doesn't have true color
  if (function_exists('imageCreateTrueColor')) {
    $res = imageCreateTrueColor($width, $height);
  }
  else {
    $res = imageCreate($width, $height);
  }

  imageCopy($im, $res, 0, 0, $x, $y, $width, $height);

  $result = image_gd_close($res, $destination, $info['extension']);

  imageDestroy($res);
  imageDestroy($im);

  return $result;
}

/**
 * GD helper function to create an image resource from a file.
 */
function image_gd_open($file, $extension) {
  $extension = str_replace('jpg', 'jpeg', $extension);
  $open_func = 'imageCreateFrom'. $extension;
  if (!function_exists($open_func)) {
    return false;
  }
  return $open_func($file);
}

/**
 * GD helper to write an image resource to a destination file.
 */
function image_gd_close($res, $destination, $extension) {
  $extension = str_replace('jpg', 'jpeg', $extension);
  $close_func = 'image'. $extension;
  if (!function_exists($close_func)) {
    return false;
  }
  return $close_func($res, $destination);
}

?>
