<?php

/**
 * @file
 * Contains \Drupal\system\Plugin\ImageToolkit\Operation\gd\Resize.
 */

namespace Drupal\system\Plugin\ImageToolkit\Operation\gd;

use Drupal\Component\Utility\String;

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
    return array(
      'width' => array(
        'description' => 'The new width of the resized image, in pixels',
      ),
      'height' => array(
        'description' => 'The new height of the resized image, in pixels',
      ),
    );
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
      throw new \InvalidArgumentException(String::format("Invalid width (@value) specified for the image 'resize' operation", array('@value' => $arguments['width'])));
    }
    if ($arguments['height'] <= 0) {
      throw new \InvalidArgumentException(String::format("Invalid height (@value) specified for the image 'resize' operation", array('@value' => $arguments['height'])));
    }

    return $arguments;
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(array $arguments = array()) {
    $res = $this->getToolkit()->createTmp($this->getToolkit()->getType(), $arguments['width'], $arguments['height']);

    if (!imagecopyresampled($res, $this->getToolkit()->getResource(), 0, 0, 0, 0, $arguments['width'], $arguments['height'], $this->getToolkit()->getWidth(), $this->getToolkit()->getHeight())) {
      return FALSE;
    }

    imagedestroy($this->getToolkit()->getResource());
    // Update image object.
    $this->getToolkit()->setResource($res);

    return TRUE;
  }

}
