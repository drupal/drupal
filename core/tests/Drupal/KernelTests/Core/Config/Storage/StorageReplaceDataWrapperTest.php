<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Config\Storage;

use Drupal\config\StorageReplaceDataWrapper;
use Drupal\Core\Config\StorageInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests StorageReplaceDataWrapper operations.
 */
#[Group('config')]
#[RunTestsInSeparateProcesses]
class StorageReplaceDataWrapperTest extends ConfigStorageTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->storage = new StorageReplaceDataWrapper($this->container->get('config.storage'));
  }

  /**
   * {@inheritdoc}
   */
  protected function read($name) {
    return $this->storage->read($name);
  }

  /**
   * {@inheritdoc}
   */
  protected function insert($name, $data): void {
    $this->storage->write($name, $data);
  }

  /**
   * {@inheritdoc}
   */
  protected function update($name, $data): void {
    $this->storage->write($name, $data);
  }

  /**
   * {@inheritdoc}
   */
  protected function delete($name): void {
    $this->storage->delete($name);
  }

  /**
   * {@inheritdoc}
   */
  public function testInvalidStorage(): void {
    $this->markTestSkipped('No-op as this test does not make sense');
  }

  /**
   * Tests if new collections created correctly.
   *
   * @param string $collection
   *   The collection name.
   */
  #[DataProvider('providerCollections')]
  public function testCreateCollection($collection): void {
    $initial_collection_name = $this->storage->getCollectionName();

    // Create new storage with given collection and check it is set correctly.
    $new_storage = $this->storage->createCollection($collection);
    $this->assertSame($collection, $new_storage->getCollectionName());

    // Check collection not changed in the current storage instance.
    $this->assertSame($initial_collection_name, $this->storage->getCollectionName());
  }

  /**
   * Data provider for testing different collections.
   *
   * @return array
   *   Returns an array of collection names.
   */
  public static function providerCollections(): array {
    return [
      [StorageInterface::DEFAULT_COLLECTION],
      ['foo.bar'],
    ];
  }

}
