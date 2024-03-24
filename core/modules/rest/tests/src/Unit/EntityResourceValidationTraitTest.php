<?php

declare(strict_types=1);

namespace Drupal\Tests\rest\Unit;

use Drupal\Core\Entity\EntityConstraintViolationList;
use Drupal\node\Entity\Node;
use Drupal\rest\Plugin\rest\resource\EntityResourceValidationTrait;
use Drupal\Tests\UnitTestCase;
use Drupal\user\Entity\User;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Validator\ConstraintViolationInterface;

/**
 * @group rest
 * @coversDefaultClass \Drupal\rest\Plugin\rest\resource\EntityResourceValidationTrait
 */
class EntityResourceValidationTraitTest extends UnitTestCase {

  /**
   * @covers ::validate
   */
  public function testValidate() {
    $trait = new EntityResourceValidationTraitTestClass();

    $method = new \ReflectionMethod($trait, 'validate');

    $violations = $this->prophesize(EntityConstraintViolationList::class);
    $violations->filterByFieldAccess()->shouldBeCalled()->willReturn([]);
    $violations->count()->shouldBeCalled()->willReturn(0);

    $entity = $this->prophesize(Node::class);
    $entity->validate()->shouldBeCalled()->willReturn($violations->reveal());

    $method->invoke($trait, $entity->reveal());
  }

  /**
   * @covers ::validate
   */
  public function testFailedValidate() {
    $violation1 = $this->prophesize(ConstraintViolationInterface::class);
    $violation1->getPropertyPath()->willReturn('property_path');
    $violation1->getMessage()->willReturn('message');

    $violation2 = $this->prophesize(ConstraintViolationInterface::class);
    $violation2->getPropertyPath()->willReturn('property_path');
    $violation2->getMessage()->willReturn('message');

    $entity = $this->prophesize(User::class);

    $violations = $this->getMockBuilder(EntityConstraintViolationList::class)
      ->setConstructorArgs([$entity->reveal(), [$violation1->reveal(), $violation2->reveal()]])
      ->onlyMethods(['filterByFieldAccess'])
      ->getMock();

    $violations->expects($this->once())
      ->method('filterByFieldAccess')
      ->willReturn([]);

    $entity->validate()->willReturn($violations);

    $trait = new EntityResourceValidationTraitTestClass();

    $method = new \ReflectionMethod($trait, 'validate');

    $this->expectException(UnprocessableEntityHttpException::class);

    $method->invoke($trait, $entity->reveal());
  }

}

/**
 * A test class to use to test EntityResourceValidationTrait.
 *
 * Because the mock doesn't use the \Drupal namespace, the Symfony 4+ class
 * loader will throw a deprecation error.
 */
class EntityResourceValidationTraitTestClass {
  use EntityResourceValidationTrait;

}
