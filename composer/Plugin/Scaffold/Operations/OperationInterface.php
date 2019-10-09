<?php

namespace Drupal\Composer\Plugin\Scaffold\Operations;

use Composer\IO\IOInterface;
use Drupal\Composer\Plugin\Scaffold\ScaffoldFilePath;
use Drupal\Composer\Plugin\Scaffold\ScaffoldOptions;

/**
 * Interface for scaffold operation objects.
 */
interface OperationInterface {

  /**
   * Process this scaffold operation.
   *
   * @param \Drupal\Composer\Plugin\Scaffold\ScaffoldFilePath $destination
   *   Scaffold file's destination path.
   * @param \Composer\IO\IOInterface $io
   *   IOInterface to write to.
   * @param \Drupal\Composer\Plugin\Scaffold\ScaffoldOptions $options
   *   Various options that may alter the behavior of the operation.
   *
   * @return \Drupal\Composer\Plugin\Scaffold\Operations\ScaffoldResult
   *   Result of the scaffolding operation.
   */
  public function process(ScaffoldFilePath $destination, IOInterface $io, ScaffoldOptions $options);

}
