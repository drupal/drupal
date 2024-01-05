<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Database;

use Drupal\Core\Database\RowCountException;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\Core\Database\RowCountException
 *
 * @group Database
 */
class RowCountExceptionTest extends UnitTestCase {

  /**
   * The default exception message.
   */
  private const DEFAULT_EXCEPTION_MESSAGE = "rowCount() is supported for DELETE, INSERT, or UPDATE statements performed with structured query builders only, since they would not be portable across database engines otherwise. If the query builders are not sufficient, use a prepareStatement() with an \$allow_row_count argument set to TRUE, execute() the Statement and get the number of matched rows via rowCount().";

  /**
   * Data provider for ::testExceptionMessage()
   *
   * @return array
   */
  public function providerTestExceptionMessage() {
    return [
      [static::DEFAULT_EXCEPTION_MESSAGE, ''],
      ['test', 'test'],
    ];
  }

  /**
   * @covers ::__construct
   * @dataProvider providerTestExceptionMessage
   */
  public function testExceptionMessage($expected, $message) {
    $e = new RowCountException($message);
    $this->assertSame($expected, $e->getMessage());
  }

  /**
   * @covers ::__construct
   * @group legacy
   */
  public function testExceptionMessageNull() {
    $e = new RowCountException(NULL);
    $this->assertSame(static::DEFAULT_EXCEPTION_MESSAGE, $e->getMessage());
  }

}
