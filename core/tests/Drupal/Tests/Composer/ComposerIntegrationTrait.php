<?php

declare(strict_types=1);

namespace Drupal\Tests\Composer;

use Symfony\Component\Finder\Finder;

/**
 * Some utility functions for testing the Composer integration.
 */
trait ComposerIntegrationTrait {

  /**
   * Get a Finder object to traverse all of the composer.json files in core.
   *
   * @param string $drupal_root
   *   Absolute path to the root of the Drupal installation.
   *
   * @return \Symfony\Component\Finder\Finder
   *   A Finder object able to iterate all the composer.json files in core.
   */
  public static function getComposerJsonFinder($drupal_root) {
    $composer_json_finder = new Finder();
    $composer_json_finder->name('composer.json')
      ->in([
        // Only find composer.json files within composer/ and core/ directories
        // so we don't inadvertently test contrib in local dev environments.
        $drupal_root . '/composer',
        $drupal_root . '/core',
      ])
      ->ignoreUnreadableDirs()
      ->notPath('#^vendor#')
      ->notPath('#/fixture#');
    return $composer_json_finder;
  }

}
