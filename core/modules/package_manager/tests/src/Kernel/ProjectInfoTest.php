<?php

declare(strict_types=1);

namespace Drupal\Tests\package_manager\Kernel;

use Drupal\Core\Logger\RfcLogLevel;
use Drupal\package_manager\ProjectInfo;

/**
 * @coversDefaultClass \Drupal\package_manager\ProjectInfo
 * @group auto_updates
 * @internal
 */
class ProjectInfoTest extends PackageManagerKernelTestBase {

  /**
   * @covers ::getInstallableReleases
   *
   * @param string $fixture
   *   The fixture file name.
   * @param string $installed_version
   *   The installed version core version to set.
   * @param string[] $expected_versions
   *   The expected versions.
   *
   * @dataProvider providerGetInstallableReleases
   */
  public function testGetInstallableReleases(string $fixture, string $installed_version, array $expected_versions): void {
    [$project] = explode('.', $fixture);
    $fixtures_directory = __DIR__ . '/../../fixtures/release-history/';
    if ($project === 'drupal') {
      $this->setCoreVersion($installed_version);
    }
    else {
      // Update the version and the project of the project.
      $this->enableModules(['package_manager_test_update']);
      $extension_info_update = [
        'version' => $installed_version,
        'project' => 'package_manager_test_update',
      ];
      // @todo Replace with use of the trait from the Update module in https://drupal.org/i/3348234.
      $this->config('update_test.settings')
        ->set("system_info.$project", $extension_info_update)
        ->save();
      // The Update module will always request Drupal core's update XML.
      $metadata_fixtures['drupal'] = $fixtures_directory . 'drupal.9.8.2.xml';
    }
    $metadata_fixtures[$project] = "$fixtures_directory$fixture";
    $this->setReleaseMetadata($metadata_fixtures);
    $project_info = new ProjectInfo($project);
    $actual_releases = $project_info->getInstallableReleases();
    // Assert that we returned the correct releases in the expected order.
    $this->assertSame($expected_versions, array_keys($actual_releases));
    // Assert that we version keys match the actual releases.
    foreach ($actual_releases as $version => $release) {
      $this->assertSame($version, $release->getVersion());
    }
  }

  /**
   * Data provider for testGetInstallableReleases().
   *
   * @return mixed[][]
   *   The test cases.
   */
  public static function providerGetInstallableReleases(): array {
    return [
      'core, no updates' => [
        'drupal.9.8.2.xml',
        '9.8.2',
        [],
      ],
      'core, on supported branch, pre-release in next minor' => [
        'drupal.9.8.0-alpha1.xml',
        '9.7.1',
        ['9.8.0-alpha1'],
      ],
      'core, on unsupported branch, updates in multiple supported branches' => [
        'drupal.9.8.2.xml',
        '9.6.0-alpha1',
        ['9.8.2', '9.8.1', '9.8.0', '9.8.0-alpha1', '9.7.1', '9.7.0', '9.7.0-alpha1'],
      ],
      // A test case with an unpublished release, 9.8.0, and unsupported
      // release, 9.8.1, both of these releases should not be returned.
      'core, filter out unsupported and unpublished releases' => [
        'drupal.9.8.2-unsupported_unpublished.xml',
        '9.6.0-alpha1',
        ['9.8.2', '9.8.0-alpha1', '9.7.1', '9.7.0', '9.7.0-alpha1'],
      ],
      'core, supported branches before and after installed release' => [
        'drupal.9.8.2.xml',
        '9.8.0-alpha1',
        ['9.8.2', '9.8.1', '9.8.0'],
      ],
      'core, one insecure release filtered out' => [
        'drupal.9.8.1-security.xml',
        '9.8.0-alpha1',
        ['9.8.1'],
      ],
      'core, skip insecure releases and return secure releases' => [
        'drupal.9.8.2-older-sec-release.xml',
        '9.7.0-alpha1',
        ['9.8.2', '9.8.1', '9.8.1-beta1', '9.8.0-alpha1', '9.7.1'],
      ],
      'contrib, semver and legacy' => [
        'package_manager_test_update.7.0.1.xml',
        '8.x-6.0-alpha1',
        ['7.0.1', '7.0.0', '7.0.0-alpha1', '8.x-6.2', '8.x-6.1', '8.x-6.0'],
      ],
      'contrib, semver and legacy, some lower' => [
        'package_manager_test_update.7.0.1.xml',
        '8.x-6.1',
        ['7.0.1', '7.0.0', '7.0.0-alpha1', '8.x-6.2'],
      ],
      'contrib, semver and legacy, on semantic dev' => [
        'package_manager_test_update.7.0.1.xml',
        '7.0.x-dev',
        ['7.0.1', '7.0.0', '7.0.0-alpha1'],
      ],
      'contrib, semver and legacy, on legacy dev' => [
        'package_manager_test_update.7.0.1.xml',
        '8.x-6.x-dev',
        ['7.0.1', '7.0.0', '7.0.0-alpha1', '8.x-6.2', '8.x-6.1', '8.x-6.0', '8.x-6.0-alpha1'],
      ],
    ];
  }

  /**
   * Tests a project that is not in the codebase.
   */
  public function testNewProject(): void {
    $fixtures_directory = __DIR__ . '/../../fixtures/release-history/';
    $metadata_fixtures['drupal'] = $fixtures_directory . 'drupal.9.8.2.xml';
    $metadata_fixtures['package_manager_test_update'] = $fixtures_directory . 'package_manager_test_update.7.0.1.xml';
    $this->setReleaseMetadata($metadata_fixtures);
    $available = update_get_available(TRUE);
    $this->assertSame(['drupal'], array_keys($available));
    $this->setReleaseMetadata($metadata_fixtures);
    $state = $this->container->get('state');
    // Set the state that the update module uses to store last checked time
    // ensure our calls do not affect it.
    $state->set('update.last_check', 123);
    $project_info = new ProjectInfo('package_manager_test_update');
    $project_data = $project_info->getProjectInfo();
    // Ensure the project information is correct.
    $this->assertSame('Package Manager Test Update', $project_data['title']);
    $all_releases = [
      '7.0.1',
      '7.0.0',
      '7.0.0-alpha1',
      '8.x-6.2',
      '8.x-6.1',
      '8.x-6.0',
      '8.x-6.0-alpha1',
      '7.0.x-dev',
      '8.x-6.x-dev',
      '8.x-5.x',
    ];
    $uninstallable_releases = ['7.0.x-dev', '8.x-6.x-dev', '8.x-5.x'];
    $installable_releases = array_values(array_diff($all_releases, $uninstallable_releases));
    $this->assertSame(
      $all_releases,
      array_keys($project_data['releases'])
    );
    $this->assertSame(
      $installable_releases,
      array_keys($project_info->getInstallableReleases())
    );
    $this->assertNull($project_info->getInstalledVersion());
    // Ensure we have not changed the state the update module uses to store
    // the last checked time.
    $this->assertSame(123, $state->get('update.last_check'));

    $this->assertTrue($this->failureLogger->hasRecordThatContains('Invalid project format: Array', (string) RfcLogLevel::ERROR));
    $this->assertTrue($this->failureLogger->hasRecordThatContains('[name] => Package Manager Test Update 8.x-5.x', (string) RfcLogLevel::ERROR));
    // Prevent the logged errors from causing failures during tear-down.
    $this->failureLogger->reset();
  }

  /**
   * Tests a project with a status other than "published".
   *
   * @covers ::getInstallableReleases
   */
  public function testNotPublishedProject(): void {
    $this->setReleaseMetadata(['drupal' => __DIR__ . '/../../fixtures/release-history/drupal.9.8.2_unknown_status.xml']);
    $project_info = new ProjectInfo('drupal');
    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage("The project 'drupal' can not be updated because its status is any status besides published");
    $project_info->getInstallableReleases();
  }

  /**
   * Data provider for ::testInstalledVersionSafe().
   *
   * @return array[]
   *   The test cases.
   */
  public static function providerInstalledVersionSafe(): array {
    $dir = __DIR__ . '/../../fixtures/release-history';

    return [
      'safe version' => [
        '9.8.0',
        $dir . '/drupal.9.8.2.xml',
        TRUE,
      ],
      'unpublished version' => [
        '9.8.0',
        $dir . '/drupal.9.8.2-unsupported_unpublished.xml',
        FALSE,
      ],
      'unsupported branch' => [
        '9.6.1',
        $dir . '/drupal.9.8.2-unsupported_unpublished.xml',
        FALSE,
      ],
      'unsupported version' => [
        '9.8.1',
        $dir . '/drupal.9.8.2-unsupported_unpublished.xml',
        FALSE,
      ],
      'insecure version' => [
        '9.8.0',
        $dir . '/drupal.9.8.1-security.xml',
        FALSE,
      ],
    ];
  }

  /**
   * Tests checking if the currently installed version of a project is safe.
   *
   * @param string $installed_version
   *   The currently installed version of the project.
   * @param string $release_xml
   *   The path of the release metadata.
   * @param bool $expected_to_be_safe
   *   Whether the installed version of the project is expected to be found
   *   safe.
   *
   * @covers ::isInstalledVersionSafe
   *
   * @dataProvider providerInstalledVersionSafe
   */
  public function testInstalledVersionSafe(string $installed_version, string $release_xml, bool $expected_to_be_safe): void {
    $this->setCoreVersion($installed_version);
    $this->setReleaseMetadata(['drupal' => $release_xml]);

    $project_info = new ProjectInfo('drupal');
    $this->assertSame($expected_to_be_safe, $project_info->isInstalledVersionSafe());
  }

  /**
   * Data provider for testGetSupportedBranches().
   *
   * @return mixed[][]
   *   The test cases.
   */
  public static function providerGetSupportedBranches(): array {
    $dir = __DIR__ . '/../../fixtures/release-history/';

    return [
      'xml with supported branches' => [
        $dir . 'drupal.10.0.0.xml',
        [
          '9.5.',
          '9.6.',
          '9.7.',
          '10.0.',
        ],
      ],
      'xml with supported branches not set' => [
        $dir . 'drupal.9.8.1-supported_branches_not_set.xml',
        [],
      ],
      'xml with empty supported branches' => [
        $dir . 'drupal.9.8.1-empty_supported_branches.xml',
        [
          '',
        ],
      ],
    ];
  }

  /**
   * @covers ::getSupportedBranches
   *
   * @param string $release_xml
   *   The path of the release metadata.
   * @param string[] $expected_supported_branches
   *   The expected supported branches.
   *
   * @dataProvider providerGetSupportedBranches
   */
  public function testGetSupportedBranches(string $release_xml, array $expected_supported_branches): void {
    $this->setReleaseMetadata(['drupal' => $release_xml]);
    $project_info = new ProjectInfo('drupal');
    $this->assertSame($expected_supported_branches, $project_info->getSupportedBranches());
  }

}
