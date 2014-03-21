<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Config\Entity\EntityDisplayModeBaseUnitTest.
 */

namespace Drupal\Tests\Core\Config\Entity;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\entity\EntityDisplayModeBase
 *
 * @group Drupal
 * @group Config
 */
class EntityDisplayModeBaseUnitTest extends UnitTestCase {

  /**
   * The entity under test.
   *
   * @var \Drupal\entity\EntityDisplayModeBase|\PHPUnit_Framework_MockObject_MockObject
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
  public static function getInfo() {
    return array(
      'description' => '',
      'name' => '\Drupal\entity\EntityDisplayModeBase unit test',
      'group' => 'Entity',
    );
  }

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    $this->entityType = $this->randomName();

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
    $target_entity_type_id = $this->randomName(16);

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

    $this->entity = $this->getMockBuilder('\Drupal\entity\EntityDisplayModeBase')
      ->setConstructorArgs(array($values, $this->entityType))
      ->setMethods(array('getFilterFormat'))
      ->getMock();

    $dependencies = $this->entity->calculateDependencies();
    $this->assertContains('test_module', $dependencies['module']);

  }

}
