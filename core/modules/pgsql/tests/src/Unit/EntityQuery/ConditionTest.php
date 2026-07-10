<?php

declare(strict_types=1);

namespace Drupal\Tests\pgsql\Unit\EntityQuery;

use Drupal\Core\Database\Query\SelectInterface;
use Drupal\pgsql\EntityQuery\Condition;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests \Drupal\pgsql\EntityQuery\Condition.
 */
#[CoversClass(Condition::class)]
#[Group('Database')]
class ConditionTest extends UnitTestCase {

  /**
   * Tests that valid operators are accepted for case-insensitive array values.
   */
  #[DataProvider('providerValidOperators')]
  public function testValidOperatorsAreAccepted(string $operator): void {
    $sql_query = $this->createStub(SelectInterface::class);
    $sql_query->method('escapeField')->willReturn('field');

    $condition = [
      'real_field' => 'field',
      'operator' => $operator,
      'value' => ['foo', 'bar'],
    ];

    Condition::translateCondition($condition, $sql_query, FALSE);

    $this->assertStringContainsString($operator, $condition['where']);
  }

  /**
   * Data provider for ::testValidOperatorsAreAccepted().
   */
  public static function providerValidOperators(): array {
    return [
      'IN operator' => ['IN'],
      'NOT IN operator' => ['NOT IN'],
    ];
  }

  /**
   * Tests that an invalid operator throws an exception.
   */
  public function testInvalidOperatorThrowsException(): void {
    $sql_query = $this->createStub(SelectInterface::class);

    $condition = [
      'real_field' => 'field',
      'operator' => 'LIKE',
      'value' => ['foo', 'bar'],
    ];

    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('Invalid operator "LIKE"');

    Condition::translateCondition($condition, $sql_query, FALSE);
  }

  /**
   * Tests that case-sensitive conditions are not affected by the validation.
   */
  public function testCaseSensitiveArrayConditionIsNotValidated(): void {
    $sql_query = $this->createStub(SelectInterface::class);

    $condition = [
      'real_field' => 'field',
      'operator' => 'LIKE',
      'value' => ['foo', 'bar'],
    ];

    // No exception should be thrown for case-sensitive conditions.
    Condition::translateCondition($condition, $sql_query, TRUE);

    $this->assertArrayNotHasKey('where', $condition);
  }

}
