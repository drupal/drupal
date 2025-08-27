<?php

declare(strict_types=1);

namespace Drupal\Tests\migrate\Unit\Exception;

use Drupal\migrate\Exception\RequirementsException;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests Drupal\migrate\Exception\RequirementsException.
 */
#[CoversClass(RequirementsException::class)]
#[Group('migrate')]
class RequirementsExceptionTest extends UnitTestCase {

  protected const MISSING_REQUIREMENTS = ['random_jackson_pivot', 'exoplanet'];

  /**
   * Tests get requirements.
   *
   * @legacy-covers ::getRequirements
   */
  public function testGetRequirements(): void {
    $exception = new RequirementsException('Missing requirements ', ['requirements' => static::MISSING_REQUIREMENTS]);
    $this->assertEquals(['requirements' => static::MISSING_REQUIREMENTS], $exception->getRequirements());
  }

  /**
   * Tests get exception string.
   *
   * @legacy-covers ::getRequirementsString
   */
  #[DataProvider('getRequirementsProvider')]
  public function testGetExceptionString($expected, $message, $requirements): void {
    $exception = new RequirementsException($message, $requirements);
    $this->assertEquals($expected, $exception->getRequirementsString());
  }

  /**
   * Provides a list of requirements to test.
   */
  public static function getRequirementsProvider() {
    return [
      [
        'requirements: random_jackson_pivot.',
        'Single Requirement',
        ['requirements' => static::MISSING_REQUIREMENTS[0]],
      ],
      [
        'requirements: random_jackson_pivot. requirements: exoplanet.',
        'Multiple Requirements',
        ['requirements' => static::MISSING_REQUIREMENTS],
      ],
    ];
  }

}
