<?php

declare(strict_types=1);

namespace Drupal\Tests\package_manager\Build;

use Drupal\package_manager_test_api\ControllerSandboxManager;

/**
 * Tests updating packages in a stage directory.
 *
 * @group package_manager
 * @group #slow
 * @internal
 */
class PackageUpdateTest extends TemplateProjectTestBase {

  /**
   * Tests updating packages in a stage directory.
   */
  public function testPackageUpdate(): void {
    $this->createTestProject('RecommendedProject');

    $fixtures = __DIR__ . '/../../fixtures/build_test_projects';

    $alpha_repo_path = $this->copyFixtureToTempDirectory("$fixtures/alpha/1.0.0");
    $this->addRepository('alpha', $alpha_repo_path);
    $updated_module_repo_path = $this->copyFixtureToTempDirectory("$fixtures/updated_module/1.0.0");
    $this->addRepository('updated_module', $updated_module_repo_path);
    $this->setReleaseMetadata([
      'updated_module' => __DIR__ . '/../../fixtures/release-history/updated_module.1.1.0.xml',
    ]);
    $this->runComposer('composer require drupal/alpha drupal/updated_module --update-with-all-dependencies', 'project');

    // The updated_module provides actual Drupal-facing functionality that we're
    // testing as well, so we need to install it.
    $this->installModules(['updated_module']);

    // Change both modules' upstream version.
    static::copyFixtureFilesTo("$fixtures/alpha/1.1.0", $alpha_repo_path);
    static::copyFixtureFilesTo("$fixtures/updated_module/1.1.0", $updated_module_repo_path);
    // Make .git folder

    // Use the API endpoint to create a stage and update updated_module to
    // 1.1.0. Even though both modules have version 1.1.0 available, only
    // updated_module should be updated.
    $this->makePackageManagerTestApiRequest(
      '/package-manager-test-api',
      [
        'runtime' => [
          'drupal/updated_module:1.1.0',
        ],
      ]
    );

    $expected_versions = [
      'alpha' => '1.0.0',
      'updated_module' => '1.1.0',
    ];
    foreach ($expected_versions as $module_name => $expected_version) {
      $path = "/modules/contrib/$module_name/composer.json";
      $module_composer_json = json_decode(file_get_contents($this->getWebRoot() . "/$path"));
      $this->assertSame($expected_version, $module_composer_json?->version);
    }
    // The post-apply event subscriber in updated_module 1.1.0 should have
    // created this file.
    // @see \Drupal\updated_module\PostApplySubscriber::postApply()
    $this->assertSame('Bravo!', file_get_contents($this->getWorkspaceDirectory() . '/project/bravo.txt'));

    $this->assertExpectedStageEventsFired(ControllerSandboxManager::class);
    $this->assertRequestedChangesWereLogged(['Update drupal/updated_module from 1.0.0 to 1.1.0']);
    $this->assertAppliedChangesWereLogged(['Updated drupal/updated_module from 1.0.0 to 1.1.0']);
  }

}
