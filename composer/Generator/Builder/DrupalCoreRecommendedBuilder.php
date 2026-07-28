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

    // The list of package prefixes we do not want in the 'require' section.
    $remove_list = [
      'composer/installers',
      'drupal/core',
      // Guzzle regularly releases security fixes in only minor releases.
      'guzzlehttp/',
      // This package contains no code other than interfaces, so allow sites
      // to use any compatible version without needing to switch off of
      // drupal/core-recommended.
      'psr/http-message',
      // sebastian/diff is a PHPUnit dependency with an annual major release
      // cycle. In order to allow supporting multiple PHPUnit major versions,
      // we cannot let it be pinned.
      'sebastian/diff',
      // Symfony polyfills are transitive dependency that almost exclusively
      // release security releases as minor releases.
      'symfony/polyfill-',
      // Twig regularly releases security fixes in only minor releases.
      'twig/',
      'wikimedia/composer-merge-plugin',
    ];

    // Copy the 'packages' section from the Composer lock into our 'require'
    // section. There is also a 'packages-dev' section, but we do not need
    // to pin 'require-dev' versions, as 'require-dev' dependencies are never
    // included from subprojects. Use 'drupal/core-dev' to get Drupal's
    // dev dependencies.
    foreach ($composerLockData['packages'] as $package) {
      // If there is no 'source' record, then this is a path repository
      // or something else that we do not want to include.
      if (!isset($package['source'])) {
        continue;
      }

      // Skip packages that are in the remove list.
      foreach ($remove_list as $remove) {
        if (str_starts_with($package['name'], $remove)) {
          continue 2;
        }
      }

      $composer['require'][$package['name']] = '~' . $package['version'];
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
      "name" => "drupal/core-recommended",
      "type" => "metapackage",
      "description" => "Core and its dependencies with known-compatible minor versions. Require this project INSTEAD OF drupal/core.",
      "license" => "GPL-2.0-or-later",
      "conflict" => [
        "webflo/drupal-core-strict" => "*",
      ],
      "require" => [
        "drupal/core" => Composer::drupalVersionBranch(),
      ],
      "extra" => [
        "branch-alias" => [
          "dev-main" => Composer::drupalVersionBranch(),
        ],
      ],
    ];
  }

}
