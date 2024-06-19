<?php

declare(strict_types=1);

namespace Drupal\Tests\update\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\update\ProjectCoreCompatibility;

/**
 * @coversDefaultClass \Drupal\update\ProjectCoreCompatibility
 *
 * @group update
 */
class ProjectCoreCompatibilityTest extends UnitTestCase {

  /**
   * @covers ::setReleaseMessage
   * @dataProvider providerSetProjectCoreCompatibilityRanges
   */
  public function testSetProjectCoreCompatibilityRanges(array $project_data, $core_data, array $supported_branches, array $core_releases, array $expected_releases, array $expected_security_updates): void {
    $project_compatibility = new ProjectCoreCompatibility($core_data, $core_releases, $supported_branches);
    $project_compatibility->setStringTranslation($this->getStringTranslationStub());
    $project_compatibility->setReleaseMessage($project_data);
    $this->assertSame($expected_releases, $project_data['releases']);
    $this->assertSame($expected_security_updates, $project_data['security updates']);
  }

  /**
   * Data provider for testSetProjectCoreCompatibilityRanges().
   */
  public static function providerSetProjectCoreCompatibilityRanges() {
    $test_cases['no 9 releases, no supported branches'] = [
      'project_data' => [
        'recommended' => '1.0.1',
        'latest_version' => '1.2.3',
        'also' => [
          '1.2.4',
          '1.2.5',
          '1.2.6',
        ],
        'releases' => [
          '1.0.1' => [
            'core_compatibility' => '8.x',
          ],
          '1.2.3' => [
            'core_compatibility' => '^8.9 || ^9',
          ],
          '1.2.4' => [
            'core_compatibility' => '^8.9.2 || ^9',
          ],
          '1.2.6' => [],
        ],
        'security updates' => [
          '1.2.5' => [
            'core_compatibility' => '8.9.0 || 8.9.2 || ^9.0.1',
          ],
        ],
      ],
      'core_data' => [
        'existing_version' => '8.8.0',
      ],
      'supported_branches' => [],
      'core_releases' => [
        '8.8.0-alpha1' => [],
        '8.8.0-beta1' => [],
        '8.8.0-rc1' => [],
        '8.8.0' => [],
        '8.8.1' => [],
        '8.8.2' => [],
        '8.9.0' => [],
        '8.9.1' => [],
        '8.9.2' => [],
      ],
    ];
    // Confirm that with no core supported branches the releases are not changed.
    $test_cases['no 9 releases, no supported branches'] += [
      'expected_releases' => $test_cases['no 9 releases, no supported branches']['project_data']['releases'],
      'expected_security_updates' => $test_cases['no 9 releases, no supported branches']['project_data']['security updates'],
    ];

    // Confirm that if core has supported branches the releases will updated
    // with 'core_compatible' and 'core_compatibility_message'.
    $test_cases['no 9 releases'] = $test_cases['no 9 releases, no supported branches'];
    $test_cases['no 9 releases']['supported_branches'] = ['8.8.', '8.9.'];
    $test_cases['no 9 releases']['expected_releases'] = [
      '1.0.1' => [
        'core_compatibility' => '8.x',
        'core_compatible' => TRUE,
        'core_compatibility_message' => 'Requires Drupal core: 8.8.0 to 8.9.2',
      ],
      '1.2.3' => [
        'core_compatibility' => '^8.9 || ^9',
        'core_compatible' => FALSE,
        'core_compatibility_message' => 'Requires Drupal core: 8.9.0 to 8.9.2',
      ],
      '1.2.4' => [
        'core_compatibility' => '^8.9.2 || ^9',
        'core_compatible' => FALSE,
        'core_compatibility_message' => 'Requires Drupal core: 8.9.2',
      ],
      '1.2.6' => [],
    ];
    $test_cases['no 9 releases']['expected_security_updates'] = [
      '1.2.5' => [
        'core_compatibility' => '8.9.0 || 8.9.2 || ^9.0.1',
        'core_compatible' => FALSE,
        'core_compatibility_message' => 'Requires Drupal core: 8.9.0, 8.9.2',
      ],
    ];
    // Ensure that when only Drupal 9 pre-releases none of the expected ranges
    // change.
    $test_cases['with 9 pre releases'] = $test_cases['no 9 releases'];
    $test_cases['with 9 pre releases']['core_releases'] += [
      '9.0.0-alpha1' => [],
      '9.0.0-beta1' => [],
      '9.0.0-rc1' => [],
    ];
    // Ensure that when the Drupal 9 full releases are added but they are not
    // supported none of the expected ranges change.
    $test_cases['with 9 full releases, not supported'] = $test_cases['with 9 pre releases'];
    $test_cases['with 9 full releases, not supported']['core_releases'] += [
      '9.0.0' => [],
      '9.0.1' => [],
      '9.0.2' => [],
    ];
    // Ensure that when the Drupal 9 full releases are supported the expected
    // ranges do change.
    $test_cases['with 9 full releases, supported'] = $test_cases['with 9 full releases, not supported'];
    $test_cases['with 9 full releases, supported']['supported_branches'][] = '9.0.';
    $test_cases['with 9 full releases, supported']['expected_releases'] = [
      '1.0.1' => [
        'core_compatibility' => '8.x',
        'core_compatible' => TRUE,
        'core_compatibility_message' => 'Requires Drupal core: 8.8.0 to 8.9.2',
      ],
      '1.2.3' => [
        'core_compatibility' => '^8.9 || ^9',
        'core_compatible' => FALSE,
        'core_compatibility_message' => 'Requires Drupal core: 8.9.0 to 9.0.2',
      ],
      '1.2.4' => [
        'core_compatibility' => '^8.9.2 || ^9',
        'core_compatible' => FALSE,
        'core_compatibility_message' => 'Requires Drupal core: 8.9.2 to 9.0.2',
      ],
      '1.2.6' => [],
    ];
    $test_cases['with 9 full releases, supported']['expected_security_updates'] = [
      '1.2.5' => [
        'core_compatibility' => '8.9.0 || 8.9.2 || ^9.0.1',
        'core_compatible' => FALSE,
        'core_compatibility_message' => 'Requires Drupal core: 8.9.0, 8.9.2, 9.0.1 to 9.0.2',
      ],
    ];
    return $test_cases;
  }

  /**
   * @covers ::isCoreCompatible
   * @dataProvider providerIsCoreCompatible
   *
   * @param string $constraint
   *   The core_version_constraint to test.
   * @param string $installed_core
   *   The installed version of core to compare against.
   * @param bool $expected
   *   The expected result.
   */
  public function testIsCoreCompatible(string $constraint, string $installed_core, bool $expected): void {
    $core_data['existing_version'] = $installed_core;
    $project_compatibility = new ProjectCoreCompatibility($core_data, [], []);
    $reflection = new \ReflectionClass(ProjectCoreCompatibility::class);
    $reflection_method = $reflection->getMethod('isCoreCompatible');
    $result = $reflection_method->invokeArgs($project_compatibility, [$constraint]);
    $this->assertSame($expected, $result);
  }

  /**
   * Data provider for testIsCoreCompatible().
   */
  public static function providerIsCoreCompatible(): array {
    $test_cases['compatible exact'] = [
      '10.3.0',
      '10.3.0',
      TRUE,
    ];
    $test_cases['compatible with OR'] = [
      '^9 || ^10',
      '10.3.0',
      TRUE,
    ];
    $test_cases['incompatible'] = [
      '^10',
      '11.0.0',
      FALSE,
    ];
    $test_cases['broken'] = [
      '^^11',
      '11.0.0',
      FALSE,
    ];
    return $test_cases;
  }

}
