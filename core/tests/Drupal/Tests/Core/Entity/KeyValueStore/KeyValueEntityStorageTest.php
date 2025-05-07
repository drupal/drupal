<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Entity\KeyValueStore;

use Drupal\Component\Datetime\Time;
use Drupal\Core\Cache\MemoryCache\MemoryCache;
use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityBase;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityMalformedException;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\KeyValueStore\KeyValueEntityStorage;
use Drupal\Core\Language\Language;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @coversDefaultClass \Drupal\Core\Entity\KeyValueStore\KeyValueEntityStorage
 * @group Entity
 */
class KeyValueEntityStorageTest extends UnitTestCase {

  /**
   * The entity type.
   *
   * @var \Drupal\Core\Entity\EntityTypeInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $entityType;

  /**
   * The key value store.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueStoreInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $keyValueStore;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $moduleHandler;

  /**
   * The UUID service.
   *
   * @var \Drupal\Component\Uuid\UuidInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $uuidService;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $languageManager;

  /**
   * The entity storage.
   *
   * @var \Drupal\Core\Entity\KeyValueStore\KeyValueEntityStorage
   */
  protected $entityStorage;

  /**
   * The mocked entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $entityTypeManager;

  /**
   * The mocked entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $entityFieldManager;

  /**
   * The mocked cache tags invalidator.
   *
   * @var \Drupal\Core\Cache\CacheTagsInvalidatorInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $cacheTagsInvalidator;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->entityType = $this->createMock('Drupal\Core\Entity\EntityTypeInterface');
  }

  /**
   * Prepares the key value entity storage.
   *
   * @param string $uuid_key
   *   (optional) The entity key used for the UUID. Defaults to 'uuid'.
   *
   * @covers ::__construct
   */
  protected function setUpKeyValueEntityStorage($uuid_key = 'uuid'): void {
    $this->entityType->expects($this->atLeastOnce())
      ->method('getKey')
      ->willReturnMap([
        ['id', 'id'],
        ['uuid', $uuid_key],
        ['langcode', 'langcode'],
      ]);
    $this->entityType->expects($this->atLeastOnce())
      ->method('id')
      ->willReturn('test_entity_type');
    $this->entityType->expects($this->any())
      ->method('getListCacheTags')
      ->willReturn(['test_entity_type_list']);

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->entityTypeManager->expects($this->any())
      ->method('getDefinition')
      ->with('test_entity_type')
      ->willReturn($this->entityType);

    $this->entityFieldManager = $this->createMock(EntityFieldManagerInterface::class);

    $this->cacheTagsInvalidator = $this->createMock('Drupal\Core\Cache\CacheTagsInvalidatorInterface');

    $this->keyValueStore = $this->createMock('Drupal\Core\KeyValueStore\KeyValueStoreInterface');
    $this->moduleHandler = $this->createMock('Drupal\Core\Extension\ModuleHandlerInterface');
    $this->uuidService = $this->createMock('Drupal\Component\Uuid\UuidInterface');
    $this->languageManager = $this->createMock('Drupal\Core\Language\LanguageManagerInterface');
    $language = new Language(['langcode' => 'en']);
    $this->languageManager->expects($this->any())
      ->method('getDefaultLanguage')
      ->willReturn($language);
    $this->languageManager->expects($this->any())
      ->method('getCurrentLanguage')
      ->willReturn($language);

    $this->entityStorage = new KeyValueEntityStorage($this->entityType, $this->keyValueStore, $this->uuidService, $this->languageManager, new MemoryCache(new Time()));
    $this->entityStorage->setModuleHandler($this->moduleHandler);

    $container = new ContainerBuilder();
    $container->set('entity_field.manager', $this->entityFieldManager);
    $container->set('entity_type.manager', $this->entityTypeManager);
    $container->set('language_manager', $this->languageManager);
    $container->set('cache_tags.invalidator', $this->cacheTagsInvalidator);
    \Drupal::setContainer($container);
  }

  /**
   * @covers ::create
   * @covers ::doCreate
   */
  public function testCreateWithPredefinedUuid(): void {
    $this->entityType->expects($this->once())
      ->method('getClass')
      ->willReturn(get_class($this->getMockEntity()));
    $this->setUpKeyValueEntityStorage();

    $hooks = ['test_entity_type_create', 'entity_create'];
    $this->moduleHandler->expects($this->exactly(count($hooks)))
      ->method('invokeAll')
      ->with($this->callback(function (string $hook) use (&$hooks): bool {
        return array_shift($hooks) === $hook;
      }));
    $this->uuidService->expects($this->never())
      ->method('generate');

    $entity = $this->entityStorage->create(['id' => 'foo', 'uuid' => 'baz']);
    $this->assertInstanceOf('Drupal\Core\Entity\EntityInterface', $entity);
    $this->assertSame('foo', $entity->id());
    $this->assertSame('baz', $entity->uuid());
  }

  /**
   * @covers ::create
   * @covers ::doCreate
   */
  public function testCreateWithoutUuidKey(): void {
    // Set up the entity storage to expect no UUID key.
    $this->entityType->expects($this->once())
      ->method('getClass')
      ->willReturn(get_class($this->getMockEntity()));
    $this->setUpKeyValueEntityStorage(NULL);

    $hooks = ['test_entity_type_create', 'entity_create'];
    $this->moduleHandler->expects($this->exactly(count($hooks)))
      ->method('invokeAll')
      ->with($this->callback(function (string $hook) use (&$hooks): bool {
        return array_shift($hooks) === $hook;
      }));
    $this->uuidService->expects($this->never())
      ->method('generate');

    $entity = $this->entityStorage->create(['id' => 'foo', 'uuid' => 'baz']);
    $this->assertInstanceOf('Drupal\Core\Entity\EntityInterface', $entity);
    $this->assertSame('foo', $entity->id());
    $this->assertSame('baz', $entity->uuid());
  }

  /**
   * @covers ::create
   * @covers ::doCreate
   */
  public function testCreate(): void {
    $entity = $this->getMockEntity(EntityBaseTest::class, [], ['toArray']);
    $this->entityType->expects($this->once())
      ->method('getClass')
      ->willReturn(get_class($entity));
    $this->setUpKeyValueEntityStorage();

    $hooks = ['test_entity_type_create', 'entity_create'];
    $this->moduleHandler->expects($this->exactly(count($hooks)))
      ->method('invokeAll')
      ->with($this->callback(function (string $hook) use (&$hooks): bool {
        return array_shift($hooks) === $hook;
      }));
    $this->uuidService->expects($this->once())
      ->method('generate')
      ->willReturn('bar');

    $entity = $this->entityStorage->create(['id' => 'foo']);
    $this->assertInstanceOf('Drupal\Core\Entity\EntityInterface', $entity);
    $this->assertSame('foo', $entity->id());
    $this->assertSame('bar', $entity->uuid());
  }

  /**
   * @covers ::save
   * @covers ::doSave
   */
  public function testSaveInsert(): EntityInterface&MockObject {
    $this->setUpKeyValueEntityStorage();

    $entity = $this->getMockEntity(EntityBaseTest::class, [['id' => 'foo']], ['toArray']);
    $entity->enforceIsNew();

    $expected = ['id' => 'foo'];
    $this->keyValueStore->expects($this->exactly(2))
      ->method('has')
      ->with('foo')
      ->willReturn(FALSE);
    $this->keyValueStore->expects($this->never())
      ->method('getMultiple');
    $this->keyValueStore->expects($this->never())
      ->method('delete');

    $entity->expects($this->atLeastOnce())
      ->method('toArray')
      ->willReturn($expected);

    $hooks = ['test_entity_type_presave', 'entity_presave', 'test_entity_type_insert', 'entity_insert'];
    $this->moduleHandler->expects($this->exactly(count($hooks)))
      ->method('invokeAll')
      ->with($this->callback(function (string $hook) use (&$hooks): bool {
        return array_shift($hooks) === $hook;
      }));

    $this->keyValueStore->expects($this->once())
      ->method('set')
      ->with('foo', $expected);
    $return = $this->entityStorage->save($entity);
    $this->assertSame(SAVED_NEW, $return);
    return $entity;
  }

  /**
   * @covers ::save
   * @covers ::doSave
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   *
   * @depends testSaveInsert
   */
  public function testSaveUpdate(EntityInterface $entity): void {
    $this->entityType->expects($this->once())
      ->method('getClass')
      ->willReturn(get_class($entity));
    $this->setUpKeyValueEntityStorage();

    $expected = ['id' => 'foo'];
    $this->keyValueStore->expects($this->exactly(2))
      ->method('has')
      ->with('foo')
      ->willReturn(TRUE);
    $this->keyValueStore->expects($this->once())
      ->method('getMultiple')
      ->with(['foo'])
      ->willReturn([['id' => 'foo']]);
    $this->keyValueStore->expects($this->never())
      ->method('delete');

    $hooks = ['test_entity_type_presave', 'entity_presave', 'test_entity_type_update', 'entity_update'];
    $this->moduleHandler->expects($this->exactly(count($hooks)))
      ->method('invokeAll')
      ->with($this->callback(function (string $hook) use (&$hooks): bool {
        return array_shift($hooks) === $hook;
      }));

    $this->keyValueStore->expects($this->once())
      ->method('set')
      ->with('foo', $expected);
    $return = $this->entityStorage->save($entity);
    $this->assertSame(SAVED_UPDATED, $return);
  }

  /**
   * @covers ::save
   * @covers ::doSave
   */
  public function testSaveConfigEntity() {
    $this->setUpKeyValueEntityStorage();

    $entity = $this->getMockEntity('Drupal\Core\Config\Entity\ConfigEntityBase', [['id' => 'foo']], [
      'toArray',
      'preSave',
    ]);
    $entity->enforceIsNew();
    // When creating a new entity, the ID is tracked as the original ID.
    $this->assertSame('foo', $entity->getOriginalId());

    $expected = ['id' => 'foo'];
    $entity->expects($this->atLeastOnce())
      ->method('toArray')
      ->willReturn($expected);

    $this->keyValueStore->expects($this->exactly(2))
      ->method('has')
      ->with('foo')
      ->willReturn(FALSE);
    $this->keyValueStore->expects($this->once())
      ->method('set')
      ->with('foo', $expected);
    $this->keyValueStore->expects($this->never())
      ->method('delete');

    $return = $this->entityStorage->save($entity);
    $this->assertSame(SAVED_NEW, $return);
    return $entity;
  }

  /**
   * @covers ::save
   * @covers ::doSave
   *
   * @depends testSaveConfigEntity
   */
  public function testSaveRenameConfigEntity(ConfigEntityInterface $entity): void {
    $this->entityType->expects($this->once())
      ->method('getClass')
      ->willReturn(get_class($entity));
    $this->setUpKeyValueEntityStorage();

    $expected = ['id' => 'foo'];
    $entity->expects($this->once())
      ->method('toArray')
      ->willReturn($expected);
    $this->keyValueStore->expects($this->exactly(2))
      ->method('has')
      ->with('foo')
      ->willReturn(TRUE);
    $this->keyValueStore->expects($this->once())
      ->method('getMultiple')
      ->with(['foo'])
      ->willReturn([['id' => 'foo']]);
    $this->keyValueStore->expects($this->once())
      ->method('delete')
      ->with('foo');
    $this->keyValueStore->expects($this->once())
      ->method('set')
      ->with('bar', $expected);

    // Performing a rename does not change the original ID until saving.
    $this->assertSame('foo', $entity->getOriginalId());
    $entity->set('id', 'bar');
    $this->assertSame('foo', $entity->getOriginalId());

    $return = $this->entityStorage->save($entity);
    $this->assertSame(SAVED_UPDATED, $return);
    $this->assertSame('bar', $entity->getOriginalId());
  }

  /**
   * @covers ::save
   * @covers ::doSave
   */
  public function testSaveContentEntity(): void {
    $this->entityType->expects($this->any())
      ->method('getKeys')
      ->willReturn([
        'id' => 'id',
      ]);
    $this->setUpKeyValueEntityStorage();

    $expected = ['id' => 'foo'];
    $this->keyValueStore->expects($this->exactly(2))
      ->method('has')
      ->with('foo')
      ->willReturn(FALSE);
    $this->keyValueStore->expects($this->once())
      ->method('set')
      ->with('foo', $expected);
    $this->keyValueStore->expects($this->never())
      ->method('delete');
    $entity = $this->getMockEntity('Drupal\Core\Entity\ContentEntityBase', [], [
      'toArray',
      'id',
    ]);
    $entity->expects($this->atLeastOnce())
      ->method('id')
      ->willReturn('foo');
    $entity->expects($this->once())
      ->method('toArray')
      ->willReturn($expected);
    $this->entityStorage->save($entity);
  }

  /**
   * @covers ::save
   * @covers ::doSave
   */
  public function testSaveInvalid(): void {
    $this->setUpKeyValueEntityStorage();

    $entity = $this->getMockEntity('Drupal\Core\Config\Entity\ConfigEntityBase');
    $this->keyValueStore->expects($this->never())
      ->method('has');
    $this->keyValueStore->expects($this->never())
      ->method('set');
    $this->keyValueStore->expects($this->never())
      ->method('delete');
    $this->expectException(EntityMalformedException::class);
    $this->expectExceptionMessage('The entity does not have an ID.');
    $this->entityStorage->save($entity);
  }

  /**
   * @covers ::save
   * @covers ::doSave
   */
  public function testSaveDuplicate(): void {
    $this->setUpKeyValueEntityStorage();

    $entity = $this->getMockEntity(EntityBaseTest::class, [['id' => 'foo']]);
    $entity->enforceIsNew();
    $this->keyValueStore->expects($this->once())
      ->method('has')
      ->willReturn(TRUE);
    $this->keyValueStore->expects($this->never())
      ->method('set');
    $this->keyValueStore->expects($this->never())
      ->method('delete');
    $this->expectException(EntityStorageException::class);
    $this->expectExceptionMessage("'test_entity_type' entity with ID 'foo' already exists");
    $this->entityStorage->save($entity);
  }

  /**
   * @covers ::load
   * @covers ::postLoad
   */
  public function testLoad(): void {
    $entity = $this->getMockEntity();
    $this->entityType->expects($this->once())
      ->method('getClass')
      ->willReturn(get_class($entity));
    $this->setUpKeyValueEntityStorage();

    $this->keyValueStore->expects($this->once())
      ->method('getMultiple')
      ->with(['foo'])
      ->willReturn([['id' => 'foo']]);
    $entity = $this->entityStorage->load('foo');
    $this->assertInstanceOf('Drupal\Core\Entity\EntityInterface', $entity);
    $this->assertSame('foo', $entity->id());
  }

  /**
   * @covers ::load
   */
  public function testLoadMissingEntity(): void {
    $this->setUpKeyValueEntityStorage();

    $this->keyValueStore->expects($this->once())
      ->method('getMultiple')
      ->with(['foo'])
      ->willReturn([]);
    $entity = $this->entityStorage->load('foo');
    $this->assertNull($entity);
  }

  /**
   * @covers ::loadMultiple
   * @covers ::postLoad
   * @covers ::mapFromStorageRecords
   * @covers ::doLoadMultiple
   */
  public function testLoadMultipleAll(): void {
    $expected['foo'] = $this->getMockEntity(EntityBaseTest::class, [['id' => 'foo']]);
    $expected['bar'] = $this->getMockEntity(EntityBaseTest::class, [['id' => 'bar']]);
    $this->entityType->expects($this->once())
      ->method('getClass')
      ->willReturn(get_class(reset($expected)));
    $this->setUpKeyValueEntityStorage();

    $this->keyValueStore->expects($this->once())
      ->method('getAll')
      ->willReturn([['id' => 'foo'], ['id' => 'bar']]);
    $entities = $this->entityStorage->loadMultiple();
    foreach ($entities as $id => $entity) {
      $this->assertInstanceOf('Drupal\Core\Entity\EntityInterface', $entity);
      $this->assertSame($id, $entity->id());
      $this->assertSame($id, $expected[$id]->id());
    }
  }

  /**
   * @covers ::loadMultiple
   * @covers ::postLoad
   * @covers ::mapFromStorageRecords
   * @covers ::doLoadMultiple
   */
  public function testLoadMultipleIds(): void {
    $entity = $this->getMockEntity(EntityBaseTest::class, [['id' => 'foo']]);
    $this->entityType->expects($this->once())
      ->method('getClass')
      ->willReturn(get_class($entity));
    $this->setUpKeyValueEntityStorage();

    $expected[] = $entity;
    $this->keyValueStore->expects($this->once())
      ->method('getMultiple')
      ->with(['foo'])
      ->willReturn([['id' => 'foo']]);
    $entities = $this->entityStorage->loadMultiple(['foo']);
    foreach ($entities as $id => $entity) {
      $this->assertInstanceOf('Drupal\Core\Entity\EntityInterface', $entity);
      $this->assertSame($id, $entity->id());
    }
  }

  /**
   * @covers ::delete
   * @covers ::doDelete
   */
  public function testDelete(): void {
    $entities['foo'] = $this->getMockEntity(EntityBaseTest::class, [['id' => 'foo']]);
    $entities['bar'] = $this->getMockEntity(EntityBaseTest::class, [['id' => 'bar']]);
    $this->setUpKeyValueEntityStorage();

    $hooks = [
      'test_entity_type_predelete',
      'entity_predelete',
      'test_entity_type_predelete',
      'entity_predelete',
      'test_entity_type_delete',
      'entity_delete',
      'test_entity_type_delete',
      'entity_delete',
    ];
    $this->moduleHandler->expects($this->exactly(count($hooks)))
      ->method('invokeAll')
      ->with($this->callback(function (string $hook) use (&$hooks): bool {
        return array_shift($hooks) === $hook;
      }));

    $this->keyValueStore->expects($this->once())
      ->method('deleteMultiple')
      ->with(['foo', 'bar']);
    $this->entityStorage->delete($entities);
  }

  /**
   * @covers ::delete
   * @covers ::doDelete
   */
  public function testDeleteNothing(): void {
    $this->setUpKeyValueEntityStorage();

    $this->moduleHandler->expects($this->never())
      ->method($this->anything());
    $this->keyValueStore->expects($this->never())
      ->method('delete');
    $this->keyValueStore->expects($this->never())
      ->method('deleteMultiple');

    $this->entityStorage->delete([]);
  }

  /**
   * Creates an entity with specific methods mocked.
   *
   * @param string $class
   *   (optional) The concrete entity class to mock. Defaults to a stub of
   *   \Drupal\Core\Entity\EntityBase defined for test purposes.
   * @param array $arguments
   *   (optional) Arguments to pass to the constructor. An empty set of values
   *   and an entity type ID will be provided.
   * @param array $methods
   *   (optional) The methods to mock.
   *
   * @return \Drupal\Core\Entity\EntityInterface&\PHPUnit\Framework\MockObject\MockObject
   *   A mock entity instance with the specified methods mocked.
   */
  protected function getMockEntity(string $class = EntityBaseTest::class, array $arguments = [], array $methods = []): EntityInterface&MockObject {
    // Ensure the entity is passed at least an array of values and an entity
    // type ID
    if (!isset($arguments[0])) {
      $arguments[0] = [];
    }
    if (!isset($arguments[1])) {
      $arguments[1] = 'test_entity_type';
    }
    return $this->getMockBuilder($class)
      ->setConstructorArgs($arguments)
      ->onlyMethods($methods)
      ->getMock();
  }

}

/**
 * A simple entity class for testing key value entity storage.
 */
class EntityBaseTest extends EntityBase {

  /**
   * The entity ID.
   *
   * @var string
   */
  public $id;

  /**
   * The language code for the entity.
   *
   * @var string
   */
  public $langcode;

  /**
   * The entity UUID.
   *
   * @var string
   */
  public $uuid;

  /**
   * The entity label.
   *
   * @var string
   */
  public $label;

  /**
   * The original, or NULL if the entity cannot be loaded.
   *
   * @var string
   */
  public $original;

}

namespace Drupal\Core\Entity\KeyValueStore;

if (!defined('SAVED_NEW')) {
  define('SAVED_NEW', 1);
}
if (!defined('SAVED_UPDATED')) {
  define('SAVED_UPDATED', 2);
}
