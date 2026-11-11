<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Condition;

use Drupal\Component\Plugin\Exception\ContextException;
use Drupal\Core\Condition\ConditionAccessResolverTrait;
use Drupal\Core\Condition\ConditionInterface;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\MockObject\Stub;

/**
 * Tests Drupal\Core\Condition\ConditionAccessResolverTrait.
 */
#[CoversTrait(ConditionAccessResolverTrait::class)]
#[Group('Condition')]
class ConditionAccessResolverTraitTest extends UnitTestCase {

  /**
   * Tests the resolveConditions() method.
   */
  #[DataProvider('providerTestResolveConditions')]
  public function testResolveConditions(array $conditions, string $logic, bool $expected): void {
    $mocks['true'] = $this->createStub(ConditionInterface::class);
    $mocks['true']
      ->method('execute')
      ->willReturn(TRUE);
    $mocks['false'] = $this->createStub(ConditionInterface::class);
    $mocks['false']
      ->method('execute')
      ->willReturn(FALSE);
    $mocks['exception'] = $this->createStub(ConditionInterface::class);
    $mocks['exception']
      ->method('execute')
      ->will($this->throwException(new ContextException()));
    $mocks['exception']
      ->method('isNegated')
      ->willReturn(FALSE);
    $mocks['negated'] = $this->createStub(ConditionInterface::class);
    $mocks['negated']
      ->method('execute')
      ->will($this->throwException(new ContextException()));
    $mocks['negated']
      ->method('isNegated')
      ->willReturn(TRUE);

    $conditions = array_map(fn($id): ConditionInterface&Stub => $mocks[$id], $conditions);

    $trait_object = new TestConditionAccessResolverTrait();
    $this->assertEquals($expected, $trait_object->resolveConditions($conditions, $logic));
  }

  public static function providerTestResolveConditions(): \Iterator {
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
  use ConditionAccessResolverTrait {
    resolveConditions as public;
  }

}
