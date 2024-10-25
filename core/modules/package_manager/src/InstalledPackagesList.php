<?php

declare(strict_types=1);

namespace Drupal\package_manager;

use Composer\Semver\Comparator;

/**
 * Defines a class to list installed Composer packages.
 *
 * This only lists the packages that were installed at the time this object was
 * instantiated. If packages are added or removed later on, a new package list
 * must be created to reflect those changes.
 *
 * @see \Drupal\package_manager\ComposerInspector::getInstalledPackagesList()
 */
final class InstalledPackagesList extends \ArrayObject {

  /**
   * {@inheritdoc}
   */
  public function append(mixed $value): never {
    throw new \LogicException('Installed package lists cannot be modified.');
  }

  /**
   * {@inheritdoc}
   */
  public function offsetSet(mixed $key, mixed $value): never {
    throw new \LogicException('Installed package lists cannot be modified.');
  }

  /**
   * {@inheritdoc}
   */
  public function offsetUnset(mixed $key): never {
    throw new \LogicException('Installed package lists cannot be modified.');
  }

  /**
   * {@inheritdoc}
   */
  public function offsetGet(mixed $key): ?InstalledPackage {
    // Overridden to provide a clearer return type hint and compatibility with
    // the null-safe operator.
    if ($this->offsetExists($key)) {
      return parent::offsetGet($key);
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function exchangeArray(mixed $array): never {
    throw new \LogicException('Installed package lists cannot be modified.');
  }

  /**
   * Returns the packages that are in this list, but not in another.
   *
   * @param self $other
   *   Another list of installed packages.
   *
   * @return static
   *   A list of packages which are in this list but not the other one, keyed by
   *   name.
   */
  public function getPackagesNotIn(self $other): static {
    $packages = array_diff_key($this->getArrayCopy(), $other->getArrayCopy());
    return new static($packages);
  }

  /**
   * Returns the packages which have a different version in another list.
   *
   * This compares this list with another one, and returns a list of packages
   * which are present in both, but in different versions.
   *
   * @param self $other
   *   Another list of installed packages.
   *
   * @return static
   *   A list of packages which are present in both this list and the other one,
   *   but in different versions, keyed by name.
   */
  public function getPackagesWithDifferentVersionsIn(self $other): static {
    // Only compare packages that are both here and there.
    $packages = array_intersect_key($this->getArrayCopy(), $other->getArrayCopy());

    $packages = array_filter($packages, fn (InstalledPackage $p) => Comparator::notEqualTo($p->version, $other[$p->name]->version));
    return new static($packages);
  }

  /**
   * Returns the package for a given Drupal project name, if it is installed.
   *
   * Although it is common for the package name to match the project name (for
   * example, a project name of `token` is likely part of the `drupal/token`
   * package), it's not guaranteed. Therefore, in order to avoid inadvertently
   * reading information about the wrong package, use this method to properly
   * determine which package installs a particular Drupal project.
   *
   * @param string $project_name
   *   The name of a Drupal project.
   *
   * @return \Drupal\package_manager\InstalledPackage|null
   *   The Composer package which installs the project, or NULL if it could not
   *   be determined.
   */
  public function getPackageByDrupalProjectName(string $project_name): ?InstalledPackage {
    $matching_package = NULL;
    foreach ($this as $package) {
      if ($package->getProjectName() === $project_name) {
        if ($matching_package) {
          throw new \UnexpectedValueException(sprintf("Project '%s' was found in packages '%s' and '%s'.", $project_name, $matching_package->name, $package->name));
        }
        $matching_package = $package;
      }
    }
    return $matching_package;
  }

  /**
   * Returns the canonical names of the supported core packages.
   *
   * @return string[]
   *   The canonical list of supported core package names.
   */
  private static function getCorePackageList(): array {
    // This method returns the installed packages that are considered part of
    // Drupal core. There's no way to tell by package type alone, since these
    // packages have varying types, but are all part of Drupal core's
    // repository.
    return [
      'drupal/core',
      'drupal/core-composer-scaffold',
      'drupal/core-dev',
      'drupal/core-dev-pinned',
      'drupal/core-project-message',
      'drupal/core-recommended',
      'drupal/core-vendor-hardening',
    ];
  }

  /**
   * Returns a list of installed core packages.
   *
   * Packages returned by ::getCorePackageList() are considered core packages.
   *
   * @param bool $include_dev
   *   (optional) Whether to include core packages intended for development.
   *   Defaults to TRUE.
   *
   * @return static
   *   A list of the installed core packages.
   */
  public function getCorePackages(bool $include_dev = TRUE): static {
    $core_packages = array_intersect_key(
      $this->getArrayCopy(),
      array_flip(static::getCorePackageList())
    );

    // If drupal/core-recommended is present, it supersedes drupal/core, since
    // drupal/core will always be one of its direct dependencies.
    if (array_key_exists('drupal/core-recommended', $core_packages)) {
      unset($core_packages['drupal/core']);
    }
    if (!$include_dev) {
      unset($core_packages['drupal/core-dev']);
      unset($core_packages['drupal/core-dev-pinned']);
    }
    return new static($core_packages);
  }

}
