<?php

namespace Drupal\auto_updates\ReadinessChecker;

use Composer\Autoload\ClassLoader;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Base class for file system checkers.
 *
 * Readiness checkers that require knowing the web root and/or vendor
 * directories to perform their checks should extend this class.
 */
abstract class FileSystemBase implements ReadinessCheckerInterface {
  use StringTranslationTrait;

  /**
   * The root file path.
   *
   * @var string
   */
  protected $rootPath;

  /**
   * The path of the vendor folder.
   *
   * @var string|null
   */
  protected $vendorDir = NULL;

  /**
   * FileSystemBase constructor.
   *
   * @param string $app_root
   *   The app root.
   * @param string|null $vendor_dir
   *   The vendor directory path.
   */
  public function __construct(string $app_root, ?string $vendor_dir = NULL) {
    $this->rootPath = $app_root;
    $this->vendorDir = $vendor_dir;
  }

  /**
   * Determines if a valid root path can be located.
   *
   * @return bool
   *   TRUE if a valid root path can be determined, otherwise false.
   */
  protected function hasValidRootPath() {
    return file_exists(implode(DIRECTORY_SEPARATOR, [$this->getRootPath(), 'core', 'core.api.php']));
  }

  /**
   * Determines if a valid vendor path can be located.
   *
   * @return bool
   *   TRUE if a valid root path can be determined, otherwise false.
   */
  protected function hasValidVendorPath() {
    return file_exists($this->getVendorPath() . DIRECTORY_SEPARATOR . 'autoload.php');
  }

  /**
   * Gets the absolute path at which Drupal is installed.
   *
   * @return string
   *   The root file path.
   */
  protected function getRootPath(): string {
    return $this->rootPath;
  }

  /**
   * Get the vendor file path.
   *
   * @return string
   *   The vendor file path.
   */
  protected function getVendorPath(): string {
    if ($this->vendorDir === NULL) {
      try {
        $class_loader_reflection = new \ReflectionClass(ClassLoader::class);
        $this->vendorDir = dirname($class_loader_reflection->getFileName(), 2);
      }
      catch (\ReflectionException $e) {
        // ClassLoader does not exists. Vendor directory cannot be determined.
      }
    }
    return $this->vendorDir;
  }

  /**
   * Determines if the root and vendor directories are on the same logical disk.
   *
   * @param string $root
   *   Root file path.
   * @param string $vendor
   *   Vendor file path.
   *
   * @return bool
   *   TRUE if they are on the same file system, FALSE otherwise.
   *
   * @throws \Exception
   *   Thrown if the an error is found trying get the directory information.
   */
  protected function areSameLogicalDisk(string $root, string $vendor): bool {
    $root_statistics = stat($root);
    $vendor_statistics = stat($vendor);
    if ($root_statistics === FALSE || $vendor_statistics === FALSE) {
      throw new \RuntimeException('Unable to determine if the root and vendor directories are on the same logic disk.');
    }
    return $root_statistics['dev'] === $vendor_statistics['dev'];
  }

}
