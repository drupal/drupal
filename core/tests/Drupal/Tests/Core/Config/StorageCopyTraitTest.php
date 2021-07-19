<?php

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

  protected static $useComparer;

  /**
   * {@inheritdoc}
   */
  protected static function shouldUseStorageComparer(StorageInterface $source, StorageInterface $target, string $collection = NULL, bool $optimistic = TRUE): bool {
    // We test both branches in replaceStorageContents by explicitly selecting it.
    return static::$useComparer;
  }

  /**
   * @covers ::replaceStorageContents
   *
   * @dataProvider providerTestReplaceStorageContents
   */
  public function testReplaceStorageContents($source_collections, $target_collections, $use_comparer) {
    static::$useComparer = $use_comparer;
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
  public function providerTestReplaceStorageContents() {
    $data = [];
    $data[] = [TRUE, TRUE, TRUE];
    $data[] = [TRUE, TRUE, FALSE];
    $data[] = [TRUE, FALSE, TRUE];
    $data[] = [TRUE, FALSE, FALSE];
    $data[] = [FALSE, TRUE, TRUE];
    $data[] = [FALSE, TRUE, FALSE];
    $data[] = [FALSE, FALSE, TRUE];
    $data[] = [FALSE, FALSE, FALSE];

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

  /**
   * Tests replaceStorageContents() with config with an invalid configuration.
   *
   * @covers ::replaceStorageContents
   */
  public function testWithInvalidConfiguration() {
    static::$useComparer = FALSE;
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

  /**
   * Tests the default implementation of shouldUseStorageComparer().
   *
   * @covers ::shouldUseStorageComparer
   */
  public function testShouldUseStorageComparer() {
    $empty = new MemoryStorage();
    $default = new MemoryStorage();
    $this->generateRandomData($default, FALSE);
    $collections = new MemoryStorage();
    $this->generateRandomData($collections, TRUE);

    // When copying to an empty storage, it should not use the comparer.
    $this->assertFalse(TestDecider::usesComparer($default, $empty));
    $this->assertFalse(TestDecider::usesComparer($collections, $empty));

    // When copying storages that have the same content it should use it.
    $this->assertTrue(TestDecider::usesComparer($default, $default));
    $this->assertTrue(TestDecider::usesComparer($collections, $collections));
  }

}

/**
 * Provides a test implementation of \Drupal\Core\Config\StorageInterface.
 */
class TestStorage extends MemoryStorage {

  /**
   * Provides a setter to bypass the array typehint on ::write().
   *
   * This method allows us to create invalid configurations. The method
   * ::write() only allows values of the type array.
   */
  public function setValue($name, $value) {
    $this->config[$this->collection][$name] = $value;
  }

}

/**
 * Uses the trait under test and makes the decider method public.
 */
class TestDecider {
  use StorageCopyTrait;

  /**
   * Make the protected method public under a new name.
   *
   * @param \Drupal\Core\Config\StorageInterface $source
   *   The configuration storage to copy from.
   * @param \Drupal\Core\Config\StorageInterface $target
   *   The configuration storage to copy to.
   * @param string|null $collection
   *   The collection name to check, null to check all collections.
   * @param bool $optimistic
   *   True for when the StorageComparer is already set up anyway.
   *
   * @return bool
   *   Whether or not to use the StorageComparer for making the storages equal.
   */
  public static function usesComparer(StorageInterface $source, StorageInterface $target, string $collection = NULL, bool $optimistic = TRUE) {
    return static::shouldUseStorageComparer($source, $target, $collection, $optimistic);
  }

}
