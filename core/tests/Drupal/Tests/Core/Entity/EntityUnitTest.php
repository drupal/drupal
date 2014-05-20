<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Entity\EntityUnitTest.
 */

namespace Drupal\Tests\Core\Entity;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\Entity;
use Drupal\Core\Language\Language;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\entity_test\Entity\EntityTestMul;
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
    $this->values = array(
      'id' => 1,
      'langcode' => 'en',
      'uuid' => '3bb9ee60-bea5-4622-b89b-a63319d10b3a',
    );
    $this->entityTypeId = $this->randomName();

    $this->entityType = $this->getMock('\Drupal\Core\Entity\EntityTypeInterface');

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
    $callback_label = $this->randomName();
    $property_label = $this->randomName();
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
    $this->assertSame('en', $this->entity->language()->id);
  }

  /**
   * Setup for the tests of the ::load() method.
   */
  function setupTestLoad() {
    // Use an entity type object which has the methods enabled which are being
    // called by the protected method Entity::getEntityTypeFromStaticClass().
    $methods = get_class_methods('Drupal\Core\Entity\EntityType');
    unset($methods[array_search('getClass', $methods)]);
    unset($methods[array_search('setClass', $methods)]);
    $this->entityType = $this->getMockBuilder('\Drupal\Core\Entity\EntityType')
      ->disableOriginalConstructor()
      ->setMethods($methods)
      ->getMock();

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
    $this->entityType->setClass(get_class($this->entity));

    $this->entityManager->expects($this->once())
      ->method('getDefinitions')
      ->will($this->returnValue(array($this->entityTypeId => $this->entityType)));

    $this->entityType->expects($this->any())
      ->method('id')
      ->will($this->returnValue($this->entityTypeId));
  }

  /**
   * @covers ::load
   * @covers ::getEntityTypeFromStaticClass
   *
   * Tests Entity::load() when called statically on the Entity base class.
   */
  public function testLoad() {
    $this->setupTestLoad();

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
    $this->assertSame($this->entity, Entity::load(1));
  }

  /**
   * @covers ::load
   * @covers ::getEntityTypeFromStaticClass
   *
   * Tests if an assertion is thrown if Entity::load() is called on a base class
   * which is subclassed multiple times.
   *
   * @expectedException \Drupal\Core\Entity\Exception\AmbiguousEntityClassException
   */
  public function testLoadWithAmbiguousSubclasses() {
    // Use an entity type object which has the methods enabled which are being
    // called by the protected method Entity::getEntityTypeFromStaticClass().
    $methods = get_class_methods('Drupal\Core\Entity\EntityType');
    unset($methods[array_search('getClass', $methods)]);
    unset($methods[array_search('setClass', $methods)]);

    $first_entity_type = $this->getMockBuilder('\Drupal\Core\Entity\EntityType')
      ->disableOriginalConstructor()
      ->setMethods($methods)
      ->getMock();
    $first_entity_type->setClass('Drupal\entity_test\Entity\EntityTestMul');

    $second_entity_type = $this->getMockBuilder('\Drupal\Core\Entity\EntityType')
      ->disableOriginalConstructor()
      ->setMethods($methods)
      ->setMockClassName($this->randomName())
      ->getMock();
    $second_entity_type->setClass('Drupal\entity_test\Entity\EntityTestMulRev');

    $this->entityManager->expects($this->once())
      ->method('getDefinitions')
      ->will($this->returnValue(array(
        'entity_test_mul' => $first_entity_type,
        'entity_test_mul_rev' => $second_entity_type,
      )));

    // Call Entity::load statically and check that it throws an exception.
    Entity::load(1);
  }

  /**
   * @covers ::load
   * @covers ::getEntityTypeFromStaticClass
   *
   * Tests if an assertion is thrown if Entity::load() is called on a class
   * that matches multiple times.
   *
   * @expectedException \Drupal\Core\Entity\Exception\AmbiguousEntityClassException
   */
  public function testLoadWithAmbiguousClasses() {
    // Use an entity type object which has the methods enabled which are being
    // called by the protected method Entity::getEntityTypeFromStaticClass().
    $methods = get_class_methods('Drupal\Core\Entity\EntityType');
    unset($methods[array_search('getClass', $methods)]);
    unset($methods[array_search('setClass', $methods)]);

    $first_entity_type = $this->getMockBuilder('\Drupal\Core\Entity\EntityType')
      ->disableOriginalConstructor()
      ->setMethods($methods)
      ->getMock();
    $first_entity_type->setClass('Drupal\entity_test\Entity\EntityTest');

    $second_entity_type = $this->getMockBuilder('\Drupal\Core\Entity\EntityType')
      ->disableOriginalConstructor()
      ->setMethods($methods)
      ->setMockClassName($this->randomName())
      ->getMock();
    $second_entity_type->setClass('Drupal\entity_test\Entity\EntityTest');

    $this->entityManager->expects($this->once())
      ->method('getDefinitions')
      ->will($this->returnValue(array(
        'entity_test_mul' => $first_entity_type,
        'entity_test_mul_rev' => $second_entity_type,
      )));

    // Call EntityTest::load() statically and check that it throws an exception.
    EntityTest::load(1);
  }

  /**
   * @covers ::load
   * @covers ::getEntityTypeFromStaticClass
   *
   * Tests if an assertion is thrown if Entity::load() is called and there are
   * no subclasses defined that can return entities.
   *
   * @expectedException \Drupal\Core\Entity\Exception\NoCorrespondingEntityClassException
   */
  public function testLoadWithNoCorrespondingSubclasses() {
    $this->entityManager->expects($this->once())
      ->method('getDefinitions')
      ->will($this->returnValue(array()));

    // Call Entity::load statically and check that it throws an exception.
    Entity::load(1);
  }

  /**
   * @covers ::load
   * @covers ::getEntityTypeFromStaticClass
   *
   * Tests Entity::load() when called statically on a subclass of Entity.
   */
  public function testLoadSubClass() {
    $this->setupTestLoad();

    $storage = $this->getMock('\Drupal\Core\Entity\EntityStorageInterface');
    $storage->expects($this->once())
      ->method('load')
      ->with(1)
      ->will($this->returnValue($this->entity));
    $this->entityManager->expects($this->once())
      ->method('getStorage')
      ->with($this->entityTypeId)
      ->will($this->returnValue($storage));

    // Call Entity::load statically on the subclass and check that it returns
    // the mock entity.
    $class = get_class($this->entity);
    $this->assertSame($this->entity, $class::load(1));
  }

  /**
   * @covers ::loadMultiple
   * @covers ::getEntityTypeFromStaticClass
   *
   * Tests Entity::loadMultiple() when called statically on the Entity base
   * class.
   */
  public function testLoadMultiple() {
    $this->setupTestLoad();

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
    $this->assertSame(array(1 => $this->entity), Entity::loadMultiple(array(1)));
  }

  /**
   * @covers ::loadMultiple
   * @covers ::getEntityTypeFromStaticClass
   *
   * Tests Entity::loadMultiple() when called statically on a subclass of
   * Entity.
   */
  public function testLoadMultipleSubClass() {
    $this->setupTestLoad();

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
    $class = get_class($this->entity);
    $this->assertSame(array(1 => $this->entity), $class::loadMultiple(array(1)));
  }

  /**
   * @covers ::create
   * @covers ::getEntityTypeFromStaticClass
   */
  public function testCreate() {
    $this->setupTestLoad();

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
    $class = get_class($this->entity);
    $this->assertSame($this->entity, $class::create(array()));
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
    $this->entity->id = $this->randomName();
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
    $this->entity->preSave($storage);
  }

  /**
   * @covers ::postSave
   */
  public function testPostSave() {
    $this->cacheBackend->expects($this->at(0))
      ->method('invalidateTags')
      ->with(array(
        $this->entityTypeId . 's' => TRUE, // List cache tag.
      ));
    $this->cacheBackend->expects($this->at(1))
      ->method('invalidateTags')
      ->with(array(
        $this->entityTypeId . 's' => TRUE, // List cache tag.
        $this->entityTypeId => array($this->values['id']), // Own cache tag.
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
    $this->entity->preCreate($storage, $values);
  }

  /**
   * @covers ::postCreate
   */
  public function testPostCreate() {
    // This method is internal, so check for errors on calling it only.
    $storage = $this->getMock('\Drupal\Core\Entity\EntityStorageInterface');
    $this->entity->postCreate($storage);
  }

  /**
   * @covers ::preDelete
   */
  public function testPreDelete() {
    // This method is internal, so check for errors on calling it only.
    $storage = $this->getMock('\Drupal\Core\Entity\EntityStorageInterface');
    $this->entity->preDelete($storage, array($this->entity));
  }

  /**
   * @covers ::postDelete
   */
  public function testPostDelete() {
    $this->cacheBackend->expects($this->once())
      ->method('invalidateTags')
      ->with(array(
        $this->entityTypeId => array($this->values['id']),
        $this->entityTypeId . 's' => TRUE,
      ));
    $storage = $this->getMock('\Drupal\Core\Entity\EntityStorageInterface');

    $entity = $this->getMockBuilder('\Drupal\Core\Entity\Entity')
      ->setConstructorArgs(array($this->values, $this->entityTypeId))
      ->setMethods(array('onSaveOrDelete'))
      ->getMock();
    $entity->expects($this->once())
      ->method('onSaveOrDelete');

    $entities = array($this->values['id'] => $entity);
    $this->entity->postDelete($storage, $entities);
  }

  /**
   * @covers ::postLoad
   */
  public function testPostLoad() {
    // This method is internal, so check for errors on calling it only.
    $storage = $this->getMock('\Drupal\Core\Entity\EntityStorageInterface');
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
