<?php

namespace Drupal\Tests\layout_builder\Unit;

use Drupal\Core\TempStore\SharedTempStore;
use Drupal\Core\TempStore\SharedTempStoreFactory;
use Drupal\layout_builder\LayoutTempstoreRepository;
use Drupal\layout_builder\SectionStorageInterface;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\layout_builder\LayoutTempstoreRepository
 * @group layout_builder
 */
class LayoutTempstoreRepositoryTest extends UnitTestCase {

  /**
   * @covers ::get
   * @covers ::has
   */
  public function testGetEmptyTempstore() {
    $section_storage = $this->prophesize(SectionStorageInterface::class);
    $section_storage->getStorageType()->willReturn('my_storage_type');
    $section_storage->getStorageId()->willReturn('my_storage_id');

    $tempstore = $this->prophesize(SharedTempStore::class);
    $tempstore->get('my_storage_id')->shouldBeCalled();

    $tempstore_factory = $this->prophesize(SharedTempStoreFactory::class);
    $tempstore_factory->get('layout_builder.section_storage.my_storage_type')->willReturn($tempstore->reveal());

    $repository = new LayoutTempstoreRepository($tempstore_factory->reveal());

    $this->assertFalse($repository->has($section_storage->reveal()));

    $result = $repository->get($section_storage->reveal());
    $this->assertSame($section_storage->reveal(), $result);
  }

  /**
   * @covers ::get
   * @covers ::has
   */
  public function testGetLoadedTempstore() {
    $section_storage = $this->prophesize(SectionStorageInterface::class);
    $section_storage->getStorageType()->willReturn('my_storage_type');
    $section_storage->getStorageId()->willReturn('my_storage_id');

    $tempstore_section_storage = $this->prophesize(SectionStorageInterface::class);
    $tempstore = $this->prophesize(SharedTempStore::class);
    $tempstore->get('my_storage_id')->willReturn(['section_storage' => $tempstore_section_storage->reveal()]);
    $tempstore_factory = $this->prophesize(SharedTempStoreFactory::class);
    $tempstore_factory->get('layout_builder.section_storage.my_storage_type')->willReturn($tempstore->reveal());

    $repository = new LayoutTempstoreRepository($tempstore_factory->reveal());

    $this->assertTrue($repository->has($section_storage->reveal()));

    $result = $repository->get($section_storage->reveal());
    $this->assertSame($tempstore_section_storage->reveal(), $result);
    $this->assertNotSame($section_storage->reveal(), $result);
  }

  /**
   * @covers ::get
   */
  public function testGetInvalidEntry() {
    $section_storage = $this->prophesize(SectionStorageInterface::class);
    $section_storage->getStorageType()->willReturn('my_storage_type');
    $section_storage->getStorageId()->willReturn('my_storage_id');

    $tempstore = $this->prophesize(SharedTempStore::class);
    $tempstore->get('my_storage_id')->willReturn(['section_storage' => 'this_is_not_an_entity']);

    $tempstore_factory = $this->prophesize(SharedTempStoreFactory::class);
    $tempstore_factory->get('layout_builder.section_storage.my_storage_type')->willReturn($tempstore->reveal());

    $repository = new LayoutTempstoreRepository($tempstore_factory->reveal());

    $this->expectException(\UnexpectedValueException::class);
    $this->expectExceptionMessage('The entry with storage type "my_storage_type" and ID "my_storage_id" is invalid');
    $repository->get($section_storage->reveal());
  }

}
