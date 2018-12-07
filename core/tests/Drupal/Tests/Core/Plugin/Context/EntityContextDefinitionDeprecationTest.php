<?php

namespace Drupal\Tests\Core\Plugin\Context;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Plugin\Context\Context;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\Core\Plugin\Context\EntityContextDefinition;
use Drupal\Core\TypedData\TypedDataManagerInterface;
use Drupal\Core\Validation\ConstraintManager;
use Drupal\Tests\UnitTestCase;
use Prophecy\Argument;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Test deprecated use of ContextDefinition as an EntityContextDefinition.
 *
 * @coversDefaultClass \Drupal\Core\Plugin\Context\ContextDefinition
 *
 * @group Plugin
 * @group legacy
 *
 * @see https://www.drupal.org/node/2976400
 */
class EntityContextDefinitionDeprecationTest extends UnitTestCase {

  /**
   * The context definition under test.
   *
   * @var \Drupal\Core\Plugin\Context\ContextDefinition
   */
  protected $definition;

  /**
   * The compatibility layer property on the context definition under test.
   *
   * @var \ReflectionProperty
   */
  protected $compatibilityLayer;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Mock container services needed for constraint validation.
    $constraint_manager = $this->prophesize(ConstraintManager::class);
    $constraint_manager->create(Argument::type('string'), Argument::any())->willReturn(TRUE);

    $typed_data_manager = $this->prophesize(TypedDataManagerInterface::class);
    $typed_data_manager->getValidationConstraintManager()->willReturn($constraint_manager->reveal());

    $validator = $this->prophesize(ValidatorInterface::class)
      ->reveal();
    $typed_data_manager->getValidator()->willReturn($validator);

    $container = new ContainerBuilder();
    $container->set('typed_data_manager', $typed_data_manager->reveal());
    \Drupal::setContainer($container);

    // Create a deprecated entity context definition and prepare the
    // compatibility layer to be overridden.
    $this->definition = new ContextDefinition('entity:node');
    // The code paths we're testing are private and protected, so use reflection
    // to manipulate protected properties.
    $reflector = new \ReflectionObject($this->definition);

    // Ensure that the BC object was created correctly.
    $this->assertTrue($reflector->hasProperty('entityContextDefinition'));
    $this->compatibilityLayer = $reflector->getProperty('entityContextDefinition');
    $this->compatibilityLayer->setAccessible(TRUE);
    $this->assertInstanceOf(EntityContextDefinition::class, $this->compatibilityLayer->getValue($this->definition));
  }

  /**
   * Test that the BC layer survives serialization and unserialization.
   *
   * @expectedDeprecation Constructing a ContextDefinition object for an entity type is deprecated in Drupal 8.6.0. Use Drupal\Core\Plugin\Context\EntityContextDefinition instead. See https://www.drupal.org/node/2976400 for more information.
   */
  public function testSerialization() {
    $definition = unserialize(serialize($this->definition));
    $bc_layer = $this->compatibilityLayer->getValue($definition);
    $this->assertInstanceOf(EntityContextDefinition::class, $bc_layer);
  }

  /**
   * Test that getConstraints() proxies to the compatibility layer.
   *
   * @covers ::getConstraints
   * @expectedDeprecation Constructing a ContextDefinition object for an entity type is deprecated in Drupal 8.6.0. Use Drupal\Core\Plugin\Context\EntityContextDefinition instead. See https://www.drupal.org/node/2976400 for more information.
   */
  public function testGetConstraints() {
    $bc_mock = $this->getMockBuilder(EntityContextDefinition::class)
      ->setMethods(['getConstraints'])
      ->getMock();

    $constraints = ['test_constraint'];
    $bc_mock->expects($this->once())
      ->method('getConstraints')
      ->willReturn($constraints);
    $this->compatibilityLayer->setValue($this->definition, $bc_mock);

    $this->assertSame($constraints, $this->definition->getConstraints());
  }

  /**
   * Test that getConstraint() proxies to the compatibility layer.
   *
   * @covers ::getConstraint
   * @expectedDeprecation Constructing a ContextDefinition object for an entity type is deprecated in Drupal 8.6.0. Use Drupal\Core\Plugin\Context\EntityContextDefinition instead. See https://www.drupal.org/node/2976400 for more information.
   */
  public function testGetConstraint() {
    $bc_mock = $this->getMockBuilder(EntityContextDefinition::class)
      ->setMethods(['getConstraint'])
      ->getMock();

    $bc_mock->expects($this->once())
      ->method('getConstraint')
      ->with('constraint_name')
      ->willReturn('test_constraint');
    $this->compatibilityLayer->setValue($this->definition, $bc_mock);

    $this->assertSame('test_constraint', $this->definition->getConstraint('constraint_name'));
  }

  /**
   * Test that setConstraints() proxies to the compatibility layer.
   *
   * @covers ::setConstraints
   * @expectedDeprecation Constructing a ContextDefinition object for an entity type is deprecated in Drupal 8.6.0. Use Drupal\Core\Plugin\Context\EntityContextDefinition instead. See https://www.drupal.org/node/2976400 for more information.
   */
  public function testSetConstraints() {
    $bc_mock = $this->getMockBuilder(EntityContextDefinition::class)
      ->setMethods(['setConstraints'])
      ->getMock();

    $constraints = ['TestConstraint' => []];
    $bc_mock->expects($this->once())
      ->method('setConstraints')
      ->with($constraints)
      ->willReturnSelf();
    $this->compatibilityLayer->setValue($this->definition, $bc_mock);

    $this->assertSame($this->definition, $this->definition->setConstraints($constraints));
  }

  /**
   * Test that addConstraint() proxies to the compatibility layer.
   *
   * @covers ::addConstraint
   * @expectedDeprecation Constructing a ContextDefinition object for an entity type is deprecated in Drupal 8.6.0. Use Drupal\Core\Plugin\Context\EntityContextDefinition instead. See https://www.drupal.org/node/2976400 for more information.
   */
  public function testAddConstraint() {
    $bc_mock = $this->getMockBuilder(EntityContextDefinition::class)
      ->setMethods(['addConstraint'])
      ->getMock();

    $options = ['options'];
    $bc_mock->expects($this->once())
      ->method('addConstraint')
      ->with('constraint_name', $options)
      ->willReturnSelf();
    $this->compatibilityLayer->setValue($this->definition, $bc_mock);

    $this->assertSame($this->definition, $this->definition->addConstraint('constraint_name', $options));
  }

  /**
   * Test that isSatisfiedBy() calls the compatibility layer.
   *
   * @covers ::isSatisfiedBy
   * @expectedDeprecation Constructing a ContextDefinition object for an entity type is deprecated in Drupal 8.6.0. Use Drupal\Core\Plugin\Context\EntityContextDefinition instead. See https://www.drupal.org/node/2976400 for more information.
   */
  public function testIsSatisfiedBy() {
    // Ensure that the BC object's getSampleValues() method is called during
    // validation.
    $bc_mock = $this->getMockBuilder(EntityContextDefinition::class)
      ->setMethods(['getSampleValues'])
      ->getMock();

    $bc_mock->expects($this->atLeastOnce())
      ->method('getSampleValues')
      ->willReturn([]);
    $this->compatibilityLayer->setValue($this->definition, $bc_mock);
    $this->definition->isSatisfiedBy(new Context($this->definition));
  }

  /**
   * Test that getConstraintObjects() adds the EntityType constraint.
   *
   * @covers ::getConstraintObjects
   * @expectedDeprecation Constructing a ContextDefinition object for an entity type is deprecated in Drupal 8.6.0. Use Drupal\Core\Plugin\Context\EntityContextDefinition instead. See https://www.drupal.org/node/2976400 for more information.
   */
  public function testGetConstraintObjects() {
    $reflector = new \ReflectionObject($this->definition);
    $method = $reflector->getMethod('getConstraintObjects');
    $method->setAccessible(TRUE);
    $this->assertArrayHasKey('EntityType', $method->invoke($this->definition));
  }

}
