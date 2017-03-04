<?php

namespace Drupal\system\Plugin\ImageToolkit\Operation\gd;

/**
 * Defines GD2 Desaturate operation.
 *
 * @ImageToolkitOperation(
 *   id = "gd_desaturate",
 *   toolkit = "gd",
 *   operation = "desaturate",
 *   label = @Translation("Desaturate"),
 *   description = @Translation("Converts an image to grayscale.")
 * )
 */
class Desaturate extends GDImageToolkitOperationBase {

  /**
   * {@inheritdoc}
   */
  protected function arguments() {
    // This operation does not use any parameters.
    return [];
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(array $arguments) {
    // PHP installations using non-bundled GD do not have imagefilter.
    if (!function_exists('imagefilter')) {
      $this->logger->notice("The image '@file' could not be desaturated because the imagefilter() function is not available in this PHP installation.", ['@file' => $this->getToolkit()->getSource()]);
      return FALSE;
    }

    return imagefilter($this->getToolkit()->getResource(), IMG_FILTER_GRAYSCALE);
  }

}
