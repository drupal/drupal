<?php

/**
 * @file
 * Contains \Drupal\system\Plugin\ImageToolkit\GDToolkit;.
 */

namespace Drupal\system\Plugin\ImageToolkit;

use Drupal\Component\Plugin\PluginBase;
use Drupal\system\Annotation\ImageToolkit;
use Drupal\Core\Annotation\Translation;
use Drupal\Core\Image\ImageInterface;
use Drupal\system\Plugin\ImageToolkitInterface;

/**
 * Defines the GD2 toolkit for image manipulation within Drupal.
 *
 * @ImageToolkit(
 *   id = "gd",
 *   title = @Translation("GD2 image manipulation toolkit")
 * )
 */
class GDToolkit extends PluginBase implements ImageToolkitInterface {

  /**
   * {@inheritdoc}
   */
  public function settingsForm() {
    $form['image_jpeg_quality'] = array(
      '#type' => 'number',
      '#title' => t('JPEG quality'),
      '#description' => t('Define the image quality for JPEG manipulations. Ranges from 0 to 100. Higher values mean better image quality but bigger files.'),
      '#min' => 0,
      '#max' => 100,
      '#default_value' => \Drupal::config('system.image.gd')->get('jpeg_quality'),
      '#field_suffix' => t('%'),
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsFormSubmit($form, &$form_state) {
    \Drupal::config('system.image.gd')
      ->set('jpeg_quality', $form_state['values']['gd']['image_jpeg_quality'])
      ->save();
  }

  /**
   * {@inheritdoc}
   */
  public function resize(ImageInterface $image, $width, $height) {
    $res = $this->createTmp($image, $width, $height);

    if (!imagecopyresampled($res, $image->getResource(), 0, 0, 0, 0, $width, $height, $image->getWidth(), $image->getHeight())) {
      return FALSE;
    }

    imagedestroy($image->getResource());
    // Update image object.
    $image
      ->setResource($res)
      ->setWidth($width)
      ->setHeight($height);
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function rotate(ImageInterface $image, $degrees, $background = NULL) {
    // PHP installations using non-bundled GD do not have imagerotate.
    if (!function_exists('imagerotate')) {
      watchdog('image', 'The image %file could not be rotated because the imagerotate() function is not available in this PHP installation.', array('%file' => $image->getSource()));
      return FALSE;
    }

    // Convert the hexadecimal background value to a color index value.
    if (isset($background)) {
      $rgb = array();
      for ($i = 16; $i >= 0; $i -= 8) {
        $rgb[] = (($background >> $i) & 0xFF);
      }
      $background = imagecolorallocatealpha($image->getResource(), $rgb[0], $rgb[1], $rgb[2], 0);
    }
    // Set the background color as transparent if $background is NULL.
    else {
      // Get the current transparent color.
      $background = imagecolortransparent($image->getResource());

      // If no transparent colors, use white.
      if ($background == 0) {
        $background = imagecolorallocatealpha($image->getResource(), 255, 255, 255, 0);
      }
    }

    // Images are assigned a new color palette when rotating, removing any
    // transparency flags. For GIF images, keep a record of the transparent color.
    if ($image->getExtension() == 'gif') {
      $transparent_index = imagecolortransparent($image->getResource());
      if ($transparent_index != 0) {
        $transparent_gif_color = imagecolorsforindex($image->getResource(), $transparent_index);
      }
    }

    $image->setResource(imagerotate($image->getResource(), 360 - $degrees, $background));

    // GIFs need to reassign the transparent color after performing the rotate.
    if (isset($transparent_gif_color)) {
      $background = imagecolorexactalpha($image->getResource(), $transparent_gif_color['red'], $transparent_gif_color['green'], $transparent_gif_color['blue'], $transparent_gif_color['alpha']);
      imagecolortransparent($image->getResource(), $background);
    }

    $image
      ->setWidth(imagesx($image->getResource()))
      ->setHeight(imagesy($image->getResource()));
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function crop(ImageInterface $image, $x, $y, $width, $height) {
    $res = $this->createTmp($image, $width, $height);

    if (!imagecopyresampled($res, $image->getResource(), 0, 0, $x, $y, $width, $height, $width, $height)) {
      return FALSE;
    }

    // Destroy the original image and return the modified image.
    imagedestroy($image->getResource());
    $image
      ->setResource($res)
      ->setWidth($width)
      ->setHeight($height);
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function desaturate(ImageInterface $image) {
    // PHP installations using non-bundled GD do not have imagefilter.
    if (!function_exists('imagefilter')) {
      watchdog('image', 'The image %file could not be desaturated because the imagefilter() function is not available in this PHP installation.', array('%file' => $image->getSource()));
      return FALSE;
    }

    return imagefilter($image->getResource(), IMG_FILTER_GRAYSCALE);
  }

  /**
   * {@inheritdoc}
   */
  public function load(ImageInterface $image) {
    $extension = str_replace('jpg', 'jpeg', $image->getExtension());
    $function = 'imagecreatefrom' . $extension;
    if (function_exists($function) && $resource = $function($image->getSource())) {
      $image->setResource($resource);
      if (!imageistruecolor($resource)) {
        // Convert indexed images to true color, so that filters work
        // correctly and don't result in unnecessary dither.
        $new_image = $this->createTmp($image, $image->getWidth(), $image->getHeight());
        imagecopy($new_image, $resource, 0, 0, 0, 0, $image->getWidth(), $image->getHeight());
        imagedestroy($resource);
        $image->setResource($new_image);
      }
      return (bool) $image->getResource();
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function save(ImageInterface $image, $destination) {
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

    $extension = str_replace('jpg', 'jpeg', $image->getExtension());
    $function = 'image' . $extension;
    if (!function_exists($function)) {
      return FALSE;
    }
    if ($extension == 'jpeg') {
      $success = $function($image->getResource(), $destination, \Drupal::config('system.image.gd')->get('jpeg_quality'));
    }
    else {
      // Always save PNG images with full transparency.
      if ($extension == 'png') {
        imagealphablending($image->getResource(), FALSE);
        imagesavealpha($image->getResource(), TRUE);
      }
      $success = $function($image->getResource(), $destination);
    }
    // Move temporary local file to remote destination.
    if (isset($permanent_destination) && $success) {
      return (bool) file_unmanaged_move($destination, $permanent_destination, FILE_EXISTS_REPLACE);
    }
    return $success;
  }

  /**
   * {@inheritdoc}
   */
  public function getInfo(ImageInterface $image) {
    $details = FALSE;
    $data = getimagesize($image->getSource());

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
   * @param \Drupal\Core\Image\ImageInterface $image
   *   An image object.
   * @param int $width
   *   The new width of the new image, in pixels.
   * @param int $height
   *   The new height of the new image, in pixels.
   *
   * @return resource
   *   A GD image handle.
   */
  public function createTmp(ImageInterface $image, $width, $height) {
    $res = imagecreatetruecolor($width, $height);

    if ($image->getExtension() == 'gif') {
      // Grab transparent color index from image resource.
      $transparent = imagecolortransparent($image->getResource());

      if ($transparent >= 0) {
        // The original must have a transparent color, allocate to the new image.
        $transparent_color = imagecolorsforindex($image->getResource(), $transparent);
        $transparent = imagecolorallocate($res, $transparent_color['red'], $transparent_color['green'], $transparent_color['blue']);

        // Flood with our new transparent color.
        imagefill($res, 0, 0, $transparent);
        imagecolortransparent($res, $transparent);
      }
    }
    elseif ($image->getExtension() == 'png') {
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
   * {@inheritdoc}
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
