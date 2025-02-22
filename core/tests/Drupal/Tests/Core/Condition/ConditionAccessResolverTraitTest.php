<?php

declare(strict_types=1);

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
  public function testResolveConditions($conditions, $logic, $expected): void {
    $mocks['true'] = $this->createMock('Drupal\Core\Condition\ConditionInterface');
    $mocks['true']->expects($this->any())
      ->method('execute')
      ->willReturn(TRUE);
    $mocks['false'] = $this->createMock('Drupal\Core\Condition\ConditionInterface');
    $mocks['false']->expects($this->any())
      ->method('execute')
      ->willReturn(FALSE);
    $mocks['exception'] = $this->createMock('Drupal\Core\Condition\ConditionInterface');
    $mocks['exception']->expects($this->any())
      ->method('execute')
      ->will($this->throwException(new ContextException()));
    $mocks['exception']->expects($this->any())
      ->method('isNegated')
      ->willReturn(FALSE);
    $mocks['negated'] = $this->createMock('Drupal\Core\Condition\ConditionInterface');
    $mocks['negated']->expects($this->any())
      ->method('execute')
      ->will($this->throwException(new ContextException()));
    $mocks['negated']->expects($this->any())
      ->method('isNegated')
      ->willReturn(TRUE);

    $conditions = array_map(fn($id) => $mocks[$id], $conditions);

    $trait_object = new TestConditionAccessResolverTrait();
    $this->assertEquals($expected, $trait_object->resolveConditions($conditions, $logic));
  }

  public static function providerTestResolveConditions() {
    yield [[], 'and', TRUE];
    yield [[], 'or', FALSE];
    yield [['false'], 'or', FALSE];
    yield [['false'], 'and', FALSE];
    yield [['true'], 'or', TRUE];
    yield [['true'], 'and', TRUE];
    yield [['true', 'false'], 'or', TRUE];
    yield [['true', 'false'], 'and', FALSE];
    yield [['exception'], 'or', FALSE];
    yield [['exception'], 'and', FALSE];
    yield [['true', 'exception'], 'or', TRUE];
    yield [['true', 'exception'], 'and', FALSE];
    yield [['exception', 'true'], 'or', TRUE];
    yield [['exception', 'true'], 'and', FALSE];
    yield [['false', 'exception'], 'or', FALSE];
    yield [['false', 'exception'], 'and', FALSE];
    yield [['exception', 'false'], 'or', FALSE];
    yield [['exception', 'false'], 'and', FALSE];
    yield [['negated'], 'or', TRUE];
    yield [['negated'], 'and', TRUE];
    yield [['negated', 'negated'], 'or', TRUE];
    yield [['negated', 'negated'], 'and', TRUE];
  }

}

/**
 * Stub class for testing trait.
 */
class TestConditionAccessResolverTrait {
  use \Drupal\Core\Condition\ConditionAccessResolverTrait {
    resolveConditions as public;
  }

}
