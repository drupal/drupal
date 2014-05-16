<?php

/**
 * @file
 * Contains \Drupal\system\Plugin\ImageToolkit\GDToolkit;.
 */

namespace Drupal\system\Plugin\ImageToolkit;

use Drupal\Core\Image\ImageInterface;
use Drupal\Core\ImageToolkit\ImageToolkitBase;
use Drupal\Component\Utility\Image as ImageUtility;

/**
 * Defines the GD2 toolkit for image manipulation within Drupal.
 *
 * @ImageToolkit(
 *   id = "gd",
 *   title = @Translation("GD2 image manipulation toolkit")
 * )
 */
class GDToolkit extends ImageToolkitBase {

  /**
   * A GD image resource.
   *
   * @var resource
   */
  protected $resource;

  /**
   * Sets the GD image resource.
   *
   * @param resource $resource
   *   The GD image resource.
   *
   * @return self
   *   Returns this toolkit object.
   */
  public function setResource($resource) {
    $this->resource = $resource;
    return $this;
  }

  /**
   * Retrieves the GD image resource.
   *
   * @return resource
   *   The GD image resource.
   */
  public function getResource() {
    return $this->resource;
  }

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
    // @todo Dimensions computation will be moved into a dedicated functionality
    //   in https://drupal.org/node/2108307.
    $width = (int) round($width);
    $height = (int) round($height);

    $res = $this->createTmp($image->getType(), $width, $height);

    if (!imagecopyresampled($res, $this->getResource(), 0, 0, 0, 0, $width, $height, $this->getWidth($image), $this->getHeight($image))) {
      return FALSE;
    }

    imagedestroy($this->getResource());
    // Update image object.
    $this->setResource($res);
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
      $background = imagecolorallocatealpha($this->getResource(), $rgb[0], $rgb[1], $rgb[2], 0);
    }
    // Set the background color as transparent if $background is NULL.
    else {
      // Get the current transparent color.
      $background = imagecolortransparent($this->getResource());

      // If no transparent colors, use white.
      if ($background == 0) {
        $background = imagecolorallocatealpha($this->getResource(), 255, 255, 255, 0);
      }
    }

    // Images are assigned a new color palette when rotating, removing any
    // transparency flags. For GIF images, keep a record of the transparent color.
    if ($image->getType() == IMAGETYPE_GIF) {
      $transparent_index = imagecolortransparent($this->getResource());
      if ($transparent_index != 0) {
        $transparent_gif_color = imagecolorsforindex($this->getResource(), $transparent_index);
      }
    }

    $this->setResource(imagerotate($this->getResource(), 360 - $degrees, $background));

    // GIFs need to reassign the transparent color after performing the rotate.
    if (isset($transparent_gif_color)) {
      $background = imagecolorexactalpha($this->getResource(), $transparent_gif_color['red'], $transparent_gif_color['green'], $transparent_gif_color['blue'], $transparent_gif_color['alpha']);
      imagecolortransparent($this->getResource(), $background);
    }

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function crop(ImageInterface $image, $x, $y, $width, $height) {
    // @todo Dimensions computation will be moved into a dedicated functionality
    //   in https://drupal.org/node/2108307.
    $aspect = $this->getHeight($image) / $this->getWidth($image);
    $height = empty($height) ? $width * $aspect : $height;
    $width = empty($width) ? $height / $aspect : $width;
    $width = (int) round($width);
    $height = (int) round($height);

    $res = $this->createTmp($image->getType(), $width, $height);

    if (!imagecopyresampled($res, $this->getResource(), 0, 0, $x, $y, $width, $height, $width, $height)) {
      return FALSE;
    }

    // Destroy the original image and return the modified image.
    imagedestroy($this->getResource());
    $this->setResource($res);
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

    return imagefilter($this->getResource(), IMG_FILTER_GRAYSCALE);
  }

  /**
   * {@inheritdoc}
   */
  public function scale(ImageInterface $image, $width = NULL, $height = NULL, $upscale = FALSE) {
    // @todo Dimensions computation will be moved into a dedicated functionality
    //   in https://drupal.org/node/2108307.
    $dimensions = array(
      'width' => $this->getWidth($image),
      'height' => $this->getHeight($image),
    );

    // Scale the dimensions - if they don't change then just return success.
    if (!ImageUtility::scaleDimensions($dimensions, $width, $height, $upscale)) {
      return TRUE;
    }

    return $this->resize($image, $dimensions['width'], $dimensions['height']);
  }

  /**
   * {@inheritdoc}
   */
  public function scaleAndCrop(ImageInterface $image, $width, $height) {
    // @todo Dimensions computation will be moved into a dedicated functionality
    //   in https://drupal.org/node/2108307.
    $scale = max($width / $this->getWidth($image), $height / $this->getHeight($image));
    $x = ($this->getWidth($image) * $scale - $width) / 2;
    $y = ($this->getHeight($image) * $scale - $height) / 2;

    if ($this->resize($image, $this->getWidth($image) * $scale, $this->getHeight($image) * $scale)) {
      return $this->crop($image, $x, $y, $width, $height);
    }

    return FALSE;
  }

  /**
   * Creates a resource from a file.
   *
   * @param string $source
   *   String specifying the path of the image file.
   * @param array $details
   *   An array of image details.
   *
   * @return bool
   *   TRUE or FALSE, based on success.
   */
  protected function load($source, array $details) {
    $function = 'imagecreatefrom' . image_type_to_extension($details['type'], FALSE);
    if (function_exists($function) && $resource = $function($source)) {
      $this->setResource($resource);
      if (!imageistruecolor($resource)) {
        // Convert indexed images to true color, so that filters work
        // correctly and don't result in unnecessary dither.
        $new_image = $this->createTmp($details['type'], imagesx($resource), imagesy($resource));
        imagecopy($new_image, $resource, 0, 0, 0, 0, imagesx($resource), imagesy($resource));
        imagedestroy($resource);
        $this->setResource($new_image);
      }
      return (bool) $this->getResource();
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

    $function = 'image' . image_type_to_extension($image->getType(), FALSE);
    if (!function_exists($function)) {
      return FALSE;
    }
    if ($image->getType() == IMAGETYPE_JPEG) {
      $success = $function($this->getResource(), $destination, \Drupal::config('system.image.gd')->get('jpeg_quality'));
    }
    else {
      // Always save PNG images with full transparency.
      if ($image->getType() == IMAGETYPE_PNG) {
        imagealphablending($this->getResource(), FALSE);
        imagesavealpha($this->getResource(), TRUE);
      }
      $success = $function($this->getResource(), $destination);
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
    $details = array();
    $data = getimagesize($image->getSource());

    if (isset($data) && is_array($data) && in_array($data[2], static::supportedTypes())) {
      $details['type'] = $data[2];
      $this->load($image->getSource(), $details);
    }
    return $details;
  }

  /**
   * Creates a truecolor image preserving transparency from a provided image.
   *
   * @param int $type
   *   An image type represented by a PHP IMAGETYPE_* constant (e.g.
   *   IMAGETYPE_JPEG, IMAGETYPE_PNG, etc.).
   * @param int $width
   *   The new width of the new image, in pixels.
   * @param int $height
   *   The new height of the new image, in pixels.
   *
   * @return resource
   *   A GD image handle.
   */
  public function createTmp($type, $width, $height) {
    $res = imagecreatetruecolor($width, $height);

    if ($type == IMAGETYPE_GIF) {
      // Find out if a transparent color is set, will return -1 if no
      // transparent color has been defined in the image.
      $transparent = imagecolortransparent($this->getResource());
      if ($transparent >= 0) {
        // Find out the number of colors in the image palette. It will be 0 for
        // truecolor images.
        $palette_size = imagecolorstotal($this->getResource());
        if ($palette_size == 0 || $transparent < $palette_size) {
          // Set the transparent color in the new resource, either if it is a
          // truecolor image or if the transparent color is part of the palette.
          // Since the index of the transparency color is a property of the
          // image rather than of the palette, it is possible that an image
          // could be created with this index set outside the palette size.
          $transparent_color = imagecolorsforindex($this->getResource(), $transparent);
          $transparent = imagecolorallocate($res, $transparent_color['red'], $transparent_color['green'], $transparent_color['blue']);

          // Flood with our new transparent color.
          imagefill($res, 0, 0, $transparent);
          imagecolortransparent($res, $transparent);
        }
      }
    }
    elseif ($type == IMAGETYPE_PNG) {
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
  public function getWidth(ImageInterface $image) {
    return $this->getResource() ? imagesx($this->getResource()) : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getHeight(ImageInterface $image) {
    return $this->getResource() ? imagesy($this->getResource()) : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getRequirements() {
    $requirements = array();

    $info = gd_info();
    $requirements['version'] = array(
      'title' => t('GD library'),
      'value' => $info['GD Version'],
    );

    // Check for filter and rotate support.
    if (!function_exists('imagefilter') || !function_exists('imagerotate')) {
      $requirements['version']['severity'] = REQUIREMENT_WARNING;
      $requirements['version']['description'] = t('The GD Library for PHP is enabled, but was compiled without support for functions used by the rotate and desaturate effects. It was probably compiled using the official GD libraries from http://www.libgd.org instead of the GD library bundled with PHP. You should recompile PHP --with-gd using the bundled GD library. See <a href="@url">the PHP manual</a>.', array('@url' => 'http://www.php.net/manual/book.image.php'));
    }

    return $requirements;
  }

  /**
   * {@inheritdoc}
   */
  public static function isAvailable() {
    // GD2 support is available.
    return function_exists('imagegd2');
  }

  /**
   * {@inheritdoc}
   */
  public static function supportedTypes() {
    return array(IMAGETYPE_PNG, IMAGETYPE_JPEG, IMAGETYPE_GIF);
  }
}
