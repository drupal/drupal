<?php

declare(strict_types=1);

namespace Drupal\Tests\package_manager\Build;

/**
 * Tests installing packages in a stage directory.
 *
 * @group package_manager
 * @internal
 */
class PackageInstallTest extends TemplateProjectTestBase {

  /**
   * Tests installing packages in a stage directory.
   */
  public function testPackageInstall(): void {
    $this->createTestProject('RecommendedProject');

    $this->setReleaseMetadata([
      'alpha' => __DIR__ . '/../../fixtures/release-history/alpha.1.1.0.xml',
    ]);
    $this->addRepository('alpha', $this->copyFixtureToTempDirectory(__DIR__ . '/../../fixtures/build_test_projects/alpha/1.0.0'));
    // Repository definitions affect the lock file hash, so update the hash to
    // ensure that Composer won't complain that the lock file is out of sync.
    $this->runComposer('composer update --lock', 'project');

    // Use the API endpoint to create a stage and install alpha 1.0.0.
    $this->makePackageManagerTestApiRequest(
      '/package-manager-test-api',
      [
        'runtime' => [
          'drupal/alpha:1.0.0',
        ],
      ]
    );
    // Assert the module was installed.
    $this->assertFileEquals(
      __DIR__ . '/../../fixtures/build_test_projects/alpha/1.0.0/composer.json',
      $this->getWebRoot() . '/modules/contrib/alpha/composer.json',
    );
    $this->assertRequestedChangesWereLogged(['Install drupal/alpha 1.0.0']);
    $this->assertAppliedChangesWereLogged(['Installed drupal/alpha 1.0.0']);
  }

  /**
   * Tests installing a Drupal submodule.
   *
   * This test installs a submodule using a set-up that mimics how
   * packages.drupal.org handles submodules. Submodules are Composer
   * metapackages which depend on the Composer package of the main module.
   */
  public function testSubModules(): void {
    $this->createTestProject('RecommendedProject');
    // Set up the release metadata for the main module. The submodule does not
    // have its own release metadata.
    $this->setReleaseMetadata([
      'main_module' => __DIR__ . '/../../fixtures/release-history/main_module.1.0.0.xml',
    ]);

    // Add repositories for Drupal modules which will contain the code for its
    // submodule also.
    $this->addRepository('main_module', $this->copyFixtureToTempDirectory(__DIR__ . '/../../fixtures/build_test_projects/main_module'));

    // Add a repository for the submodule of 'main_module'. Submodule
    // repositories are metapackages which have no code of their own but that
    // require the main module.
    $this->addRepository('main_module_submodule', $this->copyFixtureToTempDirectory(__DIR__ . '/../../fixtures/path_repos/main_module_submodule'));

    // Repository definitions affect the lock file hash, so update the hash to
    // ensure that Composer won't complain that the lock file is out of sync.
    $this->runComposer('composer update --lock', 'project');

    $this->makePackageManagerTestApiRequest(
      '/package-manager-test-api',
      [
        'runtime' => [
          'drupal/main_module_submodule:1.0.0',
        ],
      ]
    );

    // Assert main_module and the submodule were installed.
    $main_module_path = $this->getWebRoot() . '/modules/contrib/main_module';
    $this->assertFileExists("$main_module_path/main_module.info.yml");
    $this->assertFileExists("$main_module_path/main_module_submodule/main_module_submodule.info.yml");
  }

}
