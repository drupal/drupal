<?php

namespace Drupal\system\Plugin\ImageToolkit\Operation\gd;

use Drupal\Core\ImageToolkit\ImageToolkitOperationBase;

/**
 * Provides a GD base class with correctly typed image toolkit.
 */
abstract class GDImageToolkitOperationBase extends ImageToolkitOperationBase {

  /**
   * The correctly typed image toolkit for GD operations.
   *
   * @return \Drupal\system\Plugin\ImageToolkit\GDToolkit
   *   The GD toolkit instance for image operations.
   */
  protected function getToolkit() {
    return parent::getToolkit();
  }

}
