<?php

namespace Drupal\Composer\Plugin\RecipeUnpack;

use Composer\Composer;
use Composer\Factory;
use Composer\IO\IOInterface;
use Composer\Json\JsonFile;
use Composer\Json\JsonManipulator;
use Composer\Package\Locker;
use Composer\Package\RootPackageInterface;

/**
 * Provides access to and manipulation of the root composer files.
 *
 * @internal
 */
final class RootComposer {

  /**
   * The JSON manipulator for the contents of the root composer.json.
   */
  private JsonManipulator $composerManipulator;

  /**
   * The locked root composer.json content.
   *
   * @var array<string, mixed>|null
   */
  private ?array $composerLockedContent = NULL;

  public function __construct(
    private readonly Composer $composer,
    private readonly IOInterface $io,
  ) {}

  /**
   * Retrieves the JSON manipulator for the contents of the root composer.json.
   *
   * @return \Composer\Json\JsonManipulator
   *   The JSON manipulator.
   */
  public function getComposerManipulator(): JsonManipulator {
    $this->composerManipulator ??= new JsonManipulator(file_get_contents(Factory::getComposerFile()));
    return $this->composerManipulator;
  }

  /**
   * Gets the locked root composer.json content.
   *
   * @return array<string, mixed>
   *   The locked root composer.json content.
   */
  public function getComposerLockedContent(): array {
    $this->composerLockedContent ??= $this->composer->getLocker()->getLockData();
    return $this->composerLockedContent;
  }

  /**
   * Removes an element from the composer lock.
   *
   * @param string $key
   *   The key of the element to remove.
   * @param string $index
   *   The index of the element to remove.
   */
  public function removeFromComposerLock(string $key, string $index): void {
    unset($this->composerLockedContent[$key][$index]);
  }

  /**
   * Adds an element to the composer lock.
   *
   * @param string $key
   *   The key of the element to add.
   * @param array $data
   *   The data to add.
   */
  public function addToComposerLock(string $key, array $data): void {
    $this->composerLockedContent[$key][] = $data;
  }

  /**
   * Writes the root composer files.
   *
   * The files written are:
   *   - composer.json
   *   - composer.lock
   *   - vendor/composer/installed.json
   *   - vendor/composer/installed.php
   *
   * @throws \RuntimeException
   *   If the root composer could not be updated.
   */
  public function writeFiles(): void {
    // Write composer.json.
    $composer_json = Factory::getComposerFile();
    $composer_content = $this->getComposerManipulator()->getContents();
    if (!file_put_contents($composer_json, $composer_content)) {
      throw new \RuntimeException(sprintf('Could not update %s', $composer_json));
    }

    // Create package lists for lock file update.
    $local_repo = $this->composer->getRepositoryManager()->getLocalRepository();
    $packages = $dev_packages = [];
    $dev_package_names = $local_repo->getDevPackageNames();
    foreach ($local_repo->getPackages() as $package) {
      if (in_array($package->getName(), $dev_package_names, TRUE)) {
        $dev_packages[] = $package;
      }
      else {
        $packages[] = $package;
      }
    }

    $lock_file_path = Factory::getLockFile(Factory::getComposerFile());
    $lock_file = new JsonFile($lock_file_path, io: $this->io);
    $old_locker = $this->composer->getLocker();
    $locker = new Locker($this->io, $lock_file, $this->composer->getInstallationManager(), $composer_content);
    $composer_locker_content = $this->getComposerLockedContent();

    // Write the lock file.
    $locker->setLockData(
      $packages,
      $dev_packages,
      $composer_locker_content['platform'],
      $composer_locker_content['platform-dev'],
      $composer_locker_content['aliases'],
      $old_locker->getMinimumStability(),
      $old_locker->getStabilityFlags(),
      $old_locker->getPreferStable(),
      $old_locker->getPreferLowest(),
      $old_locker->getPlatformOverrides(),
    );
    $this->composer->setLocker($locker);

    // Update installed.json and installed.php.
    $local_repo->write($local_repo->getDevMode() ?? TRUE, $this->composer->getInstallationManager());

    $this->io->write("Unpacking has updated the root composer files.", verbosity: IOInterface::VERBOSE);

    assert(self::checkRootPackage($composer_content, $this->composer->getPackage()), 'Composer root package and composer.json match');
  }

  /**
   * Checks that the composer content and root package match.
   *
   * @param string $composer_content
   *   The root composer content.
   * @param \Composer\Package\RootPackageInterface $root_package
   *   The root package.
   *
   * @return bool
   *   TRUE if the composer content and root package match, FALSE if not.
   */
  private static function checkRootPackage(string $composer_content, RootPackageInterface $root_package): bool {
    $composer = JsonFile::parseJson($composer_content);
    return empty(array_diff_key($root_package->getRequires(), $composer['require'] ?? [])) && empty(array_diff_key($root_package->getDevRequires(), $composer['require-dev'] ?? []));
  }

}
