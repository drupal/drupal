<?php

namespace Drupal\system\Plugin\ImageToolkit\Operation\gd;

/**
 * Defines GD2 resize operation.
 *
 * @ImageToolkitOperation(
 *   id = "gd_resize",
 *   toolkit = "gd",
 *   operation = "resize",
 *   label = @Translation("Resize"),
 *   description = @Translation("Resizes an image to the given dimensions (ignoring aspect ratio).")
 * )
 */
class Resize extends GDImageToolkitOperationBase {

  /**
   * {@inheritdoc}
   */
  protected function arguments() {
    return [
      'width' => [
        'description' => 'The new width of the resized image, in pixels',
      ],
      'height' => [
        'description' => 'The new height of the resized image, in pixels',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function validateArguments(array $arguments) {
    // Assure integers for all arguments.
    $arguments['width'] = (int) round($arguments['width']);
    $arguments['height'] = (int) round($arguments['height']);

    // Fail when width or height are 0 or negative.
    if ($arguments['width'] <= 0) {
      throw new \InvalidArgumentException("Invalid width ('{$arguments['width']}') specified for the image 'resize' operation");
    }
    if ($arguments['height'] <= 0) {
      throw new \InvalidArgumentException("Invalid height ('{$arguments['height']}') specified for the image 'resize' operation");
    }

    return $arguments;
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(array $arguments = []) {
    // Create a new resource of the required dimensions, and copy and resize
    // the original resource on it with resampling. Destroy the original
    // resource upon success.
    $original_resource = $this->getToolkit()->getResource();
    $data = [
      'width' => $arguments['width'],
      'height' => $arguments['height'],
      'extension' => image_type_to_extension($this->getToolkit()->getType(), FALSE),
      'transparent_color' => $this->getToolkit()->getTransparentColor(),
      'is_temp' => TRUE,
    ];
    if ($this->getToolkit()->apply('create_new', $data)) {
      if (imagecopyresampled($this->getToolkit()->getResource(), $original_resource, 0, 0, 0, 0, $arguments['width'], $arguments['height'], imagesx($original_resource), imagesy($original_resource))) {
        imagedestroy($original_resource);
        return TRUE;
      }
      else {
        // In case of failure, destroy the temporary resource and restore
        // the original one.
        imagedestroy($this->getToolkit()->getResource());
        $this->getToolkit()->setResource($original_resource);
      }
    }
    return FALSE;
  }

}
