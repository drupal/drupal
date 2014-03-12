<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Entity\EntityUnitTest.
 */

namespace Drupal\Tests\Core\Entity;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\Core\Entity\Entity
 *
 * @group Drupal
 */
class EntityUnitTest extends UnitTestCase {

  /**
   * The entity under test.
   *
   * @var \Drupal\Core\Entity\Entity|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $entity;

  /**
   * The entity info used for testing..
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
  protected $entityTypeId;

  /**
   * The route provider used for testing.
   *
   * @var \Drupal\Core\Routing\RouteProvider|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $routeProvider;

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
      'name' => '\Drupal\Core\Entity\Entity unit test',
      'group' => 'Entity',
    );
  }

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    $values = array();
    $this->entityTypeId = $this->randomName();

    $this->entityInfo = $this->getMock('\Drupal\Core\Entity\EntityTypeInterface');

    $this->entityManager = $this->getMock('\Drupal\Core\Entity\EntityManagerInterface');
    $this->entityManager->expects($this->any())
      ->method('getDefinition')
      ->with($this->entityTypeId)
      ->will($this->returnValue($this->entityInfo));

    $this->uuid = $this->getMock('\Drupal\Component\Uuid\UuidInterface');

    $container = new ContainerBuilder();
    $container->set('entity.manager', $this->entityManager);
    $container->set('uuid', $this->uuid);
    \Drupal::setContainer($container);

    $this->entity = $this->getMockBuilder('\Drupal\Core\Entity\Entity')
      ->setConstructorArgs(array($values, $this->entityTypeId))
      ->setMethods(array('languageLoad'))
      ->getMock();
    $this->entity->expects($this->any())
      ->method('languageLoad')
      ->will($this->returnValue(NULL));
  }

  /**
   * @covers ::id
   */
  public function testId() {
    // @todo How to test this?
    $this->assertNull($this->entity->id());
  }

  /**
   * @covers ::uuid
   */
  public function testUuid() {
    // @todo How to test this?
    $this->assertNull($this->entity->uuid());
  }

  /**
   * @covers ::isNew
   */
  public function testIsNew() {
    // @todo How to test this?
    $this->assertInternalType('bool', $this->entity->isNew());
  }

  /**
   * @covers ::enforceIsNew
   */
  public function testEnforceIsNew() {
    $this->assertSame(spl_object_hash($this->entity), spl_object_hash($this->entity->enforceIsNew()));
  }

  /**
   * @covers ::getEntityType
   */
  public function testGetEntityType() {
    $this->assertInstanceOf('\Drupal\Core\Entity\EntityTypeInterface', $this->entity->getEntityType());
  }

  /**
   * @covers ::bundle
   */
  public function testBundle() {
    $this->assertSame($this->entityTypeId, $this->entity->bundle());
  }

  /**
   * @covers ::label
   */
  public function testLabel() {
    // Make a mock with one method that we use as the entity's uri_callback. We
    // check that it is called, and that the entity's label is the callback's
    // return value.
    $callback_label = $this->randomName();
    $property_label = $this->randomName();
    $callback_container = $this->getMock(get_class());
    $callback_container->expects($this->once())
      ->method(__FUNCTION__)
      ->will($this->returnValue($callback_label));
    $this->entityInfo->expects($this->at(0))
      ->method('getLabelCallback')
      ->will($this->returnValue(array($callback_container, __FUNCTION__)));
    $this->entityInfo->expects($this->at(1))
      ->method('getLabelCallback')
      ->will($this->returnValue(NULL));
    $this->entityInfo->expects($this->at(2))
      ->method('getKey')
      ->with('label')
      ->will($this->returnValue('label'));

    // Set a dummy property on the entity under test to test that the label can
    // be returned form a property if there is no callback.
    $this->entityManager->expects($this->at(1))
      ->method('getDefinition')
      ->with($this->entityTypeId)
      ->will($this->returnValue(array(
        'entity_keys' => array(
          'label' => 'label',
        ),
      )));
    $this->entity->label = $property_label;

    $this->assertSame($callback_label, $this->entity->label());
    $this->assertSame($property_label, $this->entity->label());
  }

  /**
   * @covers ::access
   */
  public function testAccess() {
    $access = $this->getMock('\Drupal\Core\Entity\EntityAccessControllerInterface');
    $operation = $this->randomName();
    $access->expects($this->at(0))
      ->method('access')
      ->with($this->entity, $operation)
      ->will($this->returnValue(TRUE));
    $access->expects($this->at(1))
      ->method('createAccess')
      ->will($this->returnValue(TRUE));
    $this->entityManager->expects($this->exactly(2))
      ->method('getAccessController')
      ->will($this->returnValue($access));
    $this->assertTrue($this->entity->access($operation));
    $this->assertTrue($this->entity->access('create'));
  }

  /**
   * @covers ::language
   */
  public function testLanguage() {
    $this->assertInstanceOf('\Drupal\Core\Language\Language', $this->entity->language());
  }

  /**
   * @covers ::save
   */
  public function testSave() {
    $storage = $this->getMock('\Drupal\Core\Entity\EntityStorageControllerInterface');
    $storage->expects($this->once())
      ->method('save')
      ->with($this->entity);
    $this->entityManager->expects($this->once())
      ->method('getStorageController')
      ->with($this->entityTypeId)
      ->will($this->returnValue($storage));
    $this->entity->save();
  }

  /**
   * @covers ::delete
   */
  public function testDelete() {
    $this->entity->id = $this->randomName();
    $storage = $this->getMock('\Drupal\Core\Entity\EntityStorageControllerInterface');
    // Testing the argument of the delete() method consumes too much memory.
    $storage->expects($this->once())
      ->method('delete');
    $this->entityManager->expects($this->once())
      ->method('getStorageController')
      ->with($this->entityTypeId)
      ->will($this->returnValue($storage));
    $this->entity->delete();
  }

  /**
   * @covers ::getEntityTypeId
   */
  public function testGetEntityTypeId() {
    $this->assertSame($this->entityTypeId, $this->entity->getEntityTypeId());
  }

  /**
   * @covers ::preSave
   */
  public function testPreSave() {
    // This method is internal, so check for errors on calling it only.
    $storage = $this->getMock('\Drupal\Core\Entity\EntityStorageControllerInterface');
    $this->entity->preSave($storage);
  }

  /**
   * @covers ::postSave
   */
  public function testPostSave() {
    // This method is internal, so check for errors on calling it only.
    $storage = $this->getMock('\Drupal\Core\Entity\EntityStorageControllerInterface');
    $this->entity->postSave($storage);
  }

  /**
   * @covers ::preCreate
   */
  public function testPreCreate() {
    // This method is internal, so check for errors on calling it only.
    $storage = $this->getMock('\Drupal\Core\Entity\EntityStorageControllerInterface');
    $values = array();
    $this->entity->preCreate($storage, $values);
  }

  /**
   * @covers ::postCreate
   */
  public function testPostCreate() {
    // This method is internal, so check for errors on calling it only.
    $storage = $this->getMock('\Drupal\Core\Entity\EntityStorageControllerInterface');
    $this->entity->postCreate($storage);
  }

  /**
   * @covers ::preDelete
   */
  public function testPreDelete() {
    // This method is internal, so check for errors on calling it only.
    $storage = $this->getMock('\Drupal\Core\Entity\EntityStorageControllerInterface');
    $this->entity->preDelete($storage, array($this->entity));
  }

  /**
   * @covers ::postDelete
   */
  public function testPostDelete() {
    $storage = $this->getMock('\Drupal\Core\Entity\EntityStorageControllerInterface');

    $entity = $this->getMockBuilder('\Drupal\Core\Entity\Entity')
      ->setMethods(array('onSaveOrDelete'))
      ->disableOriginalConstructor()
      ->getMock();
    $entity->expects($this->once())
      ->method('onSaveOrDelete');

    $this->entity->postDelete($storage, array($entity));
  }

  /**
   * @covers ::postLoad
   */
  public function testPostLoad() {
    // This method is internal, so check for errors on calling it only.
    $storage = $this->getMock('\Drupal\Core\Entity\EntityStorageControllerInterface');
    $entities = array($this->entity);
    $this->entity->postLoad($storage, $entities);
  }

  /**
   * @covers ::referencedEntities
   */
  public function testReferencedEntities() {
    $this->assertSame(array(), $this->entity->referencedEntities());
  }
}
