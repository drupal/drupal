<?php

namespace Drupal\Composer\Plugin\RecipeUnpack;

use Composer\Composer;
use Composer\InstalledVersions;
use Composer\IO\IOInterface;
use Composer\Package\PackageInterface;
use Composer\Semver\Intervals;

/**
 * Manages the recipe unpacking process.
 *
 * @internal
 */
final readonly class UnpackManager {

  /**
   * The root composer with the root dependencies to be manipulated.
   */
  private RootComposer $rootComposer;

  /**
   * The unpack options.
   */
  public UnpackOptions $unpackOptions;

  public function __construct(
    private Composer $composer,
    private IOInterface $io,
  ) {
    $this->rootComposer = new RootComposer($composer, $io);
    $this->unpackOptions = UnpackOptions::create($composer->getPackage()->getExtra());
  }

  /**
   * Unpacks the packages in the provided collection.
   *
   * @param \Drupal\Composer\Plugin\RecipeUnpack\UnpackCollection $unpackCollection
   *   The collection of recipe packages to unpack.
   */
  public function unpack(UnpackCollection $unpackCollection): void {
    if (count($unpackCollection) === 0) {
      // Early return to avoid unnecessary work.
      return;
    }

    foreach ($unpackCollection as $package) {
      $unpacker = new Unpacker(
        $package,
        $this->composer,
        $this->rootComposer,
        $unpackCollection,
        $this->unpackOptions,
        $this->io,
      );
      $unpacker->unpackDependencies();
      $this->io->write("<info>{$package->getName()}</info> unpacked.");
    }

    // Unpacking uses \Composer\Semver\Intervals::isSubsetOf() to choose between
    // constraints.
    Intervals::clear();

    $this->rootComposer->writeFiles();
  }

  /**
   * Determines if the provided package is present in the root composer.json.
   *
   * @param string $package_name
   *   The package name to check.
   *
   * @return bool
   *   TRUE if the package is present in the root composer.json, FALSE if not.
   */
  public function isRootDependency(string $package_name): bool {
    $root_package = $this->composer->getPackage();
    return isset($root_package->getRequires()[$package_name]) || isset($root_package->getDevRequires()[$package_name]);
  }

  /**
   * Checks if a package is a dev requirement.
   *
   * @param \Composer\Package\PackageInterface $package
   *   The package to check.
   *
   * @return bool
   *   TRUE if the package is present in require-dev or due to a package in
   *   require-dev, FALSE if not.
   */
  public static function isDevRequirement(PackageInterface $package): bool {
    // Check if package is either a regular or dev requirement.
    return InstalledVersions::isInstalled($package->getName()) &&
      // Check if package is a regular requirement.
      !InstalledVersions::isInstalled($package->getName(), FALSE);
  }

}
