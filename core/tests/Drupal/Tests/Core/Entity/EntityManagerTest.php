<?php

namespace Drupal\Tests\Core\Entity;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityManager;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityTypeRepositoryInterface;
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
   * The entity display repository.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface|\Prophecy\Prophecy\ProphecyInterface
   */
  protected $entityRepository;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->entityTypeManager = $this->prophesize(EntityTypeManagerInterface::class);
    $this->entityTypeRepository = $this->prophesize(EntityTypeRepositoryInterface::class);
    $this->entityTypeBundleInfo = $this->prophesize(EntityTypeBundleInfoInterface::class);
    $this->entityFieldManager = $this->prophesize(EntityFieldManagerInterface::class);
    $this->entityRepository = $this->prophesize(EntityRepositoryInterface::class);

    $container = new ContainerBuilder();
    $container->set('entity_type.manager', $this->entityTypeManager->reveal());
    $container->set('entity_type.repository', $this->entityTypeRepository->reveal());
    $container->set('entity_type.bundle.info', $this->entityTypeBundleInfo->reveal());
    $container->set('entity_field.manager', $this->entityFieldManager->reveal());
    $container->set('entity.repository', $this->entityRepository->reveal());

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

}
