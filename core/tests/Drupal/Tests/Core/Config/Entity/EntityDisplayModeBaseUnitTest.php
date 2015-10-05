<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Config\Entity\EntityDisplayModeBaseUnitTest.
 */

namespace Drupal\Tests\Core\Config\Entity;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\Core\Entity\EntityDisplayModeBase
 * @group Config
 */
class EntityDisplayModeBaseUnitTest extends UnitTestCase {

  /**
   * The entity under test.
   *
   * @var \Drupal\Core\Entity\EntityDisplayModeBase|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $entity;

  /**
   * The entity type used for testing.
   *
   * @var \Drupal\Core\Entity\EntityTypeInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $entityInfo;

  /**
   * The entity manager used for testing.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $entityManager;

  /**
   * The ID of the type of the entity under test.
   *
   * @var string
   */
  protected $entityType;

  /**
   * The UUID generator used for testing.
   *
   * @var \Drupal\Component\Uuid\UuidInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $uuid;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    $this->entityType = $this->randomMachineName();

    $this->entityInfo = $this->getMock('\Drupal\Core\Entity\EntityTypeInterface');
    $this->entityInfo->expects($this->any())
      ->method('getProvider')
      ->will($this->returnValue('entity'));

    $this->entityManager = $this->getMock('\Drupal\Core\Entity\EntityManagerInterface');

    $this->uuid = $this->getMock('\Drupal\Component\Uuid\UuidInterface');

    $container = new ContainerBuilder();
    $container->set('entity.manager', $this->entityManager);
    $container->set('uuid', $this->uuid);
    \Drupal::setContainer($container);
  }

  /**
   * @covers ::calculateDependencies
   */
  public function testCalculateDependencies() {
    $target_entity_type_id = $this->randomMachineName(16);

    $target_entity_type = $this->getMock('\Drupal\Core\Entity\EntityTypeInterface');
    $target_entity_type->expects($this->any())
      ->method('getProvider')
      ->will($this->returnValue('test_module'));
    $values = array('targetEntityType' => $target_entity_type_id);

    $this->entityManager->expects($this->at(0))
      ->method('getDefinition')
      ->with($target_entity_type_id)
      ->will($this->returnValue($target_entity_type));
    $this->entityManager->expects($this->at(1))
      ->method('getDefinition')
      ->with($this->entityType)
      ->will($this->returnValue($this->entityInfo));

    $this->entity = $this->getMockBuilder('\Drupal\Core\Entity\EntityDisplayModeBase')
      ->setConstructorArgs(array($values, $this->entityType))
      ->setMethods(array('getFilterFormat'))
      ->getMock();

    $dependencies = $this->entity->calculateDependencies()->getDependencies();
    $this->assertContains('test_module', $dependencies['module']);
  }

  /**
   * @covers ::setTargetType
   */
  public function testSetTargetType() {
    // Generate mock.
    $mock = $this->getMock(
      'Drupal\Core\Entity\EntityDisplayModeBase',
      NULL,
      array(array('something' => 'nothing'), 'test_type')
    );

    // Some test values.
    $bad_target = 'uninitialized';
    $target = 'test_target_type';

    // Gain access to the protected property.
    $property = new \ReflectionProperty($mock, 'targetEntityType');
    $property->setAccessible(TRUE);
    // Set the property to a known state.
    $property->setValue($mock, $bad_target);

    // Set the target type.
    $mock->setTargetType($target);

    // Test the outcome.
    $this->assertNotEquals($bad_target, $property->getValue($mock));
    $this->assertEquals($target, $property->getValue($mock));
  }

  /**
   * @covers ::getTargetType
   */
  public function testGetTargetType() {
    // Generate mock.
    $mock = $this->getMock(
      'Drupal\Core\Entity\EntityDisplayModeBase',
      NULL,
      array(array('something' => 'nothing'), 'test_type')
    );

    // A test value.
    $target = 'test_target_type';

    // Gain access to the protected property.
    $property = new \ReflectionProperty($mock, 'targetEntityType');
    $property->setAccessible(TRUE);
    // Set the property to a known state.
    $property->setValue($mock, $target);

    // Get the target type.
    $value = $mock->getTargetType($target);

    // Test the outcome.
    $this->assertEquals($value, $property->getValue($mock));
  }

}
