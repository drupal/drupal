<?php

namespace Drupal\system\Plugin\ImageToolkit\Operation\gd;

use Drupal\Component\Utility\Color;

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
    return [
      'width' => [
        'description' => 'The width of the image, in pixels',
      ],
      'height' => [
        'description' => 'The height of the image, in pixels',
      ],
      'extension' => [
        'description' => 'The extension of the image file (e.g. png, gif, etc.)',
        'required' => FALSE,
        'default' => 'png',
      ],
      'transparent_color' => [
        'description' => 'The RGB hex color for GIF transparency',
        'required' => FALSE,
        'default' => '#ffffff',
      ],
      'is_temp' => [
        'description' => 'If TRUE, this operation is being used to create a temporary image by another GD operation. After performing its function, the caller is responsible for destroying the original GD resource.',
        'required' => FALSE,
        'default' => FALSE,
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function validateArguments(array $arguments) {
    // Assure extension is supported.
    if (!in_array($arguments['extension'], $this->getToolkit()->getSupportedExtensions())) {
      throw new \InvalidArgumentException("Invalid extension ('{$arguments['extension']}') specified for the image 'convert' operation");
    }

    // Assure integers for width and height.
    $arguments['width'] = (int) round($arguments['width']);
    $arguments['height'] = (int) round($arguments['height']);

    // Fail when width or height are 0 or negative.
    if ($arguments['width'] <= 0) {
      throw new \InvalidArgumentException("Invalid width ('{$arguments['width']}') specified for the image 'create_new' operation");
    }
    if ($arguments['height'] <= 0) {
      throw new \InvalidArgumentException("Invalid height ({$arguments['height']}) specified for the image 'create_new' operation");
    }

    // Assure transparent color is a valid hex string.
    if ($arguments['transparent_color'] && !Color::validateHex($arguments['transparent_color'])) {
      throw new \InvalidArgumentException("Invalid transparent color ({$arguments['transparent_color']}) specified for the image 'create_new' operation");
    }

    return $arguments;
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(array $arguments) {
    // Get the image type.
    $type = $this->getToolkit()->extensionToImageType($arguments['extension']);

    // Store the original GD resource.
    $original_res = $this->getToolkit()->getResource();

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
          // No transparency color specified, fill white transparent.
          $fill_color = imagecolorallocatealpha($res, 255, 255, 255, 127);
        }
        else {
          $fill_rgb = Color::hexToRgb($arguments['transparent_color']);
          $fill_color = imagecolorallocatealpha($res, $fill_rgb['red'], $fill_rgb['green'], $fill_rgb['blue'], 127);
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

    // Destroy the original resource if it is not needed by other operations.
    if (!$arguments['is_temp'] && is_resource($original_res)) {
      imagedestroy($original_res);
    }

    return TRUE;
  }

}
