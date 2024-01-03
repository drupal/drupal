<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Config;

use Drupal\Core\Config\MemoryStorage;
use Drupal\Core\Config\ReadOnlyStorage;
use Drupal\Core\Config\StorageCopyTrait;
use Drupal\Core\Config\StorageInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\TestTools\Random;

/**
 * @coversDefaultClass \Drupal\Core\Config\ReadOnlyStorage
 * @group Config
 */
class ReadOnlyStorageTest extends UnitTestCase {

  use StorageCopyTrait;

  /**
   * The memory storage containing the data.
   *
   * @var \Drupal\Core\Config\MemoryStorage
   */
  protected $memory;

  /**
   * The read-only storage under test.
   *
   * @var \Drupal\Core\Config\ReadOnlyStorage
   */
  protected $storage;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Set up a memory storage we can manipulate to set fixtures.
    $this->memory = new MemoryStorage();
    // Wrap the memory storage in the read-only storage to test it.
    $this->storage = new ReadOnlyStorage($this->memory);
  }

  /**
   * @covers ::exists
   * @covers ::read
   * @covers ::readMultiple
   * @covers ::listAll
   *
   * @dataProvider readMethodsProvider
   */
  public function testReadOperations($method, $arguments, $fixture) {
    $this->setRandomFixtureConfig($fixture);

    $expected = call_user_func_array([$this->memory, $method], $arguments);
    $actual = call_user_func_array([$this->storage, $method], $arguments);
    $this->assertEquals($expected, $actual);
  }

  /**
   * Provide the methods that work transparently.
   *
   * @return array
   *   The data.
   */
  public function readMethodsProvider() {
    $fixture = [
      StorageInterface::DEFAULT_COLLECTION => ['config.a', 'config.b', 'other.a'],
    ];

    $data = [];
    $data[] = ['exists', ['config.a'], $fixture];
    $data[] = ['exists', ['not.existing'], $fixture];
    $data[] = ['read', ['config.a'], $fixture];
    $data[] = ['read', ['not.existing'], $fixture];
    $data[] = ['readMultiple', [['config.a', 'config.b', 'not']], $fixture];
    $data[] = ['listAll', [''], $fixture];
    $data[] = ['listAll', ['config'], $fixture];
    $data[] = ['listAll', ['none'], $fixture];

    return $data;
  }

  /**
   * @covers ::write
   * @covers ::delete
   * @covers ::rename
   * @covers ::deleteAll
   *
   * @dataProvider writeMethodsProvider
   */
  public function testWriteOperations($method, $arguments, $fixture) {
    $this->setRandomFixtureConfig($fixture);

    // Create an independent memory storage as a backup.
    $backup = new MemoryStorage();
    static::replaceStorageContents($this->memory, $backup);

    try {
      call_user_func_array([$this->storage, $method], $arguments);
      $this->fail("exception not thrown");
    }
    catch (\BadMethodCallException $exception) {
      $this->assertEquals(ReadOnlyStorage::class . '::' . $method . ' is not allowed on a ReadOnlyStorage', $exception->getMessage());
    }

    // Assert that the memory storage has not been altered.
    $this->assertEquals($backup, $this->memory);
  }

  /**
   * Provide the methods that throw an exception.
   *
   * @return array
   *   The data
   */
  public static function writeMethodsProvider() {
    $fixture = [
      StorageInterface::DEFAULT_COLLECTION => ['config.a', 'config.b'],
    ];

    $data = [];
    $data[] = ['write', ['config.a', (array) Random::getGenerator()->object()], $fixture];
    $data[] = ['write', [Random::MachineName(), (array) Random::getGenerator()->object()], $fixture];
    $data[] = ['delete', ['config.a'], $fixture];
    $data[] = ['delete', [Random::MachineName()], $fixture];
    $data[] = ['rename', ['config.a', 'config.b'], $fixture];
    $data[] = ['rename', ['config.a', Random::MachineName()], $fixture];
    $data[] = ['rename', [Random::MachineName(), Random::MachineName()], $fixture];
    $data[] = ['deleteAll', [''], $fixture];
    $data[] = ['deleteAll', ['config'], $fixture];
    $data[] = ['deleteAll', ['other'], $fixture];

    return $data;
  }

  /**
   * @covers ::getAllCollectionNames
   * @covers ::getCollectionName
   * @covers ::createCollection
   */
  public function testCollections() {
    $fixture = [
      StorageInterface::DEFAULT_COLLECTION => [$this->randomMachineName()],
      'A' => [$this->randomMachineName()],
      'B' => [$this->randomMachineName()],
      'C' => [$this->randomMachineName()],
    ];
    $this->setRandomFixtureConfig($fixture);

    $this->assertEquals(['A', 'B', 'C'], $this->storage->getAllCollectionNames());
    foreach (array_keys($fixture) as $collection) {
      $storage = $this->storage->createCollection($collection);
      // Assert that the collection storage is still a read-only storage.
      $this->assertInstanceOf(ReadOnlyStorage::class, $storage);
      $this->assertEquals($collection, $storage->getCollectionName());
    }
  }

  /**
   * @covers ::encode
   * @covers ::decode
   */
  public function testEncodeDecode() {
    $array = (array) $this->getRandomGenerator()->object();
    $string = $this->getRandomGenerator()->string();

    // Assert reversibility of encoding and decoding.
    $this->assertEquals($array, $this->storage->decode($this->storage->encode($array)));
    $this->assertEquals($string, $this->storage->encode($this->storage->decode($string)));
    // Assert same results as the decorated storage.
    $this->assertEquals($this->memory->encode($array), $this->storage->encode($array));
    $this->assertEquals($this->memory->decode($string), $this->storage->decode($string));
  }

  /**
   * Generate random config in the memory storage.
   *
   * @param array $config
   *   The config keys, keyed by the collection.
   */
  protected function setRandomFixtureConfig($config) {
    // Erase previous fixture.
    foreach (array_merge([StorageInterface::DEFAULT_COLLECTION], $this->memory->getAllCollectionNames()) as $collection) {
      $this->memory->createCollection($collection)->deleteAll();
    }

    foreach ($config as $collection => $keys) {
      $storage = $this->memory->createCollection($collection);
      foreach ($keys as $key) {
        // Create some random config.
        $storage->write($key, (array) $this->getRandomGenerator()->object());
      }
    }
  }

}
