<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Config\Checkpoint;

use Drupal\Component\Datetime\Time;
use Drupal\Core\Cache\NullBackend;
use Drupal\Core\Config\Checkpoint\Checkpoint;
use Drupal\Core\Config\Checkpoint\LinearHistory;
use Drupal\Core\Config\Checkpoint\CheckpointStorage;
use Drupal\Core\Config\Config;
use Drupal\Core\Config\ConfigCrudEvent;
use Drupal\Core\Config\MemoryStorage;
use Drupal\Core\Config\StorageCopyTrait;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\KeyValueStore\KeyValueMemoryFactory;
use Drupal\Core\Lock\NullLockBackend;
use Drupal\Core\State\State;
use Drupal\Tests\UnitTestCase;
use Drupal\TestTools\Random;

/**
 * @coversDefaultClass \Drupal\Core\Config\Checkpoint\CheckpointStorage
 * @group Config
 */
class CheckpointStorageTest extends UnitTestCase {

  use StorageCopyTrait;

  /**
   * The memory storage containing the data.
   *
   * @var \Drupal\Core\Config\MemoryStorage
   */
  protected MemoryStorage $memory;

  /**
   * The checkpoint storage under test.
   *
   * @var \Drupal\Core\Config\Checkpoint\CheckpointStorage
   */
  protected CheckpointStorage $storage;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Set up a memory storage we can manipulate to set fixtures.
    $this->memory = new MemoryStorage();
    $keyValueMemoryFactory = new KeyValueMemoryFactory();
    $state = new State($keyValueMemoryFactory, new NullBackend('test'), new NullLockBackend());
    $time = new Time();
    $checkpoints = new LinearHistory($state, $time);
    $this->storage = new CheckpointStorage($this->memory, $checkpoints, $keyValueMemoryFactory);
  }

  /**
   * @covers ::checkpoint
   * @covers \Drupal\Core\Config\Checkpoint\Checkpoint
   */
  public function testCheckpointCreation(): void {
    $checkpoint = $this->storage->checkpoint('Test');
    $this->assertInstanceOf(Checkpoint::class, $checkpoint);
    $this->assertSame('Test', $checkpoint->label);

    $checkpoint2 = $this->storage->checkpoint('This will not make a checkpoint because nothing has changed');
    $this->assertSame($checkpoint2, $checkpoint);
    $config = $this->prophesize(Config::class);
    $config->getName()->willReturn('test.config');
    $config->getOriginal('', FALSE)->willReturn([]);
    $config->getRawData()->willReturn(['foo' => 'bar']);
    $config->getStorage()->willReturn($this->storage);
    $event = new ConfigCrudEvent($config->reveal());
    $this->storage->onConfigSaveAndDelete($event);

    $checkpoint3 = $this->storage->checkpoint('Created test.config');
    $this->assertNotSame($checkpoint3, $checkpoint);
    $this->assertSame('Created test.config', $checkpoint3->label);

    $checkpoint4 = $this->storage->checkpoint('This will not create a checkpoint either');
    $this->assertSame($checkpoint4, $checkpoint3);

    // Simulate a save with no change.
    $config = $this->prophesize(Config::class);
    $config->getName()->willReturn('test.config');
    $config->getOriginal('', FALSE)->willReturn(['foo' => 'bar']);
    $config->getRawData()->willReturn(['foo' => 'bar']);
    $config->getStorage()->willReturn($this->storage);
    $event = new ConfigCrudEvent($config->reveal());
    $this->storage->onConfigSaveAndDelete($event);

    $checkpoint5 = $this->storage->checkpoint('Save with no change');
    $this->assertSame($checkpoint5, $checkpoint3);

    // Create collection and ensure that checkpoints are kept in sync.
    $collection = $this->storage->createCollection('test');
    $config = $this->prophesize(Config::class);
    $config->getName()->willReturn('test.config');
    $config->getOriginal('', FALSE)->willReturn(['foo' => 'bar']);
    $config->getRawData()->willReturn(['foo' => 'collection_bar']);
    $config->getStorage()->willReturn($collection);
    $event = new ConfigCrudEvent($config->reveal());
    $collection->onConfigSaveAndDelete($event);

    $checkpoint6 = $this->storage->checkpoint('Save in collection');
    $this->assertNotSame($checkpoint6, $checkpoint3);
    $this->assertSame($collection->checkpoint('Calling checkpoint on collection'), $checkpoint6);
  }

  /**
   * @covers ::exists
   * @covers ::read
   * @covers ::readMultiple
   * @covers ::listAll
   *
   * @dataProvider readMethodsProvider
   */
  public function testReadOperations(string $method, array $arguments, array $fixture): void {
    // Create a checkpoint so the checkpoint storage can be read from.
    $this->storage->checkpoint('');
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
  public static function readMethodsProvider(): array {
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
  public function testWriteOperations(string $method, array $arguments, array $fixture): void {
    $this->setRandomFixtureConfig($fixture);

    // Create an independent memory storage as a backup.
    $backup = new MemoryStorage();
    static::replaceStorageContents($this->memory, $backup);

    try {
      call_user_func_array([$this->storage, $method], $arguments);
      $this->fail("exception not thrown");
    }
    catch (\BadMethodCallException $exception) {
      $this->assertEquals(CheckpointStorage::class . '::' . $method . ' is not allowed on a CheckpointStorage', $exception->getMessage());
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
  public static function writeMethodsProvider(): array {
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
  public function testCollections(): void {
    $ref_readFromCheckpoint = new \ReflectionProperty($this->storage, 'readFromCheckpoint');

    // Create some checkpoints so the checkpoint storage can be read from.
    $checkpoint1 = $this->storage->checkpoint('1');
    $config = $this->prophesize(Config::class);
    $config->getName()->willReturn('test.config');
    $config->getOriginal('', FALSE)->willReturn([]);
    $config->getRawData()->willReturn(['foo' => 'bar']);
    $config->getStorage()->willReturn($this->storage);
    $event = new ConfigCrudEvent($config->reveal());
    $this->storage->onConfigSaveAndDelete($event);
    $checkpoint2 = $this->storage->checkpoint('2');

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
      // Assert that the collection storage is still a checkpoint storage.
      $this->assertInstanceOf(CheckpointStorage::class, $storage);
      $this->assertEquals($collection, $storage->getCollectionName());

      // Ensure that the
      // \Drupal\Core\Config\Checkpoint\CheckpointStorage::$readFromCheckpoint
      // property is kept in sync.
      $this->storage->setCheckpointToReadFrom($checkpoint2);
      $this->assertSame($checkpoint2->id, $ref_readFromCheckpoint->getValue($storage->createCollection($collection))?->id);
      if (isset($previous_collection)) {
        $previous_collection->setCheckpointToReadFrom($checkpoint1);
        $this->assertSame($checkpoint1->id, $ref_readFromCheckpoint->getValue($storage->createCollection($collection))?->id);
        $this->assertSame($checkpoint1->id, $ref_readFromCheckpoint->getValue($this->storage->createCollection($collection))?->id);
      }

      // Save the storage in a variable so we can test use
      // setCheckpointToReadFrom() on it.
      $previous_collection = $storage;
    }
  }

  /**
   * @covers ::encode
   * @covers ::decode
   */
  public function testEncodeDecode(): void {
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
  protected function setRandomFixtureConfig(array $config): void {
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
