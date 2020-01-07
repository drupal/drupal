<?php

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
  public function testSetProjectCoreCompatibilityRanges(array $project_data, $core_data, array $core_releases, array $expected_releases, array $expected_security_updates) {
    $project_compatibility = new ProjectCoreCompatibility($core_data, $core_releases);
    $project_compatibility->setStringTranslation($this->getStringTranslationStub());
    $project_compatibility->setReleaseMessage($project_data);
    $this->assertSame($expected_releases, $project_data['releases']);
    $this->assertSame($expected_security_updates, $project_data['security updates']);
  }

  /**
   * Data provider for testSetProjectCoreCompatibilityRanges().
   */
  public function providerSetProjectCoreCompatibilityRanges() {
    $test_cases['no 9 releases'] = [
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
      'expected_releases' => [
        '1.0.1' => [
          'core_compatibility' => '8.x',
          'core_compatibility_message' => 'This module is compatible with Drupal core: 8.8.0 to 8.9.2',
        ],
        '1.2.3' => [
          'core_compatibility' => '^8.9 || ^9',
          'core_compatibility_message' => 'This module is compatible with Drupal core: 8.9.0 to 8.9.2',
        ],
        '1.2.4' => [
          'core_compatibility' => '^8.9.2 || ^9',
          'core_compatibility_message' => 'This module is compatible with Drupal core: 8.9.2',
        ],
        '1.2.6' => [],
      ],
      'expected_security_updates' => [
        '1.2.5' => [
          'core_compatibility' => '8.9.0 || 8.9.2 || ^9.0.1',
          'core_compatibility_message' => 'This module is compatible with Drupal core: 8.9.0, 8.9.2',
        ],
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
    // Ensure that when the Drupal 9 full release are added the expected ranges
    // do change.
    $test_cases['with 9 full releases'] = $test_cases['with 9 pre releases'];
    $test_cases['with 9 full releases']['core_releases'] += [
      '9.0.0' => [],
      '9.0.1' => [],
      '9.0.2' => [],
    ];
    $test_cases['with 9 full releases']['expected_releases'] = [
      '1.0.1' => [
        'core_compatibility' => '8.x',
        'core_compatibility_message' => 'This module is compatible with Drupal core: 8.8.0 to 8.9.2',
      ],
      '1.2.3' => [
        'core_compatibility' => '^8.9 || ^9',
        'core_compatibility_message' => 'This module is compatible with Drupal core: 8.9.0 to 9.0.2',
      ],
      '1.2.4' => [
        'core_compatibility' => '^8.9.2 || ^9',
        'core_compatibility_message' => 'This module is compatible with Drupal core: 8.9.2 to 9.0.2',
      ],
      '1.2.6' => [],
    ];
    $test_cases['with 9 full releases']['expected_security_updates'] = [
      '1.2.5' => [
        'core_compatibility' => '8.9.0 || 8.9.2 || ^9.0.1',
        'core_compatibility_message' => 'This module is compatible with Drupal core: 8.9.0, 8.9.2, 9.0.1 to 9.0.2',
      ],
    ];
    return $test_cases;
  }

}
