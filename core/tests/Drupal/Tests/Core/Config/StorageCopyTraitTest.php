<?php

namespace Drupal\Tests\Core\Config;

use Drupal\Core\Config\MemoryStorage;
use Drupal\Core\Config\StorageCopyTrait;
use Drupal\Core\Config\StorageInterface;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\Core\Config\StorageCopyTrait
 * @group Config
 */
class StorageCopyTraitTest extends UnitTestCase {

  use StorageCopyTrait;

  /**
   * @covers ::replaceStorageContents
   *
   * @dataProvider providerTestReplaceStorageContents
   */
  public function testReplaceStorageContents($source_collections, $target_collections) {
    $source = new MemoryStorage();
    $target = new MemoryStorage();
    // Empty the storage should be the same.
    $this->assertArrayEquals(self::toArray($source), self::toArray($target));

    // When the source is populated, they are not the same any more.
    $this->generateRandomData($source, $source_collections);
    $this->assertNotEquals(self::toArray($source), self::toArray($target));

    // When the target is filled with random data they are also not the same.
    $this->generateRandomData($target, $target_collections);
    $this->assertNotEquals(self::toArray($source), self::toArray($target));

    // Set the active collection to a random one on both source and target.
    if ($source_collections) {
      $collections = $source->getAllCollectionNames();
      $source = $source->createCollection($collections[array_rand($collections)]);
    }
    if ($target_collections) {
      $collections = $target->getAllCollectionNames();
      $target = $target->createCollection($collections[array_rand($collections)]);
    }

    $source_data = self::toArray($source);
    $source_name = $source->getCollectionName();

    // After copying they are the same, this asserts that items not present
    // in the source get removed from the target.
    self::replaceStorageContents($source, $target);
    $this->assertArrayEquals($source_data, self::toArray($target));
    // Assert that the copy method did indeed not change the source.
    $this->assertArrayEquals($source_data, self::toArray($source));

    // Assert that the active collection is the same as the original source.
    $this->assertEquals($source_name, $source->getCollectionName());
    $this->assertEquals($source_name, $target->getCollectionName());
  }

  /**
   * Provides data for testCheckRequirements().
   */
  public function providerTestReplaceStorageContents() {
    $data = [];
    $data[] = [TRUE, TRUE];
    $data[] = [TRUE, FALSE];
    $data[] = [FALSE, TRUE];
    $data[] = [FALSE, FALSE];

    return $data;
  }

  /**
   * Get the protected config data out of a MemoryStorage.
   *
   * @param \Drupal\Core\Config\MemoryStorage $storage
   *   The config storage to extract the data from.
   *
   * @return array
   */
  protected static function toArray(MemoryStorage $storage) {
    $reflection = new \ReflectionObject($storage);
    $property = $reflection->getProperty('config');
    $property->setAccessible(TRUE);

    return $property->getValue($storage)->getArrayCopy();
  }

  /**
   * Generate random data in a config storage.
   *
   * @param \Drupal\Core\Config\StorageInterface $storage
   *   The storage to populate with random data.
   * @param bool $collections
   *   Add random collections or not.
   */
  protected function generateRandomData(StorageInterface $storage, $collections = TRUE) {
    $generator = $this->getRandomGenerator();
    for ($i = 0; $i < rand(2, 10); $i++) {
      $storage->write($this->randomMachineName(), (array) $generator->object());
    }
    if ($collections) {
      for ($i = 0; $i < rand(1, 5); $i++) {
        $collection = $storage->createCollection($this->randomMachineName());
        for ($i = 0; $i < rand(2, 10); $i++) {
          $collection->write($this->randomMachineName(), (array) $generator->object());
        }
      }
    }
  }

}
