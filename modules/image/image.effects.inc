<?php
// $Id: image.effects.inc,v 1.3 2009/12/04 16:49:46 dries Exp $

/**
 * @file
 * Functions needed to execute image effects provided by Image module.
 */

/**
 * Implements hook_image_effect_info().
 */
function image_image_effect_info() {
  $effects = array(
    'image_resize' => array(
      'label' => t('Resize'),
      'help' => t('Resizing will make images an exact set of dimensions. This may cause images to be stretched or shrunk disproportionately.'),
      'effect callback' => 'image_resize_effect',
      'form callback' => 'image_resize_form',
      'summary theme' => 'image_resize_summary',
    ),
    'image_scale' => array(
      'label' => t('Scale'),
      'help' => t('Scaling will maintain the aspect-ratio of the original image. If only a single dimension is specified, the other dimension will be calculated.'),
      'effect callback' => 'image_scale_effect',
      'form callback' => 'image_scale_form',
      'summary theme' => 'image_scale_summary',
    ),
    'image_scale_and_crop' => array(
      'label' => t('Scale and crop'),
      'help' => t('Scale and crop will maintain the aspect-ratio of the original image, then crop the larger dimension. This is most useful for creating perfectly square thumbnails without stretching the image.'),
      'effect callback' => 'image_scale_and_crop_effect',
      'form callback' => 'image_resize_form',
      'summary theme' => 'image_resize_summary',
    ),
    'image_crop' => array(
      'label' => t('Crop'),
      'help' => t('Cropping will remove portions of an image to make it the specified dimensions.'),
      'effect callback' => 'image_crop_effect',
      'form callback' => 'image_crop_form',
      'summary theme' => 'image_crop_summary',
    ),
    'image_desaturate' => array(
      'label' => t('Desaturate'),
      'help' => t('Desaturate converts an image to grayscale.'),
      'effect callback' => 'image_desaturate_effect',
    ),
    'image_rotate' => array(
      'label' => t('Rotate'),
      'help' => t('Rotating an image may cause the dimensions of an image to increase to fit the diagonal.'),
      'effect callback' => 'image_rotate_effect',
      'form callback' => 'image_rotate_form',
      'summary theme' => 'image_rotate_summary',
    ),
  );

  return $effects;
}

/**
 * Image effect callback; Resize an image resource.
 *
 * @param $image
 *   An image object returned by image_load().
 * @param $data
 *   An array of attributes to use when performing the resize effect with the
 *   following items:
 *   - "width": An integer representing the desired width in pixels.
 *   - "height": An integer representing the desired height in pixels.
 * @return
 *   TRUE on success. FALSE on failure to resize image.
 * @see image_resize()
 */
function image_resize_effect(&$image, $data) {
  if (!image_resize($image, $data['width'], $data['height'])) {
    watchdog('image', 'Image resize failed using the %toolkit toolkit on %path (%mimetype, %dimensions)', array('%toolkit' => $image->toolkit, '%path' => $image->source, '%mimetype' => $image->info['mime_type'], '%dimensions' => $image->info['height'] . 'x' . $image->info['height']), WATCHDOG_ERROR);
    return FALSE;
  }
  return TRUE;
}

/**
 * Image effect callback; Scale an image resource.
 *
 * @param $image
 *   An image object returned by image_load().
 * @param $data
 *   An array of attributes to use when performing the scale effect with the
 *   following items:
 *   - "width": An integer representing the desired width in pixels.
 *   - "height": An integer representing the desired height in pixels.
 *   - "upscale": A Boolean indicating that the image should be upscalled if
 *     the dimensions are larger than the original image.
 * @return
 *   TRUE on success. FALSE on failure to scale image.
 * @see image_scale()
 */
function image_scale_effect(&$image, $data) {
  // Set sane default values.
  $data += array(
    'upscale' => FALSE,
  );

  // Set impossibly large values if the width and height aren't set.
  $data['width'] = empty($data['width']) ? PHP_INT_MAX : $data['width'];
  $data['height'] = empty($data['height']) ? PHP_INT_MAX : $data['height'];

  if (!image_scale($image, $data['width'], $data['height'], $data['upscale'])) {
    watchdog('image', 'Image scale failed using the %toolkit toolkit on %path (%mimetype, %dimensions)', array('%toolkit' => $image->toolkit, '%path' => $image->source, '%mimetype' => $image->info['mime_type'], '%dimensions' => $image->info['height'] . 'x' . $image->info['height']), WATCHDOG_ERROR);
    return FALSE;
  }
  return TRUE;
}

/**
 * Image effect callback; Crop an image resource.
 *
 * @param $image
 *   An image object returned by image_load().
 * @param $data
 *   An array of attributes to use when performing the crop effect with the
 *   following items:
 *   - "width": An integer representing the desired width in pixels.
 *   - "height": An integer representing the desired height in pixels.
 *   - "anchor": A string describing where the crop should originate in the form
 *     of "XOFFSET-YOFFSET". XOFFSET is either a number of pixels or
 *     "left", "center", "right" and YOFFSET is either a number of pixels or
 *     "top", "center", "bottom".
 * @return
 *   TRUE on success. FALSE on failure to crop image.
 * @see image_crop()
 */
function image_crop_effect(&$image, $data) {
  // Set sane default values.
  $data += array(
    'anchor' => 'center-center',
  );

  list($x, $y) = explode('-', $data['anchor']);
  $x = image_filter_keyword($x, $image->info['width'], $data['width']);
  $y = image_filter_keyword($y, $image->info['height'], $data['height']);
  if (!image_crop($image, $x, $y, $data['width'], $data['height'])) {
    watchdog('image', 'Image crop failed using the %toolkit toolkit on %path (%mimetype, %dimensions)', array('%toolkit' => $image->toolkit, '%path' => $image->source, '%mimetype' => $image->info['mime_type'], '%dimensions' => $image->info['height'] . 'x' . $image->info['height']), WATCHDOG_ERROR);
    return FALSE;
  }
  return TRUE;
}

/**
 * Image effect callback; Scale and crop an image resource.
 *
 * @param $image
 *   An image object returned by image_load().
 * @param $data
 *   An array of attributes to use when performing the scale and crop effect
 *   with the following items:
 *   - "width": An integer representing the desired width in pixels.
 *   - "height": An integer representing the desired height in pixels.
 * @return
 *   TRUE on success. FALSE on failure to scale and crop image.
 * @see image_scale_and_crop()
 */
function image_scale_and_crop_effect(&$image, $data) {
  if (!image_scale_and_crop($image, $data['width'], $data['height'])) {
    watchdog('image', 'Image scale and crop failed using the %toolkit toolkit on %path (%mimetype, %dimensions)', array('%toolkit' => $image->toolkit, '%path' => $image->source, '%mimetype' => $image->info['mime_type'], '%dimensions' => $image->info['height'] . 'x' . $image->info['height']), WATCHDOG_ERROR);
    return FALSE;
  }
  return TRUE;
}

/**
 * Image effect callback; Desaturate (grayscale) an image resource.
 *
 * @param $image
 *   An image object returned by image_load().
 * @param $data
 *   An array of attributes to use when performing the desaturate effect.
 * @return
 *   TRUE on success. FALSE on failure to desaturate image.
 * @see image_desaturate()
 */
function image_desaturate_effect(&$image, $data) {
  if (!image_desaturate($image)) {
    watchdog('image', 'Image desaturate failed using the %toolkit toolkit on %path (%mimetype, %dimensions)', array('%toolkit' => $image->toolkit, '%path' => $image->source, '%mimetype' => $image->info['mime_type'], '%dimensions' => $image->info['height'] . 'x' . $image->info['height']), WATCHDOG_ERROR);
    return FALSE;
  }
  return TRUE;
}

/**
 * Image effect callback; Rotate an image resource.
 *
 * @param $image
 *   An image object returned by image_load().
 * @param $data
 *   An array of attributes to use when performing the rotate effect containing
 *   the following items:
 *   - "degrees": The number of (clockwise) degrees to rotate the image.
 *   - "random": A Boolean indicating that a random rotation angle should be
 *     used for this image. The angle specified in "degrees" is used as a
 *     positive and negative maximum.
 *   - "bgcolor": The background color to use for exposed areas of the image.
 *     Use web-style hex colors (#FFFFFF for white, #000000 for black). Leave
 *     blank for transparency on image types that support it.
 * @return
 *   TRUE on success. FALSE on failure to rotate image.
 * @see image_rotate().
 */
function image_rotate_effect(&$image, $data) {
  // Set sane default values.
  $data += array(
    'degrees' => 0,
    'bgcolor' => NULL,
    'random' => FALSE,
  );

  // Convert short #FFF syntax to full #FFFFFF syntax.
  if (strlen($data['bgcolor']) == 4) {
    $c = $data['bgcolor'];
    $data['bgcolor'] = $c[0] . $c[1] . $c[1] . $c[2] . $c[2] . $c[3] . $c[3];
  }

  // Convert #FFFFFF syntax to hexadecimal colors.
  if ($data['bgcolor'] != '') {
    $data['bgcolor'] = hexdec(str_replace('#', '0x', $data['bgcolor']));
  }
  else {
    $data['bgcolor'] = NULL;
  }

  if (!empty($data['random'])) {
    $degrees = abs((float)$data['degrees']);
    $data['degrees'] = rand(-1 * $degrees, $degrees);
  }

  if (!image_rotate($image, $data['degrees'], $data['bgcolor'])) {
    watchdog('image', 'Image rotate failed using the %toolkit toolkit on %path (%mimetype, %dimensions)', array('%toolkit' => $image->toolkit, '%path' => $image->source, '%mimetype' => $image->info['mime_type'], '%dimensions' => $image->info['height'] . 'x' . $image->info['height']), WATCHDOG_ERROR);
    return FALSE;
  }
  return TRUE;
}
