<?php

/**
 * @file
 * Contains \Drupal\system\Plugin\ImageToolkit\Operation\gd\Convert.
 */

namespace Drupal\system\Plugin\ImageToolkit\Operation\gd;

use Drupal\Component\Utility\String;

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
      throw new \InvalidArgumentException(String::format("Invalid extension (@value) specified for the image 'convert' operation", array('@value' => $arguments['extension'])));
    }
    return $arguments;
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(array $arguments) {
    $type = $this->getToolkit()->extensionToImageType($arguments['extension']);

    $res = $this->getToolkit()->createTmp($type, $this->getToolkit()->getWidth(), $this->getToolkit()->getHeight());
    if (!imagecopyresampled($res, $this->getToolkit()->getResource(), 0, 0, 0, 0, $this->getToolkit()->getWidth(), $this->getToolkit()->getHeight(), $this->getToolkit()->getWidth(), $this->getToolkit()->getHeight())) {
      return FALSE;
    }
    imagedestroy($this->getToolkit()->getResource());

    // Update the image object.
    $this->getToolkit()->setType($type);
    $this->getToolkit()->setResource($res);

    return TRUE;
  }

}
