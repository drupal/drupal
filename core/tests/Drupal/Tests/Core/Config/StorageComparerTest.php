<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Config;

use Drupal\Component\Uuid\Php;
use Drupal\Core\Config\MemoryStorage;
use Drupal\Core\Config\StorageComparer;
use Drupal\Core\Config\StorageInterface;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\Core\Config\StorageComparer
 * @group Config
 */
class StorageComparerTest extends UnitTestCase {

  /**
   * @var \Drupal\Core\Config\StorageInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $sourceStorage;

  /**
   * @var \Drupal\Core\Config\StorageInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $targetStorage;

  /**
   * The storage comparer to test.
   *
   * @var \Drupal\Core\Config\StorageComparer
   */
  protected $storageComparer;

  /**
   * An array of test configuration data keyed by configuration name.
   *
   * @var array
   */
  protected $configData;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->sourceStorage = $this->createMock('Drupal\Core\Config\StorageInterface');
    $this->targetStorage = $this->createMock('Drupal\Core\Config\StorageInterface');

    $this->sourceStorage->expects($this->atLeastOnce())
      ->method('getCollectionName')
      ->willReturn(StorageInterface::DEFAULT_COLLECTION);
    $this->targetStorage->expects($this->atLeastOnce())
      ->method('getCollectionName')
      ->willReturn(StorageInterface::DEFAULT_COLLECTION);

    $this->storageComparer = new StorageComparer($this->sourceStorage, $this->targetStorage);
  }

  protected function getConfigData() {
    $uuid = new Php();
    // Mock data using minimal data to use ConfigDependencyManger.
    $this->configData = [
      // Simple config that controls configuration sync.
      'system.site' => [
        'title' => 'Drupal',
        'uuid' => $uuid->generate(),
      ],
      // Config entity which requires another config entity.
      'field.field.node.article.body' => [
        'id' => 'node.article.body',
        'uuid' => $uuid->generate(),
        'dependencies' => [
          'config' => [
            'field.storage.node.body',
          ],
        ],
      ],
      // Config entity which is required by another config entity.
      'field.storage.node.body' => [
        'id' => 'node.body',
        'uuid' => $uuid->generate(),
        'dependencies' => [
          'module' => [
            'text',
          ],
        ],
      ],
      // Config entity not which has no dependencies on configuration.
      'views.view.test_view' => [
        'id' => 'test_view',
        'uuid' => $uuid->generate(),
        'dependencies' => [
          'module' => [
            'node',
          ],
        ],
      ],
      // Simple config.
      'system.logging' => [
        'error_level' => 'hide',
      ],

    ];
    return $this->configData;
  }

  /**
   * @covers ::createChangelist
   */
  public function testCreateChangelistNoChange(): void {
    $config_data = $this->getConfigData();
    $config_files = array_keys($config_data);
    $this->sourceStorage->expects($this->once())
      ->method('listAll')
      ->willReturn($config_files);
    $this->targetStorage->expects($this->once())
      ->method('listAll')
      ->willReturn($config_files);
    $this->sourceStorage->expects($this->once())
      ->method('readMultiple')
      ->willReturn($config_data);
    $this->targetStorage->expects($this->once())
      ->method('readMultiple')
      ->willReturn($config_data);
    $this->sourceStorage->expects($this->once())
      ->method('getAllCollectionNames')
      ->willReturn([]);
    $this->targetStorage->expects($this->once())
      ->method('getAllCollectionNames')
      ->willReturn([]);

    $this->storageComparer->createChangelist();
    $this->assertEmpty($this->storageComparer->getChangelist('create'));
    $this->assertEmpty($this->storageComparer->getChangelist('delete'));
    $this->assertEmpty($this->storageComparer->getChangelist('update'));
  }

  /**
   * @covers ::createChangelist
   */
  public function testCreateChangelistCreate(): void {
    $target_data = $source_data = $this->getConfigData();
    unset($target_data['field.storage.node.body']);
    unset($target_data['field.field.node.article.body']);
    unset($target_data['views.view.test_view']);

    $this->sourceStorage->expects($this->once())
      ->method('listAll')
      ->willReturn(array_keys($source_data));
    $this->targetStorage->expects($this->once())
      ->method('listAll')
      ->willReturn(array_keys($target_data));
    $this->sourceStorage->expects($this->once())
      ->method('readMultiple')
      ->willReturn($source_data);
    $this->targetStorage->expects($this->once())
      ->method('readMultiple')
      ->willReturn($target_data);
    $this->sourceStorage->expects($this->once())
      ->method('getAllCollectionNames')
      ->willReturn([]);
    $this->targetStorage->expects($this->once())
      ->method('getAllCollectionNames')
      ->willReturn([]);

    $this->storageComparer->createChangelist();
    $expected = [
      'field.storage.node.body',
      'field.field.node.article.body',
      'views.view.test_view',
    ];
    $this->assertEquals($expected, $this->storageComparer->getChangelist('create'));
    $this->assertEmpty($this->storageComparer->getChangelist('delete'));
    $this->assertEmpty($this->storageComparer->getChangelist('update'));
  }

  /**
   * @covers ::createChangelist
   */
  public function testCreateChangelistDelete(): void {
    $target_data = $source_data = $this->getConfigData();
    unset($source_data['field.storage.node.body']);
    unset($source_data['field.field.node.article.body']);
    unset($source_data['views.view.test_view']);

    $this->sourceStorage->expects($this->once())
      ->method('listAll')
      ->willReturn(array_keys($source_data));
    $this->targetStorage->expects($this->once())
      ->method('listAll')
      ->willReturn(array_keys($target_data));
    $this->sourceStorage->expects($this->once())
      ->method('readMultiple')
      ->willReturn($source_data);
    $this->targetStorage->expects($this->once())
      ->method('readMultiple')
      ->willReturn($target_data);
    $this->sourceStorage->expects($this->once())
      ->method('getAllCollectionNames')
      ->willReturn([]);
    $this->targetStorage->expects($this->once())
      ->method('getAllCollectionNames')
      ->willReturn([]);

    $this->storageComparer->createChangelist();
    $expected = [
      'views.view.test_view',
      'field.field.node.article.body',
      'field.storage.node.body',
    ];
    $this->assertEquals($expected, $this->storageComparer->getChangelist('delete'));
    $this->assertEmpty($this->storageComparer->getChangelist('create'));
    $this->assertEmpty($this->storageComparer->getChangelist('update'));
  }

  /**
   * @covers ::createChangelist
   */
  public function testCreateChangelistUpdate(): void {
    $target_data = $source_data = $this->getConfigData();
    $source_data['system.site']['title'] = 'Drupal New!';
    $source_data['field.field.node.article.body']['new_config_key'] = 'new data';
    $source_data['field.storage.node.body']['new_config_key'] = 'new data';

    $this->sourceStorage->expects($this->once())
      ->method('listAll')
      ->willReturn(array_keys($source_data));
    $this->targetStorage->expects($this->once())
      ->method('listAll')
      ->willReturn(array_keys($target_data));
    $this->sourceStorage->expects($this->once())
      ->method('readMultiple')
      ->willReturn($source_data);
    $this->targetStorage->expects($this->once())
      ->method('readMultiple')
      ->willReturn($target_data);
    $this->sourceStorage->expects($this->once())
      ->method('getAllCollectionNames')
      ->willReturn([]);
    $this->targetStorage->expects($this->once())
      ->method('getAllCollectionNames')
      ->willReturn([]);

    $this->storageComparer->createChangelist();
    $expected = [
      'field.storage.node.body',
      'field.field.node.article.body',
      'system.site',
    ];
    $this->assertEquals($expected, $this->storageComparer->getChangelist('update'));
    $this->assertEmpty($this->storageComparer->getChangelist('create'));
    $this->assertEmpty($this->storageComparer->getChangelist('delete'));
  }

  /**
   * @covers ::createChangelist
   */
  public function testDifferentCollections(): void {
    $source = new MemoryStorage();
    $target = new MemoryStorage();

    $this->generateRandomData($source, 's');
    $this->generateRandomData($target, 't');

    // Use random collections for source and target.
    $collections = $source->getAllCollectionNames();
    $source = $source->createCollection($collections[array_rand($collections)]);
    $collections = $target->getAllCollectionNames();
    $target = $target->createCollection($collections[array_rand($collections)]);

    $comparer = new StorageComparer($source, $target);
    $comparer->createChangelist();

    foreach (array_merge([StorageInterface::DEFAULT_COLLECTION], $source->getAllCollectionNames(), $target->getAllCollectionNames()) as $collection) {
      $expected = [
        'create' => $source->createCollection($collection)->listAll(),
        'update' => [],
        'delete' => $target->createCollection($collection)->listAll(),
        'rename' => [],
      ];

      $this->assertEqualsCanonicalizing($expected, $comparer->getChangelist(NULL, $collection));
    }
  }

  /**
   * Generate random data in a config storage.
   *
   * @param \Drupal\Core\Config\StorageInterface $storage
   *   The storage to populate with random data.
   * @param string $prefix
   *   The prefix for random names to make sure they are unique.
   */
  protected function generateRandomData(StorageInterface $storage, string $prefix = ''): void {
    $generator = $this->getRandomGenerator();
    for ($i = 0; $i < rand(2, 10); $i++) {
      $storage->write($prefix . $this->randomMachineName(), (array) $generator->object());
    }
    for ($i = 0; $i < rand(1, 5); $i++) {
      $collection = $storage->createCollection($prefix . $this->randomMachineName());
      for ($i = 0; $i < rand(2, 10); $i++) {
        $collection->write($prefix . $this->randomMachineName(), (array) $generator->object());
      }
    }
  }

}
