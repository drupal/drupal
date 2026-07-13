<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Entity\KeyValueStore;

use Drupal\Component\Datetime\Time;
use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\Cache\MemoryCache\MemoryCache;
use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityBase;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityMalformedException;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\KeyValueStore\KeyValueEntityStorage;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\KeyValueStore\KeyValueStoreInterface;
use Drupal\Core\Language\Language;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;

/**
 * Tests Drupal\Core\Entity\KeyValueStore\KeyValueEntityStorage.
 */
#[CoversClass(KeyValueEntityStorage::class)]
#[Group('Entity')]
class KeyValueEntityStorageTest extends UnitTestCase {

  /**
   * The entity type.
   */
  protected EntityTypeInterface&MockObject $entityType;

  /**
   * The key value store.
   */
  protected KeyValueStoreInterface&Stub $keyValueStore;

  /**
   * The module handler.
   */
  protected ModuleHandlerInterface&Stub $moduleHandler;

  /**
   * The UUID service.
   */
  protected UuidInterface&Stub $uuidService;

  /**
   * The language manager.
   */
  protected LanguageManagerInterface&Stub $languageManager;

  /**
   * The entity storage.
   *
   * @var \Drupal\Core\Entity\KeyValueStore\KeyValueEntityStorage
   */
  protected $entityStorage;

  /**
   * The mocked entity type manager.
   */
  protected EntityTypeManagerInterface&MockObject $entityTypeManager;

  /**
   * The mocked entity field manager.
   */
  protected EntityFieldManagerInterface&Stub $entityFieldManager;

  /**
   * The mocked cache tags invalidator.
   */
  protected CacheTagsInvalidatorInterface&Stub $cacheTagsInvalidator;

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
   * @param string|null $uuid_key
   *   (optional) The entity key used for the UUID. Defaults to 'uuid'.
   *
   * @legacy-covers ::__construct
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
    $this->entityType
      ->method('getListCacheTags')
      ->willReturn(['test_entity_type_list']);

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->entityTypeManager
      ->method('getDefinition')
      ->with('test_entity_type')
      ->willReturn($this->entityType);

    $this->entityFieldManager = $this->createStub(EntityFieldManagerInterface::class);

    $this->cacheTagsInvalidator = $this->createStub(CacheTagsInvalidatorInterface::class);

    $this->keyValueStore = $this->createStub(KeyValueStoreInterface::class);
    $this->moduleHandler = $this->createStub(ModuleHandlerInterface::class);
    $this->uuidService = $this->createStub(UuidInterface::class);
    $this->languageManager = $this->createStub(LanguageManagerInterface::class);
    $language = new Language(['langcode' => 'en']);
    $this->languageManager
      ->method('getDefaultLanguage')
      ->willReturn($language);
    $this->languageManager
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
   * Reinitializes the key-value store as a mock object.
   */
  protected function setUpMockKeyValueStore(): void {
    $this->keyValueStore = $this->createMock(KeyValueStoreInterface::class);
    $reflection = new \ReflectionProperty($this->entityStorage, 'keyValueStore');
    $reflection->setValue($this->entityStorage, $this->keyValueStore);
  }

  /**
   * Reinitializes the module handler as a mock object.
   */
  protected function setUpMockModuleHandler(): void {
    $this->moduleHandler = $this->createMock(ModuleHandlerInterface::class);
    $this->entityStorage->setModuleHandler($this->moduleHandler);
  }

  /**
   * Reinitializes the UUID service as a mock object.
   */
  protected function setUpMockUuidService(): void {
    $this->uuidService = $this->createMock(UuidInterface::class);
    $reflection = new \ReflectionProperty($this->entityStorage, 'uuidService');
    $reflection->setValue($this->entityStorage, $this->uuidService);
  }

  /**
   * Tests create with predefined uuid.
   *
   * @legacy-covers ::create
   * @legacy-covers ::doCreate
   */
  public function testCreateWithPredefinedUuid(): void {
    $this->entityType->expects($this->once())
      ->method('getClass')
      ->willReturn(EntityBaseTest::class);
    $this->setUpKeyValueEntityStorage();
    $this->setUpMockModuleHandler();
    $this->setUpMockUuidService();

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
   * Tests create without uuid key.
   *
   * @legacy-covers ::create
   * @legacy-covers ::doCreate
   */
  public function testCreateWithoutUuidKey(): void {
    // Set up the entity storage to expect no UUID key.
    $this->entityType->expects($this->once())
      ->method('getClass')
      ->willReturn(EntityBaseTest::class);
    $this->setUpKeyValueEntityStorage(NULL);
    $this->setUpMockModuleHandler();
    $this->setUpMockUuidService();

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
   * Tests create.
   *
   * @legacy-covers ::create
   * @legacy-covers ::doCreate
   */
  public function testCreate(): void {
    $entity = new EntityBaseTest([], 'test_entity_type');
    $this->entityType->expects($this->once())
      ->method('getClass')
      ->willReturn(get_class($entity));
    $this->setUpKeyValueEntityStorage();
    $this->setUpMockModuleHandler();
    $this->setUpMockUuidService();

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
   * Tests save insert.
   *
   * @legacy-covers ::save
   * @legacy-covers ::doSave
   */
  public function testSaveInsert(): EntityInterface&MockObject {
    $this->setUpKeyValueEntityStorage();
    $this->setUpMockKeyValueStore();
    $this->setUpMockModuleHandler();

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
   * Tests save update.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   *
   * @legacy-covers ::save
   * @legacy-covers ::doSave
   */
  #[\PHPUnit\Framework\Attributes\Depends('testSaveInsert')]
  public function testSaveUpdate(EntityInterface $entity): void {
    $this->entityType->expects($this->once())
      ->method('getClass')
      ->willReturn(get_class($entity));
    $this->setUpKeyValueEntityStorage();
    $this->setUpMockKeyValueStore();
    $this->setUpMockModuleHandler();

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
   * Tests save config entity.
   *
   * @legacy-covers ::save
   * @legacy-covers ::doSave
   */
  public function testSaveConfigEntity(): MockObject {
    $this->setUpKeyValueEntityStorage();
    $this->setUpMockKeyValueStore();

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
   * Tests save rename config entity.
   *
   * @legacy-covers ::save
   * @legacy-covers ::doSave
   */
  #[\PHPUnit\Framework\Attributes\Depends('testSaveConfigEntity')]
  public function testSaveRenameConfigEntity(ConfigEntityInterface $entity): void {
    $this->entityType->expects($this->once())
      ->method('getClass')
      ->willReturn(get_class($entity));
    $this->setUpKeyValueEntityStorage();
    $this->setUpMockKeyValueStore();

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
   * Tests save content entity.
   *
   * @legacy-covers ::save
   * @legacy-covers ::doSave
   */
  public function testSaveContentEntity(): void {
    $this->entityType
      ->method('getKeys')
      ->willReturn([
        'id' => 'id',
      ]);
    $this->setUpKeyValueEntityStorage();
    $this->setUpMockKeyValueStore();

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
   * Tests save invalid.
   *
   * @legacy-covers ::save
   * @legacy-covers ::doSave
   */
  public function testSaveInvalid(): void {
    $this->setUpKeyValueEntityStorage();
    $this->setUpMockKeyValueStore();

    $entity = $this->createStub(ConfigEntityBase::class);
    $this->keyValueStore->expects($this->never())
      ->method('has');
    $this->keyValueStore->expects($this->never())
      ->method('set');
    $this->keyValueStore->expects($this->never())
      ->method('delete');
    $this->expectException(EntityMalformedException::class);
    $this->expectExceptionMessageIs('The entity does not have an ID.');
    $this->entityStorage->save($entity);
  }

  /**
   * Tests save duplicate.
   *
   * @legacy-covers ::save
   * @legacy-covers ::doSave
   */
  public function testSaveDuplicate(): void {
    $this->setUpKeyValueEntityStorage();
    $this->setUpMockKeyValueStore();

    $entity = new EntityBaseTest(['id' => 'foo'], 'test_entity_type');
    $entity->enforceIsNew();
    $this->keyValueStore->expects($this->once())
      ->method('has')
      ->willReturn(TRUE);
    $this->keyValueStore->expects($this->never())
      ->method('set');
    $this->keyValueStore->expects($this->never())
      ->method('delete');
    $this->expectException(EntityStorageException::class);
    $this->expectExceptionMessageIs("'test_entity_type' entity with ID 'foo' already exists");
    $this->entityStorage->save($entity);
  }

  /**
   * Tests load.
   *
   * @legacy-covers ::load
   * @legacy-covers ::postLoad
   */
  public function testLoad(): void {
    $entity = new EntityBaseTest([], 'test_entity_type');
    $this->entityType->expects($this->once())
      ->method('getClass')
      ->willReturn(get_class($entity));
    $this->setUpKeyValueEntityStorage();
    $this->setUpMockKeyValueStore();

    $this->keyValueStore->expects($this->once())
      ->method('getMultiple')
      ->with(['foo'])
      ->willReturn([['id' => 'foo']]);
    $entity = $this->entityStorage->load('foo');
    $this->assertInstanceOf('Drupal\Core\Entity\EntityInterface', $entity);
    $this->assertSame('foo', $entity->id());
  }

  /**
   * Tests load missing entity.
   */
  public function testLoadMissingEntity(): void {
    $this->setUpKeyValueEntityStorage();
    $this->setUpMockKeyValueStore();

    $this->keyValueStore->expects($this->once())
      ->method('getMultiple')
      ->with(['foo'])
      ->willReturn([]);
    $entity = $this->entityStorage->load('foo');
    $this->assertNull($entity);
  }

  /**
   * Tests load multiple all.
   *
   * @legacy-covers ::loadMultiple
   * @legacy-covers ::postLoad
   * @legacy-covers ::mapFromStorageRecords
   * @legacy-covers ::doLoadMultiple
   */
  public function testLoadMultipleAll(): void {
    $expected['foo'] = new EntityBaseTest(['id' => 'foo'], 'test_entity_type');
    $expected['bar'] = new EntityBaseTest(['id' => 'bar'], 'test_entity_type');
    $this->entityType->expects($this->once())
      ->method('getClass')
      ->willReturn(get_class(reset($expected)));
    $this->setUpKeyValueEntityStorage();
    $this->setUpMockKeyValueStore();

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
   * Tests load multiple ids.
   *
   * @legacy-covers ::loadMultiple
   * @legacy-covers ::postLoad
   * @legacy-covers ::mapFromStorageRecords
   * @legacy-covers ::doLoadMultiple
   */
  public function testLoadMultipleIds(): void {
    $entity = new EntityBaseTest(['id' => 'foo'], 'test_entity_type');
    $this->entityType->expects($this->once())
      ->method('getClass')
      ->willReturn(get_class($entity));
    $this->setUpKeyValueEntityStorage();
    $this->setUpMockKeyValueStore();

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
   * Tests delete.
   *
   * @legacy-covers ::delete
   * @legacy-covers ::doDelete
   */
  public function testDelete(): void {
    $entities['foo'] = new EntityBaseTest(['id' => 'foo'], 'test_entity_type');
    $entities['bar'] = new EntityBaseTest(['id' => 'bar'], 'test_entity_type');
    $this->setUpKeyValueEntityStorage();
    $this->setUpMockKeyValueStore();
    $this->setUpMockModuleHandler();

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
   * Tests delete nothing.
   *
   * @legacy-covers ::delete
   * @legacy-covers ::doDelete
   */
  public function testDeleteNothing(): void {
    $this->setUpKeyValueEntityStorage();
    $this->setUpMockKeyValueStore();
    $this->setUpMockModuleHandler();

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
    // type ID.
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
