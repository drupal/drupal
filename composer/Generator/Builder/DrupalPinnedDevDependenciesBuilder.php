<?php

namespace Drupal\Composer\Generator\Builder;

use Drupal\Composer\Composer;

/**
 * Builder to produce metapackage for drupal/core-dev-pinned.
 */
class DrupalPinnedDevDependenciesBuilder extends DrupalPackageBuilder {

  /**
   * {@inheritdoc}
   */
  public function getPath() {
    return 'PinnedDevDependencies';
  }

  /**
   * {@inheritdoc}
   */
  public function getPackage() {

    $composer = $this->initialPackageMetadata();

    // Pull the exact versions of the dependencies from the composer.lock
    // file and use it to build our 'require' section.
    $composerLockData = $this->drupalCoreInfo->composerLock();

    if (isset($composerLockData['packages-dev'])) {
      foreach ($composerLockData['packages-dev'] as $package) {
        $composer['require'][$package['name']] = $package['version'];
      }
    }
    return $composer;
  }

  /**
   * Returns the initial package metadata that describes the metapackage.
   *
   * @return array
   *   The initial package metadata.
   */
  protected function initialPackageMetadata() {
    return [
      "name" => "drupal/core-dev-pinned",
      "type" => "metapackage",
      "description" => "Pinned require-dev dependencies from drupal/drupal; use in addition to drupal/core-recommended to run tests from drupal/core.",
      "license" => "GPL-2.0-or-later",
      "conflict" => [
        "webflo/drupal-core-require-dev" => "*",
      ],
      "require" => [
        "drupal/core" => Composer::drupalVersionBranch(),
      ],
    ];
  }

}
