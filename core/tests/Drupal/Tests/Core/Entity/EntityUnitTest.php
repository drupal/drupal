<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Entity;

use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheTagsInvalidator;
use Drupal\Core\Cache\Context\CacheContextsManager;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityTypeRepositoryInterface;
use Drupal\Core\Language\Language;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Routing\RouteProvider;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;

/**
 * Tests Drupal\Core\Entity\EntityBase.
 */
#[CoversClass(EntityBase::class)]
#[Group('Entity')]
#[Group('Access')]
class EntityUnitTest extends UnitTestCase {

  /**
   * The entity under test.
   */
  protected StubEntityBase $entity;

  /**
   * The entity type used for testing.
   */
  protected EntityTypeInterface&MockObject $entityType;

  /**
   * The entity type manager used for testing.
   */
  protected EntityTypeManagerInterface&MockObject $entityTypeManager;

  /**
   * The ID of the type of the entity under test.
   */
  protected string $entityTypeId;

  /**
   * The route provider used for testing.
   */
  protected RouteProvider&MockObject $routeProvider;

  /**
   * The UUID generator used for testing.
   */
  protected UuidInterface&Stub $uuid;

  /**
   * The language manager.
   */
  protected LanguageManagerInterface&MockObject $languageManager;

  /**
   * The mocked cache tags invalidator.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy<\Drupal\Core\Cache\CacheTagsInvalidatorInterface>
   */
  protected ObjectProphecy $cacheTagsInvalidator;

  /**
   * The entity values.
   *
   * @var array
   */
  protected array $values;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->values = [
      'id' => 1,
      'langcode' => 'en',
      'uuid' => '3bb9ee60-bea5-4622-b89b-a63319d10b3a',
    ];
    $this->entityTypeId = $this->randomMachineName();
  }

  /**
   * Sets up the entity under test.
   *
   * @param int $getBundleListCacheTagsCallsCount
   *   The number of expected calls to the
   *   EntityTypeInterface::getBundleListCacheTags() method.
   * @param int $getDefinitionCallsCount
   *   The number of expected calls to the
   *   EntityTypeManagerInterface::getDefinition() method.
   * @param int $getLanguageCallsCount
   *   The number of expected calls to the
   *   LanguageManagerInterface::getLanguage() method.
   */
  protected function setUpEntity(int $getBundleListCacheTagsCallsCount, int $getDefinitionCallsCount, int $getLanguageCallsCount): void {
    $this->entityType = $this->createMock(EntityTypeInterface::class);
    $this->entityType
      ->method('getListCacheTags')
      ->willReturn([$this->entityTypeId . '_list']);
    $this->entityType->expects($this->exactly($getBundleListCacheTagsCallsCount))
      ->method('getBundleListCacheTags')
      ->with($this->entityTypeId)
      ->willReturn([$this->entityTypeId . '_list:' . $this->entityTypeId]);

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->entityTypeManager->expects($this->exactly($getDefinitionCallsCount))
      ->method('getDefinition')
      ->with($this->entityTypeId)
      ->willReturn($this->entityType);

    $this->uuid = $this->createStub(UuidInterface::class);

    $this->languageManager = $this->createMock(LanguageManagerInterface::class);
    $this->languageManager->expects($this->exactly($getLanguageCallsCount))
      ->method('getLanguage')
      ->with('en')
      ->willReturn(new Language(['id' => 'en']));

    $this->cacheTagsInvalidator = $this->prophesize(CacheTagsInvalidator::class);

    $container = new ContainerBuilder();
    $container->set('entity_type.manager', $this->entityTypeManager);
    $container->set('uuid', $this->uuid);
    $container->set('language_manager', $this->languageManager);
    $container->set('cache_tags.invalidator', $this->cacheTagsInvalidator->reveal());
    \Drupal::setContainer($container);

    $this->entity = new StubEntityBase($this->values, $this->entityTypeId);
  }

  /**
   * Tests id.
   */
  public function testId(): void {
    $this->setUpEntity(0, 0, 0);
    $this->assertSame($this->values['id'], $this->entity->id());
  }

  /**
   * Tests uuid.
   */
  public function testUuid(): void {
    $this->setUpEntity(0, 0, 0);
    $this->assertSame($this->values['uuid'], $this->entity->uuid());
  }

  /**
   * Tests is new.
   *
   * @legacy-covers ::isNew
   * @legacy-covers ::enforceIsNew
   */
  public function testIsNew(): void {
    $this->setUpEntity(0, 0, 0);
    // We provided an ID, so the entity is not new.
    $this->assertFalse($this->entity->isNew());
    // Force it to be new.
    $this->assertSame($this->entity, $this->entity->enforceIsNew());
    $this->assertTrue($this->entity->isNew());
  }

  /**
   * Tests get entity type.
   */
  public function testGetEntityType(): void {
    $this->setUpEntity(0, 1, 0);
    $this->assertSame($this->entityType, $this->entity->getEntityType());
  }

  /**
   * Tests bundle.
   */
  public function testBundle(): void {
    $this->setUpEntity(0, 0, 0);
    $this->assertSame($this->entityTypeId, $this->entity->bundle());
  }

  /**
   * Tests label.
   */
  public function testLabel(): void {
    $this->setUpEntity(0, 1, 0);
    $property_label = $this->randomMachineName();
    $this->entityType->expects($this->atLeastOnce())
      ->method('getKey')
      ->with('label')
      ->willReturn('label');

    // Set a dummy property on the entity under test to test that the label can
    // be returned form a property if there is no callback.
    $this->entityTypeManager->expects($this->atLeastOnce())
      ->method('getDefinition')
      ->with($this->entityTypeId)
      ->willReturn([
        'entity_keys' => [
          'label' => 'label',
        ],
      ]);
    $this->entity->label = $property_label;

    $this->assertSame($property_label, $this->entity->label());
  }

  /**
   * Tests access.
   */
  public function testAccess(): void {
    $this->setUpEntity(0, 0, 0);
    $access = $this->createMock('\Drupal\Core\Entity\EntityAccessControlHandlerInterface');
    $operation = $this->randomMachineName();
    $access->expects($this->once())
      ->method('access')
      ->with($this->entity, $operation)
      ->willReturn(AccessResult::allowed());
    $access->expects($this->once())
      ->method('createAccess')
      ->willReturn(AccessResult::allowed());
    $this->entityTypeManager->expects($this->exactly(2))
      ->method('getAccessControlHandler')
      ->willReturn($access);

    $this->assertEquals(AccessResult::allowed(), $this->entity->access($operation));
    $this->assertEquals(AccessResult::allowed(), $this->entity->access('create'));
  }

  /**
   * Tests language.
   */
  public function testLanguage(): void {
    $this->setUpEntity(0, 1, 1);
    $this->entityType
      ->method('getKey')
      ->willReturnMap([
        ['langcode', 'langcode'],
      ]);
    $this->assertSame('en', $this->entity->language()->getId());
  }

  /**
   * Setup for the tests of the ::load() method.
   */
  public function setupTestLoad(): void {
    $this->setUpEntity(0, 0, 0);
    // Base our test entity on a real entity class so we can test if calling
    // EntityBase::load() on the base class will bubble up to an actual entity.
    $this->entityTypeId = 'stub_entity_base';
    $this->entity = new StubEntityBase([], $this->entityTypeId);
  }

  /**
   * Tests EntityBase::load().
   *
   * When called statically on a subclass of Entity.
   */
  public function testLoad(): void {
    $this->setUpEntity(0, 0, 0);
    $this->setupTestLoad();

    $class_name = get_class($this->entity);

    $entity_type_repository = $this->createMock(EntityTypeRepositoryInterface::class);
    $entity_type_repository->expects($this->once())
      ->method('getEntityTypeFromClass')
      ->with($class_name)
      ->willReturn($this->entityTypeId);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->expects($this->once())
      ->method('load')
      ->with(1)
      ->willReturn($this->entity);

    $this->entityTypeManager->expects($this->once())
      ->method('getStorage')
      ->with($this->entityTypeId)
      ->willReturn($storage);

    \Drupal::getContainer()->set('entity_type.repository', $entity_type_repository);

    // Call EntityBase::load statically and check that it returns the mock
    // entity.
    $this->assertSame($this->entity, $class_name::load(1));
  }

  /**
   * Tests EntityBase::loadMultiple().
   *
   * When called statically on a subclass of Entity.
   */
  public function testLoadMultiple(): void {
    $this->setUpEntity(0, 0, 0);
    $this->setupTestLoad();

    $class_name = get_class($this->entity);

    $entity_type_repository = $this->createMock(EntityTypeRepositoryInterface::class);
    $entity_type_repository->expects($this->once())
      ->method('getEntityTypeFromClass')
      ->with($class_name)
      ->willReturn($this->entityTypeId);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->expects($this->once())
      ->method('loadMultiple')
      ->with([1])
      ->willReturn([1 => $this->entity]);

    $this->entityTypeManager->expects($this->once())
      ->method('getStorage')
      ->with($this->entityTypeId)
      ->willReturn($storage);

    \Drupal::getContainer()->set('entity_type.repository', $entity_type_repository);

    // Call EntityBase::loadMultiple() statically and check that it returns the
    // mock entity.
    $this->assertSame([1 => $this->entity], $class_name::loadMultiple([1]));
  }

  /**
   * Tests create.
   */
  public function testCreate(): void {
    $this->setUpEntity(0, 0, 0);
    $this->setupTestLoad();

    $class_name = get_class($this->entity);

    $entity_type_repository = $this->createMock(EntityTypeRepositoryInterface::class);
    $entity_type_repository->expects($this->once())
      ->method('getEntityTypeFromClass')
      ->with($class_name)
      ->willReturn($this->entityTypeId);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->expects($this->once())
      ->method('create')
      ->with([])
      ->willReturn($this->entity);

    $this->entityTypeManager->expects($this->once())
      ->method('getStorage')
      ->with($this->entityTypeId)
      ->willReturn($storage);

    \Drupal::getContainer()->set('entity_type.repository', $entity_type_repository);

    // Call EntityBase::create() statically and check that it returns the mock
    // entity.
    $this->assertSame($this->entity, $class_name::create([]));
  }

  /**
   * Tests save.
   */
  public function testSave(): void {
    $this->setUpEntity(0, 0, 0);
    $storage = $this->createMock('\Drupal\Core\Entity\EntityStorageInterface');
    $storage->expects($this->once())
      ->method('save')
      ->with($this->entity);

    $this->entityTypeManager->expects($this->once())
      ->method('getStorage')
      ->with($this->entityTypeId)
      ->willReturn($storage);

    $this->entity->save();
  }

  /**
   * Tests delete.
   */
  public function testDelete(): void {
    $this->setUpEntity(0, 0, 0);
    $this->entity->id = $this->randomMachineName();
    $storage = $this->createMock('\Drupal\Core\Entity\EntityStorageInterface');
    // Testing the argument of the delete() method consumes too much memory.
    $storage->expects($this->once())
      ->method('delete');

    $this->entityTypeManager->expects($this->once())
      ->method('getStorage')
      ->with($this->entityTypeId)
      ->willReturn($storage);

    $this->entity->delete();
  }

  /**
   * Tests get entity type id.
   */
  public function testGetEntityTypeId(): void {
    $this->setUpEntity(0, 0, 0);
    $this->assertSame($this->entityTypeId, $this->entity->getEntityTypeId());
  }

  /**
   * Tests pre save.
   */
  public function testPreSave(): void {
    $this->setUpEntity(0, 1, 0);
    // This method is internal, so check for errors on calling it only.
    $storage = $this->createStub(EntityStorageInterface::class);
    // Our mocked entity->preSave() returns NULL, so assert that.
    $this->assertNull($this->entity->preSave($storage));
  }

  /**
   * Tests post save.
   */
  public function testPostSave(): void {
    $this->setUpEntity(0, 6, 0);
    // This method is internal, so check for errors on calling it only.
    $storage = $this->createStub(EntityStorageInterface::class);

    // A creation should trigger the invalidation of the "list" cache tag.
    $this->entity->postSave($storage, FALSE);
    $this->cacheTagsInvalidator->invalidateTags([
      $this->entityTypeId . '_list',
    ])->shouldHaveBeenCalledOnce();

    // An update should trigger the invalidation of both the "list" and the
    // "own" cache tags.
    $this->entity->postSave($storage, TRUE);
    $this->cacheTagsInvalidator->invalidateTags([
      $this->entityTypeId . '_list',
      $this->entityTypeId . ':' . $this->values['id'],
    ])->shouldHaveBeenCalledOnce();
  }

  /**
   * Tests post save bundle.
   */
  public function testPostSaveBundle(): void {
    $this->setUpEntity(2, 8, 0);
    $this->entityType->expects($this->atLeastOnce())
      ->method('hasKey')
      ->with('bundle')
      ->willReturn(TRUE);

    // This method is internal, so check for errors on calling it only.
    $storage = $this->createStub(EntityStorageInterface::class);

    // A creation should trigger the invalidation of the global list cache tag
    // and the one for the bundle.
    $this->entity->postSave($storage, FALSE);
    $this->cacheTagsInvalidator->invalidateTags([
      $this->entityTypeId . '_list',
      $this->entityTypeId . '_list:' . $this->entity->bundle(),
    ])->shouldHaveBeenCalledOnce();

    // An update should trigger the invalidation of the "list", bundle list and
    // the "own" cache tags.
    $this->entity->postSave($storage, TRUE);
    $this->cacheTagsInvalidator->invalidateTags([
      $this->entityTypeId . '_list',
      $this->entityTypeId . '_list:' . $this->entity->bundle(),
      $this->entityTypeId . ':' . $this->values['id'],
    ])->shouldHaveBeenCalledOnce();
  }

  /**
   * Tests pre create.
   */
  public function testPreCreate(): void {
    $this->setUpEntity(0, 0, 0);
    // This method is internal, so check for errors on calling it only.
    $storage = $this->createStub(EntityStorageInterface::class);
    $values = [];
    // Our mocked entity->preCreate() returns NULL, so assert that.
    $this->assertNull($this->entity->preCreate($storage, $values));
  }

  /**
   * Tests post create.
   */
  public function testPostCreate(): void {
    $this->setUpEntity(0, 0, 0);
    // This method is internal, so check for errors on calling it only.
    $storage = $this->createStub(EntityStorageInterface::class);
    // Our mocked entity->postCreate() returns NULL, so assert that.
    $this->assertNull($this->entity->postCreate($storage));
  }

  /**
   * Tests pre delete.
   */
  public function testPreDelete(): void {
    $this->setUpEntity(0, 0, 0);
    // This method is internal, so check for errors on calling it only.
    $storage = $this->createStub(EntityStorageInterface::class);
    // Our mocked entity->preDelete() returns NULL, so assert that.
    $this->assertNull($this->entity->preDelete($storage, [$this->entity]));
  }

  /**
   * Tests post delete.
   */
  public function testPostDelete(): void {
    $this->setUpEntity(0, 2, 0);
    $storage = $this->createMock('\Drupal\Core\Entity\EntityStorageInterface');
    $storage->expects($this->once())
      ->method('getEntityType')
      ->willReturn($this->entityType);

    $entities = [$this->values['id'] => $this->entity];
    $this->entity->postDelete($storage, $entities);

    $this->cacheTagsInvalidator->invalidateTags([
      $this->entityTypeId . '_list',
      $this->entityTypeId . ':' . $this->values['id'],
    ])->shouldHaveBeenCalledOnce();
  }

  /**
   * Tests post delete bundle.
   */
  public function testPostDeleteBundle(): void {
    $this->setUpEntity(1, 3, 0);
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

    // We avoid asserting on the order of array values, just that the values
    // all exist.
    $this->cacheTagsInvalidator->invalidateTags(Argument::allOf(
      Argument::containing($this->entityTypeId . '_list'),
      Argument::containing($this->entityTypeId . ':' . $this->values['id']),
      Argument::containing($this->entityTypeId . '_list:' . $this->entity->bundle()),
    ))->shouldHaveBeenCalledOnce();
  }

  /**
   * Tests post load.
   */
  public function testPostLoad(): void {
    $this->setUpEntity(0, 0, 0);
    // This method is internal, so check for errors on calling it only.
    $storage = $this->createStub(EntityStorageInterface::class);
    $entities = [$this->entity];
    // Our mocked entity->postLoad() returns NULL, so assert that.
    $this->assertNull($this->entity->postLoad($storage, $entities));
  }

  /**
   * Tests referenced entities.
   */
  public function testReferencedEntities(): void {
    $this->setUpEntity(0, 0, 0);
    $this->assertSame([], $this->entity->referencedEntities());
  }

  /**
   * Tests cache tags.
   *
   * @legacy-covers ::getCacheTags
   * @legacy-covers ::getCacheTagsToInvalidate
   * @legacy-covers ::addCacheTags
   */
  public function testCacheTags(): void {
    $this->setUpEntity(0, 0, 0);
    // Ensure that both methods return the same by default.
    $this->assertEqualsCanonicalizing([$this->entityTypeId . ':' . 1], $this->entity->getCacheTags());
    $this->assertEqualsCanonicalizing([$this->entityTypeId . ':' . 1], $this->entity->getCacheTagsToInvalidate());

    // Add an additional cache tag and make sure only getCacheTags() returns
    // that.
    $this->entity->addCacheTags(['additional_cache_tag']);

    // EntityTypeId is random so it can shift order. We need to duplicate the
    // sort from \Drupal\Core\Cache\Cache::mergeTags().
    $tags = [$this->entityTypeId . ':' . 1, 'additional_cache_tag'];
    $this->assertEqualsCanonicalizing($tags, $this->entity->getCacheTags());
    $this->assertEqualsCanonicalizing([$this->entityTypeId . ':' . 1], $this->entity->getCacheTagsToInvalidate());
  }

  /**
   * Tests cache contexts.
   *
   * @legacy-covers ::getCacheContexts
   * @legacy-covers ::addCacheContexts
   */
  public function testCacheContexts(): void {
    $this->setUpEntity(0, 0, 0);
    $cache_contexts_manager = $this->createStub(CacheContextsManager::class);
    $cache_contexts_manager->method('assertValidTokens')->willReturn(TRUE);

    $container = new ContainerBuilder();
    $container->set('cache_contexts_manager', $cache_contexts_manager);
    \Drupal::setContainer($container);

    // There are no cache contexts by default.
    $this->assertEqualsCanonicalizing([], $this->entity->getCacheContexts());

    // Add an additional cache context.
    $this->entity->addCacheContexts(['user']);
    $this->assertEqualsCanonicalizing(['user'], $this->entity->getCacheContexts());
  }

  /**
   * Tests cache max age.
   *
   * @legacy-covers ::getCacheMaxAge
   * @legacy-covers ::mergeCacheMaxAge
   */
  public function testCacheMaxAge(): void {
    $this->setUpEntity(0, 0, 0);
    // Cache max age is permanent by default.
    $this->assertEquals(Cache::PERMANENT, $this->entity->getCacheMaxAge());

    // Set two cache max ages, the lower value is the one that needs to be
    // returned.
    $this->entity->mergeCacheMaxAge(600);
    $this->entity->mergeCacheMaxAge(1800);
    $this->assertEquals(600, $this->entity->getCacheMaxAge());
  }

}
