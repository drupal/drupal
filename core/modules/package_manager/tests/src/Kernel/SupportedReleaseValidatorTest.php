<?php

declare(strict_types=1);

namespace Drupal\Tests\package_manager\Kernel;

use Drupal\fixture_manipulator\ActiveFixtureManipulator;
use Drupal\package_manager\Event\PreApplyEvent;
use Drupal\package_manager\ValidationResult;
use Drupal\Tests\package_manager\Traits\FixtureUtilityTrait;

/**
 * @coversDefaultClass \Drupal\package_manager\Validator\SupportedReleaseValidator
 * @group #slow
 * @group package_manager
 * @internal
 */
class SupportedReleaseValidatorTest extends PackageManagerKernelTestBase {

  use FixtureUtilityTrait;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    (new ActiveFixtureManipulator())
      ->addPackage([
        'name' => "drupal/dependency",
        'version' => '9.8.0',
        'type' => 'drupal-library',
      ])
      ->addPackage([
        'name' => "drupal/semver_test",
        'version' => '8.1.0',
        'type' => 'drupal-module',
      ])
      ->addPackage([
        'name' => "drupal/aaa_update_test",
        'version' => '2.0.0',
        'type' => 'drupal-module',
      ])
      ->addPackage([
        'name' => "drupal/package_manager_theme",
        'version' => '8.1.0',
        'type' => 'drupal-theme',
      ])
      ->addPackage([
        'name' => "somewhere/a_drupal_module",
        'version' => '8.1.0',
        'type' => 'drupal-module',
      ])
      ->addPackage(
          [
            'name' => "drupal/module_no_project",
            'version' => '1.0.0',
            'type' => 'drupal-module',
          ],
          FALSE,
          FALSE,
          [
            'module_no_project.info.yml' => '{name: "Module No Project", type: "module"}',
          ],
      )
      ->commitChanges();
  }

  /**
   * Data provider for testException().
   *
   * @return mixed[][]
   *   The test cases.
   */
  public static function providerException(): array {
    $release_fixture_folder = __DIR__ . '/../../fixtures/release-history';
    $summary = t('Cannot update because the following project version is not in the list of installable releases.');
    return [
      'semver, supported update' => [
        [
          'semver_test' => "$release_fixture_folder/semver_test.1.1.xml",
        ],
        TRUE,
        [
          'name' => "drupal/semver_test",
          'version' => '8.1.1',
          'type' => 'drupal-module',
        ],
        [],
      ],
      'semver, update to unsupported branch' => [
        [
          'semver_test' => "$release_fixture_folder/semver_test.1.1.xml",
        ],
        TRUE,
        [
          'name' => "drupal/semver_test",
          'version' => '8.2.0',
          'type' => 'drupal-module',
        ],
        [
          ValidationResult::createError([t('semver_test (drupal/semver_test) 8.2.0')], $summary),
        ],
      ],
      'legacy, supported update' => [
        [
          'aaa_update_test' => "$release_fixture_folder/aaa_update_test.1.1.xml",
        ],
        TRUE,
        [
          'name' => "drupal/aaa_update_test",
          'version' => '2.1.0',
          'type' => 'drupal-module',
        ],
        [],
      ],
      'legacy, update to unsupported branch' => [
        [
          'aaa_update_test' => "$release_fixture_folder/aaa_update_test.1.1.xml",
        ],
        TRUE,
        [
          'name' => "drupal/aaa_update_test",
          'version' => '3.0.0',
          'type' => 'drupal-module',
        ],
        [
          ValidationResult::createError([t('aaa_update_test (drupal/aaa_update_test) 3.0.0')], $summary),
        ],
      ],
      'package_manager_test_update(not in active), update to unsupported branch' => [
        [
          'package_manager_test_update' => "$release_fixture_folder/package_manager_test_update.7.0.1.xml",
        ],
        FALSE,
        [
          'name' => "drupal/package_manager_test_update",
          'version' => '7.0.1-dev',
          'type' => 'drupal-module',
        ],
        [
          ValidationResult::createError([t('package_manager_test_update (drupal/package_manager_test_update) 7.0.1-dev')], $summary),
        ],
      ],
      'package_manager_test_update(not in active), update to supported branch' => [
        [
          'package_manager_test_update' => "$release_fixture_folder/package_manager_test_update.7.0.1.xml",
        ],
        FALSE,
        [
          'name' => "drupal/package_manager_test_update",
          'version' => '7.0.1',
          'type' => 'drupal-module',
        ],
        [],
      ],
      'package_manager_theme, supported update' => [
        [
          'package_manager_theme' => "$release_fixture_folder/package_manager_theme.1.1.xml",
        ],
        TRUE,
        [
          'name' => "drupal/package_manager_theme",
          'version' => '8.1.1',
          'type' => 'drupal-theme',
        ],
        [],
      ],
      'package_manager_theme, update to unsupported branch' => [
        [
          'package_manager_theme' => "$release_fixture_folder/package_manager_theme.1.1.xml",
        ],
        TRUE,
        [
          'name' => "drupal/package_manager_theme",
          'version' => '8.2.0',
          'type' => 'drupal-theme',
        ],
        [
          ValidationResult::createError([t('package_manager_theme (drupal/package_manager_theme) 8.2.0')], $summary),
        ],
      ],
      // For modules that don't start with 'drupal/' will not have update XML
      // from drupal.org and so will not be checked by the validator.
      // @see \Drupal\package_manager\Validator\SupportedReleaseValidator::checkStagedReleases()
      'updating a module that does not start with drupal/' => [
        [],
        TRUE,
        [
          'name' => "somewhere/a_drupal_module",
          'version' => '8.1.1',
          'type' => 'drupal-module',
        ],
        [],
      ],
      'updating a module that does not have project info' => [
        [],
        TRUE,
        [
          'name' => "drupal/module_no_project",
          'version' => '1.1.0',
          'type' => 'drupal-module',
        ],
        [
          ValidationResult::createError([t('Cannot update because the following new or updated Drupal package does not have project information: drupal/module_no_project')]),
        ],
      ],
    ];
  }

  /**
   * Tests exceptions when updating to unsupported or insecure releases.
   *
   * @param array $release_metadata
   *   Array of paths of the fake release metadata keyed by project name.
   * @param bool $project_in_active
   *   Whether the project is in the active directory or not.
   * @param array $package
   *   The package that will be added or modified.
   * @param array $expected_results
   *   The expected validation results.
   *
   * @dataProvider providerException
   */
  public function testException(array $release_metadata, bool $project_in_active, array $package, array $expected_results): void {
    $this->setReleaseMetadata(['drupal' => __DIR__ . '/../../fixtures/release-history/drupal.9.8.2.xml'] + $release_metadata);

    $stage_manipulator = $this->getStageFixtureManipulator();
    if ($project_in_active) {
      $stage_manipulator->setVersion($package['name'], $package['version']);
    }
    else {
      $stage_manipulator->addPackage($package);
    }
    // We always update this module to prove that the validator will skip this
    // module as it's of type 'drupal-library'.
    // @see \Drupal\package_manager\Validator\SupportedReleaseValidator::checkStagedReleases()
    $stage_manipulator->setVersion('drupal/dependency', '9.8.1');
    $this->assertResults($expected_results, PreApplyEvent::class);
    // Ensure that any errors arising from invalid project info (which we expect
    // in this test) will not fail the test during tear-down.
    $this->failureLogger->reset();
  }

}
