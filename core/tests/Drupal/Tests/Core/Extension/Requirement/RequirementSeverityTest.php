<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Extension\Requirement;

include_once \DRUPAL_ROOT . '/core/includes/install.inc';

use Drupal\Core\Extension\Requirement\RequirementSeverity;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\Core\Extension\Requirement\RequirementSeverity
 *
 * @group Extension
 */
class RequirementSeverityTest extends UnitTestCase {

  /**
   * @covers ::convertLegacyIntSeveritiesToEnums
   * @group legacy
   */
  public function testConvertLegacySeverities(): void {
    $requirements['foo'] = [
      'title' => new TranslatableMarkup('Foo'),
      'severity' => \REQUIREMENT_INFO,
    ];
    $requirements['bar'] = [
      'title' => new TranslatableMarkup('Bar'),
      'severity' => \REQUIREMENT_ERROR,
    ];
    $this->expectDeprecation(
      'Calling ' . __METHOD__ . '() with an array of $requirements with \'severity\' with values not of type Drupal\Core\Extension\Requirement\RequirementSeverity enums is deprecated in drupal:11.2.0 and is required in drupal:12.0.0. See https://www.drupal.org/node/3410939'
    );
    RequirementSeverity::convertLegacyIntSeveritiesToEnums($requirements, __METHOD__);
    $this->assertEquals(
      RequirementSeverity::Info,
      $requirements['foo']['severity']
    );
    $this->assertEquals(
      RequirementSeverity::Error,
      $requirements['bar']['severity']
    );
  }

  /**
   * @covers ::maxSeverityFromRequirements
   * @dataProvider requirementProvider
   */
  public function testGetMaxSeverity(array $requirements, RequirementSeverity $expectedSeverity): void {
    $severity = RequirementSeverity::maxSeverityFromRequirements($requirements);
    $this->assertEquals($expectedSeverity, $severity);
  }

  /**
   * Data provider for requirement helper test.
   */
  public static function requirementProvider(): array {
    $info = [
      'title' => 'Foo',
      'severity' => RequirementSeverity::Info,
    ];
    $warning = [
      'title' => 'Baz',
      'severity' => RequirementSeverity::Warning,
    ];
    $error = [
      'title' => 'Wiz',
      'severity' => RequirementSeverity::Error,
    ];
    $ok = [
      'title' => 'Bar',
      'severity' => RequirementSeverity::OK,
    ];

    return [
      'error is most severe' => [
        [
          $info,
          $error,
          $ok,
        ],
        RequirementSeverity::Error,
      ],
      'ok is most severe' => [
        [
          $info,
          $ok,
        ],
        RequirementSeverity::OK,
      ],
      'warning is most severe' => [
        [
          $warning,
          $info,
          $ok,
        ],
        RequirementSeverity::Warning,
      ],
    ];
  }

}
