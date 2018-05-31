<?php

namespace Drupal\Tests\Core\Config\Entity;

use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\Cache\MemoryCache\MemoryCache;
use Drupal\Core\Config\Config;
use Drupal\Core\Config\ConfigDuplicateUUIDException;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ConfigManagerInterface;
use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Config\Entity\ConfigEntityStorage;
use Drupal\Core\Config\Entity\ConfigEntityType;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityMalformedException;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryFactoryInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Language\Language;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Tests\UnitTestCase;
use Prophecy\Argument;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @coversDefaultClass \Drupal\Core\Config\Entity\ConfigEntityStorage
 * @group Config
 */
class ConfigEntityStorageTest extends UnitTestCase {

  /**
   * The type ID of the entity under test.
   *
   * @var string
   */
  protected $entityTypeId;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface|\Prophecy\Prophecy\ProphecyInterface
   */
  protected $moduleHandler;

  /**
   * The UUID service.
   *
   * @var \Drupal\Component\Uuid\UuidInterface|\Prophecy\Prophecy\ProphecyInterface
   */
  protected $uuidService;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface|\Prophecy\Prophecy\ProphecyInterface
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
   * @var \Drupal\Core\Config\ConfigFactoryInterface|\Prophecy\Prophecy\ProphecyInterface
   */
  protected $configFactory;

  /**
   * The entity query.
   *
   * @var \Drupal\Core\Entity\Query\QueryInterface|\Prophecy\Prophecy\ProphecyInterface
   */
  protected $entityQuery;

  /**
   * The mocked cache backend.
   *
   * @var \Drupal\Core\Cache\CacheTagsInvalidatorInterface|\Prophecy\Prophecy\ProphecyInterface
   */
  protected $cacheTagsInvalidator;

  /**
   * The configuration manager.
   *
   * @var \Drupal\Core\Config\ConfigManagerInterface|\Prophecy\Prophecy\ProphecyInterface
   */
  protected $configManager;

  /**
   * {@inheritdoc}
   *
   * @covers ::__construct
   */
  protected function setUp() {
    parent::setUp();

    $this->entityTypeId = 'test_entity_type';

    $entity_type = new ConfigEntityType([
      'id' => $this->entityTypeId,
      'class' => get_class($this->getMockEntity()),
      'provider' => 'the_provider',
      'config_prefix' => 'the_config_prefix',
      'entity_keys' => [
        'id' => 'id',
        'uuid' => 'uuid',
        'langcode' => 'langcode',
      ],
      'list_cache_tags' => [$this->entityTypeId . '_list'],
    ]);

    $this->moduleHandler = $this->prophesize(ModuleHandlerInterface::class);

    $this->uuidService = $this->prophesize(UuidInterface::class);

    $this->languageManager = $this->prophesize(LanguageManagerInterface::class);
    $this->languageManager->getCurrentLanguage()->willReturn(new Language(['id' => 'hu']));

    $this->configFactory = $this->prophesize(ConfigFactoryInterface::class);

    $this->entityQuery = $this->prophesize(QueryInterface::class);
    $entity_query_factory = $this->prophesize(QueryFactoryInterface::class);
    $entity_query_factory->get($entity_type, 'AND')->willReturn($this->entityQuery->reveal());

    $this->entityStorage = new ConfigEntityStorage($entity_type, $this->configFactory->reveal(), $this->uuidService->reveal(), $this->languageManager->reveal(), new MemoryCache());
    $this->entityStorage->setModuleHandler($this->moduleHandler->reveal());

    $entity_type_manager = $this->prophesize(EntityTypeManagerInterface::class);
    $entity_type_manager->getDefinition('test_entity_type')->willReturn($entity_type);

    $this->cacheTagsInvalidator = $this->prophesize(CacheTagsInvalidatorInterface::class);

    $typed_config_manager = $this->prophesize(TypedConfigManagerInterface::class);
    $typed_config_manager
      ->getDefinition(Argument::containingString('the_provider.the_config_prefix.'))
      ->willReturn(['mapping' => ['id' => '', 'uuid' => '', 'dependencies' => '']]);

    $this->configManager = $this->prophesize(ConfigManagerInterface::class);

    $container = new ContainerBuilder();
    $container->set('entity_type.manager', $entity_type_manager->reveal());
    $container->set('entity.query.config', $entity_query_factory->reveal());
    $container->set('config.typed', $typed_config_manager->reveal());
    $container->set('cache_tags.invalidator', $this->cacheTagsInvalidator->reveal());
    $container->set('config.manager', $this->configManager->reveal());
    $container->set('language_manager', $this->languageManager->reveal());
    \Drupal::setContainer($container);

  }

  /**
   * @covers ::create
   * @covers ::doCreate
   */
  public function testCreateWithPredefinedUuid() {
    $this->cacheTagsInvalidator->invalidateTags(Argument::cetera())->shouldNotBeCalled();

    $entity = $this->getMockEntity();
    $entity->set('id', 'foo');
    $entity->set('langcode', 'hu');
    $entity->set('uuid', 'baz');
    $entity->setOriginalId('foo');
    $entity->enforceIsNew();

    $this->moduleHandler->invokeAll('test_entity_type_create', [$entity])
      ->shouldBeCalled();
    $this->moduleHandler->invokeAll('entity_create', [$entity, 'test_entity_type'])
      ->shouldBeCalled();

    $this->uuidService->generate()->shouldNotBeCalled();

    $entity = $this->entityStorage->create(['id' => 'foo', 'uuid' => 'baz']);
    $this->assertInstanceOf(EntityInterface::class, $entity);
    $this->assertSame('foo', $entity->id());
    $this->assertSame('baz', $entity->uuid());
  }

  /**
   * @covers ::create
   * @covers ::doCreate
   *
   * @return \Drupal\Core\Entity\EntityInterface
   */
  public function testCreate() {
    $this->cacheTagsInvalidator->invalidateTags(Argument::cetera())->shouldNotBeCalled();

    $entity = $this->getMockEntity();
    $entity->set('id', 'foo');
    $entity->set('langcode', 'hu');
    $entity->set('uuid', 'bar');
    $entity->setOriginalId('foo');
    $entity->enforceIsNew();

    $this->moduleHandler->invokeAll('test_entity_type_create', [$entity])
      ->shouldBeCalled();
    $this->moduleHandler->invokeAll('entity_create', [$entity, 'test_entity_type'])
      ->shouldBeCalled();

    $this->uuidService->generate()->willReturn('bar');

    $entity = $this->entityStorage->create(['id' => 'foo']);
    $this->assertInstanceOf(EntityInterface::class, $entity);
    $this->assertSame('foo', $entity->id());
    $this->assertSame('bar', $entity->uuid());
    return $entity;
  }

  /**
   * @covers ::create
   * @covers ::doCreate
   */
  public function testCreateWithCurrentLanguage() {
    $this->languageManager->getLanguage('hu')->willReturn(new Language(['id' => 'hu']));

    $entity = $this->entityStorage->create(['id' => 'foo']);
    $this->assertSame('hu', $entity->language()->getId());
  }

  /**
   * @covers ::create
   * @covers ::doCreate
   */
  public function testCreateWithExplicitLanguage() {
    $this->languageManager->getLanguage('en')->willReturn(new Language(['id' => 'en']));

    $entity = $this->entityStorage->create(['id' => 'foo', 'langcode' => 'en']);
    $this->assertSame('en', $entity->language()->getId());
  }

  /**
   * @covers ::save
   * @covers ::doSave
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *
   * @depends testCreate
   */
  public function testSaveInsert(EntityInterface $entity) {
    $immutable_config_object = $this->prophesize(ImmutableConfig::class);
    $immutable_config_object->isNew()->willReturn(TRUE);

    $config_object = $this->prophesize(Config::class);
    $config_object->setData(['id' => 'foo', 'uuid' => 'bar', 'dependencies' => []])
      ->shouldBeCalled();
    $config_object->save(FALSE)->shouldBeCalled();
    $config_object->get()->willReturn([]);

    $this->cacheTagsInvalidator->invalidateTags([$this->entityTypeId . '_list'])
      ->shouldBeCalled();

    $this->configFactory->get('the_provider.the_config_prefix.foo')
      ->willReturn($immutable_config_object->reveal());
    $this->configFactory->getEditable('the_provider.the_config_prefix.foo')
      ->willReturn($config_object->reveal());

    $this->moduleHandler->invokeAll('test_entity_type_presave', [$entity])
      ->shouldBeCalled();
    $this->moduleHandler->invokeAll('entity_presave', [$entity, 'test_entity_type'])
      ->shouldBeCalled();
    $this->moduleHandler->invokeAll('test_entity_type_insert', [$entity])
      ->shouldBeCalled();
    $this->moduleHandler->invokeAll('entity_insert', [$entity, 'test_entity_type'])
      ->shouldBeCalled();

    $this->entityQuery->condition('uuid', 'bar')->willReturn($this->entityQuery);
    $this->entityQuery->execute()->willReturn([]);

    $return = $this->entityStorage->save($entity);
    $this->assertSame(SAVED_NEW, $return);
    return $entity;
  }

  /**
   * @covers ::save
   * @covers ::doSave
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *
   * @depends testSaveInsert
   */
  public function testSaveUpdate(EntityInterface $entity) {
    $immutable_config_object = $this->prophesize(ImmutableConfig::class);
    $immutable_config_object->isNew()->willReturn(FALSE);

    $config_object = $this->prophesize(Config::class);
    $config_object->setData(['id' => 'foo', 'uuid' => 'bar', 'dependencies' => []])
      ->shouldBeCalled();
    $config_object->save(FALSE)->shouldBeCalled();
    $config_object->get()->willReturn([]);

    $this->cacheTagsInvalidator->invalidateTags([$this->entityTypeId . '_list'])
      ->shouldBeCalled();

    $this->configFactory->loadMultiple(['the_provider.the_config_prefix.foo'])
      ->willReturn([])
      ->shouldBeCalledTimes(2);
    $this->configFactory
      ->get('the_provider.the_config_prefix.foo')
      ->willReturn($immutable_config_object->reveal())
      ->shouldBeCalledTimes(1);
    $this->configFactory
      ->getEditable('the_provider.the_config_prefix.foo')
      ->willReturn($config_object->reveal())
      ->shouldBeCalledTimes(1);

    $this->moduleHandler->invokeAll('test_entity_type_presave', [$entity])
      ->shouldBeCalled();
    $this->moduleHandler->invokeAll('entity_presave', [$entity, 'test_entity_type'])
      ->shouldBeCalled();
    $this->moduleHandler->invokeAll('test_entity_type_update', [$entity])
      ->shouldBeCalled();
    $this->moduleHandler->invokeAll('entity_update', [$entity, 'test_entity_type'])
      ->shouldBeCalled();

    $this->entityQuery->condition('uuid', 'bar')->willReturn($this->entityQuery);
    $this->entityQuery->execute()->willReturn([$entity->id()]);

    $return = $this->entityStorage->save($entity);
    $this->assertSame(SAVED_UPDATED, $return);
    return $entity;
  }

  /**
   * @covers ::save
   * @covers ::doSave
   *
   * @depends testSaveInsert
   */
  public function testSaveRename(ConfigEntityInterface $entity) {
    $immutable_config_object = $this->prophesize(ImmutableConfig::class);
    $immutable_config_object->isNew()->willReturn(FALSE);

    $config_object = $this->prophesize(Config::class);
    $config_object->setData(['id' => 'bar', 'uuid' => 'bar', 'dependencies' => []])
      ->shouldBeCalled();
    $config_object->save(FALSE)
      ->shouldBeCalled();
    $config_object->get()->willReturn([]);

    $this->cacheTagsInvalidator->invalidateTags([$this->entityTypeId . '_list'])
      ->shouldBeCalled();

    $this->configFactory->get('the_provider.the_config_prefix.foo')
      ->willReturn($immutable_config_object->reveal());
    $this->configFactory->loadMultiple(['the_provider.the_config_prefix.foo'])
      ->willReturn([]);
    $this->configFactory->rename('the_provider.the_config_prefix.foo', 'the_provider.the_config_prefix.bar')
      ->shouldBeCalled();
    $this->configFactory->getEditable('the_provider.the_config_prefix.bar')
      ->willReturn($config_object->reveal());

    // Performing a rename does not change the original ID until saving.
    $this->assertSame('foo', $entity->getOriginalId());
    $entity->set('id', 'bar');
    $this->assertSame('foo', $entity->getOriginalId());

    $this->entityQuery->condition('uuid', 'bar')->willReturn($this->entityQuery);
    $this->entityQuery->execute()->willReturn([$entity->id()]);

    $return = $this->entityStorage->save($entity);
    $this->assertSame(SAVED_UPDATED, $return);
    $this->assertSame('bar', $entity->getOriginalId());
  }

  /**
   * @covers ::save
   */
  public function testSaveInvalid() {
    $this->cacheTagsInvalidator->invalidateTags(Argument::cetera())
      ->shouldNotBeCalled();

    $entity = $this->getMockEntity();
    $this->setExpectedException(EntityMalformedException::class, 'The entity does not have an ID.');
    $this->entityStorage->save($entity);
  }

  /**
   * @covers ::save
   * @covers ::doSave
   */
  public function testSaveDuplicate() {
    $config_object = $this->prophesize(ImmutableConfig::class);
    $config_object->isNew()->willReturn(FALSE);

    $this->cacheTagsInvalidator->invalidateTags(Argument::cetera())
      ->shouldNotBeCalled();

    $this->configFactory->get('the_provider.the_config_prefix.foo')
      ->willReturn($config_object->reveal());

    $entity = $this->getMockEntity(['id' => 'foo']);
    $entity->enforceIsNew();

    $this->setExpectedException(EntityStorageException::class);
    $this->entityStorage->save($entity);
  }

  /**
   * @covers ::save
   * @covers ::doSave
   */
  public function testSaveMismatch() {
    $config_object = $this->prophesize(ImmutableConfig::class);
    $config_object->isNew()->willReturn(TRUE);

    $this->cacheTagsInvalidator->invalidateTags(Argument::cetera())
      ->shouldNotBeCalled();

    $this->configFactory->get('the_provider.the_config_prefix.foo')
      ->willReturn($config_object->reveal());

    $this->entityQuery->condition('uuid', NULL)->willReturn($this->entityQuery);
    $this->entityQuery->execute()->willReturn(['baz']);

    $entity = $this->getMockEntity(['id' => 'foo']);
    $this->setExpectedException(ConfigDuplicateUUIDException::class, 'when this UUID is already used for');
    $this->entityStorage->save($entity);
  }

  /**
   * @covers ::save
   * @covers ::doSave
   */
  public function testSaveNoMismatch() {
    $immutable_config_object = $this->prophesize(ImmutableConfig::class);
    $immutable_config_object->isNew()->willReturn(TRUE);

    $config_object = $this->prophesize(Config::class);
    $config_object->get()->willReturn([]);
    $config_object->setData(['id' => 'foo', 'uuid' => NULL, 'dependencies' => []])
      ->shouldBeCalled();
    $config_object->save(FALSE)->shouldBeCalled();

    $this->cacheTagsInvalidator->invalidateTags([$this->entityTypeId . '_list'])
      ->shouldBeCalled();

    $this->configFactory->get('the_provider.the_config_prefix.baz')
      ->willReturn($immutable_config_object->reveal())
      ->shouldBeCalled();
    $this->configFactory->rename('the_provider.the_config_prefix.baz', 'the_provider.the_config_prefix.foo')
      ->shouldBeCalled();
    $this->configFactory->getEditable('the_provider.the_config_prefix.foo')
      ->willReturn($config_object->reveal())
      ->shouldBeCalled();

    $this->entityQuery->condition('uuid', NULL)->willReturn($this->entityQuery);
    $this->entityQuery->execute()->willReturn(['baz']);

    $entity = $this->getMockEntity(['id' => 'foo']);
    $entity->setOriginalId('baz');
    $entity->enforceIsNew();
    $this->entityStorage->save($entity);
  }

  /**
   * @covers ::save
   * @covers ::doSave
   */
  public function testSaveChangedUuid() {
    $config_object = $this->prophesize(ImmutableConfig::class);
    $config_object->get()->willReturn(['id' => 'foo']);
    $config_object->get('id')->willReturn('foo');
    $config_object->isNew()->willReturn(FALSE);
    $config_object->getName()->willReturn('foo');
    $config_object->getCacheContexts()->willReturn([]);
    $config_object->getCacheTags()->willReturn(['config:foo']);
    $config_object->getCacheMaxAge()->willReturn(Cache::PERMANENT);

    $this->cacheTagsInvalidator->invalidateTags(Argument::cetera())
      ->shouldNotBeCalled();

    $this->configFactory->loadMultiple(['the_provider.the_config_prefix.foo'])
      ->willReturn([$config_object->reveal()]);
    $this->configFactory->get('the_provider.the_config_prefix.foo')
      ->willReturn($config_object->reveal());
    $this->configFactory->rename(Argument::cetera())->shouldNotBeCalled();

    $this->moduleHandler->getImplementations('entity_load')->willReturn([]);
    $this->moduleHandler->getImplementations('test_entity_type_load')->willReturn([]);

    $this->entityQuery->condition('uuid', 'baz')->willReturn($this->entityQuery);
    $this->entityQuery->execute()->willReturn(['foo']);

    $entity = $this->getMockEntity(['id' => 'foo']);

    $entity->set('uuid', 'baz');
    $this->setExpectedException(ConfigDuplicateUUIDException::class, 'when this entity already exists with UUID');
    $this->entityStorage->save($entity);
  }

  /**
   * @covers ::load
   * @covers ::postLoad
   * @covers ::mapFromStorageRecords
   * @covers ::doLoadMultiple
   */
  public function testLoad() {
    $config_object = $this->prophesize(ImmutableConfig::class);
    $config_object->get()->willReturn(['id' => 'foo']);
    $config_object->get('id')->willReturn('foo');
    $config_object->getCacheContexts()->willReturn([]);
    $config_object->getCacheTags()->willReturn(['config:foo']);
    $config_object->getCacheMaxAge()->willReturn(Cache::PERMANENT);
    $config_object->getName()->willReturn('foo');

    $this->configFactory->loadMultiple(['the_provider.the_config_prefix.foo'])
      ->willReturn([$config_object->reveal()]);

    $this->moduleHandler->getImplementations('entity_load')->willReturn([]);
    $this->moduleHandler->getImplementations('test_entity_type_load')->willReturn([]);

    $entity = $this->entityStorage->load('foo');
    $this->assertInstanceOf(EntityInterface::class, $entity);
    $this->assertSame('foo', $entity->id());
  }

  /**
   * @covers ::loadMultiple
   * @covers ::postLoad
   * @covers ::mapFromStorageRecords
   * @covers ::doLoadMultiple
   */
  public function testLoadMultipleAll() {
    $foo_config_object = $this->prophesize(ImmutableConfig::class);
    $foo_config_object->get()->willReturn(['id' => 'foo']);
    $foo_config_object->get('id')->willReturn('foo');
    $foo_config_object->getCacheContexts()->willReturn([]);
    $foo_config_object->getCacheTags()->willReturn(['config:foo']);
    $foo_config_object->getCacheMaxAge()->willReturn(Cache::PERMANENT);
    $foo_config_object->getName()->willReturn('foo');

    $bar_config_object = $this->prophesize(ImmutableConfig::class);
    $bar_config_object->get()->willReturn(['id' => 'bar']);
    $bar_config_object->get('id')->willReturn('bar');
    $bar_config_object->getCacheContexts()->willReturn([]);
    $bar_config_object->getCacheTags()->willReturn(['config:bar']);
    $bar_config_object->getCacheMaxAge()->willReturn(Cache::PERMANENT);
    $bar_config_object->getName()->willReturn('foo');

    $this->configFactory->listAll('the_provider.the_config_prefix.')
      ->willReturn(['the_provider.the_config_prefix.foo', 'the_provider.the_config_prefix.bar']);
    $this->configFactory->loadMultiple(['the_provider.the_config_prefix.foo', 'the_provider.the_config_prefix.bar'])
      ->willReturn([$foo_config_object->reveal(), $bar_config_object->reveal()]);

    $this->moduleHandler->getImplementations('entity_load')->willReturn([]);
    $this->moduleHandler->getImplementations('test_entity_type_load')->willReturn([]);

    $entities = $this->entityStorage->loadMultiple();
    $expected['foo'] = 'foo';
    $expected['bar'] = 'bar';
    $this->assertContainsOnlyInstancesOf(EntityInterface::class, $entities);
    foreach ($entities as $id => $entity) {
      $this->assertSame($id, $entity->id());
      $this->assertSame($expected[$id], $entity->id());
    }
  }

  /**
   * @covers ::loadMultiple
   * @covers ::postLoad
   * @covers ::mapFromStorageRecords
   * @covers ::doLoadMultiple
   */
  public function testLoadMultipleIds() {
    $config_object = $this->prophesize(ImmutableConfig::class);
    $config_object->get()->willReturn(['id' => 'foo']);
    $config_object->get('id')->willReturn('foo');
    $config_object->getCacheContexts()->willReturn([]);
    $config_object->getCacheTags()->willReturn(['config:foo']);
    $config_object->getCacheMaxAge()->willReturn(Cache::PERMANENT);
    $config_object->getName()->willReturn('foo');

    $this->configFactory->loadMultiple(['the_provider.the_config_prefix.foo'])
      ->willReturn([$config_object->reveal()]);

    $this->moduleHandler->getImplementations('entity_load')->willReturn([]);
    $this->moduleHandler->getImplementations('test_entity_type_load')->willReturn([]);

    $entities = $this->entityStorage->loadMultiple(['foo']);
    $this->assertContainsOnlyInstancesOf(EntityInterface::class, $entities);
    foreach ($entities as $id => $entity) {
      $this->assertSame($id, $entity->id());
    }
  }

  /**
   * @covers ::loadRevision
   */
  public function testLoadRevision() {
    $this->assertSame(NULL, $this->entityStorage->loadRevision(1));
  }

  /**
   * @covers ::deleteRevision
   */
  public function testDeleteRevision() {
    $this->cacheTagsInvalidator->invalidateTags(Argument::cetera())
      ->shouldNotBeCalled();

    $this->assertSame(NULL, $this->entityStorage->deleteRevision(1));
  }

  /**
   * @covers ::delete
   * @covers ::doDelete
   */
  public function testDelete() {
    // Dependencies are tested in
    // \Drupal\Tests\config\Kernel\ConfigDependencyTest.
    $this->configManager
      ->getConfigEntitiesToChangeOnDependencyRemoval('config', ['the_provider.the_config_prefix.foo'], FALSE)
      ->willReturn(['update' => [], 'delete' => [], 'unchanged' => []]);
    $this->configManager
      ->getConfigEntitiesToChangeOnDependencyRemoval('config', ['the_provider.the_config_prefix.bar'], FALSE)
      ->willReturn(['update' => [], 'delete' => [], 'unchanged' => []]);

    $entities = [];
    foreach (['foo', 'bar'] as $id) {
      $entity = $this->getMockEntity(['id' => $id]);
      $entities[] = $entity;

      $config_object = $this->prophesize(Config::class);
      $config_object->delete()->shouldBeCalled();

      $this->configFactory->getEditable("the_provider.the_config_prefix.$id")
        ->willReturn($config_object->reveal());

      $this->moduleHandler->invokeAll('test_entity_type_predelete', [$entity])
        ->shouldBeCalled();
      $this->moduleHandler->invokeAll('entity_predelete', [$entity, 'test_entity_type'])
        ->shouldBeCalled();

      $this->moduleHandler->invokeAll('test_entity_type_delete', [$entity])
        ->shouldBeCalled();
      $this->moduleHandler->invokeAll('entity_delete', [$entity, 'test_entity_type'])
        ->shouldBeCalled();
    }

    $this->cacheTagsInvalidator->invalidateTags([$this->entityTypeId . '_list'])
      ->shouldBeCalled();

    $this->entityStorage->delete($entities);
  }

  /**
   * @covers ::delete
   * @covers ::doDelete
   */
  public function testDeleteNothing() {
    $this->moduleHandler->getImplementations(Argument::cetera())->shouldNotBeCalled();
    $this->moduleHandler->invokeAll(Argument::cetera())->shouldNotBeCalled();

    $this->configFactory->get(Argument::cetera())->shouldNotBeCalled();
    $this->configFactory->getEditable(Argument::cetera())->shouldNotBeCalled();

    $this->cacheTagsInvalidator->invalidateTags(Argument::cetera())->shouldNotBeCalled();

    $this->entityStorage->delete([]);
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
  public function getMockEntity(array $values = [], $methods = []) {
    return $this->getMockForAbstractClass(ConfigEntityBase::class, [$values, 'test_entity_type'], '', TRUE, TRUE, TRUE, $methods);
  }

}

namespace Drupal\Core\Config\Entity;

if (!defined('SAVED_NEW')) {
  define('SAVED_NEW', 1);
}
if (!defined('SAVED_UPDATED')) {
  define('SAVED_UPDATED', 2);
}
