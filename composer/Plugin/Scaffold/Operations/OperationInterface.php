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

  /**
   * Determines what to do if operation is used with a previous operation.
   *
   * Default behavior is to scaffold this operation at the specified
   * destination, ignoring whatever was there before.
   *
   * @param OperationInterface $conjunction_target
   *   Existing file at the destination path that we should combine with.
   *
   * @return OperationInterface
   *   The op to use at this destination.
   */
  public function combineWithConjunctionTarget(OperationInterface $conjunction_target);

  /**
   * Determines what to do if operation is used without a previous operation.
   *
   * Default behavior is to scaffold this operation at the specified
   * destination. Most operations overwrite rather than modify existing files,
   * and therefore do not need to do anything special when there is no existing
   * file.
   *
   * @return OperationInterface
   *   The op to use at this destination.
   */
  public function missingConjunctionTarget(ScaffoldFilePath $destination);

}
