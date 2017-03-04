<?php

namespace Drupal\system\Plugin\ImageToolkit\Operation\gd;

/**
 * Defines GD2 Scale and crop operation.
 *
 * @ImageToolkitOperation(
 *   id = "gd_scale_and_crop",
 *   toolkit = "gd",
 *   operation = "scale_and_crop",
 *   label = @Translation("Scale and crop"),
 *   description = @Translation("Scales an image to the exact width and height given. This plugin achieves the target aspect ratio by cropping the original image equally on both sides, or equally on the top and bottom. This function is useful to create uniform sized avatars from larger images.")
 * )
 */
class ScaleAndCrop extends GDImageToolkitOperationBase {

  /**
   * {@inheritdoc}
   */
  protected function arguments() {
    return [
      'width' => [
        'description' => 'The target width, in pixels',
      ],
      'height' => [
        'description' => 'The target height, in pixels',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function validateArguments(array $arguments) {
    $actualWidth = $this->getToolkit()->getWidth();
    $actualHeight = $this->getToolkit()->getHeight();

    $scaleFactor = max($arguments['width'] / $actualWidth, $arguments['height'] / $actualHeight);

    $arguments['x'] = (int) round(($actualWidth * $scaleFactor - $arguments['width']) / 2);
    $arguments['y'] = (int) round(($actualHeight * $scaleFactor - $arguments['height']) / 2);
    $arguments['resize'] = [
      'width' => (int) round($actualWidth * $scaleFactor),
      'height' => (int) round($actualHeight * $scaleFactor),
    ];

    // Fail when width or height are 0 or negative.
    if ($arguments['width'] <= 0) {
      throw new \InvalidArgumentException("Invalid width ('{$arguments['width']}') specified for the image 'scale_and_crop' operation");
    }
    if ($arguments['height'] <= 0) {
      throw new \InvalidArgumentException("Invalid height ('{$arguments['height']}') specified for the image 'scale_and_crop' operation");
    }

    return $arguments;
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(array $arguments = []) {
    return $this->getToolkit()->apply('resize', $arguments['resize'])
        && $this->getToolkit()->apply('crop', $arguments);
  }

}
