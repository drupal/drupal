<?php

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
 *
 * @requires externalCommand composer
 */
class ComponentsIsolatedBuildTest extends ComposerBuildTestBase {

  /**
   * Provides an array with relative paths to the component paths.
   *
   * @return array
   *   An array with relative paths to the component paths.
   */
  public function provideComponentPaths(): array {
    $data = [];
    // During the dataProvider phase, there is not a workspace directory yet.
    // So we will find relative paths and assemble them with the workspace
    // path later.
    $drupal_root = $this->getDrupalRoot();
    $composer_json_finder = $this->getComponentPathsFinder($drupal_root);

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
    $repo_paths = [
      'Render' => 'drupal/core-render',
      'Utility' => 'drupal/core-utility',
    ];
    foreach ($repo_paths as $path => $package_name) {
      $path_repo = $this->getWorkingPath() . static::$componentsPath . '/' . $path;
      $repo_name = strtolower($path);
      // Add path repositories with the current version number to the current
      // package under test.
      $drupal_version = Composer::drupalVersionBranch();
      $this->executeCommand("composer config repositories.$repo_name " .
        "'{\"type\": \"path\",\"url\": \"$path_repo\",\"options\": {\"versions\": {\"$package_name\": \"$drupal_version\"}}}' --working-dir=$working_dir");
    }
  }

}
