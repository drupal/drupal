<?php

namespace Drupal\system\Plugin\ImageToolkit\Operation\gd;

use Drupal\Core\ImageToolkit\ImageToolkitOperationBase;

abstract class GDImageToolkitOperationBase extends ImageToolkitOperationBase {

  /**
   * The correctly typed image toolkit for GD operations.
   *
   * @return \Drupal\system\Plugin\ImageToolkit\GDToolkit
   */
  protected function getToolkit() {
    return parent::getToolkit();
  }

}
