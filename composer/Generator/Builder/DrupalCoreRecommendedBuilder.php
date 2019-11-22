<?php

namespace Drupal\Composer\Generator\Builder;

use Drupal\Composer\Composer;

/**
 * Builder to produce metapackage for drupal/core-recommended.
 */
class DrupalCoreRecommendedBuilder extends DrupalPackageBuilder {

  /**
   * {@inheritdoc}
   */
  public function getPath() {
    return 'CoreRecommended';
  }

  /**
   * {@inheritdoc}
   */
  public function getPackage() {

    $composer = $this->initialPackageMetadata();

    // Pull up the composer lock data.
    $composerLockData = $this->drupalCoreInfo->composerLock();
    if (!isset($composerLockData['packages'])) {
      return $composer;
    }

    // Make a list of packages we do not want to put in the 'require' section.
    $remove_list = ['drupal/core', 'wikimedia/composer-merge-plugin'];

    // Copy the 'packages' section from the Composer lock into our 'require'
    // section. There is also a 'packages-dev' section, but we do not need
    // to pin 'require-dev' versions, as 'require-dev' dependencies are never
    // included from subprojects. Use 'drupal/core-dev' to get Drupal's
    // dev dependencies.
    foreach ($composerLockData['packages'] as $package) {
      // If there is no 'source' record, then this is a path repository
      // or something else that we do not want to include.
      if (isset($package['source']) && !in_array($package['name'], $remove_list)) {
        $composer['require'][$package['name']] = $package['version'];
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
      "name" => "drupal/core-recommended",
      "type" => "metapackage",
      "description" => "Locked core dependencies; require this project INSTEAD OF drupal/core.",
      "license" => "GPL-2.0-or-later",
      "conflict" => [
        "webflo/drupal-core-strict" => "*",
      ],
      "require" => [
        "drupal/core" => Composer::drupalVersionBranch(),
      ],
    ];
  }

}
