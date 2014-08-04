<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Config\Entity\ConfigEntityStorageTest.
 */

namespace Drupal\Tests\Core\Config\Entity {

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Language\Language;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @coversDefaultClass \Drupal\Core\Config\Entity\ConfigEntityStorage
 * @group Config
 */
class ConfigEntityStorageTest extends UnitTestCase {

  /**
   * The entity type.
   *
   * @var \Drupal\Core\Entity\EntityTypeInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $entityType;

  /**
   * The type ID of the entity under test.
   *
   * @var string
   */
  protected $entityTypeId;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $moduleHandler;

  /**
   * The UUID service.
   *
   * @var \Drupal\Component\Uuid\UuidInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $uuidService;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $languageManager;

  /**
   * The config storage.
   *
   * @var \Drupal\Core\Config\Entity\ConfigEntityStorage
   */
  protected $entityStorage;

  /**
   * The config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $configFactory;

  /**
   * The entity query.
   *
   * @var \Drupal\Core\Entity\Query\QueryInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $entityQuery;

  /**
   * The entity manager used for testing.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $entityManager;

  /**
   * The mocked cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $cacheBackend;

  /**
   * The mocked typed config manager.
   *
   * @var \Drupal\Core\Config\TypedConfigManagerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $typedConfigManager;

  /**
   * {@inheritdoc}
   *
   * @covers ::__construct()
   */
  protected function setUp() {
    parent::setUp();

    $this->entityType = $this->getMock('Drupal\Core\Entity\EntityTypeInterface');
    $this->entityTypeId = 'test_entity_type';
    $this->entityType->expects($this->any())
      ->method('getKey')
      ->will($this->returnValueMap(array(
        array('id', 'id'),
        array('uuid', 'uuid'),
      )));
    $this->entityType->expects($this->any())
      ->method('id')
      ->will($this->returnValue($this->entityTypeId));
    $this->entityType->expects($this->any())
      ->method('getConfigPrefix')
      ->will($this->returnValue('the_config_prefix'));
    $this->entityType->expects($this->any())
      ->method('getClass')
      ->will($this->returnValue(get_class($this->getMockEntity())));


    $this->moduleHandler = $this->getMock('Drupal\Core\Extension\ModuleHandlerInterface');

    $this->uuidService = $this->getMock('Drupal\Component\Uuid\UuidInterface');

    $this->languageManager = $this->getMock('Drupal\Core\Language\LanguageManagerInterface');
    $this->languageManager->expects($this->any())
      ->method('getDefaultLanguage')
      ->will($this->returnValue(new Language(array('langcode' => 'en'))));

    $this->configFactory = $this->getMock('Drupal\Core\Config\ConfigFactoryInterface');

    $this->entityQuery = $this->getMock('Drupal\Core\Entity\Query\QueryInterface');

    $this->entityStorage = $this->getMockBuilder('Drupal\Core\Config\Entity\ConfigEntityStorage')
      ->setConstructorArgs(array($this->entityType, $this->configFactory, $this->uuidService, $this->languageManager))
      ->setMethods(array('getQuery'))
      ->getMock();
    $this->entityStorage->expects($this->any())
      ->method('getQuery')
      ->will($this->returnValue($this->entityQuery));
    $this->entityStorage->setModuleHandler($this->moduleHandler);

    $this->entityManager = $this->getMock('\Drupal\Core\Entity\EntityManagerInterface');
    $this->entityManager->expects($this->any())
      ->method('getDefinition')
      ->with('test_entity_type')
      ->will($this->returnValue($this->entityType));

    $this->cacheBackend = $this->getMock('Drupal\Core\Cache\CacheBackendInterface');

    $this->typedConfigManager = $this->getMock('Drupal\Core\Config\TypedConfigManagerInterface');
    $this->typedConfigManager->expects($this->any())
      ->method('getDefinition')
      ->will($this->returnValue(array('mapping' => array('id' => '', 'uuid' => '', 'dependencies' => ''))));
    $container = new ContainerBuilder();
    $container->set('entity.manager', $this->entityManager);
    $container->set('config.typed', $this->typedConfigManager);
    $container->set('cache.test', $this->cacheBackend);
    $container->setParameter('cache_bins', array('cache.test' => 'test'));
    \Drupal::setContainer($container);

  }

  /**
   * @covers ::create()
   * @covers ::doCreate()
   */
  public function testCreateWithPredefinedUuid() {
    $this->cacheBackend->expects($this->never())
      ->method('invalidateTags');

    $this->moduleHandler->expects($this->at(0))
      ->method('invokeAll')
      ->with('test_entity_type_create');
    $this->moduleHandler->expects($this->at(1))
      ->method('invokeAll')
      ->with('entity_create');
    $this->uuidService->expects($this->never())
      ->method('generate');

    $entity = $this->entityStorage->create(array('id' => 'foo', 'uuid' => 'baz'));
    $this->assertInstanceOf('Drupal\Core\Entity\EntityInterface', $entity);
    $this->assertSame('foo', $entity->id());
    $this->assertSame('baz', $entity->uuid());
  }

  /**
   * @covers ::create()
   * @covers ::doCreate()
   *
   * @return \Drupal\Core\Entity\EntityInterface
   */
  public function testCreate() {
    $this->cacheBackend->expects($this->never())
      ->method('invalidateTags');

    $this->moduleHandler->expects($this->at(0))
      ->method('invokeAll')
      ->with('test_entity_type_create');
    $this->moduleHandler->expects($this->at(1))
      ->method('invokeAll')
      ->with('entity_create');
    $this->uuidService->expects($this->once())
      ->method('generate')
      ->will($this->returnValue('bar'));

    $entity = $this->entityStorage->create(array('id' => 'foo'));
    $this->assertInstanceOf('Drupal\Core\Entity\EntityInterface', $entity);
    $this->assertSame('foo', $entity->id());
    $this->assertSame('bar', $entity->uuid());
    return $entity;
  }

  /**
   * @covers ::save()
   * @covers ::doSave()
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *
   * @depends testCreate
   */
  public function testSaveInsert(EntityInterface $entity) {
    $config_object = $this->getMockBuilder('Drupal\Core\Config\Config')
      ->disableOriginalConstructor()
      ->getMock();
    $config_object->expects($this->atLeastOnce())
      ->method('isNew')
      ->will($this->returnValue(TRUE));
    $config_object->expects($this->exactly(3))
      ->method('set');
    $config_object->expects($this->once())
      ->method('save');

    $this->cacheBackend->expects($this->once())
      ->method('invalidateTags')
      ->with(array(
        $this->entityTypeId . 's' => TRUE, // List cache tag.
      ));

    $this->configFactory->expects($this->exactly(2))
      ->method('get')
      ->with('the_config_prefix.foo')
      ->will($this->returnValue($config_object));

    $this->moduleHandler->expects($this->at(0))
      ->method('invokeAll')
      ->with('test_entity_type_presave');
    $this->moduleHandler->expects($this->at(1))
      ->method('invokeAll')
      ->with('entity_presave');
    $this->moduleHandler->expects($this->at(2))
      ->method('invokeAll')
      ->with('test_entity_type_insert');
    $this->moduleHandler->expects($this->at(3))
      ->method('invokeAll')
      ->with('entity_insert');

    $this->entityQuery->expects($this->once())
      ->method('condition')
      ->with('uuid', 'bar')
      ->will($this->returnSelf());
    $this->entityQuery->expects($this->once())
      ->method('execute')
      ->will($this->returnValue(array()));

    $return = $this->entityStorage->save($entity);
    $this->assertSame(SAVED_NEW, $return);
    return $entity;
  }

  /**
   * @covers ::save()
   * @covers ::doSave()
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *
   * @depends testSaveInsert
   */
  public function testSaveUpdate(EntityInterface $entity) {
    $config_object = $this->getMockBuilder('Drupal\Core\Config\Config')
      ->disableOriginalConstructor()
      ->getMock();
    $config_object->expects($this->atLeastOnce())
      ->method('isNew')
      ->will($this->returnValue(FALSE));
    $config_object->expects($this->exactly(3))
      ->method('set');
    $config_object->expects($this->once())
      ->method('save');

    $this->cacheBackend->expects($this->once())
      ->method('invalidateTags')
      ->with(array(
        $this->entityTypeId . 's' => TRUE, // List cache tag.
        $this->entityTypeId => array('foo'), // Own cache tag.
      ));

    $this->configFactory->expects($this->exactly(2))
      ->method('loadMultiple')
      ->with(array('the_config_prefix.foo'))
      ->will($this->returnValue(array()));
    $this->configFactory->expects($this->exactly(2))
      ->method('get')
      ->with('the_config_prefix.foo')
      ->will($this->returnValue($config_object));

    $this->moduleHandler->expects($this->at(0))
      ->method('invokeAll')
      ->with('test_entity_type_presave');
    $this->moduleHandler->expects($this->at(1))
      ->method('invokeAll')
      ->with('entity_presave');
    $this->moduleHandler->expects($this->at(2))
      ->method('invokeAll')
      ->with('test_entity_type_update');
    $this->moduleHandler->expects($this->at(3))
      ->method('invokeAll')
      ->with('entity_update');

    $this->entityQuery->expects($this->once())
      ->method('condition')
      ->with('uuid', 'bar')
      ->will($this->returnSelf());
    $this->entityQuery->expects($this->once())
      ->method('execute')
      ->will($this->returnValue(array($entity->id())));

    $return = $this->entityStorage->save($entity);
    $this->assertSame(SAVED_UPDATED, $return);
    return $entity;
  }

  /**
   * @covers ::save()
   * @covers ::doSave()
   *
   * @depends testSaveInsert
   */
  public function testSaveRename(ConfigEntityInterface $entity) {
    $config_object = $this->getMockBuilder('Drupal\Core\Config\Config')
      ->disableOriginalConstructor()
      ->getMock();
    $config_object->expects($this->atLeastOnce())
      ->method('isNew')
      ->will($this->returnValue(FALSE));
    $config_object->expects($this->exactly(3))
      ->method('set');
    $config_object->expects($this->once())
      ->method('save');

    $this->cacheBackend->expects($this->once())
      ->method('invalidateTags')
      ->with(array(
        $this->entityTypeId . 's' => TRUE, // List cache tag.
        $this->entityTypeId => array('bar'), // Own cache tag.
      ));

    $this->configFactory->expects($this->once())
      ->method('rename')
      ->will($this->returnValue($config_object));
    $this->configFactory->expects($this->exactly(2))
      ->method('loadMultiple')
      ->with(array('the_config_prefix.foo'))
      ->will($this->returnValue(array()));
    $this->configFactory->expects($this->once())
      ->method('get')
      ->with('the_config_prefix.foo')
      ->will($this->returnValue($config_object));

    // Performing a rename does not change the original ID until saving.
    $this->assertSame('foo', $entity->getOriginalId());
    $entity->set('id', 'bar');
    $this->assertSame('foo', $entity->getOriginalId());

    $this->entityQuery->expects($this->once())
      ->method('condition')
      ->with('uuid', 'bar')
      ->will($this->returnSelf());
    $this->entityQuery->expects($this->once())
      ->method('execute')
      ->will($this->returnValue(array($entity->id())));

    $return = $this->entityStorage->save($entity);
    $this->assertSame(SAVED_UPDATED, $return);
    $this->assertSame('bar', $entity->getOriginalId());
  }

  /**
   * @covers ::save()
   *
   * @expectedException \Drupal\Core\Entity\EntityMalformedException
   * @expectedExceptionMessage The entity does not have an ID.
   */
  public function testSaveInvalid() {
    $this->cacheBackend->expects($this->never())
      ->method('invalidateTags');

    $entity = $this->getMockEntity();
    $this->entityStorage->save($entity);
  }

  /**
   * @covers ::save()
   * @covers ::doSave()
   *
   * @expectedException \Drupal\Core\Entity\EntityStorageException
   */
  public function testSaveDuplicate() {
    $config_object = $this->getMockBuilder('Drupal\Core\Config\Config')
      ->disableOriginalConstructor()
      ->getMock();
    $config_object->expects($this->atLeastOnce())
      ->method('isNew')
      ->will($this->returnValue(FALSE));
    $config_object->expects($this->never())
      ->method('set');
    $config_object->expects($this->never())
      ->method('save');

    $this->cacheBackend->expects($this->never())
      ->method('invalidateTags');

    $this->configFactory->expects($this->once())
      ->method('get')
      ->with('the_config_prefix.foo')
      ->will($this->returnValue($config_object));

    $entity = $this->getMockEntity(array('id' => 'foo'));
    $entity->enforceIsNew();

    $this->entityStorage->save($entity);
  }

  /**
   * @covers ::save()
   * @covers ::doSave()
   *
   * @expectedException \Drupal\Core\Config\ConfigDuplicateUUIDException
   * @expectedExceptionMessage when this UUID is already used for
   */
  public function testSaveMismatch() {
    $config_object = $this->getMockBuilder('Drupal\Core\Config\Config')
      ->disableOriginalConstructor()
      ->getMock();
    $config_object->expects($this->atLeastOnce())
      ->method('isNew')
      ->will($this->returnValue(TRUE));
    $config_object->expects($this->never())
      ->method('save');

    $this->cacheBackend->expects($this->never())
      ->method('invalidateTags');

    $this->configFactory->expects($this->once())
      ->method('get')
      ->with('the_config_prefix.foo')
      ->will($this->returnValue($config_object));

    $this->entityQuery->expects($this->once())
      ->method('condition')
      ->will($this->returnSelf());
    $this->entityQuery->expects($this->once())
      ->method('execute')
      ->will($this->returnValue(array('baz')));

    $entity = $this->getMockEntity(array('id' => 'foo'));
    $this->entityStorage->save($entity);
  }

  /**
   * @covers ::save()
   * @covers ::doSave()
   */
  public function testSaveNoMismatch() {
    $config_object = $this->getMockBuilder('Drupal\Core\Config\Config')
      ->disableOriginalConstructor()
      ->getMock();
    $config_object->expects($this->atLeastOnce())
      ->method('isNew')
      ->will($this->returnValue(TRUE));
    $config_object->expects($this->once())
      ->method('save');

    $this->cacheBackend->expects($this->once())
      ->method('invalidateTags')
      ->with(array(
        $this->entityTypeId . 's' => TRUE, // List cache tag.
      ));

    $this->configFactory->expects($this->once())
      ->method('get')
      ->with('the_config_prefix.baz')
      ->will($this->returnValue($config_object));
    $this->configFactory->expects($this->once())
      ->method('rename')
      ->will($this->returnValue($config_object));

    $this->entityQuery->expects($this->once())
      ->method('condition')
      ->will($this->returnSelf());
    $this->entityQuery->expects($this->once())
      ->method('execute')
      ->will($this->returnValue(array('baz')));

    $entity = $this->getMockEntity(array('id' => 'foo'));
    $entity->setOriginalId('baz');
    $entity->enforceIsNew();
    $this->entityStorage->save($entity);
  }

  /**
   * @covers ::save()
   * @covers ::doSave()
   *
   * @expectedException \Drupal\Core\Config\ConfigDuplicateUUIDException
   * @expectedExceptionMessage when this entity already exists with UUID
   */
  public function testSaveChangedUuid() {
    $config_object = $this->getMockBuilder('Drupal\Core\Config\Config')
      ->disableOriginalConstructor()
      ->getMock();
    $config_object->expects($this->atLeastOnce())
      ->method('isNew')
      ->will($this->returnValue(FALSE));
    $config_object->expects($this->never())
      ->method('save');
    $config_object->expects($this->exactly(2))
      ->method('get')
      ->will($this->returnValueMap(array(
        array('', array('id' => 'foo')),
        array('id', 'foo'),
      )));

    $this->cacheBackend->expects($this->never())
      ->method('invalidateTags');

    $this->configFactory->expects($this->at(1))
      ->method('loadMultiple')
      ->with(array('the_config_prefix.foo'))
      ->will($this->returnValue(array()));
    $this->configFactory->expects($this->at(2))
      ->method('loadMultiple')
      ->with(array('the_config_prefix.foo'))
      ->will($this->returnValue(array($config_object)));
    $this->configFactory->expects($this->once())
      ->method('get')
      ->with('the_config_prefix.foo')
      ->will($this->returnValue($config_object));
    $this->configFactory->expects($this->never())
      ->method('rename')
      ->will($this->returnValue($config_object));

    $this->moduleHandler->expects($this->exactly(2))
      ->method('getImplementations')
      ->will($this->returnValue(array()));

    $this->entityQuery->expects($this->once())
      ->method('condition')
      ->will($this->returnSelf());
    $this->entityQuery->expects($this->once())
      ->method('execute')
      ->will($this->returnValue(array('foo')));

    $entity = $this->getMockEntity(array('id' => 'foo'));

    $entity->set('uuid', 'baz');
    $this->entityStorage->save($entity);
  }

  /**
   * @covers ::load()
   * @covers ::postLoad()
   * @covers ::mapFromStorageRecords()
   * @covers ::doLoadMultiple()
   */
  public function testLoad() {
    $config_object = $this->getMockBuilder('Drupal\Core\Config\Config')
      ->disableOriginalConstructor()
      ->getMock();
    $config_object->expects($this->exactly(2))
      ->method('get')
      ->will($this->returnValueMap(array(
        array('', array('id' => 'foo')),
        array('id', 'foo'),
      )));

    $this->configFactory->expects($this->once())
      ->method('loadMultiple')
      ->with(array('the_config_prefix.foo'))
      ->will($this->returnValue(array($config_object)));
    $this->moduleHandler->expects($this->exactly(2))
      ->method('getImplementations')
      ->will($this->returnValue(array()));

    $entity = $this->entityStorage->load('foo');
    $this->assertInstanceOf('Drupal\Core\Entity\EntityInterface', $entity);
    $this->assertSame('foo', $entity->id());
  }

  /**
   * @covers ::loadMultiple()
   * @covers ::postLoad()
   * @covers ::mapFromStorageRecords()
   * @covers ::doLoadMultiple()
   */
  public function testLoadMultipleAll() {
    $foo_config_object = $this->getMockBuilder('Drupal\Core\Config\Config')
      ->disableOriginalConstructor()
      ->getMock();
    $foo_config_object->expects($this->exactly(2))
      ->method('get')
      ->will($this->returnValueMap(array(
        array('', array('id' => 'foo')),
        array('id', 'foo'),
      )));
    $bar_config_object = $this->getMockBuilder('Drupal\Core\Config\Config')
      ->disableOriginalConstructor()
      ->getMock();
    $bar_config_object->expects($this->exactly(2))
      ->method('get')
      ->will($this->returnValueMap(array(
        array('', array('id' => 'bar')),
        array('id', 'bar'),
      )));

    $this->configFactory->expects($this->once())
      ->method('listAll')
      ->with('the_config_prefix.')
      ->will($this->returnValue(array('the_config_prefix.foo' , 'the_config_prefix.bar')));
    $this->configFactory->expects($this->once())
      ->method('loadMultiple')
      ->with(array('the_config_prefix.foo' , 'the_config_prefix.bar'))
      ->will($this->returnValue(array($foo_config_object, $bar_config_object)));
    $this->moduleHandler->expects($this->exactly(2))
      ->method('getImplementations')
      ->will($this->returnValue(array()));

    $entities = $this->entityStorage->loadMultiple();
    $expected['foo'] = 'foo';
    $expected['bar'] = 'bar';
    foreach ($entities as $id => $entity) {
      $this->assertInstanceOf('Drupal\Core\Entity\EntityInterface', $entity);
      $this->assertSame($id, $entity->id());
      $this->assertSame($expected[$id], $entity->id());
    }
  }

  /**
   * @covers ::loadMultiple()
   * @covers ::postLoad()
   * @covers ::mapFromStorageRecords()
   * @covers ::doLoadMultiple()
   */
  public function testLoadMultipleIds() {
    $config_object = $this->getMockBuilder('Drupal\Core\Config\Config')
      ->disableOriginalConstructor()
      ->getMock();
    $config_object->expects($this->exactly(2))
      ->method('get')
      ->will($this->returnValueMap(array(
        array('', array('id' => 'foo')),
        array('id', 'foo'),
      )));

    $this->configFactory->expects($this->once())
      ->method('loadMultiple')
      ->with(array('the_config_prefix.foo'))
      ->will($this->returnValue(array($config_object)));
    $this->moduleHandler->expects($this->exactly(2))
      ->method('getImplementations')
      ->will($this->returnValue(array()));

    $entities = $this->entityStorage->loadMultiple(array('foo'));
    foreach ($entities as $id => $entity) {
      $this->assertInstanceOf('Drupal\Core\Entity\EntityInterface', $entity);
      $this->assertSame($id, $entity->id());
    }
  }

  /**
   * @covers ::loadRevision()
   */
  public function testLoadRevision() {
    $this->assertSame(FALSE, $this->entityStorage->loadRevision(1));
  }

  /**
   * @covers ::deleteRevision()
   */
  public function testDeleteRevision() {
    $this->cacheBackend->expects($this->never())
      ->method('invalidateTags');

    $this->assertSame(NULL, $this->entityStorage->deleteRevision(1));
  }

  /**
   * @covers ::delete()
   * @covers ::doDelete()
   */
  public function testDelete() {
    $entities = array();
    $configs = array();
    $config_map = array();
    foreach (array('foo', 'bar') as $id) {
      $entity = $this->getMockEntity(array('id' => $id));
      $entities[] = $entity;
      $config_object = $this->getMockBuilder('Drupal\Core\Config\Config')
        ->disableOriginalConstructor()
        ->getMock();
      $config_object->expects($this->once())
        ->method('delete');
      $configs[] = $config_object;
      $config_map[] = array("the_config_prefix.$id", $config_object);
    }

    $this->cacheBackend->expects($this->once())
      ->method('invalidateTags')
      ->with(array(
        $this->entityTypeId . 's' => TRUE, // List cache tag.
        $this->entityTypeId => array('foo', 'bar'), // Own cache tag.
      ));

    $this->configFactory->expects($this->exactly(2))
      ->method('get')
      ->will($this->returnValueMap($config_map));

    $this->moduleHandler->expects($this->at(0))
      ->method('invokeAll')
      ->with('test_entity_type_predelete');
    $this->moduleHandler->expects($this->at(1))
      ->method('invokeAll')
      ->with('entity_predelete');
    $this->moduleHandler->expects($this->at(2))
      ->method('invokeAll')
      ->with('test_entity_type_predelete');
    $this->moduleHandler->expects($this->at(3))
      ->method('invokeAll')
      ->with('entity_predelete');
    $this->moduleHandler->expects($this->at(4))
      ->method('invokeAll')
      ->with('test_entity_type_delete');
    $this->moduleHandler->expects($this->at(5))
      ->method('invokeAll')
      ->with('entity_delete');
    $this->moduleHandler->expects($this->at(6))
      ->method('invokeAll')
      ->with('test_entity_type_delete');
    $this->moduleHandler->expects($this->at(7))
      ->method('invokeAll')
      ->with('entity_delete');

    $this->entityStorage->delete($entities);
  }

  /**
   * @covers ::delete()
   * @covers ::doDelete()
   */
  public function testDeleteNothing() {
    $this->moduleHandler->expects($this->never())
      ->method($this->anything());
    $this->configFactory->expects($this->never())
      ->method('get');

    $this->cacheBackend->expects($this->never())
      ->method('invalidateTags');

    $this->entityStorage->delete(array());
  }

  /**
   * Creates an entity with specific methods mocked.
   *
   * @param array $values
   *   (optional) Values to pass to the constructor.
   * @param array $methods
   *   (optional) The methods to mock.
   *
   * @return \Drupal\Core\Entity\EntityInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  public function getMockEntity(array $values = array(), $methods = array()) {
    $methods[] = 'onSaveOrDelete';
    $methods[] = 'onUpdateBundleEntity';
    return $this->getMockForAbstractClass('Drupal\Core\Config\Entity\ConfigEntityBase', array($values, 'test_entity_type'), '', TRUE, TRUE, TRUE, $methods);
  }

}

}
namespace {
  if (!defined('SAVED_NEW')) {
    define('SAVED_NEW', 1);
  }
  if (!defined('SAVED_UPDATED')) {
    define('SAVED_UPDATED', 2);
  }
}
