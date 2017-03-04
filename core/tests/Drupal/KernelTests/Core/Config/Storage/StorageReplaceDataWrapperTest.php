<?php

namespace Drupal\KernelTests\Core\Config\Storage;

use Drupal\config\StorageReplaceDataWrapper;
use Drupal\Core\Config\StorageInterface;

/**
 * Tests StorageReplaceDataWrapper operations.
 *
 * @group config
 */
class StorageReplaceDataWrapperTest extends ConfigStorageTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->storage = new StorageReplaceDataWrapper($this->container->get('config.storage'));
    // ::listAll() verifications require other configuration data to exist.
    $this->storage->write('system.performance', []);
    $this->storage->replaceData('system.performance', ['foo' => 'bar']);
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
  protected function insert($name, $data) {
    $this->storage->write($name, $data);
  }

  /**
   * {@inheritdoc}
   */
  protected function update($name, $data) {
    $this->storage->write($name, $data);
  }

  /**
   * {@inheritdoc}
   */
  protected function delete($name) {
    $this->storage->delete($name);
  }

  /**
   * {@inheritdoc}
   */
  public function testInvalidStorage() {
    // No-op as this test does not make sense.
  }

  /**
   * Tests if new collections created correctly.
   *
   * @param string $collection
   *   The collection name.
   *
   * @dataProvider providerCollections
   */
  public function testCreateCollection($collection) {
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
  public function providerCollections() {
    return [
      [StorageInterface::DEFAULT_COLLECTION],
      ['foo.bar'],
    ];
  }

}
