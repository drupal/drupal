<?php
// $Id: image.gd.inc,v 1.4 2009/03/09 11:44:54 dries Exp $

/**
 * @file
 * GD2 toolkit for image manipulation within Drupal.
 */

/**
 * @ingroup image
 * @{
 */

/**
 * Retrieve settings for the GD2 toolkit.
 */
function image_gd_settings() {
  if (image_gd_check_settings()) {
    $form = array();
    $form['status'] = array(
      '#markup' => t('The GD toolkit is installed and working properly.')
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
    $form['#element_validate'] = array('image_gd_settings_validate');

    return $form;
  }
  else {
    form_set_error('image_toolkit', t('The GD image toolkit requires that the GD module for PHP be installed and configured properly. For more information see <a href="@url">PHP\'s image documentation</a>.', array('@url' => 'http://php.net/image')));
    return FALSE;
  }
}

/**
 * Validate the submitted GD settings.
 */
function image_gd_settings_validate($form, &$form_state) {
  // Validate image quality range.
  $value = $form_state['values']['image_jpeg_quality'];
  if (!is_numeric($value) || $value < 0 || $value > 100) {
    form_set_error('image_jpeg_quality', t('JPEG quality must be a number between 0 and 100.'));
  }
}

/**
 * Verify GD2 settings (that the right version is actually installed).
 *
 * @return
 *   A boolean indicating if the GD toolkit is available on this machine.
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
 *
 * @param $image
 *   An image object. The $image->resource, $image->info['width'], and
 *   $image->info['height'] values will be modified by this call.
 * @param $width
 *   The new width of the resized image, in pixels.
 * @param $height
 *   The new height of the resized image, in pixels.
 * @return
 *   TRUE or FALSE, based on success.
 *
 * @see image_resize()
 */
function image_gd_resize(stdClass $image, $width, $height) {
  $res = image_gd_create_tmp($image, $width, $height);

  if (!imagecopyresampled($res, $image->resource, 0, 0, 0, 0, $width, $height, $image->info['width'], $image->info['height'])) {
    return FALSE;
  }

  imagedestroy($image->resource);
  // Update image object.
  $image->resource = $res;
  $image->info['width'] = $width;
  $image->info['height'] = $height;
  return TRUE;
}

/**
 * Crop an image using the GD toolkit.
 *
 * @param $image
 *   An image object. The $image->resource, $image->info['width'], and
 *   $image->info['height'] values will be modified by this call.
 * @param $x
 *   The starting x offset at which to start the crop, in pixels.
 * @param $y
 *   The starting y offset at which to start the crop, in pixels.
 * @param $width
 *   The width of the cropped area, in pixels.
 * @param $height
 *   The height of the cropped area, in pixels.
 * @return
 *   TRUE or FALSE, based on success.
 *
 * @see image_crop()
 */
function image_gd_crop(stdClass $image, $x, $y, $width, $height) {
  $res = image_gd_create_tmp($image, $width, $height);

  if (!imagecopyresampled($res, $image->resource, 0, 0, $x, $y, $width, $height, $width, $height)) {
    return FALSE;
  }

  // Destroy the original image and return the modified image.
  imagedestroy($image->resource);
  $image->resource = $res;
  $image->info['width'] = $width;
  $image->info['height'] = $height;
  return TRUE;
}

/**
 * Convert an image resource to grayscale.
 *
 * Note that transparent GIFs loose transparency when desaturated.
 *
 * @param $image
 *   An image object. The $image->resource value will be modified by this call.
 * @return
 *   TRUE or FALSE, based on success.
 *
 * @see image_desaturate()
 */
function image_gd_desaturate(stdClass $image) {
  return imagefilter($image->resource, IMG_FILTER_GRAYSCALE);
}

/**
 * GD helper function to create an image resource from a file.
 *
 * @param $image
 *   An image object. The $image->resource value will populated by this call.
 * @return
 *   TRUE or FALSE, based on success.
 *
 * @see image_load()
 */
function image_gd_load(stdClass $image) {
  $extension = str_replace('jpg', 'jpeg', $image->info['extension']);
  $function = 'imagecreatefrom'. $extension;
  return (function_exists($function) && $image->resource = $function($image->source));
}

/**
 * GD helper to write an image resource to a destination file.
 *
 * @param $image
 *   An image object.
 * @param $destination
 *   A string file path where the image should be saved.
 * @param $extension
 *   A string containing one of the following extensions: gif, jpg, jpeg, png.
 * @return
 *   TRUE or FALSE, based on success.
 *
 * @see image_save()
 */
function image_gd_save(stdClass $image, $destination) {
  $extension = str_replace('jpg', 'jpeg', $image->info['extension']);
  $function = 'image'. $extension;
  if (!function_exists($function)) {
    return FALSE;
  }
  if ($extension == 'jpeg') {
    return $function($image->resource, $destination, variable_get('image_jpeg_quality', 75));
  }
  else {
    // Always save PNG images with full transparency.
    if ($extension == 'png') {
      imagealphablending($image->resource, FALSE);
      imagesavealpha($image->resource, TRUE);
    }
    return $function($image->resource, $destination);
  }
}

/**
 * Create a truecolor image preserving transparency from a provided image.
 *
 * @param $image
 *   An image object.
 * @param $width
 *   The new width of the new image, in pixels.
 * @param $height
 *   The new height of the new image, in pixels.
 * @return
 *   A GD image handle.
 */
function image_gd_create_tmp(stdClass $image, $width, $height) {
  $res = imagecreatetruecolor($width, $height);

  if ($image->info['extension'] == 'gif') {
    // Grab transparent color index from image resource.
    $transparent = imagecolortransparent($image->resource);

    if ($transparent >= 0) {
      // The original must have a transparent color, allocate to the new image.
      $transparent_color = imagecolorsforindex($image->resource, $transparent);
      $transparent = imagecolorallocate($res, $transparent_color['red'], $transparent_color['green'], $transparent_color['blue']);

      // Flood with our new transparent color.
      imagefill($res, 0, 0, $transparent);
      imagecolortransparent($res, $transparent);
    }
  }
  elseif ($image->info['extension'] == 'png') {
    imagealphablending($res, FALSE);
    $transparency = imagecolorallocatealpha($res, 0, 0, 0, 127);
    imagefill($res, 0, 0, $transparency);
    imagealphablending($res, TRUE);
    imagesavealpha($res, TRUE);
  }
  else {
    imagefill($res, 0, 0, imagecolorallocate($res, 255, 255, 255));
  }

  return $res;
}

/**
 * @} End of "ingroup image".
 */
