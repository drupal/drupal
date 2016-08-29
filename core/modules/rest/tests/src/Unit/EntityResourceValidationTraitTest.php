<?php

namespace Drupal\Tests\rest\Unit;

use Drupal\Core\Entity\EntityConstraintViolationList;
use Drupal\node\Entity\Node;
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
    $trait = $this->getMockForTrait('Drupal\rest\Plugin\rest\resource\EntityResourceValidationTrait');

    $method = new \ReflectionMethod($trait, 'validate');
    $method->setAccessible(TRUE);

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
      ->setMethods(['filterByFieldAccess'])
      ->getMock();

    $violations->expects($this->once())
      ->method('filterByFieldAccess')
      ->will($this->returnValue([]));

    $entity->validate()->willReturn($violations);

    $trait = $this->getMockForTrait('Drupal\rest\Plugin\rest\resource\EntityResourceValidationTrait');

    $method = new \ReflectionMethod($trait, 'validate');
    $method->setAccessible(TRUE);

    $this->setExpectedException(UnprocessableEntityHttpException::class);

    $method->invoke($trait, $entity->reveal());
  }

}
