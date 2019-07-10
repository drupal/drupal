<?php

namespace Drupal\Component\Scaffold;

use Composer\Composer;
use Composer\Package\PackageInterface;
use Composer\Util\Filesystem;

/**
 * Per-project options from the 'extras' section of the composer.json file.
 *
 * Projects that describe scaffold files do so via their scaffold options.
 * This data is pulled from the 'composer-scaffold' portion of the extras
 * section of the project data.
 */
class ManageOptions {

  /**
   * The Composer service.
   *
   * @var \Composer\Composer
   */
  protected $composer;

  /**
   * ManageOptions constructor.
   *
   * @param \Composer\Composer $composer
   *   The Composer service.
   */
  public function __construct(Composer $composer) {
    $this->composer = $composer;
  }

  /**
   * Gets the root-level scaffold options for this project.
   *
   * @return \Drupal\Component\Scaffold\ScaffoldOptions
   *   The scaffold options object.
   */
  public function getOptions() {
    return $this->packageOptions($this->composer->getPackage());
  }

  /**
   * Gets the scaffold options for the stipulated project.
   *
   * @param \Composer\Package\PackageInterface $package
   *   The package to fetch the scaffold options from.
   *
   * @return \Drupal\Component\Scaffold\ScaffoldOptions
   *   The scaffold options object.
   */
  public function packageOptions(PackageInterface $package) {
    return ScaffoldOptions::create($package->getExtra());
  }

  /**
   * Creates an interpolator for the 'locations' element.
   *
   * The interpolator returned will replace a path string with the tokens
   * defined in the 'locations' element.
   *
   * Note that only the root package may define locations.
   *
   * @return \Drupal\Component\Scaffold\Interpolator
   *   Interpolator that will do replacements in a string using tokens in
   *   'locations' element.
   */
  public function getLocationReplacements() {
    return (new Interpolator())->setData($this->ensureLocations());
  }

  /**
   * Ensures that all of the locations defined in the scaffold files exist.
   *
   * Create them on the filesystem if they do not.
   */
  protected function ensureLocations() {
    $fs = new Filesystem();
    $locations = $this->getOptions()->locations() + ['web_root' => './'];
    $locations = array_map(function ($location) use ($fs) {
      $fs->ensureDirectoryExists($location);
      $location = realpath($location);
      return $location;
    }, $locations);
    return $locations;
  }

}
