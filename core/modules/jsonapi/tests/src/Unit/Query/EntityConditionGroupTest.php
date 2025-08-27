<?php

declare(strict_types=1);

namespace Drupal\Tests\jsonapi\Unit\Query;

use Drupal\jsonapi\Query\EntityConditionGroup;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests Drupal\jsonapi\Query\EntityConditionGroup.
 *
 * @internal
 */
#[CoversClass(EntityConditionGroup::class)]
#[Group('jsonapi')]
class EntityConditionGroupTest extends UnitTestCase {

  /**
   * Tests construct.
   *
   * @legacy-covers ::__construct
   */
  #[DataProvider('constructProvider')]
  public function testConstruct($case): void {
    $group = new EntityConditionGroup($case['conjunction'], $case['members']);

    $this->assertEquals($case['conjunction'], $group->conjunction());

    foreach ($group->members() as $key => $condition) {
      $this->assertEquals($case['members'][$key]['path'], $condition->field());
      $this->assertEquals($case['members'][$key]['value'], $condition->value());
    }
  }

  /**
   * Tests construct exception.
   *
   * @legacy-covers ::__construct
   */
  public function testConstructException(): void {
    $this->expectException(\InvalidArgumentException::class);
    new EntityConditionGroup('NOT_ALLOWED', []);
  }

  /**
   * Data provider for testConstruct.
   */
  public static function constructProvider() {
    return [
      [['conjunction' => 'AND', 'members' => []]],
      [['conjunction' => 'OR', 'members' => []]],
    ];
  }

}
