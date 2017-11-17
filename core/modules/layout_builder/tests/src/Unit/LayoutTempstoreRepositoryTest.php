<?php

namespace Drupal\Tests\layout_builder\Unit;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\RevisionableInterface;
use Drupal\Core\Language\Language;
use Drupal\layout_builder\LayoutTempstoreRepository;
use Drupal\Tests\UnitTestCase;
use Drupal\user\SharedTempStore;
use Drupal\user\SharedTempStoreFactory;

/**
 * @coversDefaultClass \Drupal\layout_builder\LayoutTempstoreRepository
 * @group layout_builder
 */
class LayoutTempstoreRepositoryTest extends UnitTestCase {

  /**
   * @covers ::getFromId
   * @covers ::get
   * @covers ::generateTempstoreId
   */
  public function testGetFromIdEmptyTempstore() {
    $tempstore = $this->prophesize(SharedTempStore::class);
    $tempstore->get('the_entity_id.en')->shouldBeCalled();

    $tempstore_factory = $this->prophesize(SharedTempStoreFactory::class);
    $tempstore_factory->get('the_entity_type_id.layout_builder__layout')->willReturn($tempstore->reveal());

    $entity = $this->prophesize(EntityInterface::class);
    $entity->getEntityTypeId()->willReturn('the_entity_type_id');
    $entity->id()->willReturn('the_entity_id');
    $entity->language()->willReturn(new Language(['id' => 'en']));

    $entity_storage = $this->prophesize(EntityStorageInterface::class);
    $entity_storage->loadRevision('the_entity_id')->willReturn($entity->reveal());

    $entity_type_manager = $this->prophesize(EntityTypeManagerInterface::class);
    $entity_type_manager->getStorage('the_entity_type_id')->willReturn($entity_storage->reveal());

    $repository = new LayoutTempstoreRepository($tempstore_factory->reveal(), $entity_type_manager->reveal());

    $result = $repository->getFromId('the_entity_type_id', 'the_entity_id');
    $this->assertSame($entity->reveal(), $result);
  }

  /**
   * @covers ::getFromId
   * @covers ::get
   * @covers ::generateTempstoreId
   */
  public function testGetFromIdLoadedTempstore() {
    $tempstore_entity = $this->prophesize(EntityInterface::class);
    $tempstore = $this->prophesize(SharedTempStore::class);
    $tempstore->get('the_entity_id.en')->willReturn(['entity' => $tempstore_entity->reveal()]);
    $tempstore_factory = $this->prophesize(SharedTempStoreFactory::class);
    $tempstore_factory->get('the_entity_type_id.layout_builder__layout')->willReturn($tempstore->reveal());

    $entity = $this->prophesize(EntityInterface::class);
    $entity->getEntityTypeId()->willReturn('the_entity_type_id');
    $entity->id()->willReturn('the_entity_id');
    $entity->language()->willReturn(new Language(['id' => 'en']));

    $entity_storage = $this->prophesize(EntityStorageInterface::class);
    $entity_storage->loadRevision('the_entity_id')->willReturn($entity->reveal());

    $entity_type_manager = $this->prophesize(EntityTypeManagerInterface::class);
    $entity_type_manager->getStorage('the_entity_type_id')->willReturn($entity_storage->reveal());

    $repository = new LayoutTempstoreRepository($tempstore_factory->reveal(), $entity_type_manager->reveal());

    $result = $repository->getFromId('the_entity_type_id', 'the_entity_id');
    $this->assertSame($tempstore_entity->reveal(), $result);
    $this->assertNotSame($entity->reveal(), $result);
  }

  /**
   * @covers ::getFromId
   * @covers ::get
   * @covers ::generateTempstoreId
   */
  public function testGetFromIdRevisionable() {
    $tempstore = $this->prophesize(SharedTempStore::class);
    $tempstore->get('the_entity_id.en.the_revision_id')->shouldBeCalled();

    $tempstore_factory = $this->prophesize(SharedTempStoreFactory::class);
    $tempstore_factory->get('the_entity_type_id.layout_builder__layout')->willReturn($tempstore->reveal());

    $entity = $this->prophesize(EntityInterface::class)->willImplement(RevisionableInterface::class);
    $entity->getEntityTypeId()->willReturn('the_entity_type_id');
    $entity->id()->willReturn('the_entity_id');
    $entity->language()->willReturn(new Language(['id' => 'en']));
    $entity->getRevisionId()->willReturn('the_revision_id');

    $entity_storage = $this->prophesize(EntityStorageInterface::class);
    $entity_storage->loadRevision('the_entity_id')->willReturn($entity->reveal());

    $entity_type_manager = $this->prophesize(EntityTypeManagerInterface::class);
    $entity_type_manager->getStorage('the_entity_type_id')->willReturn($entity_storage->reveal());

    $repository = new LayoutTempstoreRepository($tempstore_factory->reveal(), $entity_type_manager->reveal());

    $result = $repository->getFromId('the_entity_type_id', 'the_entity_id');
    $this->assertSame($entity->reveal(), $result);
  }

  /**
   * @covers ::get
   */
  public function testGetInvalidEntity() {
    $tempstore = $this->prophesize(SharedTempStore::class);
    $tempstore->get('the_entity_id.en')->willReturn(['entity' => 'this_is_not_an_entity']);

    $tempstore_factory = $this->prophesize(SharedTempStoreFactory::class);
    $tempstore_factory->get('the_entity_type_id.layout_builder__layout')->willReturn($tempstore->reveal());

    $entity_type_manager = $this->prophesize(EntityTypeManagerInterface::class);

    $repository = new LayoutTempstoreRepository($tempstore_factory->reveal(), $entity_type_manager->reveal());

    $entity = $this->prophesize(EntityInterface::class);
    $entity->language()->willReturn(new Language(['id' => 'en']));
    $entity->getEntityTypeId()->willReturn('the_entity_type_id');
    $entity->id()->willReturn('the_entity_id');

    $this->setExpectedException(\UnexpectedValueException::class, 'The entry with entity type "the_entity_type_id" and ID "the_entity_id.en" is not a valid entity');
    $repository->get($entity->reveal());
  }

}
