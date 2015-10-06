<?php

/**
 * @file
 * Contains \Drupal\system\Plugin\ImageToolkit\Operation\gd\Convert.
 */

namespace Drupal\system\Plugin\ImageToolkit\Operation\gd;

/**
 * Defines GD2 convert operation.
 *
 * @ImageToolkitOperation(
 *   id = "gd_convert",
 *   toolkit = "gd",
 *   operation = "convert",
 *   label = @Translation("Convert"),
 *   description = @Translation("Instructs the toolkit to save the image with a specified extension.")
 * )
 */
class Convert extends GDImageToolkitOperationBase {

  /**
   * {@inheritdoc}
   */
  protected function arguments() {
    return array(
      'extension' => array(
        'description' => 'The new extension of the converted image',
      ),
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function validateArguments(array $arguments) {
    if (!in_array($arguments['extension'], $this->getToolkit()->getSupportedExtensions())) {
      throw new \InvalidArgumentException("Invalid extension ({$arguments['extension']}) specified for the image 'convert' operation");
    }
    return $arguments;
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(array $arguments) {
    // Create a new resource of the required dimensions and format, and copy
    // the original resource on it with resampling. Destroy the original
    // resource upon success.
    $width = $this->getToolkit()->getWidth();
    $height = $this->getToolkit()->getHeight();
    $original_resource = $this->getToolkit()->getResource();
    $original_type = $this->getToolkit()->getType();
    $data = array(
      'width' => $width,
      'height' => $height,
      'extension' => $arguments['extension'],
      'transparent_color' => $this->getToolkit()->getTransparentColor(),
      'is_temp' => TRUE,
    );
    if ($this->getToolkit()->apply('create_new', $data)) {
      if (imagecopyresampled($this->getToolkit()->getResource(), $original_resource, 0, 0, 0, 0, $width, $height, $width, $height)) {
        imagedestroy($original_resource);
        return TRUE;
      }
      // In case of error, reset resource and type to as it was.
      $this->getToolkit()->setResource($original_resource);
      $this->getToolkit()->setType($original_type);
    }
    return FALSE;
  }

}
