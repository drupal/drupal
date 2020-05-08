<?php

namespace Drupal\BuildTests\Composer\Template;

use Composer\Json\JsonFile;
use Drupal\BuildTests\Framework\BuildTestBase;
use Drupal\Composer\Composer;
use Symfony\Component\Finder\Finder;

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
class ComposerProjectTemplatesTest extends BuildTestBase {

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
    $path_repos = Finder::create()
      ->files()
      ->name('composer.json')
      ->in($workspace_directory . '/composer/' . $subdir);

    $data = [];
    /* @var $path_repo \SplFileInfo */
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
   * Make sure we've accounted for all the templates.
   */
  public function testVerifyTemplateTestProviderIsAccurate() {
    $root = $this->getDrupalRoot();
    $data = $this->provideTemplateCreateProject($root);

    // Find all the templates.
    $template_files = Finder::create()
      ->files()
      ->name('composer.json')
      ->in($root . '/composer/Template');

    $this->assertEquals(count($template_files), count($data));

    // We could have the same number of templates but different names.
    $template_data = [];
    foreach ($data as $data_name => $data_value) {
      $template_data[$data_value[0]] = $data_name;
    }
    /* @var $file \SplFileInfo */
    foreach ($template_files as $file) {
      $json_file = new JsonFile($file->getPathname());
      $json = $json_file->read();
      $this->assertArrayHasKey('name', $json);
      // Does provideTemplateCreateProject() give us this template name?
      $this->assertArrayHasKey($json['name'], $template_data);
    }
  }

  /**
   * @dataProvider provideTemplateCreateProject
   */
  public function testTemplateCreateProject($project, $package_dir, $docroot_dir) {
    $this->copyCodebase();

    // Get the Drupal core version branch. For instance, this should be
    // 8.9.x-dev for the 8.9.x branch.
    $core_version = Composer::drupalVersionBranch();

    // Set up the template to use our path repos. Inclusion of metapackages is
    // reported differently, so we load up a separate set for them.
    $metapackage_path_repos = $this->getPathReposForType($this->getWorkspaceDirectory(), 'Metapackage');
    $path_repos = array_merge($metapackage_path_repos, $this->getPathReposForType($this->getWorkspaceDirectory(), 'Plugin'));
    // Always add drupal/core as a path repo.
    $path_repos['drupal/core'] = $this->getWorkspaceDirectory() . '/core';
    foreach ($path_repos as $name => $path) {
      $this->executeCommand('composer config repositories.' . $name . ' path ' . $path, $package_dir);
      $this->assertCommandSuccessful();
    }

    $repository_path = $this->getWorkspaceDirectory() . '/test_repository/packages.json';
    $this->makeTestPackage($repository_path, $core_version);

    $autoloader = $this->getWorkspaceDirectory() . '/testproject' . $docroot_dir . '/autoload.php';
    $this->assertFileNotExists($autoloader);

    $this->executeCommand("COMPOSER_CORE_VERSION=$core_version composer create-project $project testproject $core_version -s dev -vv --repository $repository_path");
    $this->assertCommandSuccessful();

    // Ensure we used the project from our codebase.
    $this->assertErrorOutputContains("Installing $project ($core_version): Symlinking from $package_dir");
    // Ensure that we used drupal/core from our codebase. This probably means
    // that drupal/core-recommended was added successfully by the project.
    $this->assertErrorOutputContains("Installing drupal/core ($core_version): Symlinking from");
    // Verify that there is an autoloader.
    $this->assertFileExists($autoloader);

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
          $this->assertErrorOutputContains("Installing $package_name ($core_version)");
        }
        else {
          $this->assertErrorOutputContains("Installing $package_name ($core_version): Symlinking from");
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

}
