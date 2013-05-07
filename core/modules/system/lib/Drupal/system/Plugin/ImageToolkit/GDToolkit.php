<?php

/**
 * @file
 * Contains \Drupal\system\Plugin\ImageToolkit\GDToolkit;.
 */

namespace Drupal\system\Plugin\ImageToolkit;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Component\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;
use Drupal\system\Plugin\ImageToolkitInterface;

/**
 * Defines the GD2 toolkit for image manipulation within Drupal.
 *
 * @Plugin(
 *   id = "gd",
 *   title = @Translation("GD2 image manipulation toolkit")
 * )
 */
class GDToolkit extends PluginBase implements ImageToolkitInterface {

  /**
   * Implements \Drupal\system\Plugin\ImageToolkitInterface::settingsForm().
   */
  public function settingsForm() {
    $form['image_jpeg_quality'] = array(
      '#type' => 'number',
      '#title' => t('JPEG quality'),
      '#description' => t('Define the image quality for JPEG manipulations. Ranges from 0 to 100. Higher values mean better image quality but bigger files.'),
      '#min' => 0,
      '#max' => 100,
      '#default_value' => config('system.image.gd')->get('jpeg_quality'),
      '#field_suffix' => t('%'),
    );
    return $form;
  }

  /**
   * Implements \Drupal\system\Plugin\ImageToolkitInterface::settingsFormSubmit().
   */
  public function settingsFormSubmit($form, &$form_state) {
    config('system.image.gd')
      ->set('jpeg_quality', $form_state['values']['gd']['image_jpeg_quality'])
      ->save();
  }

  /**
   * Implements \Drupal\system\Plugin\ImageToolkitInterface::resize().
   */
  public function resize($image, $width, $height) {
    $res = $this->createTmp($image, $width, $height);

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
   * Implements \Drupal\system\Plugin\ImageToolkitInterface::rotate().
   */
  public function rotate($image, $degrees, $background = NULL) {
    // PHP installations using non-bundled GD do not have imagerotate.
    if (!function_exists('imagerotate')) {
      watchdog('image', 'The image %file could not be rotated because the imagerotate() function is not available in this PHP installation.', array('%file' => $image->source));
      return FALSE;
    }

    $width = $image->info['width'];
    $height = $image->info['height'];

    // Convert the hexadecimal background value to a color index value.
    if (isset($background)) {
      $rgb = array();
      for ($i = 16; $i >= 0; $i -= 8) {
        $rgb[] = (($background >> $i) & 0xFF);
      }
      $background = imagecolorallocatealpha($image->resource, $rgb[0], $rgb[1], $rgb[2], 0);
    }
    // Set the background color as transparent if $background is NULL.
    else {
      // Get the current transparent color.
      $background = imagecolortransparent($image->resource);

      // If no transparent colors, use white.
      if ($background == 0) {
        $background = imagecolorallocatealpha($image->resource, 255, 255, 255, 0);
      }
    }

    // Images are assigned a new color palette when rotating, removing any
    // transparency flags. For GIF images, keep a record of the transparent color.
    if ($image->info['extension'] == 'gif') {
      $transparent_index = imagecolortransparent($image->resource);
      if ($transparent_index != 0) {
        $transparent_gif_color = imagecolorsforindex($image->resource, $transparent_index);
      }
    }

    $image->resource = imagerotate($image->resource, 360 - $degrees, $background);

    // GIFs need to reassign the transparent color after performing the rotate.
    if (isset($transparent_gif_color)) {
      $background = imagecolorexactalpha($image->resource, $transparent_gif_color['red'], $transparent_gif_color['green'], $transparent_gif_color['blue'], $transparent_gif_color['alpha']);
      imagecolortransparent($image->resource, $background);
    }

    $image->info['width'] = imagesx($image->resource);
    $image->info['height'] = imagesy($image->resource);
    return TRUE;
  }

  /**
   * Implements \Drupal\system\Plugin\ImageToolkitInterface::crop().
   */
  public function crop($image, $x, $y, $width, $height) {
    $res = $this->createTmp($image, $width, $height);

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
   * Implements \Drupal\system\Plugin\ImageToolkitInterface::desaturate().
   */
  public function desaturate($image) {
    // PHP installations using non-bundled GD do not have imagefilter.
    if (!function_exists('imagefilter')) {
      watchdog('image', 'The image %file could not be desaturated because the imagefilter() function is not available in this PHP installation.', array('%file' => $image->source));
      return FALSE;
    }

    return imagefilter($image->resource, IMG_FILTER_GRAYSCALE);
  }

  /**
   * Implements \Drupal\system\Plugin\ImageToolkitInterface::load().
   */
  public function load($image) {
    $extension = str_replace('jpg', 'jpeg', $image->info['extension']);
    $function = 'imagecreatefrom' . $extension;
    if (function_exists($function) && $image->resource = $function($image->source)) {
      if (!imageistruecolor($image->resource)) {
        // Convert indexed images to true color, so that filters work
        // correctly and don't result in unnecessary dither.
        $new_image = $this->createTmp($image, $image->info['width'], $image->info['height']);
        imagecopy($new_image, $image->resource, 0, 0, 0, 0, $image->info['width'], $image->info['height']);
        imagedestroy($image->resource);
        $image->resource = $new_image;
      }
      return (bool) $image->resource;
    }

    return FALSE;
  }

  /**
   * Implements \Drupal\system\Plugin\ImageToolkitInterface::save().
   */
  public function save($image, $destination) {
    $scheme = file_uri_scheme($destination);
    // Work around lack of stream wrapper support in imagejpeg() and imagepng().
    if ($scheme && file_stream_wrapper_valid_scheme($scheme)) {
      // If destination is not local, save image to temporary local file.
      $local_wrappers = file_get_stream_wrappers(STREAM_WRAPPERS_LOCAL);
      if (!isset($local_wrappers[$scheme])) {
        $permanent_destination = $destination;
        $destination = drupal_tempnam('temporary://', 'gd_');
      }
      // Convert stream wrapper URI to normal path.
      $destination = drupal_realpath($destination);
    }

    $extension = str_replace('jpg', 'jpeg', $image->info['extension']);
    $function = 'image' . $extension;
    if (!function_exists($function)) {
      return FALSE;
    }
    if ($extension == 'jpeg') {
      $success = $function($image->resource, $destination, config('system.image.gd')->get('jpeg_quality'));
    }
    else {
      // Always save PNG images with full transparency.
      if ($extension == 'png') {
        imagealphablending($image->resource, FALSE);
        imagesavealpha($image->resource, TRUE);
      }
      $success = $function($image->resource, $destination);
    }
    // Move temporary local file to remote destination.
    if (isset($permanent_destination) && $success) {
      return (bool) file_unmanaged_move($destination, $permanent_destination, FILE_EXISTS_REPLACE);
    }
    return $success;
  }

  /**
   * Implements \Drupal\system\Plugin\ImageToolkitInterface::getInfo().
   */
  public function getInfo($image) {
    $details = FALSE;
    $data = getimagesize($image->source);

    if (isset($data) && is_array($data)) {
      $extensions = array('1' => 'gif', '2' => 'jpg', '3' => 'png');
      $extension = isset($extensions[$data[2]]) ?  $extensions[$data[2]] : '';
      $details = array(
        'width'     => $data[0],
        'height'    => $data[1],
        'extension' => $extension,
        'mime_type' => $data['mime'],
      );
    }

    return $details;
  }

  /**
   * Creates a truecolor image preserving transparency from a provided image.
   *
   * @param object $image
   *   An image object.
   * @param int $width
   *   The new width of the new image, in pixels.
   * @param int $height
   *   The new height of the new image, in pixels.
   *
   * @return resource
   *   A GD image handle.
   */
  public function createTmp($image, $width, $height) {
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
   * Implements \Drupal\system\Plugin\ImageToolkitInterface::isAvailable().
   */
  public static function isAvailable() {
    if ($check = get_extension_funcs('gd')) {
      if (in_array('imagegd2', $check)) {
        // GD2 support is available.
        return TRUE;
      }
    }
    return FALSE;
  }
}
