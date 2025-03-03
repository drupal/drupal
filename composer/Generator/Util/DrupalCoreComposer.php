<?php

namespace Drupal\Composer\Generator\Util;

/**
 * Utilities for accessing composer.json data from drupal/drupal and drupal/core.
 *
 * Some data is stored in the root composer.json file, while others is found
 * in the core/composer.json file.
 */
class DrupalCoreComposer {

  /**
   * Cached composer.json data.
   *
   * @var array
   */
  protected $composerJson = [];

  /**
   * Cached composer.lock data.
   *
   * @var array
   */
  protected $composerLock = [];

  /**
   * DrupalCoreComposer constructor.
   *
   * @param array $composerJson
   *   The composer.json data.
   * @param array $composerLock
   *   The composer.lock data.
   */
  public function __construct(array $composerJson, array $composerLock) {
    $this->composerJson = $composerJson;
    $this->composerLock = $composerLock;
  }

  /**
   * DrupalCoreComposer factory.
   *
   * @param string $repositoryPath
   *   Path to a directory containing a composer.json and composer.lock files.
   *
   * @return static
   *   New DrupalCoreComposer object containing composer.json and lock data.
   */
  public static function createFromPath(string $repositoryPath) {
    $composerJson = static::loadJsonFromPath("$repositoryPath/composer.json");
    $composerLock = static::loadJsonFromPath("$repositoryPath/composer.lock");
    return new self($composerJson, $composerLock);
  }

  /**
   * Fetch the composer data from the root drupal/drupal project.
   *
   * @return array
   *   Composer json data
   */
  public function rootComposerJson() {
    return $this->composerJson;
  }

  /**
   * Fetch the composer lock data.
   *
   * @return array
   *   Composer lock data
   */
  public function composerLock() {
    return $this->composerLock;
  }

  /**
   * Return the "require-dev" section from root or core composer.json file.
   *
   * The require-dev constraints moved from core/composer.json (8.7.x and
   * earlier) to the root composer.json file (8.8.x and later).
   *
   * @return array
   *   The contents of the "require-dev" section.
   */
  public function getRequireDev() {
    $composerJsonData = $this->rootComposerJson();
    return $composerJsonData['require-dev'] ?? [];
  }

  /**
   * Look up the info for one package in the composer.lock file.
   *
   * @param string $packageName
   *   Name of package to find, e.g. 'behat/mink-selenium2-driver'.
   * @param bool $dev
   *   TRUE: consider only 'packages-dev'. Default: consider only 'packages'.
   *
   * @return array
   *   Package info from composer.lock.
   */
  public function packageLockInfo($packageName, $dev = FALSE) {
    $packagesSection = $dev ? 'packages-dev' : 'packages';
    foreach ($this->composerLock[$packagesSection] as $info) {
      if ($info['name'] == $packageName) {
        return $info;
      }
    }
    return [];
  }

  /**
   * Load json data from the specified path.
   *
   * @param string $path
   *   Relative path to the json file to load.
   *
   * @return array
   *   The contents of the json data for the specified file.
   */
  protected static function loadJsonFromPath($path) {
    return file_exists($path) ? json_decode(file_get_contents($path), TRUE) : [];
  }

}
