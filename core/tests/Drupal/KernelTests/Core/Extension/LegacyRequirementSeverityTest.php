<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Extension;

use Drupal\KernelTests\KernelTestBase;

include_once \DRUPAL_ROOT . '/core/includes/install.inc';

/**
 * Tests the legacy requirements severity deprecations.
 *
 * @coversDefaultClass \Drupal\Core\Extension\Requirement\RequirementSeverity
 * @group extension
 * @group legacy
 */
class LegacyRequirementSeverityTest extends KernelTestBase {

  /**
   * @covers \drupal_requirements_severity
   * @dataProvider requirementProvider
   */
  public function testGetMaxSeverity(array $requirements, int $expectedSeverity): void {
    $this->expectDeprecation(
      'drupal_requirements_severity() is deprecated in drupal:11.2.0 and is removed from drupal:12.0.0. Use Drupal\Core\Extension\Requirement\RequirementSeverity::maxSeverityFromRequirements() instead. See https://www.drupal.org/node/3410939'
    );
    $this->expectDeprecation(
      'Calling Drupal\Core\Extension\Requirement\RequirementSeverity::maxSeverityFromRequirements() with an array of $requirements with \'severity\' with values not of type Drupal\Core\Extension\Requirement\RequirementSeverity enums is deprecated in drupal:11.2.0 and is required in drupal:12.0.0. See https://www.drupal.org/node/3410939'
    );
    $severity = drupal_requirements_severity($requirements);
    $this->assertEquals($expectedSeverity, $severity);
  }

  /**
   * Data provider for requirement helper test.
   *
   * @return array
   *   Test data.
   */
  public static function requirementProvider(): array {
    $info = [
      'title' => 'Foo',
      'severity' => \REQUIREMENT_INFO,
    ];
    $warning = [
      'title' => 'Baz',
      'severity' => \REQUIREMENT_WARNING,
    ];
    $error = [
      'title' => 'Wiz',
      'severity' => \REQUIREMENT_ERROR,
    ];
    $ok = [
      'title' => 'Bar',
      'severity' => \REQUIREMENT_OK,
    ];

    return [
      'error is most severe' => [
        [
          $info,
          $error,
          $ok,
        ],
        \REQUIREMENT_ERROR,
      ],
      'ok is most severe' => [
        [
          $info,
          $ok,
        ],
        \REQUIREMENT_OK,
      ],
      'warning is most severe' => [
        [
          $warning,
          $info,
          $ok,
        ],
        \REQUIREMENT_WARNING,
      ],
    ];
  }

}
