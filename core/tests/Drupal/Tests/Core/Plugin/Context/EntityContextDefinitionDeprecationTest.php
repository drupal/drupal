<?php

namespace Drupal\Tests\Core\Plugin\Context;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Plugin\Context\Context;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\Core\Plugin\Context\EntityContextDefinition;
use Drupal\Tests\UnitTestCase;
use Prophecy\Argument;

/**
 * @coversDefaultClass \Drupal\Core\Plugin\Context\ContextDefinition
 *
 * @group Plugin
 * @group legacy
 */
class EntityContextDefinitionDeprecationTest extends UnitTestCase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Mock container services needed for constraint validation.
    $constraint_manager = $this->prophesize('\Drupal\Core\Validation\ConstraintManager');
    $constraint_manager->create(Argument::type('string'), Argument::any())->willReturn(TRUE);

    $typed_data_manager = $this->prophesize('\Drupal\Core\TypedData\TypedDataManagerInterface');
    $typed_data_manager->getValidationConstraintManager()->willReturn($constraint_manager->reveal());

    $validator = $this->prophesize('\Symfony\Component\Validator\Validator\ValidatorInterface')
      ->reveal();
    $typed_data_manager->getValidator()->willReturn($validator);

    $container = new ContainerBuilder();
    $container->set('typed_data_manager', $typed_data_manager->reveal());
    \Drupal::setContainer($container);
  }

  /**
   * @expectedDeprecation Constructing a ContextDefinition object for an entity type is deprecated in Drupal 8.6.0. Use Drupal\Core\Plugin\Context\EntityContextDefinition instead. See https://www.drupal.org/node/2976400 for more information.
   */
  public function testDeprecationNotice() {
    $definition = new ContextDefinition('entity:node');
    // The code paths we're testing are private and protected, so use reflection
    // to manipulate protected properties.
    $reflector = new \ReflectionObject($definition);

    // Ensure that the BC object was created correctly.
    $this->assertTrue($reflector->hasProperty('entityContextDefinition'));
    $property = $reflector->getProperty('entityContextDefinition');
    $property->setAccessible(TRUE);
    $this->assertInstanceOf(EntityContextDefinition::class, $property->getValue($definition));

    // Ensure that getConstraintObjects() adds the EntityType constraint.
    $method = $reflector->getMethod('getConstraintObjects');
    $method->setAccessible(TRUE);
    $this->assertArrayHasKey('EntityType', $method->invoke($definition));

    // Ensure that the BC object's getSampleValues() method is called during
    // validation.
    $bc_mock = $this->getMockBuilder(EntityContextDefinition::class)
      ->setMethods(['getSampleValues'])
      ->getMock();

    $bc_mock->expects($this->atLeastOnce())
      ->method('getSampleValues')
      ->willReturn([]);
    $property->setValue($definition, $bc_mock);
    $definition->isSatisfiedBy(new Context($definition));

    // Ensure that the BC layer survives serialization and unserialization.
    $definition = unserialize(serialize($definition));
    $this->assertInstanceOf(EntityContextDefinition::class, $property->getValue($definition));
  }

}
