<?php

namespace Drupal\Composer\Generator\Builder;

/**
 * Builder to produce metapackage for drupal/core-dev.
 */
class DrupalDevDependenciesBuilder extends DrupalPackageBuilder {

  /**
   * {@inheritdoc}
   */
  public function getPath() {
    return 'DevDependencies';
  }

  /**
   * {@inheritdoc}
   */
  public function getPackage() {

    $composer = $this->initialPackageMetadata();

    // Put everything from Drupal's "require-dev" into our "require" section.
    $composer['require'] = $this->drupalCoreInfo->getRequireDev();

    // If the require-dev is bringing in a dev version of behat/mink, convert
    // the requirement to a more flexible set of versions.
    // @todo: remove when https://www.drupal.org/node/3078671 is fixed.
    if (isset($composer['require']['behat/mink']) && ($composer['require']['behat/mink'] == '1.7.x-dev')) {
      $composer['require']['behat/mink'] = '1.8.0 | 1.7.1.1 | 1.7.x-dev';
    }

    // Do the same sort of conversion for behat/mink-selenium2-driver.
    if (isset($composer['require']['behat/mink-selenium2-driver']) && ($composer['require']['behat/mink-selenium2-driver'] == '1.3.x-dev')) {
      $composer['require']['behat/mink-selenium2-driver'] = '1.4.0 | 1.3.1.1 | 1.3.x-dev';
    }

    // Sort our required packages by key.
    ksort($composer['require']);

    return $composer;
  }

  /**
   * Returns the initial package metadata that describes the metapackage.
   *
   * @return array
   */
  protected function initialPackageMetadata() {
    return [
      "name" => "drupal/core-dev",
      "type" => "metapackage",
      "description" => "require-dev dependencies from drupal/drupal; use in addition to drupal/core-recommended to run tests from drupal/core.",
      "license" => "GPL-2.0-or-later",
      "conflict" => [
        "webflo/drupal-core-require-dev" => "*",
      ],
    ];
  }

}
