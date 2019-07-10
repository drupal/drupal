<?php

namespace Drupal\Component\Scaffold;

use Composer\IO\IOInterface;
use Drupal\Component\Scaffold\Operations\OperationInterface;

/**
 * Data object that keeps track of one scaffold file.
 *
 * Scaffold files are identified primarily by their destination path. Each
 * scaffold file also has an 'operation' object that controls how the scaffold
 * file will be placed (e.g. via copy or symlink, or maybe by appending multiple
 * files together). The operation may have one or more source files.
 */
class ScaffoldFileInfo {

  /**
   * The path to the destination.
   *
   * @var \Drupal\Component\Scaffold\ScaffoldFilePath
   */
  protected $destination;

  /**
   * The operation used to create the destination.
   *
   * @var \Drupal\Component\Scaffold\Operations\OperationInterface
   */
  protected $op;

  /**
   * Constructs a ScaffoldFileInfo object.
   *
   * @param \Drupal\Component\Scaffold\ScaffoldFilePath $destination
   *   The full and relative paths to the destination file and the package
   *   defining it.
   * @param \Drupal\Component\Scaffold\Operations\OperationInterface $op
   *   Operations object that will handle scaffolding operations.
   */
  public function __construct(ScaffoldFilePath $destination, OperationInterface $op) {
    $this->destination = $destination;
    $this->op = $op;
  }

  /**
   * Gets the Scaffold operation.
   *
   * @return \Drupal\Component\Scaffold\Operations\OperationInterface
   *   Operations object that handles scaffolding (copy, make symlink, etc).
   */
  public function op() {
    return $this->op;
  }

  /**
   * Gets the package name.
   *
   * @return string
   *   The name of the package this scaffold file info was collected from.
   */
  public function packageName() {
    return $this->destination->packageName();
  }

  /**
   * Gets the destination.
   *
   * @return \Drupal\Component\Scaffold\ScaffoldFilePath
   *   The scaffold path to the destination file.
   */
  public function destination() {
    return $this->destination;
  }

  /**
   * Determines if this scaffold file has been overridden by another package.
   *
   * @param string $providing_package
   *   The name of the package that provides the scaffold file at this location,
   *   as returned by self::findProvidingPackage()
   *
   * @return bool
   *   Whether this scaffold file if overridden or removed.
   */
  public function overridden($providing_package) {
    return $this->packageName() !== $providing_package;
  }

  /**
   * Replaces placeholders in a message.
   *
   * @param string $message
   *   Message with placeholders to fill in.
   * @param array $extra
   *   Additional data to merge with the interpolator.
   * @param mixed $default
   *   Default value to use for missing placeholders, or FALSE to keep them.
   *
   * @return string
   *   Interpolated string with placeholders replaced.
   */
  public function interpolate($message, array $extra = [], $default = FALSE) {
    $interpolator = $this->destination->getInterpolator();
    return $interpolator->interpolate($message, $extra, $default);
  }

  /**
   * Moves a single scaffold file from source to destination.
   *
   * @param \Composer\IO\IOInterface $io
   *   The scaffold file to be processed.
   * @param \Drupal\Component\Scaffold\ScaffoldOptions $options
   *   Assorted operational options, e.g. whether the destination should be a
   *   symlink.
   *
   * @return \Drupal\Component\Scaffold\Operations\ScaffoldResult
   *   The scaffold result.
   */
  public function process(IOInterface $io, ScaffoldOptions $options) {
    return $this->op()->process($this->destination, $io, $options);
  }

}
