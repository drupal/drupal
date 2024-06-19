<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Config;

use Drupal\Core\Config\NullStorage;
use Drupal\Core\Config\StorageInterface;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the NullStorage.
 *
 * @group Config
 */
class NullStorageTest extends UnitTestCase {

  /**
   * Tests createCollection.
   */
  public function testCollection(): void {
    $nullStorage = new NullStorage();
    $collection = $nullStorage->createCollection('test');
    $this->assertInstanceOf(StorageInterface::class, $collection);
    $this->assertEquals(StorageInterface::DEFAULT_COLLECTION, $nullStorage->getCollectionName());
    $this->assertEquals('test', $collection->getCollectionName());
    $this->assertSame([], $collection->getAllCollectionNames());
  }

}
