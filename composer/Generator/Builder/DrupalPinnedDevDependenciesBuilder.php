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

        // If the require-dev is bringing in a dev version of behat/mink,
        // convert the requirement to a more flexible set of versions.
        // @todo: remove when https://www.drupal.org/node/3078671 is fixed.
        if (($package['name'] == 'behat/mink') && (($package['version'] == 'dev-master') || ($package['version'] == '1.7.x-dev'))) {
          $composer['require']['behat/mink'] = '1.8.0 | 1.7.1.1 | 1.7.x-dev';
        }

        // Do the same sort of conversion for behat/mink-selenium2-driver.
        if (($package['name'] == 'behat/mink-selenium2-driver') && (($package['version'] == 'dev-master') || ($package['version'] == '1.3.x-dev'))) {
          $composer['require']['behat/mink-selenium2-driver'] = '1.4.0 | 1.3.1.1 | 1.3.x-dev';
        }
      }
    }
    return $composer;
  }

  /**
   * Returns the initial package metadata that describes the metapackage.
   *
   * @return array
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
