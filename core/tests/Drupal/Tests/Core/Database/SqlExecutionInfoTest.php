<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Database;

use Drupal\Core\Database\SqlExecutionInfo;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests Drupal\Core\Database\SqlExecutionInfo.
 */
#[CoversClass(SqlExecutionInfo::class)]
#[Group('Database')]
class SqlExecutionInfoTest extends UnitTestCase {

  /**
   * Tests the compact representation used in exception messages.
   */
  #[DataProvider('providerToCompactString')]
  public function testToCompactString(string $expected, string $sql, array $placeholders): void {
    $this->assertSame($expected, (new SqlExecutionInfo($sql, $placeholders))->toCompactString());
  }

  /**
   * Provides test cases for ::testToCompactString().
   */
  public static function providerToCompactString(): array {
    return [
      'no placeholders' => [
        'SELECT 1; []',
        'SELECT 1',
        [],
      ],
      'named placeholders' => [
        'SELECT [name] FROM {test} WHERE [age] > :a; [:a=18]',
        'SELECT [name] FROM {test} WHERE [age] > :a',
        [':a' => 18],
      ],
      'positional placeholders' => [
        "SELECT 1; [0='a', 1='b']",
        'SELECT 1',
        ['a', 'b'],
      ],
      'value types' => [
        "SELECT 1; [:a=NULL, :b=TRUE, :c=FALSE, :d=1.5, :e='', :f=array, :g=stdClass]",
        'SELECT 1',
        [
          ':a' => NULL,
          ':b' => TRUE,
          ':c' => FALSE,
          ':d' => 1.5,
          ':e' => '',
          ':f' => ['x', 2],
          ':g' => new \stdClass(),
        ],
      ],
      'multiline statement is collapsed' => [
        "SELECT 1 FROM {test} WHERE [a] = :a; [:a='b']",
        "  SELECT 1\nFROM {test}\n  WHERE [a] = :a  ",
        [':a' => 'b'],
      ],
      'multiline value stays on one line' => [
        "SELECT 1; [:a='first\\nsecond']",
        'SELECT 1',
        [':a' => "first\nsecond"],
      ],
    ];
  }

  /**
   * Tests appending to an exception message.
   */
  public function testAppendToMessage(): void {
    $info = new SqlExecutionInfo('SELECT 1', [':a' => 'b'], ['fetch' => 'assoc']);
    // The options are not included in the message.
    $this->assertSame("Failed: SELECT 1; [:a='b']", $info->appendToMessage('Failed'));
  }

}
