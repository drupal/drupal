<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Database;

use Drupal\Core\Database\RowCountException;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;

/**
 * Tests Drupal\Core\Database\RowCountException.
 */
#[CoversClass(RowCountException::class)]
#[Group('Database')]
class RowCountExceptionTest extends UnitTestCase {

  /**
   * The default exception message.
   */
  private const DEFAULT_EXCEPTION_MESSAGE = "rowCount() is supported for DELETE, INSERT, or UPDATE statements performed with structured query builders only, since they would not be portable across database engines otherwise. If the query builders are not sufficient, use a prepareStatement() with an \$allow_row_count argument set to TRUE, execute() the Statement and get the number of matched rows via rowCount().";

  /**
   * Data provider for ::testExceptionMessage()
   *
   * @return array
   *   An array of test data for ::testExceptionMessage().
   */
  public static function providerTestExceptionMessage(): array {
    return [
      [self::DEFAULT_EXCEPTION_MESSAGE, ''],
      ['test', 'test'],
    ];
  }

  /**
   * Tests exception message.
   *
   * @legacy-covers ::__construct
   */
  #[DataProvider('providerTestExceptionMessage')]
  public function testExceptionMessage($expected, $message): void {
    $e = new RowCountException($message);
    $this->assertSame($expected, $e->getMessage());
  }

  /**
   * Tests exception message null.
   *
   * @legacy-covers ::__construct
   */
  #[IgnoreDeprecations]
  public function testExceptionMessageNull(): void {
    $e = new RowCountException(NULL);
    $this->assertSame(self::DEFAULT_EXCEPTION_MESSAGE, $e->getMessage());
  }

}
