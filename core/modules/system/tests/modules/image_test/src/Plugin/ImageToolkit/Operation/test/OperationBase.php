<?php

namespace Drupal\image_test\Plugin\ImageToolkit\Operation\test;

use Drupal\Core\ImageToolkit\ImageToolkitOperationBase;

/**
 * Provides a base class for test operations.
 */
abstract class OperationBase extends ImageToolkitOperationBase {

  /**
   * {@inheritdoc}
   */
  public function arguments() {
    return array();
  }

  /**
   * {@inheritdoc}
   */
  public function execute(array $arguments) {
    // Nothing to do.
  }

}
