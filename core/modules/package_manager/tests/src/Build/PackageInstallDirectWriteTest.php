<?php

declare(strict_types=1);

namespace Drupal\Tests\package_manager\Build;

use PHPUnit\Framework\Attributes\Group;

/**
 * Tests installing packages with direct write mode enabled.
 *
 * @internal
 */
#[Group('package_manager')]
#[Group('#slow')]
class PackageInstallDirectWriteTest extends TemplateProjectTestBase {

  /**
   * Tests installing packages in a stage directory.
   */
  public function testPackageInstall(): void {
    $this->createTestProject('RecommendedProject');
    $allow_direct_write = var_export(TRUE, TRUE);
    $this->writeSettings("\n\$settings['package_manager_allow_direct_write'] = $allow_direct_write;");

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

}
