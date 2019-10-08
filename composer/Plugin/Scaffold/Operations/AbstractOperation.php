<?php

namespace Drupal\Composer\Plugin\Scaffold\Operations;

use Drupal\Composer\Plugin\Scaffold\ScaffoldFilePath;

/**
 * Provides default behaviors for operations.
 */
abstract class AbstractOperation implements OperationInterface {

  /**
   * {@inheritdoc}
   */
  public function combineWithConjunctionTarget(OperationInterface $conjunction_target) {
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function missingConjunctionTarget(ScaffoldFilePath $destination) {
    return $this;
  }

}
