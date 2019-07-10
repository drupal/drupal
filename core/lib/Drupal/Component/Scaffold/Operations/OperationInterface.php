<?php

namespace Drupal\Component\Scaffold\Operations;

use Composer\IO\IOInterface;
use Drupal\Component\Scaffold\ScaffoldFilePath;
use Drupal\Component\Scaffold\ScaffoldOptions;

/**
 * Interface for scaffold operation objects.
 */
interface OperationInterface {

  /**
   * Process this scaffold operation.
   *
   * @param \Drupal\Component\Scaffold\ScaffoldFilePath $destination
   *   Scaffold file's destination path.
   * @param \Composer\IO\IOInterface $io
   *   IOInterface to write to.
   * @param \Drupal\Component\Scaffold\ScaffoldOptions $options
   *   Various options that may alter the behavior of the operation.
   *
   * @return \Drupal\Component\Scaffold\Operations\ScaffoldResult
   *   Result of the scaffolding operation.
   */
  public function process(ScaffoldFilePath $destination, IOInterface $io, ScaffoldOptions $options);

}
