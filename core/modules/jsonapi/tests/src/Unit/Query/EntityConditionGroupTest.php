<?php

namespace Drupal\Tests\jsonapi\Unit\Query;

use Drupal\jsonapi\Query\EntityConditionGroup;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\jsonapi\Query\EntityConditionGroup
 * @group jsonapi
 * @group legacy
 *
 * @internal
 */
class EntityConditionGroupTest extends UnitTestCase {

  /**
   * @covers ::__construct
   * @dataProvider constructProvider
   */
  public function testConstruct($case) {
    $group = new EntityConditionGroup($case['conjunction'], $case['members']);

    $this->assertEquals($case['conjunction'], $group->conjunction());

    foreach ($group->members() as $key => $condition) {
      $this->assertEquals($case['members'][$key]['path'], $condition->field());
      $this->assertEquals($case['members'][$key]['value'], $condition->value());
    }
  }

  /**
   * @covers ::__construct
   */
  public function testConstructException() {
    $this->setExpectedException(\InvalidArgumentException::class);
    new EntityConditionGroup('NOT_ALLOWED', []);
  }

  /**
   * Data provider for testConstruct.
   */
  public function constructProvider() {
    return [
      [['conjunction' => 'AND', 'members' => []]],
      [['conjunction' => 'OR', 'members' => []]],
    ];
  }

}
