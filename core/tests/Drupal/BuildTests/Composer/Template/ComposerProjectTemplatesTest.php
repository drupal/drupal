<?php

namespace Drupal\BuildTests\Composer\Template;

use Composer\Json\JsonFile;
use Composer\Semver\VersionParser;
use Drupal\BuildTests\Composer\ComposerBuildTestBase;
use Drupal\Composer\Composer;

/**
 * Demonstrate that Composer project templates are buildable as patched.
 *
 * We have to use the packages.json fixture so that Composer will use the
 * in-codebase version of the project template.
 *
 * We also have to add path repositories to the in-codebase project template or
 * else Composer will try to use packagist to resolve dependencies we'd prefer
 * it to find locally.
 *
 * This is because Composer only uses the packages.json file to resolve the
 * project template and not any other dependencies.
 *
 * @group #slow
 * @group Template
 *
 * @requires externalCommand composer
 */
class ComposerProjectTemplatesTest extends ComposerBuildTestBase {

  /**
   * The minimum stability requirement for dependencies.
   *
   * @see https://getcomposer.org/doc/04-schema.md#minimum-stability
   */
  protected const MINIMUM_STABILITY = 'stable';

  /**
   * The order of stability strings from least stable to most stable.
   *
   * This only includes normalized stability strings: i.e., ones that are
   * returned by \Composer\Semver\VersionParser::parseStability().
   */
  protected const STABILITY_ORDER = ['dev', 'alpha', 'beta', 'RC', 'stable'];

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

  public function provideTemplateCreateProject() {
    return [
      'recommended-project' => [
        'drupal/recommended-project',
        'composer/Template/RecommendedProject',
        '/web',
      ],
      'legacy-project' => [
        'drupal/legacy-project',
        'composer/Template/LegacyProject',
        '',
      ],
    ];
  }

  /**
   * Make sure that static::MINIMUM_STABILITY is sufficiently strict.
   */
  public function testMinimumStabilityStrictness() {
    // Ensure that static::MINIMUM_STABILITY is not less stable than the
    // current core stability. For example, if we've already released a beta on
    // the branch, ensure that we no longer allow alpha dependencies.
    $this->assertGreaterThanOrEqual(array_search($this->getCoreStability(), static::STABILITY_ORDER), array_search(static::MINIMUM_STABILITY, static::STABILITY_ORDER));

    // Ensure that static::MINIMUM_STABILITY is the same as the least stable
    // dependency.
    // - We can't set it stricter than our least stable dependency.
    // - We don't want to set it looser than we need to, because we don't want
    //   to in the future accidentally commit a dependency that regresses our
    //   actual stability requirement without us explicitly changing this
    //   constant.
    $root = $this->getDrupalRoot();
    $process = $this->executeCommand("composer --working-dir=$root info --format=json");
    $this->assertCommandSuccessful();
    $installed = json_decode($process->getOutput(), TRUE);

    // A lookup of the numerical position of each of the stability terms.
    $stability_order_indexes = array_flip(static::STABILITY_ORDER);

    $minimum_stability_order_index = $stability_order_indexes[static::MINIMUM_STABILITY];

    $exclude = [
      'drupal/core',
      'drupal/core-project-message',
      'drupal/core-vendor-hardening',
    ];
    foreach ($installed['installed'] as $project) {
      // Exclude dependencies that are required with "self.version", since
      // those stabilities will automatically match the corresponding Drupal
      // release.
      if (in_array($project['name'], $exclude, TRUE)) {
        continue;
      }

      $project_stability = VersionParser::parseStability($project['version']);
      $project_stability_order_index = $stability_order_indexes[$project_stability];

      $project_stabilities[$project['name']] = $project_stability;

      $this->assertGreaterThanOrEqual($minimum_stability_order_index, $project_stability_order_index, sprintf(
        "Dependency %s with stability %s does not meet minimum stability %s.",
        $project['name'],
        $project_stability,
        static::MINIMUM_STABILITY,
      ));
    }

    // At least one project should be at the minimum stability.
    $this->assertContains(static::MINIMUM_STABILITY, $project_stabilities);
  }

  /**
   * Make sure we've accounted for all the templates.
   */
  public function testVerifyTemplateTestProviderIsAccurate() {
    $root = $this->getDrupalRoot();
    $data = $this->provideTemplateCreateProject();

    // Find all the templates.
    $template_files = Composer::composerSubprojectPaths($root, 'Template');

    $this->assertSameSize($template_files, $data);

    // We could have the same number of templates but different names.
    $template_data = [];
    foreach ($data as $data_name => $data_value) {
      $template_data[$data_value[0]] = $data_name;
    }
    /** @var \SplFileInfo $file */
    foreach ($template_files as $file) {
      $json_file = new JsonFile($file->getPathname());
      $json = $json_file->read();
      $this->assertArrayHasKey('name', $json);
      // Assert that the template name is in the project created
      // from the template.
      $this->assertArrayHasKey($json['name'], $template_data);
    }
  }

  /**
   * @dataProvider provideTemplateCreateProject
   */
  public function testTemplateCreateProject($project, $package_dir, $docroot_dir) {
    // Make a working COMPOSER_HOME directory for setting global composer config
    $composer_home = $this->getWorkspaceDirectory() . '/composer-home';
    mkdir($composer_home);
    // Create an empty global composer.json file, just to avoid warnings.
    file_put_contents("$composer_home/composer.json", '{}');

    // Disable packagist globally (but only in our own custom COMPOSER_HOME).
    // It is necessary to do this globally rather than in our SUT composer.json
    // in order to ensure that Packagist is disabled during the
    // `composer create-project` command.
    $this->executeCommand("COMPOSER_HOME=$composer_home composer config --no-interaction --global repo.packagist false");
    $this->assertCommandSuccessful();

    // Create a "Composer"-type repository containing one entry for every
    // package in the vendor directory.
    $vendor_packages_path = $this->getWorkspaceDirectory() . '/vendor_packages/packages.json';
    $this->makeVendorPackage($vendor_packages_path);

    // Make a copy of the code to alter in the workspace directory.
    $this->copyCodebase();

    // Tests are typically run on "-dev" versions, but we want to simulate
    // running them on a tagged release at the same stability as specified in
    // static::MINIMUM_STABILITY, in order to verify that everything will work
    // if/when we make such a release.
    $simulated_core_version = \Drupal::VERSION;
    $simulated_core_version_suffix = (static::MINIMUM_STABILITY === 'stable' ? '' : '-' . static::MINIMUM_STABILITY . '99');
    $simulated_core_version = str_replace('-dev', $simulated_core_version_suffix, $simulated_core_version);
    Composer::setDrupalVersion($this->getWorkspaceDirectory(), $simulated_core_version);
    $this->assertDrupalVersion($simulated_core_version, $this->getWorkspaceDirectory());

    // Remove the packages.drupal.org entry (and any other custom repository)
    // from the SUT's repositories section. There is no way to do this via
    // `composer config --unset`, so we read and rewrite composer.json.
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

    // Change drupal/core-recommended to require the simulated version of
    // drupal/core.
    $core_recommended_dir = 'composer/Metapackage/CoreRecommended';
    $this->executeCommand("composer remove --no-interaction drupal/core --no-update", $core_recommended_dir);
    $this->assertCommandSuccessful();
    $this->executeCommand("composer require --no-interaction drupal/core:^$simulated_core_version --no-update", $core_recommended_dir);
    $this->assertCommandSuccessful();

    // Add our vendor package repository to our SUT's repositories section.
    // Call it "local" (although the name does not matter).
    $this->executeCommand("composer config --no-interaction repositories.local composer file://" . $vendor_packages_path, $package_dir);
    $this->assertCommandSuccessful();

    $repository_path = $this->getWorkspaceDirectory() . '/test_repository/packages.json';
    $this->makeTestPackage($repository_path, $simulated_core_version);

    $installed_composer_json = $this->getWorkspaceDirectory() . '/testproject/composer.json';
    $autoloader = $this->getWorkspaceDirectory() . '/testproject' . $docroot_dir . '/autoload.php';
    $this->assertFileDoesNotExist($autoloader);

    $this->executeCommand("COMPOSER_HOME=$composer_home COMPOSER_ROOT_VERSION=$simulated_core_version composer create-project --no-ansi $project testproject $simulated_core_version -vvv --repository $repository_path");
    $this->assertCommandSuccessful();
    // Check the output of the project creation for the absence of warnings
    // about any non-allowed composer plugins.
    // Note: There are different warnings for unallowed composer plugins
    // depending on running in non-interactive mode or not. It seems the Drupal
    // CI environment always forces composer commands to run in the
    // non-interactive mode. The only thing these messages have in common is the
    // following string.
    $this->assertErrorOutputNotContains('See https://getcomposer.org/allow-plugins');

    // Ensure we used the project from our codebase.
    $this->assertErrorOutputContains("Installing $project ($simulated_core_version): Symlinking from $package_dir");
    // Ensure that we used drupal/core from our codebase. This probably means
    // that drupal/core-recommended was added successfully by the project.
    $this->assertErrorOutputContains("Installing drupal/core ($simulated_core_version): Symlinking from");
    // Verify that there is an autoloader. This is written by the scaffold
    // plugin, so its existence assures us that scaffolding happened.
    $this->assertFileExists($autoloader);

    // Verify that the minimum stability in the installed composer.json file
    // matches the stability of the simulated core version.
    $this->assertFileExists($installed_composer_json);
    $composer_json_contents = file_get_contents($installed_composer_json);
    $this->assertStringContainsString('"minimum-stability": "' . static::MINIMUM_STABILITY . '"', $composer_json_contents);

    // In order to verify that Composer used the path repos for our project, we
    // have to get the requirements from the project composer.json so we can
    // reconcile our expectations.
    $template_json_file = $this->getWorkspaceDirectory() . '/' . $package_dir . '/composer.json';
    $this->assertFileExists($template_json_file);
    $json_file = new JsonFile($template_json_file);
    $template_json = $json_file->read();
    // Get the require and require-dev information, and ensure that our
    // requirements are not erroneously empty.
    $this->assertNotEmpty(
      $require = array_merge($template_json['require'] ?? [], $template_json['require-dev'] ?? [])
    );
    // Verify that path repo packages were installed.
    $path_repos = array_keys($path_repos);
    foreach (array_keys($require) as $package_name) {
      if (in_array($package_name, $path_repos)) {
        // Metapackages do not report that they were installed as symlinks, but
        // we still must check that their installed version matches
        // COMPOSER_CORE_VERSION.
        if (array_key_exists($package_name, $metapackage_path_repos)) {
          $this->assertErrorOutputContains("Installing $package_name ($simulated_core_version)");
        }
        else {
          $this->assertErrorOutputContains("Installing $package_name ($simulated_core_version): Symlinking from");
        }
      }
    }
  }

  /**
   * Creates a test package that points to the templates.
   *
   * @param string $repository_path
   *   The path where to create the test package.
   * @param string $version
   *   The version under test.
   */
  protected function makeTestPackage($repository_path, $version) {
    $json = <<<JSON
{
  "packages": {
    "drupal/recommended-project": {
      "$version": {
        "name": "drupal/recommended-project",
        "dist": {
          "type": "path",
          "url": "composer/Template/RecommendedProject"
        },
        "type": "project",
        "version": "$version"
      }
    },
    "drupal/legacy-project": {
      "$version": {
        "name": "drupal/legacy-project",
        "dist": {
          "type": "path",
          "url": "composer/Template/LegacyProject"
        },
        "type": "project",
        "version": "$version"
      }
    }
  }
}
JSON;
    mkdir(dirname($repository_path));
    file_put_contents($repository_path, $json);
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
        // Ensure composer plugins are registered correctly.
        $package_json = json_decode(file_get_contents($full_path . '/composer.json'), TRUE);
        if (isset($package_json['type']) && $package_json['type'] === 'composer-plugin') {
          $packages['packages'][$name][$version]['type'] = $package_json['type'];
          $packages['packages'][$name][$version]['require'] = $package_json['require'];
          $packages['packages'][$name][$version]['extra'] = $package_json['extra'];
          if (isset($package_json['autoload'])) {
            $packages['packages'][$name][$version]['autoload'] = $package_json['autoload'];
          }
        }
      }
    }

    $json = json_encode($packages, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    mkdir(dirname($repository_path));
    file_put_contents($repository_path, $json);
  }

  /**
   * Returns the stability of the current core version.
   *
   * If the current core version is a tagged release (not a "-dev" version),
   * this returns the stability of that version.
   *
   * If the current core version is a "-dev" version, but not a "x.y.0-dev"
   * version, this returns "stable", because it means that the corresponding
   * "x.y.0" has already been released, and only stable changes are now
   * permitted on the branch.
   *
   * If the current core version is a "x.y.0-dev" version, then this returns
   * the stability of the latest tag that matches "x.y.0-*". For example, if
   * we've already released "x.y.0-alpha1" but have not yet released
   * "x.y.0-beta1", then the current stability is "alpha". If there aren't any
   * matching tags, this returns "dev", because it means that an "alpha1" has
   * not yet been released.
   *
   * @return string
   *   One of: "dev", "alpha", "beta", "RC", "stable".
   */
  protected function getCoreStability() {
    $version = \Drupal::VERSION;
    $stability = VersionParser::parseStability($version);
    if ($stability === 'dev') {
      // Strip off "-dev";
      $version_towards = substr($version, 0, -4);

      if (substr($version_towards, -2) !== '.0') {
        // If the current version is developing towards an x.y.z release where
        // z is not 0, it means that the x.y.0 has already been released, and
        // only stable changes are permitted on the branch.
        $stability = 'stable';
      }
      else {
        // If the current version is developing towards an x.y.0 release, there
        // might be tagged pre-releases. "git describe" identifies the latest
        // one.
        $root = $this->getDrupalRoot();
        $process = $this->executeCommand("git -C \"$root\" describe --abbrev=0 --match=\"$version_towards-*\"");

        // If there aren't any tagged pre-releases for this version yet, return
        // 'dev'. Ensure that any other error from "git describe" causes a test
        // failure.
        if (!$process->isSuccessful()) {
          $this->assertErrorOutputContains('No names found, cannot describe anything.');
          return 'dev';
        }

        // We expect a pre-release, because:
        // - A tag should not be of "dev" stability.
        // - After a "stable" release is made, \Drupal::VERSION is incremented,
        //   so there should not be a stable release on that new version.
        $stability = VersionParser::parseStability(trim($process->getOutput()));
        $this->assertContains($stability, ['alpha', 'beta', 'RC']);
      }
    }
    return $stability;
  }

}
