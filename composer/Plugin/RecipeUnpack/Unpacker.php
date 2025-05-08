<?php

namespace Drupal\Composer\Plugin\RecipeUnpack;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Package\Link;
use Composer\Package\PackageInterface;
use Composer\Semver\VersionParser;

/**
 * Handles the details of unpacking a specific recipe.
 *
 * @internal
 */
final readonly class Unpacker {

  /**
   * The version parser.
   */
  private VersionParser $versionParser;

  public function __construct(
    private PackageInterface $package,
    private Composer $composer,
    private RootComposer $rootComposer,
    private UnpackCollection $unpackCollection,
    private UnpackOptions $unpackOptions,
    private IOInterface $io,
  ) {
    $this->versionParser = new VersionParser();
  }

  /**
   * Unpacks the package's dependencies to the root composer.json and lock file.
   */
  public function unpackDependencies(): void {
    $this->updateComposerJsonPackages();
    $this->updateComposerLockContent();
    $this->unpackCollection->markPackageUnpacked($this->package);
  }

  /**
   * Processes dependencies of the package that is being unpacked.
   *
   * If the dependency is a recipe and should be unpacked, we add it into the
   * package queue so that it will be unpacked as well. If the dependency is not
   * a recipe, or an ignored recipe, the package link will be yielded.
   *
   * @param array<string, \Composer\Package\Link> $package_dependency_links
   *   The package dependencies to process.
   *
   * @return iterable<\Composer\Package\Link>
   *   The package dependencies to add to composer.json.
   */
  private function processPackageDependencies(array $package_dependency_links): iterable {
    foreach ($package_dependency_links as $link) {
      if ($link->getTarget() === $this->package->getName()) {
        // This dependency is the same as the current package, so let's skip it.
        continue;
      }

      $package = $this->getPackageFromLinkTarget($link);

      // If we can't find the package in the local repository that's because it
      // has already been removed therefore skip it.
      if ($package === NULL) {
        continue;
      }

      if ($package->getType() === Plugin::RECIPE_PACKAGE_TYPE) {
        if ($this->unpackCollection->isUnpacked($package)) {
          // This dependency is already unpacked.
          continue;
        }

        if (!$this->unpackOptions->isIgnored($package)) {
          // This recipe should be unpacked as well.
          $this->unpackCollection->add($package);
          continue;
        }
        else {
          // This recipe should not be unpacked. But it might need to be added
          // to the root composer.json
          $this->io->write(sprintf('<info>%s</info> not unpacked because it is ignored.', $package->getName()), verbosity: IOInterface::VERBOSE);
        }
      }

      yield $link;
    }
  }

  /**
   * Updates the composer.json content with the package being unpacked.
   *
   * This method will add all the package dependencies to the root composer.json
   * content and also remove the package itself from the root composer.json.
   *
   * @throws \RuntimeException
   *   If the composer.json could not be updated.
   */
  private function updateComposerJsonPackages(): void {
    $composer_manipulator = $this->rootComposer->getComposerManipulator();
    $composer_config = $this->composer->getConfig();
    $sort_packages = $composer_config->get('sort-packages');
    $root_package = $this->composer->getPackage();
    $root_requires = $root_package->getRequires();
    $root_dev_requires = $root_package->getDevRequires();

    foreach ($this->processPackageDependencies($this->package->getRequires()) as $package_dependency) {
      $dependency_name = $package_dependency->getTarget();
      $recipe_constraint_string = $package_dependency->getPrettyConstraint();
      if (isset($root_requires[$dependency_name])) {
        $recipe_constraint_string = SemVer::minimizeConstraints($this->versionParser, $recipe_constraint_string, $root_requires[$dependency_name]->getPrettyConstraint());
        if ($recipe_constraint_string === $root_requires[$dependency_name]) {
          // This dependency is already in the required section with the
          // correct constraint.
          continue;
        }
      }
      elseif (isset($root_dev_requires[$dependency_name])) {
        $recipe_constraint_string = SemVer::minimizeConstraints($this->versionParser, $recipe_constraint_string, $root_dev_requires[$dependency_name]->getPrettyConstraint());
        // This dependency is already in the require-dev section. We will
        // move it to the require section.
        $composer_manipulator->removeSubNode('require-dev', $dependency_name);
      }

      // Add the dependency to the required section. If it cannot be added, then
      // throw an exception.
      if (!$composer_manipulator->addLink(
          'require',
          $dependency_name,
          $recipe_constraint_string,
          $sort_packages,
      )) {
        throw new \RuntimeException(sprintf('Unable to manipulate composer.json during the unpack of %s',
          $dependency_name,
        ));
      }
      $link = new Link($root_package->getName(), $dependency_name, $this->versionParser->parseConstraints($recipe_constraint_string), Link::TYPE_REQUIRE, $recipe_constraint_string);
      $root_requires[$dependency_name] = $link;
      unset($root_dev_requires[$dependency_name]);
      $this->io->write(sprintf('Adding <info>%s</info> (<comment>%s</comment>) to composer.json during the unpack of <info>%s</info>', $dependency_name, $recipe_constraint_string, $this->package->getName()), verbosity: IOInterface::VERBOSE);
    }

    // Ensure the written packages are no longer in the dev package names.
    $local_repo = $this->composer->getRepositoryManager()->getLocalRepository();
    $local_repo->setDevPackageNames(array_diff($local_repo->getDevPackageNames(), array_keys($root_requires)));

    // Update the root package to reflect the changes.
    $root_package->setDevRequires($root_dev_requires);
    $root_package->setRequires($root_requires);

    $composer_manipulator->removeSubNode(UnpackManager::isDevRequirement($this->package) ? 'require-dev' : 'require', $this->package->getName());
    $this->io->write(sprintf('Removing <info>%s</info> from composer.json', $this->package->getName()), verbosity: IOInterface::VERBOSE);

    $composer_manipulator->removeMainKeyIfEmpty('require-dev');
  }

  /**
   * Updates the composer.lock content and keeps the local repo in sync.
   *
   * This method will remove the package itself from the composer.lock content
   * in the root composer.
   */
  private function updateComposerLockContent(): void {
    $composer_locker_content = $this->rootComposer->getComposerLockedContent();
    $root_package = $this->composer->getPackage();
    $root_requires = $root_package->getRequires();
    $root_dev_requires = $root_package->getDevRequires();
    $local_repo = $this->composer->getRepositoryManager()->getLocalRepository();

    if (isset($root_requires[$this->package->getName()])) {
      unset($root_requires[$this->package->getName()]);
      $root_package->setRequires($root_requires);
    }

    foreach ($composer_locker_content['packages'] as $key => $lock_data) {
      // Find the package being unpacked in the composer.lock content and
      // remove it.
      if ($lock_data['name'] === $this->package->getName()) {
        $this->rootComposer->removeFromComposerLock('packages', $key);
        // If the package is in require-dev we need to move the lock data.
        if (isset($root_dev_requires[$lock_data['name']])) {
          $this->rootComposer->addToComposerLock('packages-dev', $lock_data);
          $dev_package_names = $local_repo->getDevPackageNames();
          $dev_package_names[] = $lock_data['name'];
          $local_repo->setDevPackageNames($dev_package_names);
          return;
        }
        break;
      }
    }
    $local_repo->setDevPackageNames(array_diff($local_repo->getDevPackageNames(), [$this->package->getName()]));
    $local_repo->removePackage($this->package);
    if (isset($root_dev_requires[$this->package->getName()])) {
      unset($root_dev_requires[$this->package->getName()]);
      $root_package->setDevRequires($root_dev_requires);
    }
  }

  /**
   * Gets the package object from a link's target.
   *
   * @param \Composer\Package\Link $dependency
   *   The link dependency.
   *
   * @return \Composer\Package\PackageInterface|null
   *   The package object.
   */
  private function getPackageFromLinkTarget(Link $dependency): ?PackageInterface {
    return $this->composer->getRepositoryManager()
      ->getLocalRepository()
      ->findPackage($dependency->getTarget(), $dependency->getConstraint());
  }

}
