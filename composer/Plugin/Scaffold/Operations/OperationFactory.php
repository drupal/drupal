<?php

namespace Drupal\Composer\Plugin\Scaffold\Operations;

use Composer\Composer;
use Composer\Package\PackageInterface;
use Drupal\Composer\Plugin\Scaffold\ScaffoldFilePath;

/**
 * Create Scaffold operation objects based on provided metadata.
 *
 * @internal
 */
class OperationFactory {

  /**
   * The Composer service.
   *
   * @var \Composer\Composer
   */
  protected $composer;

  /**
   * OperationFactory constructor.
   *
   * @param \Composer\Composer $composer
   *   Reference to the 'Composer' object, since the Scaffold Operation Factory
   *   is also responsible for evaluating relative package paths as it creates
   *   scaffold operations.
   */
  public function __construct(Composer $composer) {
    $this->composer = $composer;
  }

  /**
   * Creates a scaffolding operation object as determined by the metadata.
   *
   * @param \Composer\Package\PackageInterface $package
   *   The package that relative paths will be relative from.
   * @param OperationData $operation_data
   *   The parameter data for this operation object; varies by operation type.
   *
   * @return \Drupal\Composer\Plugin\Scaffold\Operations\OperationInterface
   *   The scaffolding operation object (skip, replace, etc.)
   *
   * @throws \RuntimeException
   *   Exception thrown when parameter data does not identify a known scaffold
   *   operation.
   */
  public function create(PackageInterface $package, OperationData $operation_data) {
    switch ($operation_data->mode()) {
      case SkipOp::ID:
        return new SkipOp();

      case ReplaceOp::ID:
        return $this->createReplaceOp($package, $operation_data);

      case AppendOp::ID:
        return $this->createAppendOp($package, $operation_data);
    }
    throw new \RuntimeException("Unknown scaffold operation mode <comment>{$operation_data->mode()}</comment>.");
  }

  /**
   * Creates a 'replace' scaffold op.
   *
   * Replace ops may copy or symlink, depending on settings.
   *
   * @param \Composer\Package\PackageInterface $package
   *   The package that relative paths will be relative from.
   * @param OperationData $operation_data
   *   The parameter data for this operation object, i.e. the relative 'path'.
   *
   * @return \Drupal\Composer\Plugin\Scaffold\Operations\OperationInterface
   *   A scaffold replace operation object.
   */
  protected function createReplaceOp(PackageInterface $package, OperationData $operation_data) {
    if (!$operation_data->hasPath()) {
      throw new \RuntimeException("'path' component required for 'replace' operations.");
    }
    $package_name = $package->getName();
    $package_path = $this->getPackagePath($package);
    $source = ScaffoldFilePath::sourcePath($package_name, $package_path, $operation_data->destination(), $operation_data->path());
    $op = new ReplaceOp($source, $operation_data->overwrite());
    return $op;
  }

  /**
   * Creates an 'append' (or 'prepend') scaffold op.
   *
   * @param \Composer\Package\PackageInterface $package
   *   The package that relative paths will be relative from.
   * @param OperationData $operation_data
   *   The parameter data for this operation object, i.e. the relative 'path'.
   *
   * @return \Drupal\Composer\Plugin\Scaffold\Operations\OperationInterface
   *   A scaffold replace operation object.
   */
  protected function createAppendOp(PackageInterface $package, OperationData $operation_data) {
    $package_name = $package->getName();
    $package_path = $this->getPackagePath($package);
    $prepend_source_file = NULL;
    $append_source_file = NULL;
    $default_data_file = NULL;
    if ($operation_data->hasPrepend()) {
      $prepend_source_file = ScaffoldFilePath::sourcePath($package_name, $package_path, $operation_data->destination(), $operation_data->prepend());
    }
    if ($operation_data->hasAppend()) {
      $append_source_file = ScaffoldFilePath::sourcePath($package_name, $package_path, $operation_data->destination(), $operation_data->append());
    }
    if ($operation_data->hasDefault()) {
      $default_data_file = ScaffoldFilePath::sourcePath($package_name, $package_path, $operation_data->destination(), $operation_data->default());
    }
    if (!$this->hasContent($prepend_source_file) && !$this->hasContent($append_source_file)) {
      $message = '  - Keep <info>[dest-rel-path]</info> unchanged: no content to prepend / append was provided.';
      return new SkipOp($message);
    }

    return new AppendOp($prepend_source_file, $append_source_file, $operation_data->forceAppend(), $default_data_file);
  }

  /**
   * Checks to see if the specified scaffold file exists and has content.
   *
   * @param \Drupal\Composer\Plugin\Scaffold\ScaffoldFilePath $file
   *   Scaffold file to check.
   *
   * @return bool
   *   True if the file exists and has content.
   */
  protected function hasContent(ScaffoldFilePath $file = NULL) {
    if (!$file) {
      return FALSE;
    }
    $path = $file->fullPath();
    return is_file($path) && (filesize($path) > 0);
  }

  /**
   * Gets the file path of a package.
   *
   * Note that if we call getInstallPath on the root package, we get the
   * wrong answer (the installation manager thinks our package is in
   * vendor). We therefore add special checking for this case.
   *
   * @param \Composer\Package\PackageInterface $package
   *   The package.
   *
   * @return string
   *   The file path.
   */
  protected function getPackagePath(PackageInterface $package) {
    if ($package->getName() == $this->composer->getPackage()->getName()) {
      // This will respect the --working-dir option if Composer is invoked with
      // it. There is no API or method to determine the filesystem path of
      // a package's composer.json file.
      return getcwd();
    }
    return $this->composer->getInstallationManager()->getInstallPath($package);
  }

}
