<?php

declare(strict_types=1);

namespace Drupal\package_manager;

use Composer\Autoload\ClassLoader;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\File\FileSystemInterface;

/**
 * Computes file system paths that are needed to stage code changes.
 */
class PathLocator {

  public function __construct(
    protected string $appRoot,
    protected ConfigFactoryInterface $configFactory,
    protected FileSystemInterface $fileSystem,
  ) {}

  /**
   * Returns the absolute path of the project root.
   *
   * This is where the project-level composer.json should normally be found, and
   * may or may not be the same path as the Drupal code base.
   *
   * @return string
   *   The absolute path of the project root.
   */
  public function getProjectRoot(): string {
    // Assume that the vendor directory is immediately below the project root.
    return realpath($this->getVendorDirectory() . DIRECTORY_SEPARATOR . '..');
  }

  /**
   * Returns the absolute path of the vendor directory.
   *
   * @return string
   *   The absolute path of the vendor directory.
   */
  public function getVendorDirectory(): string {
    // There may be multiple class loaders at work.
    // ClassLoader::getRegisteredLoaders() keeps track of them all, indexed by
    // the path of the vendor directory they load classes from.
    $loaders = ClassLoader::getRegisteredLoaders();

    // If there's only one class loader, we don't need to search for the right
    // one.
    if (count($loaders) === 1) {
      return key($loaders);
    }

    // To determine which class loader is the one for Drupal's vendor directory,
    // look for the loader whose vendor path starts the same way as the path to
    // this file.
    foreach (array_keys($loaders) as $path) {
      if (str_starts_with(__FILE__, dirname($path))) {
        return $path;
      }
    }
    // If we couldn't find a match, assume that the first registered class
    // loader is the one we want.
    return key($loaders);
  }

  /**
   * Returns the path of the Drupal installation, relative to the project root.
   *
   * @return string
   *   The path of the Drupal installation, relative to the project root and
   *   without leading or trailing slashes. Will return an empty string if the
   *   project root and Drupal root are the same.
   */
  public function getWebRoot(): string {
    $web_root = str_replace(trim($this->getProjectRoot(), DIRECTORY_SEPARATOR), '', trim($this->appRoot, DIRECTORY_SEPARATOR));
    return trim($web_root, DIRECTORY_SEPARATOR);
  }

  /**
   * Returns the directory where stage directories will be created.
   *
   * The stage root may be affected by site settings, so stages may wish to
   * cache the value returned by this method, to ensure that they use the same
   * stage root directory throughout their life cycle.
   *
   * @return string
   *   The absolute path of the directory where stage directories should be
   *   created.
   */
  public function getStagingRoot(): string {
    $site_id = $this->configFactory->get('system.site')->get('uuid');
    return $this->fileSystem->getTempDirectory() . DIRECTORY_SEPARATOR . '.package_manager' . $site_id;
  }

}
