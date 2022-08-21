<?php

namespace Drupal\BuildTests\Composer;

use Drupal\BuildTests\Framework\BuildTestBase;
use Symfony\Component\Finder\Finder;

/**
 * Base class for Composer build tests.
 *
 * @coversNothing
 */
abstract class ComposerBuildTestBase extends BuildTestBase {

  /**
   * Relative path from Drupal root to the Components directory.
   *
   * @var string
   */
  protected static $componentsPath = '/core/lib/Drupal/Component';

  /**
   * Assert that the VERSION constant in Drupal.php is the expected value.
   *
   * @param string $expectedVersion
   *   The expected version.
   * @param string $dir
   *   The path to the site root.
   *
   * @internal
   */
  protected function assertDrupalVersion(string $expectedVersion, string $dir): void {
    $drupal_php_path = $dir . '/core/lib/Drupal.php';
    $this->assertFileExists($drupal_php_path);

    // Read back the Drupal version that was set and assert it matches
    // expectations
    $this->executeCommand("php -r 'include \"$drupal_php_path\"; print \Drupal::VERSION;'");
    $this->assertCommandSuccessful();
    $this->assertCommandOutputContains($expectedVersion);
  }

  /**
   * Find all the composer.json files for components.
   *
   * @param string $drupal_root
   *   The Drupal root directory.
   *
   * @return \Symfony\Component\Finder\Finder
   *   A Finder object with all the composer.json files for components.
   */
  protected function getComponentPathsFinder(string $drupal_root): Finder {
    $finder = new Finder();
    $finder->name('composer.json')
      ->in($drupal_root . static::$componentsPath)
      ->ignoreUnreadableDirs()
      ->depth(1);

    return $finder;
  }

}
