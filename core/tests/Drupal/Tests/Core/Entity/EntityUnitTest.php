<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Entity\EntityUnitTest.
 */

namespace Drupal\Tests\Core\Entity;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\Entity;
use Drupal\Core\Entity\Exception\NoCorrespondingEntityClassException;
use Drupal\Core\Language\Language;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\entity_test\Entity\EntityTestMul;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\Core\Entity\Entity
 * @group Entity
 * @group Access
 */
class EntityUnitTest extends UnitTestCase {

  /**
   * The entity under test.
   *
   * @var \Drupal\Core\Entity\Entity|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $entity;

  /**
   * The entity type used for testing.
   *
   * @var \Drupal\Core\Entity\EntityTypeInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $entityType;

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
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $languageManager;

  /**
   * The mocked cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $cacheBackend;

  /**
   * The entity values.
   *
   * @var array
   */
  protected $values;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    $this->values = array(
      'id' => 1,
      'langcode' => 'en',
      'uuid' => '3bb9ee60-bea5-4622-b89b-a63319d10b3a',
    );
    $this->entityTypeId = $this->randomMachineName();

    $this->entityType = $this->getMock('\Drupal\Core\Entity\EntityTypeInterface');
    $this->entityType->expects($this->any())
      ->method('getListCacheTags')
      ->willReturn(array($this->entityTypeId . '_list'));

    $this->entityManager = $this->getMock('\Drupal\Core\Entity\EntityManagerInterface');
    $this->entityManager->expects($this->any())
      ->method('getDefinition')
      ->with($this->entityTypeId)
      ->will($this->returnValue($this->entityType));

    $this->uuid = $this->getMock('\Drupal\Component\Uuid\UuidInterface');

    $this->languageManager = $this->getMock('\Drupal\Core\Language\LanguageManagerInterface');
    $this->languageManager->expects($this->any())
      ->method('getLanguage')
      ->with('en')
      ->will($this->returnValue(new Language(array('id' => 'en'))));

    $this->cacheBackend = $this->getMock('Drupal\Core\Cache\CacheBackendInterface');

    $container = new ContainerBuilder();
    $container->set('entity.manager', $this->entityManager);
    $container->set('uuid', $this->uuid);
    $container->set('language_manager', $this->languageManager);
    $container->set('cache.test', $this->cacheBackend);
    $container->setParameter('cache_bins', array('cache.test' => 'test'));
    \Drupal::setContainer($container);

    $this->entity = $this->getMockForAbstractClass('\Drupal\Core\Entity\Entity', array($this->values, $this->entityTypeId));
  }

  /**
   * @covers ::id
   */
  public function testId() {
    $this->assertSame($this->values['id'], $this->entity->id());
  }

  /**
   * @covers ::uuid
   */
  public function testUuid() {
    $this->assertSame($this->values['uuid'], $this->entity->uuid());
  }

  /**
   * @covers ::isNew
   * @covers ::enforceIsNew
   */
  public function testIsNew() {
    // We provided an ID, so the entity is not new.
    $this->assertFalse($this->entity->isNew());
    // Force it to be new.
    $this->assertSame($this->entity, $this->entity->enforceIsNew());
    $this->assertTrue($this->entity->isNew());
  }

  /**
   * @covers ::getEntityType
   */
  public function testGetEntityType() {
    $this->assertSame($this->entityType, $this->entity->getEntityType());
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
    $callback_label = $this->randomMachineName();
    $property_label = $this->randomMachineName();
    $callback_container = $this->getMock(get_class());
    $callback_container->expects($this->once())
      ->method(__FUNCTION__)
      ->will($this->returnValue($callback_label));
    $this->entityType->expects($this->at(0))
      ->method('getLabelCallback')
      ->will($this->returnValue(array($callback_container, __FUNCTION__)));
    $this->entityType->expects($this->at(1))
      ->method('getLabelCallback')
      ->will($this->returnValue(NULL));
    $this->entityType->expects($this->at(2))
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
    $access = $this->getMock('\Drupal\Core\Entity\EntityAccessControlHandlerInterface');
    $operation = $this->randomMachineName();
    $access->expects($this->at(0))
      ->method('access')
      ->with($this->entity, $operation)
      ->will($this->returnValue(AccessResult::allowed()));
    $access->expects($this->at(1))
      ->method('createAccess')
      ->will($this->returnValue(AccessResult::allowed()));
    $this->entityManager->expects($this->exactly(2))
      ->method('getAccessControlHandler')
      ->will($this->returnValue($access));
    $this->assertEquals(AccessResult::allowed(), $this->entity->access($operation));
    $this->assertEquals(AccessResult::allowed(), $this->entity->access('create'));
  }

  /**
   * @covers ::language
   */
  public function testLanguage() {
    $this->assertSame('en', $this->entity->language()->id);
  }

  /**
   * Setup for the tests of the ::load() method.
   */
  function setupTestLoad() {
    // Base our mocked entity on a real entity class so we can test if calling
    // Entity::load() on the base class will bubble up to an actual entity.
    $this->entityTypeId = 'entity_test_mul';
    $methods = get_class_methods('Drupal\entity_test\Entity\EntityTestMul');
    unset($methods[array_search('load', $methods)]);
    unset($methods[array_search('loadMultiple', $methods)]);
    unset($methods[array_search('create', $methods)]);
    $this->entity = $this->getMockBuilder('Drupal\entity_test\Entity\EntityTestMul')
      ->disableOriginalConstructor()
      ->setMethods($methods)
      ->getMock();

  }

  /**
   * @covers ::load
   *
   * Tests Entity::load() when called statically on a subclass of Entity.
   */
  public function testLoad() {
    $this->setupTestLoad();

    $class_name = get_class($this->entity);

    $this->entityManager->expects($this->once())
      ->method('getEntityTypeFromClass')
      ->with($class_name)
      ->willReturn($this->entityTypeId);

    $storage = $this->getMock('\Drupal\Core\Entity\EntityStorageInterface');
    $storage->expects($this->once())
      ->method('load')
      ->with(1)
      ->will($this->returnValue($this->entity));
    $this->entityManager->expects($this->once())
      ->method('getStorage')
      ->with($this->entityTypeId)
      ->will($this->returnValue($storage));

    // Call Entity::load statically and check that it returns the mock entity.
    $this->assertSame($this->entity, $class_name::load(1));
  }

  /**
   * @covers ::loadMultiple
   *
   * Tests Entity::loadMultiple() when called statically on a subclass of
   * Entity.
   */
  public function testLoadMultiple() {
    $this->setupTestLoad();

    $class_name = get_class($this->entity);

    $this->entityManager->expects($this->once())
      ->method('getEntityTypeFromClass')
      ->with($class_name)
      ->willReturn($this->entityTypeId);

    $storage = $this->getMock('\Drupal\Core\Entity\EntityStorageInterface');
    $storage->expects($this->once())
      ->method('loadMultiple')
      ->with(array(1))
      ->will($this->returnValue(array(1 => $this->entity)));
    $this->entityManager->expects($this->once())
      ->method('getStorage')
      ->with($this->entityTypeId)
      ->will($this->returnValue($storage));

    // Call Entity::loadMultiple statically and check that it returns the mock
    // entity.
    $this->assertSame(array(1 => $this->entity), $class_name::loadMultiple(array(1)));
  }

  /**
   * @covers ::create
   */
  public function testCreate() {
    $this->setupTestLoad();

    $class_name = get_class($this->entity);
    $this->entityManager->expects($this->once())
      ->method('getEntityTypeFromClass')
      ->with($class_name)
      ->willReturn($this->entityTypeId);

    $storage = $this->getMock('\Drupal\Core\Entity\EntityStorageInterface');
    $storage->expects($this->once())
      ->method('create')
      ->with(array())
      ->will($this->returnValue($this->entity));
    $this->entityManager->expects($this->once())
      ->method('getStorage')
      ->with($this->entityTypeId)
      ->will($this->returnValue($storage));

    // Call Entity::create() statically and check that it returns the mock
    // entity.
    $this->assertSame($this->entity, $class_name::create(array()));
  }

  /**
   * @covers ::save
   */
  public function testSave() {
    $storage = $this->getMock('\Drupal\Core\Entity\EntityStorageInterface');
    $storage->expects($this->once())
      ->method('save')
      ->with($this->entity);
    $this->entityManager->expects($this->once())
      ->method('getStorage')
      ->with($this->entityTypeId)
      ->will($this->returnValue($storage));
    $this->entity->save();
  }

  /**
   * @covers ::delete
   */
  public function testDelete() {
    $this->entity->id = $this->randomMachineName();
    $storage = $this->getMock('\Drupal\Core\Entity\EntityStorageInterface');
    // Testing the argument of the delete() method consumes too much memory.
    $storage->expects($this->once())
      ->method('delete');
    $this->entityManager->expects($this->once())
      ->method('getStorage')
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
    $storage = $this->getMock('\Drupal\Core\Entity\EntityStorageInterface');
    // Our mocked entity->preSave() returns NULL, so assert that.
    $this->assertNull($this->entity->preSave($storage));
  }

  /**
   * @covers ::postSave
   */
  public function testPostSave() {
    $this->cacheBackend->expects($this->at(0))
      ->method('invalidateTags')
      ->with(array(
        $this->entityTypeId . '_list', // List cache tag.
      ));
    $this->cacheBackend->expects($this->at(1))
      ->method('invalidateTags')
      ->with(array(
        $this->entityTypeId . ':' . $this->values['id'], // Own cache tag.
        $this->entityTypeId . '_list', // List cache tag.
      ));

    // This method is internal, so check for errors on calling it only.
    $storage = $this->getMock('\Drupal\Core\Entity\EntityStorageInterface');

    // A creation should trigger the invalidation of the "list" cache tag.
    $this->entity->postSave($storage, FALSE);
    // An update should trigger the invalidation of both the "list" and the
    // "own" cache tags.
    $this->entity->postSave($storage, TRUE);
  }

  /**
   * @covers ::preCreate
   */
  public function testPreCreate() {
    // This method is internal, so check for errors on calling it only.
    $storage = $this->getMock('\Drupal\Core\Entity\EntityStorageInterface');
    $values = array();
    // Our mocked entity->preCreate() returns NULL, so assert that.
    $this->assertNull($this->entity->preCreate($storage, $values));
  }

  /**
   * @covers ::postCreate
   */
  public function testPostCreate() {
    // This method is internal, so check for errors on calling it only.
    $storage = $this->getMock('\Drupal\Core\Entity\EntityStorageInterface');
    // Our mocked entity->postCreate() returns NULL, so assert that.
    $this->assertNull($this->entity->postCreate($storage));
  }

  /**
   * @covers ::preDelete
   */
  public function testPreDelete() {
    // This method is internal, so check for errors on calling it only.
    $storage = $this->getMock('\Drupal\Core\Entity\EntityStorageInterface');
    // Our mocked entity->preDelete() returns NULL, so assert that.
    $this->assertNull($this->entity->preDelete($storage, array($this->entity)));
  }

  /**
   * @covers ::postDelete
   */
  public function testPostDelete() {
    $this->cacheBackend->expects($this->once())
      ->method('invalidateTags')
      ->with(array(
        $this->entityTypeId . ':' . $this->values['id'],
        $this->entityTypeId . '_list',
      ));
    $storage = $this->getMock('\Drupal\Core\Entity\EntityStorageInterface');
    $storage->expects($this->once())
      ->method('getEntityType')
      ->willReturn($this->entityType);

    $entities = array($this->values['id'] => $this->entity);
    $this->entity->postDelete($storage, $entities);
  }

  /**
   * @covers ::postLoad
   */
  public function testPostLoad() {
    // This method is internal, so check for errors on calling it only.
    $storage = $this->getMock('\Drupal\Core\Entity\EntityStorageInterface');
    $entities = array($this->entity);
    // Our mocked entity->postLoad() returns NULL, so assert that.
    $this->assertNull($this->entity->postLoad($storage, $entities));
  }

  /**
   * @covers ::referencedEntities
   */
  public function testReferencedEntities() {
    $this->assertSame(array(), $this->entity->referencedEntities());
  }
}
