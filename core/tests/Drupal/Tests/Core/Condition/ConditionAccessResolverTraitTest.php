<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Condition\ConditionAccessResolverTraitTest.
 */

namespace Drupal\Tests\Core\Condition;

use Drupal\Component\Plugin\Exception\ContextException;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\Core\Condition\ConditionAccessResolverTrait
 * @group Condition
 */
class ConditionAccessResolverTraitTest extends UnitTestCase {

  /**
   * Tests the resolveConditions() method.
   *
   * @covers ::resolveConditions
   *
   * @dataProvider providerTestResolveConditions
   */
  public function testResolveConditions($conditions, $logic, $expected) {
    $trait_object = new TestConditionAccessResolverTrait();
    $this->assertEquals($expected, $trait_object->resolveConditions($conditions, $logic));
  }

  public function providerTestResolveConditions() {
    $data = [];

    $condition_true = $this->createMock('Drupal\Core\Condition\ConditionInterface');
    $condition_true->expects($this->any())
      ->method('execute')
      ->will($this->returnValue(TRUE));
    $condition_false = $this->createMock('Drupal\Core\Condition\ConditionInterface');
    $condition_false->expects($this->any())
      ->method('execute')
      ->will($this->returnValue(FALSE));
    $condition_exception = $this->createMock('Drupal\Core\Condition\ConditionInterface');
    $condition_exception->expects($this->any())
      ->method('execute')
      ->will($this->throwException(new ContextException()));
    $condition_exception->expects($this->atLeastOnce())
      ->method('isNegated')
      ->will($this->returnValue(FALSE));
    $condition_negated = $this->createMock('Drupal\Core\Condition\ConditionInterface');
    $condition_negated->expects($this->any())
      ->method('execute')
      ->will($this->throwException(new ContextException()));
    $condition_negated->expects($this->atLeastOnce())
      ->method('isNegated')
      ->will($this->returnValue(TRUE));

    $conditions = [];
    $data[] = [$conditions, 'and', TRUE];
    $data[] = [$conditions, 'or', FALSE];

    $conditions = [$condition_false];
    $data[] = [$conditions, 'or', FALSE];
    $data[] = [$conditions, 'and', FALSE];

    $conditions = [$condition_true];
    $data[] = [$conditions, 'or', TRUE];
    $data[] = [$conditions, 'and', TRUE];

    $conditions = [$condition_true, $condition_false];
    $data[] = [$conditions, 'or', TRUE];
    $data[] = [$conditions, 'and', FALSE];

    $conditions = [$condition_exception];
    $data[] = [$conditions, 'or', FALSE];
    $data[] = [$conditions, 'and', FALSE];

    $conditions = [$condition_true, $condition_exception];
    $data[] = [$conditions, 'or', TRUE];
    $data[] = [$conditions, 'and', FALSE];

    $conditions = [$condition_exception, $condition_true];
    $data[] = [$conditions, 'or', TRUE];
    $data[] = [$conditions, 'and', FALSE];

    $conditions = [$condition_false, $condition_exception];
    $data[] = [$conditions, 'or', FALSE];
    $data[] = [$conditions, 'and', FALSE];

    $conditions = [$condition_exception, $condition_false];
    $data[] = [$conditions, 'or', FALSE];
    $data[] = [$conditions, 'and', FALSE];

    $conditions = [$condition_negated];
    $data[] = [$conditions, 'or', TRUE];
    $data[] = [$conditions, 'and', TRUE];

    $conditions = [$condition_negated, $condition_negated];
    $data[] = [$conditions, 'or', TRUE];
    $data[] = [$conditions, 'and', TRUE];
    return $data;
  }

}

class TestConditionAccessResolverTrait {
  use \Drupal\Core\Condition\ConditionAccessResolverTrait {
    resolveConditions as public;
  }

}
