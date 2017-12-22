<?php

namespace Drupal\Tests\Component\Discovery;

use Drupal\Component\Discovery\DiscoveryException;
use Drupal\Component\Discovery\YamlDirectoryDiscovery;
use Drupal\Component\FileCache\FileCacheFactory;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;

/**
 * YamlDirectoryDiscoveryTest component unit tests.
 *
 * @coversDefaultClass \Drupal\Component\Discovery\YamlDirectoryDiscovery
 *
 * @group Discovery
 */
class YamlDirectoryDiscoveryTest extends TestCase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    // Ensure that FileCacheFactory has a prefix.
    FileCacheFactory::setPrefix('prefix');
  }

  /**
   * Tests YAML directory discovery.
   *
   * @covers ::findAll
   */
  public function testDiscovery() {
    vfsStream::setup('modules', NULL, [
      'test_1' => [
        'subdir1' => [
          'item_1.test.yml' => "id: item1\nname: 'test1 item 1'",
        ],
        'subdir2' => [
          'item_2.test.yml' => "id: item2\nname: 'test1 item 2'",
        ],
      ],
      'test_2' => [
        'subdir1' => [
          'item_3.test.yml' => "id: item3\nname: 'test2 item 3'",
        ],
        'subdir2' => [],
      ],
      'test_3' => [],
      'test_4' => [
        'subdir1' => [
          'item_4.test.yml' => "id: item4\nname: 'test4 item 4'",
          'item_5.test.yml' => "id: item5\nname: 'test4 item 5'",
          'item_6.test.yml' => "id: item6\nname: 'test4 item 6'",
        ],
      ],
    ]);

    // Set up the directories to search.
    $directories = [
      // Multiple directories both with valid items.
      'test_1' => [
        vfsStream::url('modules/test_1/subdir1'),
        vfsStream::url('modules/test_1/subdir2'),
      ],
      // The subdir2 directory is empty.
      'test_2' => [
        vfsStream::url('modules/test_2/subdir1'),
        vfsStream::url('modules/test_2/subdir2'),
      ],
      // Directories that do not exist.
      'test_3' => [
        vfsStream::url('modules/test_3/subdir1'),
        vfsStream::url('modules/test_3/subdir2'),
      ],
      // A single directory.
      'test_4' => vfsStream::url('modules/test_4/subdir1'),
    ];

    $discovery = new YamlDirectoryDiscovery($directories, 'test');
    $data = $discovery->findAll();

    $this->assertSame(['id' => 'item1', 'name' => 'test1 item 1', YamlDirectoryDiscovery::FILE_KEY => 'vfs://modules/test_1/subdir1/item_1.test.yml'], $data['test_1']['item1']);
    $this->assertSame(['id' => 'item2', 'name' => 'test1 item 2', YamlDirectoryDiscovery::FILE_KEY => 'vfs://modules/test_1/subdir2/item_2.test.yml'], $data['test_1']['item2']);
    $this->assertCount(2, $data['test_1']);

    $this->assertSame(['id' => 'item3', 'name' => 'test2 item 3', YamlDirectoryDiscovery::FILE_KEY => 'vfs://modules/test_2/subdir1/item_3.test.yml'], $data['test_2']['item3']);
    $this->assertCount(1, $data['test_2']);

    $this->assertTrue(empty($data['test_3']), 'test_3 provides 0 items');

    $this->assertSame(['id' => 'item4', 'name' => 'test4 item 4', YamlDirectoryDiscovery::FILE_KEY => 'vfs://modules/test_4/subdir1/item_4.test.yml'], $data['test_4']['item4']);
    $this->assertSame(['id' => 'item5', 'name' => 'test4 item 5', YamlDirectoryDiscovery::FILE_KEY => 'vfs://modules/test_4/subdir1/item_5.test.yml'], $data['test_4']['item5']);
    $this->assertSame(['id' => 'item6', 'name' => 'test4 item 6', YamlDirectoryDiscovery::FILE_KEY => 'vfs://modules/test_4/subdir1/item_6.test.yml'], $data['test_4']['item6']);
    $this->assertCount(3, $data['test_4']);
  }

  /**
   * Tests YAML directory discovery with an alternate ID key.
   *
   * @covers ::findAll
   */
  public function testDiscoveryAlternateId() {
    vfsStream::setup('modules', NULL, [
      'test_1' => [
        'item_1.test.yml' => "alt_id: item1\nid: ignored",
      ],
    ]);

    // Set up the directories to search.
    $directories = ['test_1' => vfsStream::url('modules/test_1')];

    $discovery = new YamlDirectoryDiscovery($directories, 'test', 'alt_id');
    $data = $discovery->findAll();

    $this->assertSame(['alt_id' => 'item1', 'id' => 'ignored', YamlDirectoryDiscovery::FILE_KEY => 'vfs://modules/test_1/item_1.test.yml'], $data['test_1']['item1']);
    $this->assertCount(1, $data['test_1']);
  }

  /**
   * Tests YAML directory discovery with a missing ID key.
   *
   * @covers ::findAll
   * @covers ::getIdentifier
   */
  public function testDiscoveryNoIdException() {
    if (method_exists($this, 'expectException')) {
      $this->expectException(DiscoveryException::class);
      $this->expectExceptionMessage('The vfs://modules/test_1/item_1.test.yml contains no data in the identifier key \'id\'');
    }
    else {
      $this->setExpectedException(DiscoveryException::class, 'The vfs://modules/test_1/item_1.test.yml contains no data in the identifier key \'id\'');
    }
    vfsStream::setup('modules', NULL, [
      'test_1' => [
        'item_1.test.yml' => "",
      ],
    ]);

    // Set up the directories to search.
    $directories = ['test_1' => vfsStream::url('modules/test_1')];

    $discovery = new YamlDirectoryDiscovery($directories, 'test');
    $discovery->findAll();
  }

  /**
   * Tests YAML directory discovery with invalid YAML.
   *
   * @covers ::findAll
   */
  public function testDiscoveryInvalidYamlException() {
    if (method_exists($this, 'expectException')) {
      $this->expectException(DiscoveryException::class);
      $this->expectExceptionMessage('The vfs://modules/test_1/item_1.test.yml contains invalid YAML');
    }
    else {
      $this->setExpectedException(DiscoveryException::class, 'The vfs://modules/test_1/item_1.test.yml contains invalid YAML');
    }
    vfsStream::setup('modules', NULL, [
      'test_1' => [
        'item_1.test.yml' => "id: invalid\nfoo : [bar}",
      ],
    ]);

    // Set up the directories to search.
    $directories = ['test_1' => vfsStream::url('modules/test_1')];

    $discovery = new YamlDirectoryDiscovery($directories, 'test');
    $discovery->findAll();
  }

}
