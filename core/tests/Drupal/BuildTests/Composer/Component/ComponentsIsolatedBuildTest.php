<?php

declare(strict_types=1);

namespace Drupal\BuildTests\Composer\Component;

use Drupal\BuildTests\Composer\ComposerBuildTestBase;
use Drupal\Composer\Composer;
use Symfony\Component\Finder\Finder;

/**
 * Try to install dependencies per component, using Composer.
 *
 * @group #slow
 * @group Composer
 * @group Component
 *
 * @coversNothing
 */
class ComponentsIsolatedBuildTest extends ComposerBuildTestBase {

  /**
   * Provides an array with relative paths to the component paths.
   *
   * @return array
   *   An array with relative paths to the component paths.
   */
  public static function provideComponentPaths(): array {
    $data = [];
    // During the dataProvider phase, there is not a workspace directory yet.
    // So we will find relative paths and assemble them with the workspace
    // path later.
    $drupal_root = self::getDrupalRootStatic();
    $composer_json_finder = self::getComponentPathsFinder($drupal_root);

    /** @var \Symfony\Component\Finder\SplFileInfo $path */
    foreach ($composer_json_finder->getIterator() as $path) {
      $data[$path->getRelativePath()] = ['/' . $path->getRelativePath()];
    }
    return $data;
  }

  /**
   * Test whether components' composer.json can be installed in isolation.
   *
   * @dataProvider provideComponentPaths
   */
  public function testComponentComposerJson(string $component_path): void {
    // Only copy the components. Copy all of them because some of them depend on
    // each other.
    $finder = new Finder();
    $finder->files()
      ->ignoreUnreadableDirs()
      ->in($this->getDrupalRoot() . static::$componentsPath)
      ->ignoreDotFiles(FALSE)
      ->ignoreVCS(FALSE);
    $this->copyCodebase($finder->getIterator());

    $working_dir = $this->getWorkingPath() . static::$componentsPath . $component_path;

    // We add path repositories so we can wire internal dependencies together.
    $this->addExpectedRepositories($working_dir);

    // Perform the installation.
    $this->executeCommand("composer install --working-dir=$working_dir --no-interaction --no-progress");
    $this->assertCommandSuccessful();
  }

  /**
   * Adds expected repositories as path repositories to package under test.
   *
   * @param string $working_dir
   *   The working directory.
   */
  protected function addExpectedRepositories(string $working_dir): void {
    foreach ($this->provideComponentPaths() as $path) {
      $path = $path[0];
      $package_name = 'drupal/core' . strtolower(preg_replace('/[A-Z]/', '-$0', substr($path, 1)));
      $path_repo = $this->getWorkingPath() . static::$componentsPath . $path;
      $repo_name = strtolower($path);
      // Add path repositories with the current version number to the current
      // package under test.
      $drupal_version = Composer::drupalVersionBranch();
      $this->executeCommand("composer config repositories.$repo_name " .
        "'{\"type\": \"path\",\"url\": \"$path_repo\",\"options\": {\"versions\": {\"$package_name\": \"$drupal_version\"}}}' --working-dir=$working_dir");
    }
  }

}
