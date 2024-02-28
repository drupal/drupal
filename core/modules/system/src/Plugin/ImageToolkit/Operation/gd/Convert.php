<?php

namespace Drupal\system\Plugin\ImageToolkit\Operation\gd;

use Drupal\Core\ImageToolkit\Attribute\ImageToolkitOperation;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Defines GD2 convert operation.
 */
#[ImageToolkitOperation(
  id: "gd_convert",
  toolkit: "gd",
  operation: "convert",
  label: new TranslatableMarkup("Convert"),
  description: new TranslatableMarkup("Instructs the toolkit to save the image with a specified extension.")
)]
class Convert extends GDImageToolkitOperationBase {

  /**
   * {@inheritdoc}
   */
  protected function arguments() {
    return [
      'extension' => [
        'description' => 'The new extension of the converted image',
      ],
    ];
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
    // Create a new image of the required dimensions and format, and copy
    // the original image on it with resampling. Restore the original image upon
    // failure.
    $width = $this->getToolkit()->getWidth();
    $height = $this->getToolkit()->getHeight();
    $original_image = $this->getToolkit()->getImage();
    $original_type = $this->getToolkit()->getType();
    $data = [
      'width' => $width,
      'height' => $height,
      'extension' => $arguments['extension'],
      'transparent_color' => $this->getToolkit()->getTransparentColor(),
      'is_temp' => TRUE,
    ];
    if ($this->getToolkit()->apply('create_new', $data)) {
      if (imagecopyresampled($this->getToolkit()->getImage(), $original_image, 0, 0, 0, 0, $width, $height, $width, $height)) {
        return TRUE;
      }
      // In case of error, reset image and type to as it was.
      $this->getToolkit()->setImage($original_image);
      $this->getToolkit()->setType($original_type);
    }
    return FALSE;
  }

}
