<?php

namespace Drupal\Composer\Plugin\Scaffold;

use Composer\Util\Filesystem;

/**
 * Manage the path to a file to scaffold.
 *
 * Both the relative and full path to the file is maintained so that the shorter
 * name may be used in progress and error messages, as needed. The name of the
 * package that provided the file path is also recorded for the same reason.
 *
 * ScaffoldFilePaths may be used to represent destination scaffold files, or the
 * source files used to create them. Static factory methods named
 * destinationPath and sourcePath, respectively, are provided to create
 * ScaffoldFilePath objects.
 */
class ScaffoldFilePath {

  /**
   * The type of scaffold file this is, 'src' or 'dest'.
   *
   * @var string
   */
  protected $type;

  /**
   * The name of the package containing the file.
   *
   * @var string
   */
  protected $packageName;

  /**
   * The relative path to the file.
   *
   * @var string
   */
  protected $relativePath;

  /**
   * The full path to the file.
   *
   * @var string
   */
  protected $fullPath;

  /**
   * ScaffoldFilePath constructor.
   *
   * @param string $path_type
   *   The type of scaffold file this is, 'src' or 'dest'.
   * @param string $package_name
   *   The name of the package containing the file.
   * @param string $rel_path
   *   The relative path to the file.
   * @param string $full_path
   *   The full path to the file.
   */
  public function __construct($path_type, $package_name, $rel_path, $full_path) {
    $this->type = $path_type;
    $this->packageName = $package_name;
    $this->relativePath = $rel_path;
    $this->fullPath = $full_path;

    // Ensure that the full path really is a full path. We do not use
    // 'realpath' here because the file specified by the full path might
    // not exist yet.
    $fs = new Filesystem();
    if (!$fs->isAbsolutePath($this->fullPath)) {
      $this->fullPath = getcwd() . '/' . $this->fullPath;
    }
  }

  /**
   * Gets the name of the package this source file was pulled from.
   *
   * @return string
   *   Name of package.
   */
  public function packageName() {
    return $this->packageName;
  }

  /**
   * Gets the relative path to the source file (best to use in messages).
   *
   * @return string
   *   Relative path to file.
   */
  public function relativePath() {
    return $this->relativePath;
  }

  /**
   * Gets the full path to the source file.
   *
   * @return string
   *   Full path to file.
   */
  public function fullPath() {
    return $this->fullPath;
  }

  /**
   * Converts the relative source path into an absolute path.
   *
   * The path returned will be relative to the package installation location.
   *
   * @param string $package_name
   *   The name of the package containing the source file. Only used for error
   *   messages.
   * @param string $package_path
   *   The installation path of the package containing the source file.
   * @param string $destination
   *   Destination location provided as a relative path. Only used for error
   *   messages.
   * @param string $source
   *   Source location provided as a relative path.
   *
   * @return self
   *   Object wrapping the relative and absolute path to the source file.
   */
  public static function sourcePath($package_name, $package_path, $destination, $source) {
    // Complain if there is no source path.
    if (empty($source)) {
      throw new \RuntimeException("No scaffold file path given for {$destination} in package {$package_name}.");
    }
    // Calculate the full path to the source scaffold file.
    $source_full_path = $package_path . '/' . $source;
    if (!file_exists($source_full_path)) {
      throw new \RuntimeException("Scaffold file {$source} not found in package {$package_name}.");
    }
    if (is_dir($source_full_path)) {
      throw new \RuntimeException("Scaffold file {$source} in package {$package_name} is a directory; only files may be scaffolded.");
    }
    return new self('src', $package_name, $source, $source_full_path);
  }

  /**
   * Converts the relative destination path into an absolute path.
   *
   * Any placeholders in the destination path, e.g. '[web-root]', will be
   * replaced using the provided location replacements interpolator.
   *
   * @param string $package_name
   *   The name of the package defining the destination path.
   * @param string $destination
   *   The relative path to the destination file being scaffolded.
   * @param \Drupal\Composer\Plugin\Scaffold\Interpolator $location_replacements
   *   Interpolator that includes the [web-root] and any other available
   *   placeholder replacements.
   *
   * @return self
   *   Object wrapping the relative and absolute path to the destination file.
   */
  public static function destinationPath($package_name, $destination, Interpolator $location_replacements) {
    $dest_full_path = $location_replacements->interpolate($destination);
    return new self('dest', $package_name, $destination, $dest_full_path);
  }

  /**
   * Adds data about the relative and full path to the provided interpolator.
   *
   * @param \Drupal\Composer\Plugin\Scaffold\Interpolator $interpolator
   *   Interpolator to add data to.
   * @param string $name_prefix
   *   (optional) Prefix to add before -rel-path and -full-path item names.
   *   Defaults to path type provided when constructing this object.
   */
  public function addInterpolationData(Interpolator $interpolator, $name_prefix = '') {
    if (empty($name_prefix)) {
      $name_prefix = $this->type;
    }
    $data = [
      'package-name' => $this->packageName(),
      "{$name_prefix}-rel-path" => $this->relativePath(),
      "{$name_prefix}-full-path" => $this->fullPath(),
    ];
    $interpolator->addData($data);
  }

  /**
   * Interpolate a string using the data from this scaffold file info.
   *
   * @param string $name_prefix
   *   (optional) Prefix to add before -rel-path and -full-path item names.
   *   Defaults to path type provided when constructing this object.
   *
   * @return \Drupal\Composer\Plugin\Scaffold\Interpolator
   *   An interpolator for making string replacements.
   */
  public function getInterpolator($name_prefix = '') {
    $interpolator = new Interpolator();
    $this->addInterpolationData($interpolator, $name_prefix);
    return $interpolator;
  }

}
