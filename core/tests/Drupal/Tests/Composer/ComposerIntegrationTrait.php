<?php

declare(strict_types=1);

namespace Drupal\Tests\Composer;

use Composer\InstalledVersions;
use Symfony\Component\Finder\Finder;

/**
 * Some utility functions for testing the Composer integration.
 */
trait ComposerIntegrationTrait {

  /**
   * Get a Finder object to traverse all of the composer.json files in core.
   *
   * @param string $drupal_root
   *   Absolute path to the root of the Drupal installation.
   *
   * @return \Symfony\Component\Finder\Finder
   *   A Finder object able to iterate all the composer.json files in core.
   */
  public static function getComposerJsonFinder($drupal_root): Finder {
    $composer_json_finder = new Finder();
    $composer_json_finder->name('composer.json')
      ->in([
        // Only find composer.json files within composer/ and core/ directories
        // so we don't inadvertently test contrib in local dev environments.
        $drupal_root . '/composer',
        $drupal_root . '/core',
      ])
      ->ignoreUnreadableDirs()
      ->notPath('#^vendor#')
      ->notPath('#/fixture#');
    return $composer_json_finder;
  }

  /**
   * Gets the absolute path to the Composer bin directory.
   *
   * Resolution order follows Composer itself: the COMPOSER_BIN_DIR
   * environment variable, the "bin-dir" setting in composer.json, the
   * "vendor-dir" setting plus "/bin", and finally "vendor/bin".
   *
   * @param string|null $project_root
   *   The directory containing the root composer.json. If NULL, it is
   *   detected from the Composer runtime API.
   *
   * @return string
   *   The absolute path to the bin directory. The directory is not
   *   guaranteed to exist.
   */
  public static function binDir(?string $project_root = NULL): string {
    if ($project_root === NULL) {
      $project_root = realpath(InstalledVersions::getRootPackage()['install_path']);
    }

    $bin_dir = getenv('COMPOSER_BIN_DIR');

    if ($bin_dir === FALSE || $bin_dir === '') {
      $config = [];
      $file = $project_root . '/composer.json';
      if (is_readable($file)) {
        $json = json_decode((string) file_get_contents($file), TRUE);
        $config = $json['config'] ?? [];
      }

      if (!empty($config['bin-dir'])) {
        $bin_dir = $config['bin-dir'];
      }
      else {
        $vendor_dir = !empty($config['vendor-dir']) ? $config['vendor-dir'] : 'vendor';
        $bin_dir = $vendor_dir . '/bin';
      }
    }

    // Relative paths are resolved against the composer.json location.
    if (!preg_match('{^(?:/|[a-zA-Z]:[\\\\/])}', $bin_dir)) {
      $bin_dir = $project_root . '/' . $bin_dir;
    }

    return $bin_dir;
  }

}
