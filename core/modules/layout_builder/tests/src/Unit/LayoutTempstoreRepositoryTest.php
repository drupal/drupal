<?php

namespace Drupal\Tests\layout_builder\Unit;

use Drupal\layout_builder\LayoutTempstoreRepository;
use Drupal\layout_builder\Section;
use Drupal\layout_builder\SectionStorageInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\Core\TempStore\SharedTempStore;
use Drupal\Core\TempStore\SharedTempStoreFactory;

/**
 * @coversDefaultClass \Drupal\layout_builder\LayoutTempstoreRepository
 * @group layout_builder
 */
class LayoutTempstoreRepositoryTest extends UnitTestCase {

  /**
   * @covers ::get
   */
  public function testGetEmptyTempstore() {
    $section_storage = new TestSectionStorage();

    $tempstore = $this->prophesize(SharedTempStore::class);
    $tempstore->get('my_storage_id')->shouldBeCalled();

    $tempstore_factory = $this->prophesize(SharedTempStoreFactory::class);
    $tempstore_factory->get('layout_builder.section_storage.my_storage_type')->willReturn($tempstore->reveal());

    $repository = new LayoutTempstoreRepository($tempstore_factory->reveal());

    $result = $repository->get($section_storage);
    $this->assertSame($section_storage, $result);
  }

  /**
   * @covers ::get
   */
  public function testGetLoadedTempstore() {
    $section_storage = new TestSectionStorage();

    $tempstore_section_storage = new TestSectionStorage();
    $tempstore = $this->prophesize(SharedTempStore::class);
    $tempstore->get('my_storage_id')->willReturn(['section_storage' => $tempstore_section_storage]);
    $tempstore_factory = $this->prophesize(SharedTempStoreFactory::class);
    $tempstore_factory->get('layout_builder.section_storage.my_storage_type')->willReturn($tempstore->reveal());

    $repository = new LayoutTempstoreRepository($tempstore_factory->reveal());

    $result = $repository->get($section_storage);
    $this->assertSame($tempstore_section_storage, $result);
    $this->assertNotSame($section_storage, $result);
  }

  /**
   * @covers ::get
   */
  public function testGetInvalidEntry() {
    $section_storage = new TestSectionStorage();

    $tempstore = $this->prophesize(SharedTempStore::class);
    $tempstore->get('my_storage_id')->willReturn(['section_storage' => 'this_is_not_an_entity']);

    $tempstore_factory = $this->prophesize(SharedTempStoreFactory::class);
    $tempstore_factory->get('layout_builder.section_storage.my_storage_type')->willReturn($tempstore->reveal());

    $repository = new LayoutTempstoreRepository($tempstore_factory->reveal());

    $this->setExpectedException(\UnexpectedValueException::class, 'The entry with storage type "my_storage_type" and ID "my_storage_id" is invalid');
    $repository->get($section_storage);
  }

}

/**
 * Provides a test implementation of section storage.
 *
 * @todo This works around https://github.com/phpspec/prophecy/issues/119.
 */
class TestSectionStorage implements SectionStorageInterface {

  /**
   * {@inheritdoc}
   */
  public static function getStorageType() {
    return 'my_storage_type';
  }

  /**
   * {@inheritdoc}
   */
  public function getStorageId() {
    return 'my_storage_id';
  }

  /**
   * {@inheritdoc}
   */
  public function count() {}

  /**
   * {@inheritdoc}
   */
  public function getSections() {}

  /**
   * {@inheritdoc}
   */
  public function getSection($delta) {}

  /**
   * {@inheritdoc}
   */
  public function appendSection(Section $section) {}

  /**
   * {@inheritdoc}
   */
  public function insertSection($delta, Section $section) {}

  /**
   * {@inheritdoc}
   */
  public function removeSection($delta) {}

  /**
   * {@inheritdoc}
   */
  public function getContexts() {}

  /**
   * {@inheritdoc}
   */
  public function label() {}

  /**
   * {@inheritdoc}
   */
  public function save() {}

  /**
   * {@inheritdoc}
   */
  public function getCanonicalUrl() {}

  /**
   * {@inheritdoc}
   */
  public function getLayoutBuilderUrl() {}

}
