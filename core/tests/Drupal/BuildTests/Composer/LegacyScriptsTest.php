<?php

namespace Drupal\BuildTests\Composer;

use Composer\Json\JsonFile;
use Drupal\BuildTests\Framework\BuildTestBase;
use Drupal\Composer\Composer;

/**
 * @group Composer
 * @group legacy
 * @requires externalCommand composer
 * @coversDefaultClass \Drupal\Core\Composer\Composer
 */
class LegacyScriptsTest extends BuildTestBase {

  /**
   * @covers ::vendorTestCodeCleanup
   */
  public function testVendorTestCodeCleanup() {
    $package_dir = 'composer/Template/RecommendedProject';

    // Create a "Composer"-type repository containing one entry for every
    // package in the vendor directory.
    $vendor_packages_path = $this->getWorkspaceDirectory() . '/vendor_packages/packages.json';
    $this->makeVendorPackage($vendor_packages_path);

    // Make a copy of the code to alter in the workspace directory.
    $this->copyCodebase();

    // Remove the packages.drupal.org entry (and any other custom repository)
    // from the site under test's repositories section. There is no way to do
    // this via `composer config --unset`, so we read and rewrite composer.json.
    $composer_json_path = $this->getWorkspaceDirectory() . "/$package_dir/composer.json";
    $composer_json = json_decode(file_get_contents($composer_json_path), TRUE);
    unset($composer_json['repositories']);
    $json = json_encode($composer_json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    file_put_contents($composer_json_path, $json);

    // Set up the template to use our path repos. Inclusion of metapackages is
    // reported differently, so we load up a separate set for them.
    $metapackage_path_repos = $this->getPathReposForType($this->getWorkspaceDirectory(), 'Metapackage');
    $this->assertArrayHasKey('drupal/core-recommended', $metapackage_path_repos);
    $path_repos = array_merge($metapackage_path_repos, $this->getPathReposForType($this->getWorkspaceDirectory(), 'Plugin'));
    // Always add drupal/core as a path repo.
    $path_repos['drupal/core'] = $this->getWorkspaceDirectory() . '/core';
    foreach ($path_repos as $name => $path) {
      $this->executeCommand("composer config --no-interaction repositories.$name path $path", $package_dir);
      $this->assertCommandSuccessful();
    }

    // Add our vendor package repository to our site under test's repositories
    // section. Call it "local" (although the name does not matter).
    $this->executeCommand("composer config --no-interaction repositories.local composer file://" . $vendor_packages_path, $package_dir);
    $this->assertCommandSuccessful();

    // Add the vendorTestCodeCleanup script as a post-install command.
    $this->executeCommand('composer config scripts.post-package-install "Drupal\\Core\\Composer\\Composer::vendorTestCodeCleanup"');
    $this->assertCommandSuccessful();

    // Attempt to install packages which will trigger the script.
    $this->executeCommand('composer install');
    $this->assertCommandSuccessful();
  }

  /**
   * Get Composer items that we want to be path repos, from within a directory.
   *
   * @param string $workspace_directory
   *   The full path to the workspace directory.
   * @param string $subdir
   *   The subdirectory to search under composer/.
   *
   * @return string[]
   *   Array of paths, indexed by package name.
   */
  public function getPathReposForType($workspace_directory, $subdir) {
    // Find the Composer items that we want to be path repos.
    /** @var \SplFileInfo[] $path_repos */
    $path_repos = Composer::composerSubprojectPaths($workspace_directory, $subdir);

    $data = [];
    foreach ($path_repos as $path_repo) {
      $json_file = new JsonFile($path_repo->getPathname());
      $json = $json_file->read();
      $data[$json['name']] = $path_repo->getPath();
    }
    return $data;
  }

  /**
   * Creates a test package that points to all the projects in vendor.
   *
   * @param string $repository_path
   *   The path where to create the test package.
   */
  protected function makeVendorPackage($repository_path) {
    $root = $this->getDrupalRoot();
    $process = $this->executeCommand("composer --working-dir=$root info --format=json");
    $this->assertCommandSuccessful();
    $installed = json_decode($process->getOutput(), TRUE);

    // Build out package definitions for everything installed in
    // the vendor directory.
    $packages = [];
    foreach ($installed['installed'] as $project) {
      $name = $project['name'];
      $version = $project['version'];
      $path = "vendor/$name";
      $full_path = "$root/$path";
      // We are building a set of path repositories to projects in the vendor
      // directory, so we will skip any project that does not exist in vendor.
      // Also skip the projects that are symlinked in vendor. These are in our
      // metapackage. They will be represented as path repositories in the test
      // project's composer.json.
      if (is_dir($full_path) && !is_link($full_path)) {
        $packages['packages'][$name] = [
          $version => [
            "name" => $name,
            "dist" => [
              "type" => "path",
              "url" => $full_path,
            ],
            "version" => $version,
          ],
        ];
      }
    }

    $json = json_encode($packages, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    mkdir(dirname($repository_path));
    file_put_contents($repository_path, $json);
  }

}
