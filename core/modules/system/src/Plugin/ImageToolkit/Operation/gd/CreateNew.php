<?php

/**
 * @file
 * Contains \Drupal\system\Plugin\ImageToolkit\Operation\gd\CreateNew.
 */

namespace Drupal\system\Plugin\ImageToolkit\Operation\gd;

use Drupal\Component\Utility\Color;
use Drupal\Component\Utility\SafeMarkup;

/**
 * Defines GD2 create_new image operation.
 *
 * @ImageToolkitOperation(
 *   id = "gd_create_new",
 *   toolkit = "gd",
 *   operation = "create_new",
 *   label = @Translation("Set a new image"),
 *   description = @Translation("Creates a new transparent resource and sets it for the image.")
 * )
 */
class CreateNew extends GDImageToolkitOperationBase {

  /**
   * {@inheritdoc}
   */
  protected function arguments() {
    return array(
      'width' => array(
        'description' => 'The width of the image, in pixels',
      ),
      'height' => array(
        'description' => 'The height of the image, in pixels',
      ),
      'extension' => array(
        'description' => 'The extension of the image file (e.g. png, gif, etc.)',
        'required' => FALSE,
        'default' => 'png',
      ),
      'transparent_color' => array(
        'description' => 'The RGB hex color for GIF transparency',
        'required' => FALSE,
        'default' => '#ffffff',
      ),
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function validateArguments(array $arguments) {
    // Assure extension is supported.
    if (!in_array($arguments['extension'], $this->getToolkit()->getSupportedExtensions())) {
      throw new \InvalidArgumentException(SafeMarkup::format("Invalid extension (@value) specified for the image 'convert' operation", array('@value' => $arguments['extension'])));
    }

    // Assure integers for width and height.
    $arguments['width'] = (int) round($arguments['width']);
    $arguments['height'] = (int) round($arguments['height']);

    // Fail when width or height are 0 or negative.
    if ($arguments['width'] <= 0) {
      throw new \InvalidArgumentException(SafeMarkup::format("Invalid width (@value) specified for the image 'create_new' operation", array('@value' => $arguments['width'])));
    }
    if ($arguments['height'] <= 0) {
      throw new \InvalidArgumentException(SafeMarkup::format("Invalid height (@value) specified for the image 'create_new' operation", array('@value' => $arguments['height'])));
    }

    // Assure transparent color is a valid hex string.
    if ($arguments['transparent_color'] && !Color::validateHex($arguments['transparent_color'])) {
      throw new \InvalidArgumentException(SafeMarkup::format("Invalid transparent color (@value) specified for the image 'create_new' operation", array('@value' => $arguments['transparent_color'])));
    }

    return $arguments;
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(array $arguments) {
    // Get the image type.
    $type = $this->getToolkit()->extensionToImageType($arguments['extension']);

    // Create the resource.
    if (!$res = imagecreatetruecolor($arguments['width'], $arguments['height'])) {
      return FALSE;
    }

    // Fill the resource with transparency as possible.
    switch ($type) {
      case IMAGETYPE_PNG:
        imagealphablending($res, FALSE);
        $transparency = imagecolorallocatealpha($res, 0, 0, 0, 127);
        imagefill($res, 0, 0, $transparency);
        imagealphablending($res, TRUE);
        imagesavealpha($res, TRUE);
        break;

      case IMAGETYPE_GIF:
        if (empty($arguments['transparent_color'])) {
          // No transparency color specified, fill white.
          $fill_color = imagecolorallocate($res, 255, 255, 255);
        }
        else {
          $fill_rgb = Color::hexToRgb($arguments['transparent_color']);
          $fill_color = imagecolorallocate($res, $fill_rgb['red'], $fill_rgb['green'], $fill_rgb['blue']);
          imagecolortransparent($res, $fill_color);
        }
        imagefill($res, 0, 0, $fill_color);
        break;

      case IMAGETYPE_JPEG:
        imagefill($res, 0, 0, imagecolorallocate($res, 255, 255, 255));
        break;

    }

    // Update the toolkit properties.
    $this->getToolkit()->setType($type);
    $this->getToolkit()->setResource($res);
    return TRUE;
  }

}
