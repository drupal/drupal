<?php

namespace Drupal\Tests\Core\Entity;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Cache\Cache;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityTypeRepositoryInterface;
use Drupal\Core\Language\Language;
use Drupal\entity_test\Entity\EntityTestMul;
use Drupal\Tests\Traits\ExpectDeprecationTrait;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\Core\Entity\Entity
 * @group Entity
 * @group Access
 */
class EntityUnitTest extends UnitTestCase {

  use ExpectDeprecationTrait;

  /**
   * The entity under test.
   *
   * @var \Drupal\Core\Entity\Entity|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $entity;

  /**
   * The entity type used for testing.
   *
   * @var \Drupal\Core\Entity\EntityTypeInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $entityType;

  /**
   * The entity type manager used for testing.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $entityTypeManager;

  /**
   * The ID of the type of the entity under test.
   *
   * @var string
   */
  protected $entityTypeId;

  /**
   * The route provider used for testing.
   *
   * @var \Drupal\Core\Routing\RouteProvider|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $routeProvider;

  /**
   * The UUID generator used for testing.
   *
   * @var \Drupal\Component\Uuid\UuidInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $uuid;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $languageManager;

  /**
   * The mocked cache tags invalidator.
   *
   * @var \Drupal\Core\Cache\CacheTagsInvalidatorInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $cacheTagsInvalidator;

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
    $this->values = [
      'id' => 1,
      'langcode' => 'en',
      'uuid' => '3bb9ee60-bea5-4622-b89b-a63319d10b3a',
    ];
    $this->entityTypeId = $this->randomMachineName();

    $this->entityType = $this->createMock('\Drupal\Core\Entity\EntityTypeInterface');
    $this->entityType->expects($this->any())
      ->method('getListCacheTags')
      ->willReturn([$this->entityTypeId . '_list']);

    $this->entityTypeManager = $this->getMockForAbstractClass(EntityTypeManagerInterface::class);
    $this->entityTypeManager->expects($this->any())
      ->method('getDefinition')
      ->with($this->entityTypeId)
      ->will($this->returnValue($this->entityType));

    $this->uuid = $this->createMock('\Drupal\Component\Uuid\UuidInterface');

    $this->languageManager = $this->createMock('\Drupal\Core\Language\LanguageManagerInterface');
    $this->languageManager->expects($this->any())
      ->method('getLanguage')
      ->with('en')
      ->will($this->returnValue(new Language(['id' => 'en'])));

    $this->cacheTagsInvalidator = $this->createMock('Drupal\Core\Cache\CacheTagsInvalidator');

    $container = new ContainerBuilder();
    $container->set('entity_type.manager', $this->entityTypeManager);
    $container->set('uuid', $this->uuid);
    $container->set('language_manager', $this->languageManager);
    $container->set('cache_tags.invalidator', $this->cacheTagsInvalidator);
    \Drupal::setContainer($container);

    $this->entity = $this->getMockForAbstractClass('\Drupal\Core\Entity\EntityBase', [$this->values, $this->entityTypeId]);
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
   * @group legacy
   */
  public function testLabel() {

    $this->expectDeprecation('Entity type ' . $this->entityTypeId . ' defines a label callback. Support for that is deprecated in drupal:8.0.0 and will be removed in drupal:9.0.0. Override the EntityInterface::label() method instead. See https://www.drupal.org/node/3050794');

    // Make a mock with one method that we use as the entity's uri_callback. We
    // check that it is called, and that the entity's label is the callback's
    // return value.
    $callback_label = $this->randomMachineName();
    $property_label = $this->randomMachineName();
    $callback_container = $this->createMock(get_class());
    $callback_container->expects($this->once())
      ->method(__FUNCTION__)
      ->will($this->returnValue($callback_label));
    $this->entityType->expects($this->at(0))
      ->method('get')
      ->with('label_callback')
      ->will($this->returnValue([$callback_container, __FUNCTION__]));
    $this->entityType->expects($this->at(2))
      ->method('getKey')
      ->with('label')
      ->will($this->returnValue('label'));

    // Set a dummy property on the entity under test to test that the label can
    // be returned form a property if there is no callback.
    $this->entityTypeManager->expects($this->at(1))
      ->method('getDefinition')
      ->with($this->entityTypeId)
      ->will($this->returnValue([
        'entity_keys' => [
          'label' => 'label',
        ],
      ]));
    $this->entity->label = $property_label;

    $this->assertSame($callback_label, $this->entity->label());
    $this->assertSame($property_label, $this->entity->label());
  }

  /**
   * @covers ::access
   */
  public function testAccess() {
    $access = $this->createMock('\Drupal\Core\Entity\EntityAccessControlHandlerInterface');
    $operation = $this->randomMachineName();
    $access->expects($this->at(0))
      ->method('access')
      ->with($this->entity, $operation)
      ->will($this->returnValue(AccessResult::allowed()));
    $access->expects($this->at(1))
      ->method('createAccess')
      ->will($this->returnValue(AccessResult::allowed()));
    $this->entityTypeManager->expects($this->exactly(2))
      ->method('getAccessControlHandler')
      ->will($this->returnValue($access));

    $this->assertEquals(AccessResult::allowed(), $this->entity->access($operation));
    $this->assertEquals(AccessResult::allowed(), $this->entity->access('create'));
  }

  /**
   * @covers ::language
   */
  public function testLanguage() {
    $this->entityType->expects($this->any())
      ->method('getKey')
      ->will($this->returnValueMap([
        ['langcode', 'langcode'],
      ]));
    $this->assertSame('en', $this->entity->language()->getId());
  }

  /**
   * Setup for the tests of the ::load() method.
   */
  public function setupTestLoad() {
    // Base our mocked entity on a real entity class so we can test if calling
    // Entity::load() on the base class will bubble up to an actual entity.
    $this->entityTypeId = 'entity_test_mul';
    $methods = get_class_methods(EntityTestMul::class);
    unset($methods[array_search('load', $methods)]);
    unset($methods[array_search('loadMultiple', $methods)]);
    unset($methods[array_search('create', $methods)]);
    $this->entity = $this->getMockBuilder(EntityTestMul::class)
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

    $entity_type_repository = $this->getMockForAbstractClass(EntityTypeRepositoryInterface::class);
    $entity_type_repository->expects($this->once())
      ->method('getEntityTypeFromClass')
      ->with($class_name)
      ->willReturn($this->entityTypeId);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->expects($this->once())
      ->method('load')
      ->with(1)
      ->will($this->returnValue($this->entity));

    $this->entityTypeManager->expects($this->once())
      ->method('getStorage')
      ->with($this->entityTypeId)
      ->will($this->returnValue($storage));

    \Drupal::getContainer()->set('entity_type.repository', $entity_type_repository);

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

    $entity_type_repository = $this->getMockForAbstractClass(EntityTypeRepositoryInterface::class);
    $entity_type_repository->expects($this->once())
      ->method('getEntityTypeFromClass')
      ->with($class_name)
      ->willReturn($this->entityTypeId);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->expects($this->once())
      ->method('loadMultiple')
      ->with([1])
      ->will($this->returnValue([1 => $this->entity]));

    $this->entityTypeManager->expects($this->once())
      ->method('getStorage')
      ->with($this->entityTypeId)
      ->will($this->returnValue($storage));

    \Drupal::getContainer()->set('entity_type.repository', $entity_type_repository);

    // Call Entity::loadMultiple statically and check that it returns the mock
    // entity.
    $this->assertSame([1 => $this->entity], $class_name::loadMultiple([1]));
  }

  /**
   * @covers ::create
   */
  public function testCreate() {
    $this->setupTestLoad();

    $class_name = get_class($this->entity);

    $entity_type_repository = $this->getMockForAbstractClass(EntityTypeRepositoryInterface::class);
    $entity_type_repository->expects($this->once())
      ->method('getEntityTypeFromClass')
      ->with($class_name)
      ->willReturn($this->entityTypeId);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->expects($this->once())
      ->method('create')
      ->with([])
      ->will($this->returnValue($this->entity));

    $this->entityTypeManager->expects($this->once())
      ->method('getStorage')
      ->with($this->entityTypeId)
      ->will($this->returnValue($storage));

    \Drupal::getContainer()->set('entity_type.repository', $entity_type_repository);

    // Call Entity::create() statically and check that it returns the mock
    // entity.
    $this->assertSame($this->entity, $class_name::create([]));
  }

  /**
   * @covers ::save
   */
  public function testSave() {
    $storage = $this->createMock('\Drupal\Core\Entity\EntityStorageInterface');
    $storage->expects($this->once())
      ->method('save')
      ->with($this->entity);

    $this->entityTypeManager->expects($this->once())
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
    $storage = $this->createMock('\Drupal\Core\Entity\EntityStorageInterface');
    // Testing the argument of the delete() method consumes too much memory.
    $storage->expects($this->once())
      ->method('delete');

    $this->entityTypeManager->expects($this->once())
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
    $storage = $this->createMock('\Drupal\Core\Entity\EntityStorageInterface');
    // Our mocked entity->preSave() returns NULL, so assert that.
    $this->assertNull($this->entity->preSave($storage));
  }

  /**
   * @covers ::postSave
   */
  public function testPostSave() {
    $this->cacheTagsInvalidator->expects($this->at(0))
      ->method('invalidateTags')
      ->with([
        // List cache tag.
        $this->entityTypeId . '_list',
      ]);
    $this->cacheTagsInvalidator->expects($this->at(1))
      ->method('invalidateTags')
      ->with([
        // Own cache tag.
        $this->entityTypeId . ':' . $this->values['id'],
        // List cache tag.
        $this->entityTypeId . '_list',
      ]);

    // This method is internal, so check for errors on calling it only.
    $storage = $this->createMock('\Drupal\Core\Entity\EntityStorageInterface');

    // A creation should trigger the invalidation of the "list" cache tag.
    $this->entity->postSave($storage, FALSE);
    // An update should trigger the invalidation of both the "list" and the
    // "own" cache tags.
    $this->entity->postSave($storage, TRUE);
  }

  /**
   * @covers ::postSave
   */
  public function testPostSaveBundle() {
    $this->cacheTagsInvalidator->expects($this->at(0))
      ->method('invalidateTags')
      ->with([
        // List cache tag.
        $this->entityTypeId . '_list',
        $this->entityTypeId . '_list:' . $this->entity->bundle(),
      ]);
    $this->cacheTagsInvalidator->expects($this->at(1))
      ->method('invalidateTags')
      ->with([
        // Own cache tag.
        $this->entityTypeId . ':' . $this->values['id'],
        // List cache tag.
        $this->entityTypeId . '_list',
        $this->entityTypeId . '_list:' . $this->entity->bundle(),
      ]);

    $this->entityType->expects($this->atLeastOnce())
      ->method('hasKey')
      ->with('bundle')
      ->willReturn(TRUE);

    // This method is internal, so check for errors on calling it only.
    $storage = $this->createMock('\Drupal\Core\Entity\EntityStorageInterface');

    // A creation should trigger the invalidation of the global list cache tag
    // and the one for the bundle.
    $this->entity->postSave($storage, FALSE);
    // An update should trigger the invalidation of the "list", bundle list and
    // the "own" cache tags.
    $this->entity->postSave($storage, TRUE);
  }

  /**
   * @covers ::preCreate
   */
  public function testPreCreate() {
    // This method is internal, so check for errors on calling it only.
    $storage = $this->createMock('\Drupal\Core\Entity\EntityStorageInterface');
    $values = [];
    // Our mocked entity->preCreate() returns NULL, so assert that.
    $this->assertNull($this->entity->preCreate($storage, $values));
  }

  /**
   * @covers ::postCreate
   */
  public function testPostCreate() {
    // This method is internal, so check for errors on calling it only.
    $storage = $this->createMock('\Drupal\Core\Entity\EntityStorageInterface');
    // Our mocked entity->postCreate() returns NULL, so assert that.
    $this->assertNull($this->entity->postCreate($storage));
  }

  /**
   * @covers ::preDelete
   */
  public function testPreDelete() {
    // This method is internal, so check for errors on calling it only.
    $storage = $this->createMock('\Drupal\Core\Entity\EntityStorageInterface');
    // Our mocked entity->preDelete() returns NULL, so assert that.
    $this->assertNull($this->entity->preDelete($storage, [$this->entity]));
  }

  /**
   * @covers ::postDelete
   */
  public function testPostDelete() {
    $this->cacheTagsInvalidator->expects($this->once())
      ->method('invalidateTags')
      ->with([
        $this->entityTypeId . ':' . $this->values['id'],
        $this->entityTypeId . '_list',
      ]);
    $storage = $this->createMock('\Drupal\Core\Entity\EntityStorageInterface');
    $storage->expects($this->once())
      ->method('getEntityType')
      ->willReturn($this->entityType);

    $entities = [$this->values['id'] => $this->entity];
    $this->entity->postDelete($storage, $entities);
  }

  /**
   * @covers ::postDelete
   */
  public function testPostDeleteBundle() {
    $this->cacheTagsInvalidator->expects($this->once())
      ->method('invalidateTags')
      ->with([
        $this->entityTypeId . ':' . $this->values['id'],
        $this->entityTypeId . '_list',
        $this->entityTypeId . '_list:' . $this->entity->bundle(),
      ]);
    $this->entityType->expects($this->atLeastOnce())
      ->method('hasKey')
      ->with('bundle')
      ->willReturn(TRUE);
    $storage = $this->createMock('\Drupal\Core\Entity\EntityStorageInterface');
    $storage->expects($this->once())
      ->method('getEntityType')
      ->willReturn($this->entityType);

    $entities = [$this->values['id'] => $this->entity];
    $this->entity->postDelete($storage, $entities);
  }

  /**
   * @covers ::postLoad
   */
  public function testPostLoad() {
    // This method is internal, so check for errors on calling it only.
    $storage = $this->createMock('\Drupal\Core\Entity\EntityStorageInterface');
    $entities = [$this->entity];
    // Our mocked entity->postLoad() returns NULL, so assert that.
    $this->assertNull($this->entity->postLoad($storage, $entities));
  }

  /**
   * @covers ::referencedEntities
   */
  public function testReferencedEntities() {
    $this->assertSame([], $this->entity->referencedEntities());
  }

  /**
   * @covers ::getCacheTags
   * @covers ::getCacheTagsToInvalidate
   * @covers ::addCacheTags
   */
  public function testCacheTags() {
    // Ensure that both methods return the same by default.
    $this->assertEquals([$this->entityTypeId . ':' . 1], $this->entity->getCacheTags());
    $this->assertEquals([$this->entityTypeId . ':' . 1], $this->entity->getCacheTagsToInvalidate());

    // Add an additional cache tag and make sure only getCacheTags() returns
    // that.
    $this->entity->addCacheTags(['additional_cache_tag']);

    // EntityTypeId is random so it can shift order. We need to duplicate the
    // sort from \Drupal\Core\Cache\Cache::mergeTags().
    $tags = ['additional_cache_tag', $this->entityTypeId . ':' . 1];
    sort($tags);
    $this->assertEquals($tags, $this->entity->getCacheTags());
    $this->assertEquals([$this->entityTypeId . ':' . 1], $this->entity->getCacheTagsToInvalidate());
  }

  /**
   * @covers ::getCacheContexts
   * @covers ::addCacheContexts
   */
  public function testCacheContexts() {
    $cache_contexts_manager = $this->getMockBuilder('Drupal\Core\Cache\Context\CacheContextsManager')
      ->disableOriginalConstructor()
      ->getMock();
    $cache_contexts_manager->method('assertValidTokens')->willReturn(TRUE);

    $container = new ContainerBuilder();
    $container->set('cache_contexts_manager', $cache_contexts_manager);
    \Drupal::setContainer($container);

    // There are no cache contexts by default.
    $this->assertEquals([], $this->entity->getCacheContexts());

    // Add an additional cache context.
    $this->entity->addCacheContexts(['user']);
    $this->assertEquals(['user'], $this->entity->getCacheContexts());
  }

  /**
   * @covers ::getCacheMaxAge
   * @covers ::mergeCacheMaxAge
   */
  public function testCacheMaxAge() {
    // Cache max age is permanent by default.
    $this->assertEquals(Cache::PERMANENT, $this->entity->getCacheMaxAge());

    // Set two cache max ages, the lower value is the one that needs to be
    // returned.
    $this->entity->mergeCacheMaxAge(600);
    $this->entity->mergeCacheMaxAge(1800);
    $this->assertEquals(600, $this->entity->getCacheMaxAge());
  }

}
