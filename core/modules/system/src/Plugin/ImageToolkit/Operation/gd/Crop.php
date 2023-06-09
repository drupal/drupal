<?php

namespace Drupal\system\Plugin\ImageToolkit\Operation\gd;

/**
 * Defines GD2 Crop operation.
 *
 * @ImageToolkitOperation(
 *   id = "gd_crop",
 *   toolkit = "gd",
 *   operation = "crop",
 *   label = @Translation("Crop"),
 *   description = @Translation("Crops an image to a rectangle specified by the given dimensions.")
 * )
 */
class Crop extends GDImageToolkitOperationBase {

  /**
   * {@inheritdoc}
   */
  protected function arguments() {
    return [
      'x' => [
        'description' => 'The starting x offset at which to start the crop, in pixels',
      ],
      'y' => [
        'description' => 'The starting y offset at which to start the crop, in pixels',
      ],
      'width' => [
        'description' => 'The width of the cropped area, in pixels',
        'required' => FALSE,
        'default' => NULL,
      ],
      'height' => [
        'description' => 'The height of the cropped area, in pixels',
        'required' => FALSE,
        'default' => NULL,
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function validateArguments(array $arguments) {
    // Assure at least one dimension.
    if (empty($arguments['width']) && empty($arguments['height'])) {
      throw new \InvalidArgumentException("At least one dimension ('width' or 'height') must be provided to the image 'crop' operation");
    }

    // Preserve aspect.
    $aspect = $this->getToolkit()->getHeight() / $this->getToolkit()->getWidth();
    $arguments['height'] = empty($arguments['height']) ? $arguments['width'] * $aspect : $arguments['height'];
    $arguments['width'] = empty($arguments['width']) ? $arguments['height'] / $aspect : $arguments['width'];

    // Assure integers for all arguments.
    foreach (['x', 'y', 'width', 'height'] as $key) {
      $arguments[$key] = (int) round($arguments[$key]);
    }

    // Fail when width or height are 0 or negative.
    if ($arguments['width'] <= 0) {
      throw new \InvalidArgumentException("Invalid width ('{$arguments['width']}') specified for the image 'crop' operation");
    }
    if ($arguments['height'] <= 0) {
      throw new \InvalidArgumentException("Invalid height ('{$arguments['height']}') specified for the image 'crop' operation");
    }

    return $arguments;
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(array $arguments) {
    // Create a new image of the required dimensions, and copy and resize
    // the original image on it with resampling. Restore the original image upon
    // failure.
    $original_image = $this->getToolkit()->getImage();
    $data = [
      'width' => $arguments['width'],
      'height' => $arguments['height'],
      'extension' => image_type_to_extension($this->getToolkit()->getType(), FALSE),
      'transparent_color' => $this->getToolkit()->getTransparentColor(),
      'is_temp' => TRUE,
    ];
    if ($this->getToolkit()->apply('create_new', $data)) {
      if (imagecopyresampled($this->getToolkit()->getImage(), $original_image, 0, 0, $arguments['x'], $arguments['y'], $arguments['width'], $arguments['height'], $arguments['width'], $arguments['height'])) {
        return TRUE;
      }
      // In case of failure, restore the original image.
      $this->getToolkit()->setImage($original_image);
    }
    return FALSE;
  }

}
