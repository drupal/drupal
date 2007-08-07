<?php
// $Id: image.gd.inc,v 1.2 2007/08/07 08:39:35 goba Exp $

/**
 * @file
 * GD2 toolkit for image manipulation within Drupal.
 */

/**
 * @ingroup image
 * @{
 */

/**
 * Retrieve information about the toolkit.
 */
function image_gd_info() {
  return array('name' => 'gd', 'title' => t('GD2 image manipulation toolkit'));
}

/**
 * Retrieve settings for the GD2 toolkit.
 */
function image_gd_settings() {
  if (image_gd_check_settings()) {
    $form = array();
    $form['status'] = array(
      '#value' => t('The GD toolkit is installed and working properly.')
    );

    $form['image_jpeg_quality'] = array(
      '#type' => 'textfield',
      '#title' => t('JPEG quality'),
      '#description' => t('Define the image quality for JPEG manipulations. Ranges from 0 to 100. Higher values mean better image quality but bigger files.'),
      '#size' => 10,
      '#maxlength' => 3,
      '#default_value' => variable_get('image_jpeg_quality', 75),
      '#field_suffix' => t('%'),
    );

    return $form;
  }
  else {
    form_set_error('image_toolkit', t('The GD image toolkit requires that the GD module for PHP be installed and configured properly. For more information see <a href="@url">PHP\'s image documentation</a>.', array('@url' => 'http://php.net/image')));
    return FALSE;
  }
}

/**
 * Verify GD2 settings (that the right version is actually installed).
 *
 * @return
 *   A boolean indicating if the GD toolkit is avaiable on this machine.
 */
function image_gd_check_settings() {
  if ($check = get_extension_funcs('gd')) {
    if (in_array('imagegd2', $check)) {
      // GD2 support is available.
      return TRUE;
    }
  }
  return FALSE;
}

/**
 * Scale an image to the specified size using GD.
 */
function image_gd_resize($source, $destination, $width, $height) {
  if (!file_exists($source)) {
    return FALSE;
  }

  $info = image_get_info($source);
  if (!$info) {
    return FALSE;
  }

  $im = image_gd_open($source, $info['extension']);
  if (!$im) {
    return FALSE;
  }

  $res = imageCreateTrueColor($width, $height);
  if ($info['extension'] == 'png') {
    $transparency = imagecolorallocatealpha($res, 0, 0, 0, 127);
    imagealphablending($res, FALSE);
    imagefilledrectangle($res, 0, 0, $width, $height, $transparency);
    imagealphablending($res, TRUE);
    imagesavealpha($res, TRUE);
  }
  imageCopyResampled($res, $im, 0, 0, 0, 0, $width, $height, $info['width'], $info['height']);
  $result = image_gd_close($res, $destination, $info['extension']);

  imageDestroy($res);
  imageDestroy($im);

  return $result;
}

/**
 * Rotate an image the given number of degrees.
 */
function image_gd_rotate($source, $destination, $degrees, $background = 0x000000) {
  if (!function_exists('imageRotate')) {
    return FALSE;
  }

  $info = image_get_info($source);
  if (!$info) {
    return FALSE;
  }

  $im = image_gd_open($source, $info['extension']);
  if (!$im) {
    return FALSE;
  }

  $res = imageRotate($im, $degrees, $background);
  $result = image_gd_close($res, $destination, $info['extension']);

  return $result;
}

/**
 * Crop an image using the GD toolkit.
 */
function image_gd_crop($source, $destination, $x, $y, $width, $height) {
  $info = image_get_info($source);
  if (!$info) {
    return FALSE;
  }

  $im = image_gd_open($source, $info['extension']);
  $res = imageCreateTrueColor($width, $height);
  imageCopy($res, $im, 0, 0, $x, $y, $width, $height);
  $result = image_gd_close($res, $destination, $info['extension']);

  imageDestroy($res);
  imageDestroy($im);

  return $result;
}

/**
 * GD helper function to create an image resource from a file.
 *
 * @param $file
 *   A string file path where the iamge should be saved.
 * @param $extension
 *   A string containing one of the following extensions: gif, jpg, jpeg, png.
 * @return
 *   An image resource, or FALSE on error.
 */
function image_gd_open($file, $extension) {
  $extension = str_replace('jpg', 'jpeg', $extension);
  $open_func = 'imageCreateFrom'. $extension;
  if (!function_exists($open_func)) {
    return FALSE;
  }
  return $open_func($file);
}

/**
 * GD helper to write an image resource to a destination file.
 *
 * @param $res
 *   An image resource created with image_gd_open().
 * @param $destination
 *   A string file path where the iamge should be saved.
 * @param $extension
 *   A string containing one of the following extensions: gif, jpg, jpeg, png.
 * @return
 *   Boolean indicating success.
 */
function image_gd_close($res, $destination, $extension) {
  $extension = str_replace('jpg', 'jpeg', $extension);
  $close_func = 'image'. $extension;
  if (!function_exists($close_func)) {
    return FALSE;
  }
  if ($extension == 'jpeg') {
    return $close_func($res, $destination, variable_get('image_jpeg_quality', 75));
  }
  else {
    return $close_func($res, $destination);
  }
}

/**
 * @} End of "ingroup image".
 */
