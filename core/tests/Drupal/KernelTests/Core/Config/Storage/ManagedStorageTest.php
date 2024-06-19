<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Config\Storage;

use Drupal\Core\Config\StorageManagerInterface;
use Drupal\Core\Config\ManagedStorage;
use Drupal\Core\Config\MemoryStorage;

/**
 * Tests ManagedStorage operations.
 *
 * @group config
 */
class ManagedStorageTest extends ConfigStorageTestBase implements StorageManagerInterface {

  /**
   * {@inheritdoc}
   */
  public function getStorage() {
    // We return a new storage every time to make sure the managed storage
    // only calls this once and retains the configuration by itself.
    return new MemoryStorage();
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->storage = new ManagedStorage($this);
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
  public function testInvalidStorage(): void {
    $this->markTestSkipped('ManagedStorage cannot be invalid.');
  }

}
