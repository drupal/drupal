<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Config;

use Drupal\Core\Config\MemoryStorage;
use Drupal\Core\Config\StorageCopyTrait;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Tests\UnitTestCase;
use Prophecy\Argument;

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
  public function testReplaceStorageContents($source_collections, $target_collections): void {
    $source = new MemoryStorage();
    $target = new MemoryStorage();
    // Empty the storage should be the same.
    $this->assertEquals(self::toArray($source), self::toArray($target));

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
    $this->assertEquals($source_data, self::toArray($target));
    // Assert that the copy method did indeed not change the source.
    $this->assertEquals($source_data, self::toArray($source));

    // Assert that the active collection is the same as the original source.
    $this->assertEquals($source_name, $source->getCollectionName());
    $this->assertEquals($source_name, $target->getCollectionName());
  }

  /**
   * Provides data for testCheckRequirements().
   */
  public static function providerTestReplaceStorageContents() {
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

  /**
   * Tests replaceStorageContents() with config with an invalid configuration.
   *
   * @covers ::replaceStorageContents
   */
  public function testWithInvalidConfiguration(): void {
    $source = new TestStorage();
    $this->generateRandomData($source);

    // Get a name from the source config storage and set the config value to
    // false. It mimics a config storage read return value when that config
    // storage has an invalid configuration.
    $names = $source->listAll();
    $test_name = reset($names);
    $source->setValue($test_name, FALSE);

    $logger_factory = $this->prophesize(LoggerChannelFactoryInterface::class);
    $container = new ContainerBuilder();
    $container->set('logger.factory', $logger_factory->reveal());
    \Drupal::setContainer($container);

    // Reading a config storage with an invalid configuration logs a notice.
    $channel = $this->prophesize(LoggerChannelInterface::class);
    $logger_factory->get('config')->willReturn($channel->reveal());
    $channel->notice('Missing required data for configuration: %config', Argument::withEntry('%config', $test_name))->shouldBeCalled();

    // Copy the config from the source storage to the target storage.
    $target = new TestStorage();
    self::replaceStorageContents($source, $target);

    // Test that all configuration is copied correctly and that the value of the
    // config with the invalid configuration has not been copied to the target
    // storage.
    foreach ($names as $name) {
      if ($name === $test_name) {
        $this->assertFalse($source->read($name));
        $this->assertFalse($target->exists($name));
      }
      else {
        $this->assertEquals($source->read($name), $target->read($name));
      }
    }

    // Test that the invalid configuration's name is in the source config
    // storage, but not the target config storage. This ensures that it was not
    // copied.
    $this->assertContains($test_name, $source->listAll());
    $this->assertNotContains($test_name, $target->listAll());
  }

}

/**
 * Provides a test implementation of \Drupal\Core\Config\StorageInterface.
 */
class TestStorage extends MemoryStorage {

  /**
   * Provides a setter to bypass the array type hint on ::write().
   *
   * This method allows us to create invalid configurations. The method
   * ::write() only allows values of the type array.
   */
  public function setValue($name, $value) {
    $this->config[$this->collection][$name] = $value;
  }

}
