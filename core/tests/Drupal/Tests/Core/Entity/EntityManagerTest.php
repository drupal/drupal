<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Entity\EntityManagerTest.
 */

namespace Drupal\Tests\Core\Entity;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityManager;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityTypeRepositoryInterface;
use Drupal\Tests\UnitTestCase;
use Prophecy\Argument;

/**
 * @coversDefaultClass \Drupal\Core\Entity\EntityManager
 * @group Entity
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
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->entityTypeManager = $this->prophesize(EntityTypeManagerInterface::class);
    $this->entityTypeRepository = $this->prophesize(EntityTypeRepositoryInterface::class);
    $this->entityTypeBundleInfo = $this->prophesize(EntityTypeBundleInfoInterface::class);
    $this->entityFieldManager = $this->prophesize(EntityFieldManagerInterface::class);

    $container = new ContainerBuilder();
    $container->set('entity_type.manager', $this->entityTypeManager->reveal());
    $container->set('entity_type.repository', $this->entityTypeRepository->reveal());
    $container->set('entity_type.bundle.info', $this->entityTypeBundleInfo->reveal());
    $container->set('entity_field.manager', $this->entityFieldManager->reveal());

    $this->entityManager = new EntityManager();
    $this->entityManager->setContainer($container);
  }

  /**
   * Tests the clearCachedDefinitions() method.
   *
   * @covers ::clearCachedDefinitions
   */
  public function testClearCachedDefinitions() {
    $this->entityTypeManager->clearCachedDefinitions()->shouldBeCalled();
    $this->entityTypeRepository->clearCachedDefinitions()->shouldBeCalled();
    $this->entityTypeBundleInfo->clearCachedBundles()->shouldBeCalled();
    $this->entityFieldManager->clearCachedFieldDefinitions()->shouldBeCalled();

    $this->entityManager->clearCachedDefinitions();
  }

}
