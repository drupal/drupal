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

    // Sort our required packages by key.
    ksort($composer['require']);

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
