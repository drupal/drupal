<?php

namespace Drupal\Tests\Core\Entity;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\Display\EntityFormDisplayInterface;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\EntityAccessControlHandlerInterface;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityFieldManager;
use Drupal\Core\Entity\EntityFormInterface;
use Drupal\Core\Entity\EntityHandlerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityLastInstalledSchemaRepositoryInterface;
use Drupal\Core\Entity\EntityListBuilderInterface;
use Drupal\Core\Entity\EntityManager;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityType;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Entity\EntityTypeRepositoryInterface;
use Drupal\Core\Entity\EntityViewBuilderInterface;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\Core\Entity\EntityManager
 * @group Entity
 * @group legacy
 */
class EntityManagerTest extends UnitTestCase {

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManager
   */
  protected $entityManager;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface|\Prophecy\Prophecy\ProphecyInterface
   */
  protected $entityTypeManager;

  /**
   * The entity type repository.
   *
   * @var \Drupal\Core\Entity\EntityTypeRepositoryInterface|\Prophecy\Prophecy\ProphecyInterface
   */
  protected $entityTypeRepository;

  /**
   * The entity display repository.
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface|\Prophecy\Prophecy\ProphecyInterface
   */
  protected $entityDisplayRepository;

  /**
   * The entity type bundle info.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface|\Prophecy\Prophecy\ProphecyInterface
   */
  protected $entityTypeBundleInfo;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface|\Prophecy\Prophecy\ProphecyInterface
   */
  protected $entityFieldManager;

  /**
   * The entity repository.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface|\Prophecy\Prophecy\ProphecyInterface
   */
  protected $entityRepository;

  /**
   * The entity last installed schema repository.
   *
   * @var \Drupal\Core\Entity\EntityLastInstalledSchemaRepository|\Prophecy\Prophecy\ProphecyInterface
   */
  protected $entityLastInstalledSchemaRepository;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->entityTypeManager = $this->prophesize(EntityTypeManager::class);
    $this->entityTypeRepository = $this->prophesize(EntityTypeRepositoryInterface::class);
    $this->entityTypeBundleInfo = $this->prophesize(EntityTypeBundleInfoInterface::class);
    $this->entityFieldManager = $this->prophesize(EntityFieldManager::class);
    $this->entityRepository = $this->prophesize(EntityRepositoryInterface::class);
    $this->entityDisplayRepository = $this->prophesize(EntityDisplayRepositoryInterface::class);
    $this->entityLastInstalledSchemaRepository = $this->prophesize(EntityLastInstalledSchemaRepositoryInterface::class);

    $container = new ContainerBuilder();
    $container->set('entity_type.manager', $this->entityTypeManager->reveal());
    $container->set('entity_type.repository', $this->entityTypeRepository->reveal());
    $container->set('entity_type.bundle.info', $this->entityTypeBundleInfo->reveal());
    $container->set('entity_field.manager', $this->entityFieldManager->reveal());
    $container->set('entity.repository', $this->entityRepository->reveal());
    $container->set('entity_display.repository', $this->entityDisplayRepository->reveal());
    $container->set('entity.last_installed_schema.repository', $this->entityLastInstalledSchemaRepository->reveal());

    $this->entityManager = new EntityManager();
    $this->entityManager->setContainer($container);
  }

  /**
   * Tests the clearCachedDefinitions() method.
   *
   * @covers ::clearCachedDefinitions
   *
   * @expectedDeprecation EntityManagerInterface::clearCachedDefinitions() is deprecated in 8.0.0 and will be removed before Drupal 9.0.0. Use \Drupal\Core\Entity\EntityTypeManagerInterface::clearCachedDefinitions() instead. See https://www.drupal.org/node/2549139.
   */
  public function testClearCachedDefinitions() {
    $this->entityTypeManager->clearCachedDefinitions()->shouldBeCalled();
    $this->entityTypeRepository->clearCachedDefinitions()->shouldBeCalled();
    $this->entityTypeBundleInfo->clearCachedBundles()->shouldBeCalled();
    $this->entityFieldManager->clearCachedFieldDefinitions()->shouldBeCalled();

    $this->entityManager->clearCachedDefinitions();
  }

  /**
   * Tests the clearCachedFieldDefinitions() method.
   *
   * @covers ::clearCachedFieldDefinitions
   *
   * @expectedDeprecation EntityManagerInterface::clearCachedFieldDefinitions() is deprecated in drupal:8.0.0 and will be removed before drupal:9.0.0. Use \Drupal\Core\Entity\EntityFieldManagerInterface::clearCachedFieldDefinitions() instead. See https://www.drupal.org/node/2549139.
   */
  public function testClearCachedFieldDefinitions() {
    $this->entityFieldManager->clearCachedFieldDefinitions()->shouldBeCalled();
    $this->entityManager->clearCachedFieldDefinitions();
  }

  /**
   * Tests the getBaseFieldDefinitions() method.
   *
   * @covers ::getBaseFieldDefinitions
   *
   * @expectedDeprecation EntityManagerInterface::getBaseFieldDefinitions() is deprecated in drupal:8.0.0 and will be removed before drupal:9.0.0. Use \Drupal\Core\Entity\EntityFieldManagerInterface::getBaseFieldDefinitions() instead. See https://www.drupal.org/node/2549139.
   */
  public function testGetBaseFieldDefinitions() {
    $this->entityFieldManager->getBaseFieldDefinitions('node')->shouldBeCalled()->willReturn([]);
    $this->assertEquals([], $this->entityManager->getBaseFieldDefinitions('node'));
  }

  /**
   * Tests the getFieldDefinitions() method.
   *
   * @covers ::getFieldDefinitions
   *
   * @expectedDeprecation EntityManagerInterface::getFieldDefinitions() is deprecated in drupal:8.0.0 and will be removed before drupal:9.0.0. Use \Drupal\Core\Entity\EntityFieldManagerInterface::getFieldDefinitions() instead. See https://www.drupal.org/node/2549139.
   */
  public function testGetFieldDefinitions() {
    $this->entityFieldManager->getFieldDefinitions('node', 'article')->shouldBeCalled()->willReturn([]);
    $this->assertEquals([], $this->entityManager->getFieldDefinitions('node', 'article'));
  }

  /**
   * Tests the getFieldStorageDefinitions() method.
   *
   * @covers ::getFieldStorageDefinitions
   *
   * @expectedDeprecation EntityManagerInterface::getFieldStorageDefinitions() is deprecated in drupal:8.0.0 and will be removed before drupal:9.0.0. Use \Drupal\Core\Entity\EntityFieldManagerInterface::getFieldStorageDefinitions() instead. See https://www.drupal.org/node/2549139.
   */
  public function testGetFieldStorageDefinitions() {
    $this->entityFieldManager->getFieldStorageDefinitions('node')->shouldBeCalled()->willReturn([]);
    $this->assertEquals([], $this->entityManager->getFieldStorageDefinitions('node'));
  }

  /**
   * Tests the getFieldMap() method.
   *
   * @covers ::getFieldMap
   *
   * @expectedDeprecation EntityManagerInterface::getFieldMap() is deprecated in drupal:8.0.0 and will be removed before drupal:9.0.0. Use \Drupal\Core\Entity\EntityFieldManagerInterface::getFieldMap() instead. See https://www.drupal.org/node/2549139.
   */
  public function testGetFieldMap() {
    $this->entityFieldManager->getFieldMap()->shouldBeCalled()->willReturn([]);
    $this->assertEquals([], $this->entityManager->getFieldMap());
  }

  /**
   * Tests the setFieldMap() method.
   *
   * @covers ::setFieldMap
   *
   * @expectedDeprecation EntityManagerInterface::setFieldMap() is deprecated in drupal:8.0.0 and will be removed before drupal:9.0.0. Use \Drupal\Core\Entity\EntityFieldManagerInterface::setFieldMap() instead. See https://www.drupal.org/node/2549139.
   */
  public function testSetFieldMap() {
    $this->entityFieldManager->setFieldMap([])->shouldBeCalled();
    $this->entityManager->setFieldMap([]);
  }

  /**
   * Tests the getFieldMapByFieldType() method.
   *
   * @covers ::getFieldMapByFieldType
   *
   * @expectedDeprecation EntityManagerInterface::getFieldMapByFieldType() is deprecated in drupal:8.0.0 and will be removed before drupal:9.0.0. Use \Drupal\Core\Entity\EntityFieldManagerInterface::getFieldMapByFieldType() instead. See https://www.drupal.org/node/2549139.
   */
  public function testGetFieldMapByFieldType() {
    $this->entityFieldManager->getFieldMapByFieldType('node')->shouldBeCalled()->willReturn([]);
    $this->assertEquals([], $this->entityManager->getFieldMapByFieldType('node'));
  }

  /**
   * Tests the getExtraFields() method.
   *
   * @covers ::getExtraFields
   *
   * @expectedDeprecation EntityManagerInterface::getExtraFields() is deprecated in drupal:8.0.0 and will be removed before drupal:9.0.0. Use \Drupal\Core\Entity\EntityFieldManagerInterface::getExtraFields() instead. See https://www.drupal.org/node/2549139.
   */
  public function testGetExtraFields() {
    $this->entityFieldManager->getExtraFields('entity_type_id', 'bundle')->shouldBeCalled()->willReturn([]);
    $this->assertEquals([], $this->entityManager->getExtraFields('entity_type_id', 'bundle'));
  }

  /**
   * Tests the getBundleInfo() method.
   *
   * @covers ::getBundleInfo
   *
   * @expectedDeprecation EntityManagerInterface::getBundleInfo() is deprecated in drupal:8.0.0 and will be removed before drupal:9.0.0. Use \Drupal\Core\Entity\EntityTypeBundleInfoInterface::getBundleInfo() instead. See https://www.drupal.org/node/2549139.
   */
  public function testGetBundleInfo() {
    $return = ['article' => ['label' => 'Article']];
    $this->entityTypeBundleInfo->getBundleInfo('node')->shouldBeCalled()->willReturn($return);

    $this->assertEquals($return, $this->entityManager->getBundleInfo('node'));
  }

  /**
   * Tests the getAllBundleInfo() method.
   *
   * @covers ::getAllBundleInfo
   *
   * @expectedDeprecation EntityManagerInterface::getAllBundleInfo() is deprecated in drupal:8.0.0 and will be removed before drupal:9.0.0. Use \Drupal\Core\Entity\EntityTypeBundleInfoInterface::getAllBundleInfo() instead. See https://www.drupal.org/node/2549139.
   */
  public function testGetAllBundleInfo() {
    $return = ['node' => ['article' => ['label' => 'Article']]];
    $this->entityTypeBundleInfo->getAllBundleInfo()->shouldBeCalled()->willReturn($return);
    $this->assertEquals($return, $this->entityManager->getAllBundleInfo());
  }

  /**
   * Tests the clearCachedBundles() method.
   *
   * @covers ::clearCachedBundles
   *
   * @expectedDeprecation EntityManagerInterface::clearCachedBundles() is deprecated in drupal:8.0.0 and will be removed before drupal:9.0.0. Use \Drupal\Core\Entity\EntityTypeBundleInfoInterface::clearCachedBundles() instead. See https://www.drupal.org/node/2549139.
   */
  public function testClearCachedBundles() {
    $this->entityTypeBundleInfo->clearCachedBundles()->shouldBeCalled();
    $this->entityManager->clearCachedBundles();
  }

  /**
   * Tests the getTranslationFromContext() method.
   *
   * @covers ::getTranslationFromContext
   *
   * @expectedDeprecation EntityManagerInterface::getTranslationFromContext() is deprecated in 8.0.0 and will be removed before Drupal 9.0.0. Use \Drupal\Core\Entity\EntityRepository::getTranslationFromContext() instead. See https://www.drupal.org/node/2549139.
   */
  public function testGetTranslationFromContext() {
    $entity = $this->prophesize(EntityInterface::class);
    $this->entityRepository->getTranslationFromContext($entity->reveal(), 'de', ['example' => 'context'])->shouldBeCalled();
    $this->entityManager->getTranslationFromContext($entity->reveal(), 'de', ['example' => 'context']);
  }

  /**
   * Tests the loadEntityByUuid() method.
   *
   * @covers ::loadEntityByUuid
   *
   * @expectedDeprecation EntityManagerInterface::loadEntityByUuid() is deprecated in 8.0.0 and will be removed before Drupal 9.0.0. Use \Drupal\Core\Entity\EntityRepository::loadEntityByUuid() instead. See https://www.drupal.org/node/2549139.
   */
  public function testLoadEntityByUuid() {
    $entity = $this->prophesize(EntityInterface::class);
    $this->entityRepository->loadEntityByUuid('entity_test', '9a9a3d06-5d27-493b-965d-7f9cb0115817')->shouldBeCalled()->willReturn($entity->reveal());

    $this->assertInstanceOf(EntityInterface::class, $this->entityManager->loadEntityByUuid('entity_test', '9a9a3d06-5d27-493b-965d-7f9cb0115817'));
  }

  /**
   * Tests the loadEntityByConfigTarget() method.
   *
   * @covers ::loadEntityByConfigTarget
   *
   * @expectedDeprecation EntityManagerInterface::loadEntityByConfigTarget() is deprecated in 8.0.0 and will be removed before Drupal 9.0.0. Use \Drupal\Core\Entity\EntityRepository::loadEntityByConfigTarget() instead. See https://www.drupal.org/node/2549139.
   */
  public function testLoadEntityByConfigTarget() {
    $entity = $this->prophesize(EntityInterface::class);
    $this->entityRepository->loadEntityByConfigTarget('config_test', 'test')->shouldBeCalled()->willReturn($entity->reveal());

    $this->assertInstanceOf(EntityInterface::class, $this->entityManager->loadEntityByConfigTarget('config_test', 'test'));
  }

  /**
   * Tests the getEntityTypeFromClass() method.
   *
   * @covers ::getEntityTypeFromClass
   *
   * @expectedDeprecation EntityManagerInterface::getEntityTypeFromClass() is deprecated in 8.0.0 and will be removed before Drupal 9.0.0. Use \Drupal\Core\Entity\EntityTypeRepositoryInterface::getEntityTypeFromClass() instead. See https://www.drupal.org/node/2549139.
   */
  public function testGetEntityTypeFromClass() {
    $class = '\Drupal\example\Entity\ExampleEntity';
    $this->entityTypeRepository->getEntityTypeFromClass($class)->shouldBeCalled()->willReturn('example_entity_type');

    $this->assertEquals('example_entity_type', $this->entityManager->getEntityTypeFromClass($class));
  }

  /**
   * Tests the getLastInstalledDefinition() method.
   *
   * @covers ::getLastInstalledDefinition
   *
   * @expectedDeprecation EntityManagerInterface::getLastInstalledDefinition() is deprecated in 8.0.0 and will be removed before Drupal 9.0.0. Use \Drupal\Core\Entity\EntityLastInstalledSchemaRepositoryInterface::getLastInstalledDefinition() instead. See https://www.drupal.org/node/2549139.
   */
  public function testGetLastInstalledDefinition() {
    $entity_type_id = 'example_entity_type';
    $entity_type = new EntityType(['id' => $entity_type_id]);
    $this->entityLastInstalledSchemaRepository->getLastInstalledDefinition($entity_type_id)->shouldBeCalled()->willReturn($entity_type);

    $this->assertEquals($entity_type, $this->entityManager->getLastInstalledDefinition($entity_type_id));
  }

  /**
   * Tests the getLastInstalledFieldStorageDefinitions() method.
   *
   * @covers ::getLastInstalledFieldStorageDefinitions
   *
   * @expectedDeprecation EntityManagerInterface::getLastInstalledFieldStorageDefinitions() is deprecated in 8.0.0 and will be removed before Drupal 9.0.0. Use \Drupal\Core\Entity\EntityLastInstalledSchemaRepositoryInterface::getLastInstalledFieldStorageDefinitions() instead. See https://www.drupal.org/node/2549139.
   */
  public function testGetLastInstalledFieldStorageDefinitions() {
    $entity_type_id = 'example_entity_type';
    $this->entityLastInstalledSchemaRepository->getLastInstalledFieldStorageDefinitions($entity_type_id)->shouldBeCalled()->willReturn([]);

    $this->assertEquals([], $this->entityManager->getLastInstalledFieldStorageDefinitions($entity_type_id));
  }

  /**
   * Tests the getAllViewModes() method.
   *
   * @covers ::getAllViewModes
   *
   * @expectedDeprecation EntityManagerInterface::getAllViewModes() is deprecated in Drupal 8.0.0 and will be removed before Drupal 9.0.0. Use \Drupal\Core\Entity\EntityDisplayRepositoryInterface::getAllViewModes() instead. See https://www.drupal.org/node/2549139.
   */
  public function testGetAllViewModes() {
    $this->entityDisplayRepository->getAllViewModes()->shouldBeCalled()->willReturn([]);
    $this->assertEquals([], $this->entityManager->getAllViewModes());
  }

  /**
   * Tests the getViewModes() method.
   *
   * @covers ::getViewModes
   *
   * @expectedDeprecation EntityManagerInterface::getViewModes() is deprecated in Drupal 8.0.0 and will be removed before Drupal 9.0.0. Use \Drupal\Core\Entity\EntityDisplayRepositoryInterface::getViewModes() instead. See https://www.drupal.org/node/2549139.
   */
  public function testGetViewModes() {
    $this->entityDisplayRepository->getViewModes('entity_type')->shouldBeCalled()->willReturn([]);
    $this->assertEquals([], $this->entityManager->getViewModes('entity_type'));
  }

  /**
   * Tests the getViewModeOptions() method.
   *
   * @covers ::getViewModeOptions
   *
   * @expectedDeprecation EntityManagerInterface::getViewModeOptions() is deprecated in Drupal 8.0.0 and will be removed before Drupal 9.0.0. Use \Drupal\Core\Entity\EntityDisplayRepositoryInterface::getViewModeOptions() instead. See https://www.drupal.org/node/2549139.
   */
  public function testGetViewModeOptions() {
    $this->entityDisplayRepository->getViewModeOptions('entity_type')->shouldBeCalled()->willReturn([]);
    $this->assertEquals([], $this->entityManager->getViewModeOptions('entity_type'));
  }

  /**
   * Tests the getViewModeOptionsByBundle() method.
   *
   * @covers ::getViewModeOptionsByBundle
   *
   * @expectedDeprecation EntityManagerInterface::getViewModeOptionsByBundle() is deprecated in Drupal 8.0.0 and will be removed before Drupal 9.0.0. Use \Drupal\Core\Entity\EntityDisplayRepositoryInterface::getViewModeOptionsByBundle() instead. See https://www.drupal.org/node/2549139.
   */
  public function testGetViewModeOptionsByBundle() {
    $this->entityDisplayRepository->getViewModeOptionsByBundle('entity_type', 'bundle')->shouldBeCalled()->willReturn([]);
    $this->assertEquals([], $this->entityManager->getViewModeOptionsByBundle('entity_type', 'bundle'));
  }

  /**
   * Tests the getAllFormModes() method.
   *
   * @covers ::getAllFormModes
   *
   * @expectedDeprecation EntityManagerInterface::getAllFormModes() is deprecated in Drupal 8.0.0 and will be removed before Drupal 9.0.0. Use \Drupal\Core\Entity\EntityDisplayRepositoryInterface::getAllFormModes() instead. See https://www.drupal.org/node/2549139.
   */
  public function testGetAllFormModes() {
    $this->entityDisplayRepository->getAllFormModes()->shouldBeCalled()->willReturn([]);
    $this->assertEquals([], $this->entityManager->getAllFormModes());
  }

  /**
   * Tests the getFormModes() method.
   *
   * @covers ::getFormModes
   *
   * @expectedDeprecation EntityManagerInterface::getFormModes() is deprecated in Drupal 8.0.0 and will be removed before Drupal 9.0.0. Use \Drupal\Core\Entity\EntityDisplayRepositoryInterface::getFormModes() instead. See https://www.drupal.org/node/2549139.
   */
  public function testGetFormModes() {
    $this->entityDisplayRepository->getFormModes('entity_type')->shouldBeCalled()->willReturn([]);
    $this->assertEquals([], $this->entityManager->getFormModes('entity_type'));
  }

  /**
   * Tests the getFormModeOptions() method.
   *
   * @covers ::getFormModeOptions
   *
   * @expectedDeprecation EntityManagerInterface::getFormModeOptions() is deprecated in Drupal 8.0.0 and will be removed before Drupal 9.0.0. Use \Drupal\Core\Entity\EntityDisplayRepositoryInterface::getFormModeOptions() instead. See https://www.drupal.org/node/2549139.
   */
  public function testGetFormModeOptions() {
    $this->entityDisplayRepository->getFormModeOptions('entity_type')->shouldBeCalled()->willReturn([]);
    $this->assertEquals([], $this->entityManager->getFormModeOptions('entity_type'));
  }

  /**
   * Tests the getFormModeOptionsByBundle() method.
   *
   * @covers ::getFormModeOptionsByBundle
   *
   * @expectedDeprecation EntityManagerInterface::getFormModeOptionsByBundle() is deprecated in Drupal 8.0.0 and will be removed before Drupal 9.0.0. Use \Drupal\Core\Entity\EntityDisplayRepositoryInterface::getFormModeOptionsByBundle() instead. See https://www.drupal.org/node/2549139.
   */
  public function testGetFormModeOptionsByBundle() {
    $this->entityDisplayRepository->getFormModeOptionsByBundle('entity_type', 'bundle')->shouldBeCalled()->willReturn([]);
    $this->assertEquals([], $this->entityManager->getFormModeOptionsByBundle('entity_type', 'bundle'));
  }

  /**
   * Tests the clearDisplayModeInfo() method.
   *
   * @covers ::clearDisplayModeInfo
   *
   * @expectedDeprecation EntityManagerInterface::clearDisplayModeInfo() is deprecated in Drupal 8.0.0 and will be removed before Drupal 9.0.0. Use \Drupal\Core\Entity\EntityDisplayRepositoryInterface::clearDisplayModeInfo() instead. See https://www.drupal.org/node/2549139.
   */
  public function testClearDisplayModeInfo() {
    $this->entityDisplayRepository->clearDisplayModeInfo()->shouldBeCalled()->willReturn([]);
    $this->entityManager->clearDisplayModeInfo();
  }

  /**
   * Tests the useCaches() method.
   *
   * @covers ::useCaches
   *
   * @expectedDeprecation EntityManagerInterface::useCaches() is deprecated in 8.0.0 and will be removed before Drupal 9.0.0. Use \Drupal\Core\Entity\EntityTypeManagerInterface::useCaches() and/or Drupal\Core\Entity\EntityFieldManagerInterface::useCaches() instead. See https://www.drupal.org/node/2549139.
   */
  public function testUseCaches() {
    $this->entityTypeManager->useCaches(TRUE)->shouldBeCalled();
    $this->entityFieldManager->useCaches(TRUE)->shouldBeCalled();

    $this->entityManager->useCaches(TRUE);
  }

  /**
   * Tests the createInstance() method.
   *
   * @covers ::createInstance
   *
   * @expectedDeprecation EntityManagerInterface::createInstance() is deprecated in 8.0.0 and will be removed before Drupal 9.0.0. Use \Drupal\Core\Entity\EntityTypeManagerInterface::createInstance() instead. See https://www.drupal.org/node/2549139.
   */
  public function testCreateInstance() {
    $this->entityTypeManager->createInstance('plugin_id', ['example' => TRUE])->shouldBeCalled();

    $this->entityManager->createInstance('plugin_id', ['example' => TRUE]);
  }

  /**
   * Tests the getInstance() method.
   *
   * @covers ::getInstance
   *
   * @expectedDeprecation EntityManagerInterface::getInstance() is deprecated in 8.0.0 and will be removed before Drupal 9.0.0. Use \Drupal\Core\Entity\EntityTypeManagerInterface::getInstance() instead. See https://www.drupal.org/node/2549139.
   */
  public function testGetInstance() {
    $this->entityTypeManager->getInstance(['example' => TRUE])->shouldBeCalled();

    $this->entityManager->getInstance(['example' => TRUE]);
  }

  /**
   * Tests the getActive() method.
   *
   * @covers ::getActive
   *
   * @expectedDeprecation EntityManagerInterface::getActive() is deprecated in Drupal 8.7.0 and will be removed before Drupal 9.0.0. Use \Drupal\Core\Entity\EntityRepositoryInterface::getActive() instead. See https://www.drupal.org/node/2549139.
   */
  public function testGetActive() {
    $entity_type_id = 'entity_test';
    $entity_id = 0;
    $contexts = [];
    $this->entityRepository->getActive($entity_type_id, $entity_id, $contexts)->shouldBeCalled($entity_type_id, $entity_id, $contexts);
    $this->entityManager->getActive($entity_type_id, $entity_id, $contexts);
  }

  /**
   * Tests the getActiveMultiple() method.
   *
   * @covers ::getActiveMultiple
   *
   * @expectedDeprecation EntityManagerInterface::getActiveMultiple() is deprecated in Drupal 8.7.0 and will be removed before Drupal 9.0.0. Use \Drupal\Core\Entity\EntityRepositoryInterface::getActiveMultiple() instead. See https://www.drupal.org/node/2549139.
   */
  public function testActiveMultiple() {
    $entity_type_id = 'entity_test';
    $entity_ids = [0];
    $contexts = [];
    $this->entityRepository->getActiveMultiple($entity_type_id, $entity_ids, $contexts)->shouldBeCalled($entity_type_id, $entity_ids, $contexts);
    $this->entityManager->getActiveMultiple($entity_type_id, $entity_ids, $contexts);
  }

  /**
   * Tests the getCanonical() method.
   *
   * @covers ::getCanonical
   *
   * @expectedDeprecation EntityManagerInterface::getCanonical() is deprecated in Drupal 8.7.0 and will be removed before Drupal 9.0.0. Use \Drupal\Core\Entity\EntityRepositoryInterface::getCanonical() instead. See https://www.drupal.org/node/2549139.
   */
  public function testGetCanonical() {
    $entity_type_id = 'entity_test';
    $entity_id = '';
    $contexts = [];
    $this->entityRepository->getCanonical($entity_type_id, $entity_id, $contexts)->shouldBeCalled($entity_type_id, $entity_id, $contexts);
    $this->entityManager->getCanonical($entity_type_id, $entity_id, $contexts);
  }

  /**
   * Tests the getCanonicalMultiple() method.
   *
   * @covers ::getCanonicalMultiple
   *
   * @expectedDeprecation EntityManagerInterface::getCanonicalMultiple() is deprecated in Drupal 8.7.0 and will be removed before Drupal 9.0.0. Use \Drupal\Core\Entity\EntityRepositoryInterface::getCanonicalMultiple() instead. See https://www.drupal.org/node/2549139.
   */
  public function testGetCanonicalMultiple() {
    $entity_type_id = 'entity_test';
    $entity_ids = [0];
    $contexts = [];
    $this->entityRepository->getCanonicalMultiple($entity_type_id, $entity_ids, $contexts)->shouldBeCalled($entity_type_id, $entity_ids, $contexts);
    $this->entityManager->getCanonicalMultiple($entity_type_id, $entity_ids, $contexts);
  }

  /**
   * @covers ::getActiveDefinition
   *
   * @expectedDeprecation EntityManagerInterface::getActiveDefinition() is deprecated in 8.0.0 and will be removed before Drupal 9.0.0. Use \Drupal\Core\Entity\EntityTypeManagerInterface::getActiveDefinition() instead. See https://www.drupal.org/node/3040966.
   */
  public function testGetActiveDefinition() {
    $this->entityManager->getActiveDefinition('entity_test');
  }

  /**
   * @covers ::getActiveFieldStorageDefinitions
   *
   * @expectedDeprecation EntityManagerInterface::getActiveFieldStorageDefinitions() is deprecated in 8.0.0 and will be removed before Drupal 9.0.0. Use \Drupal\Core\Entity\EntityFieldManagerInterface::getActiveFieldStorageDefinitions() instead. See https://www.drupal.org/node/3040966.
   */
  public function testGetActiveFieldStorageDefinitions() {
    $this->entityManager->getActiveFieldStorageDefinitions('entity_test');
  }

  /**
   * @covers ::getViewDisplay
   *
   * @expectedDeprecation EntityManager::getViewDisplay() is deprecated in drupal:8.8.0 and will be removed before Drupal 9.0.0. Use \Drupal::service('entity_display.repository')->getViewDisplay() instead.
   */
  public function testGetViewDisplay() {
    $view_display = $this->prophesize(EntityViewDisplayInterface::class)->reveal();
    $this->entityDisplayRepository->getViewDisplay('entity_test', 'bundle', 'default')->shouldBeCalled()->willReturn($view_display);
    $this->assertInstanceOf(EntityViewDisplayInterface::class, $this->entityManager->getViewDisplay('entity_test', 'bundle', 'default'));
  }

  /**
   * @covers ::getFormDisplay
   *
   * @expectedDeprecation EntityManager::getFormDisplay() is deprecated in drupal:8.8.0 and will be removed before Drupal 9.0.0. Use \Drupal::service('entity_display.repository')->getFormDisplay() instead.
   */
  public function testGetFormDisplay() {
    $form_display = $this->prophesize(EntityFormDisplayInterface::class)->reveal();
    $this->entityDisplayRepository->getFormDisplay('entity_test', 'bundle', 'default')->shouldBeCalled()->willReturn($form_display);
    $this->assertInstanceOf(EntityFormDisplayInterface::class, $this->entityManager->getFormDisplay('entity_test', 'bundle', 'default'));
  }

  /**
   * @covers ::getDefinition
   *
   * @expectedDeprecation EntityManagerInterface::getDefinition() is deprecated in drupal:8.0.0 and is removed from drupal:9.0.0. Use \Drupal\Core\Entity\EntityTypeManager::getDefinition() instead. See https://www.drupal.org/node/2549139
   */
  public function testGetDefinition() {
    $entity_type = $this->prophesize(EntityTypeInterface::class)->reveal();
    $this->entityTypeManager->getDefinition('entity_test', TRUE)->shouldBeCalled()->willReturn($entity_type);
    $this->assertInstanceOf(EntityTypeInterface::class, $this->entityManager->getDefinition('entity_test'));
  }

  /**
   * @covers ::getDefinitions
   *
   * @expectedDeprecation EntityManagerInterface::getDefinitions() is deprecated in drupal:8.0.0 and is removed from drupal:9.0.0. Use \Drupal\Core\Entity\EntityTypeManager::getDefinitions() instead. See https://www.drupal.org/node/2549139
   */
  public function testGetDefinitions() {
    $this->entityTypeManager->getDefinitions()->shouldBeCalled()->willReturn([]);
    $this->assertEquals([], $this->entityManager->getDefinitions());
  }

  /**
   * @covers ::hasDefinition
   *
   * @expectedDeprecation EntityManagerInterface::hasDefinition() is deprecated in drupal:8.0.0 and is removed from drupal:9.0.0. Use \Drupal\Core\Entity\EntityTypeManager::hasDefinition() instead. See https://www.drupal.org/node/2549139
   */
  public function testHasDefinition() {
    $this->entityTypeManager->hasDefinition('entity_test')->shouldBeCalled()->willReturn(TRUE);
    $this->assertTrue($this->entityManager->hasDefinition('entity_test'));
  }

  /**
   * @covers ::getDefinitions
   *
   * @expectedDeprecation EntityManagerInterface::getRouteProviders() is deprecated in drupal:8.0.0 and is removed from drupal:9.0.0. Use \Drupal\Core\Entity\EntityTypeManager::getRouteProviders() instead. See https://www.drupal.org/node/2549139
   */
  public function testGetRouteProviders() {
    $this->entityTypeManager->getRouteProviders('entity_test')->shouldBeCalled()->willReturn([]);
    $this->assertEquals([], $this->entityManager->getRouteProviders('entity_test'));
  }

  /**
   * @covers ::hasHandler
   *
   * @expectedDeprecation EntityManagerInterface::hasHandler() is deprecated in drupal:8.0.0 and is removed from drupal:9.0.0. Use \Drupal\Core\Entity\EntityTypeManager::hasHandler() instead. See https://www.drupal.org/node/2549139
   */
  public function testHasHandler() {
    $this->entityTypeManager->hasHandler('entity_test', 'storage')->shouldBeCalled()->willReturn(TRUE);
    $this->assertTrue($this->entityManager->hasHandler('entity_test', 'storage'));
  }

  /**
   * @covers ::getStorage
   *
   * @expectedDeprecation EntityManagerInterface::getStorage() is deprecated in drupal:8.0.0 and is removed from drupal:9.0.0. Use \Drupal\Core\Entity\EntityTypeManager::getStorage() instead. See https://www.drupal.org/node/2549139
   */
  public function testGetStorage() {
    $storage = $this->prophesize(EntityStorageInterface::class)->reveal();
    $this->entityTypeManager->getStorage('entity_test')->shouldBeCalled()->willReturn($storage);
    $this->assertInstanceOf(EntityStorageInterface::class, $this->entityManager->getStorage('entity_test'));
  }

  /**
   * @covers ::getFormObject
   *
   * @expectedDeprecation EntityManagerInterface::getFormObject() is deprecated in drupal:8.0.0 and is removed from drupal:9.0.0. Use \Drupal\Core\Entity\EntityTypeManager::getFormObject() instead. See https://www.drupal.org/node/2549139
   */
  public function testGetFormObject() {
    $form_object = $this->prophesize(EntityFormInterface::class)->reveal();
    $this->entityTypeManager->getFormObject('entity_test', 'edit')->shouldBeCalled()->willReturn($form_object);
    $this->assertInstanceOf(EntityFormInterface::class, $this->entityManager->getFormObject('entity_test', 'edit'));
  }

  /**
   * @covers ::getListBuilder
   *
   * @expectedDeprecation EntityManagerInterface::getListBuilder() is deprecated in drupal:8.0.0 and is removed from drupal:9.0.0. Use \Drupal\Core\Entity\EntityTypeManager::getListBuilder() instead. See https://www.drupal.org/node/2549139
   */
  public function testGetListBuilder() {
    $list_builder = $this->prophesize(EntityListBuilderInterface::class)->reveal();
    $this->entityTypeManager->getListBuilder('entity_test')->shouldBeCalled()->willReturn($list_builder);
    $this->assertInstanceOf(EntityListBuilderInterface::class, $this->entityManager->getListBuilder('entity_test'));
  }

  /**
   * @covers ::getViewBuilder
   *
   * @expectedDeprecation EntityManagerInterface::getViewBuilder() is deprecated in drupal:8.0.0 and is removed from drupal:9.0.0. Use \Drupal\Core\Entity\EntityTypeManager::getViewBuilder() instead. See https://www.drupal.org/node/2549139
   */
  public function testGetViewBuilder() {
    $view_builder = $this->prophesize(EntityViewBuilderInterface::class)->reveal();
    $this->entityTypeManager->getViewBuilder('entity_test')->shouldBeCalled()->willReturn($view_builder);
    $this->assertInstanceOf(EntityViewBuilderInterface::class, $this->entityManager->getViewBuilder('entity_test'));
  }

  /**
   * @covers ::getAccessControlHandler
   *
   * @expectedDeprecation EntityManagerInterface::getAccessControlHandler() is deprecated in drupal:8.0.0 and is removed from drupal:9.0.0. Use \Drupal\Core\Entity\EntityTypeManager::getAccessControlHandler() instead. See https://www.drupal.org/node/2549139
   */
  public function testGetAccessControlHandler() {
    $access_control_handler = $this->prophesize(EntityAccessControlHandlerInterface::class)->reveal();
    $this->entityTypeManager->getAccessControlHandler('entity_test')->shouldBeCalled()->willReturn($access_control_handler);
    $this->assertInstanceOf(EntityAccessControlHandlerInterface::class, $this->entityManager->getAccessControlHandler('entity_test'));
  }

  /**
   * @covers ::getHandler
   *
   * @expectedDeprecation EntityManagerInterface::getHandler() is deprecated in drupal:8.0.0 and is removed from drupal:9.0.0. Use \Drupal\Core\Entity\EntityTypeManager::getHandler() instead. See https://www.drupal.org/node/2549139
   */
  public function testGetHandler() {
    $handler = $this->prophesize(EntityHandlerInterface::class)->reveal();
    $this->entityTypeManager->getHandler('entity_test', 'storage')->shouldBeCalled()->willReturn($handler);
    $this->assertInstanceOf(EntityHandlerInterface::class, $this->entityManager->getHandler('entity_test', 'storage'));
  }

  /**
   * @covers ::createHandlerInstance
   *
   * @expectedDeprecation EntityManagerInterface::createHandlerInstance() is deprecated in drupal:8.0.0 and is removed from drupal:9.0.0. Use \Drupal\Core\Entity\EntityTypeManager::createHandlerInstance() instead. See https://www.drupal.org/node/2549139
   */
  public function testCreateHandlerInstance() {
    $handler = $this->prophesize(EntityHandlerInterface::class)->reveal();
    $entity_type = $this->prophesize(EntityTypeInterface::class)->reveal();
    $this->entityTypeManager->createHandlerInstance(EntityHandlerInterface::class, $entity_type)->shouldBeCalled()->willReturn($handler);
    $this->assertInstanceOf(EntityHandlerInterface::class, $this->entityManager->createHandlerInstance(EntityHandlerInterface::class, $entity_type));
  }

}
