<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Extension\Requirement;

include_once \DRUPAL_ROOT . '/core/includes/install.inc';

use Drupal\Core\Extension\Requirement\RequirementSeverity;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests Drupal\Core\Extension\Requirement\RequirementSeverity.
 */
#[CoversClass(RequirementSeverity::class)]
#[Group('Extension')]
class RequirementSeverityTest extends UnitTestCase {

  /**
   * Tests get max severity.
   *
   * @legacy-covers ::maxSeverityFromRequirements
   */
  #[DataProvider('requirementProvider')]
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
