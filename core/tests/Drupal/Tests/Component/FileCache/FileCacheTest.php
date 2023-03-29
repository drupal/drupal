<?php

namespace Drupal\Tests\Component\FileCache;

use Drupal\Component\FileCache\FileCache;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \Drupal\Component\FileCache\FileCache
 * @group FileCache
 */
class FileCacheTest extends TestCase {

  /**
   * FileCache object used for the tests.
   *
   * @var \Drupal\Component\FileCache\FileCacheInterface
   */
  protected $fileCache;

  /**
   * Static FileCache object used for verification of tests.
   *
   * @var \Drupal\Component\FileCache\FileCacheBackendInterface
   */
  protected $staticFileCache;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->fileCache = new FileCache('prefix', 'test', '\Drupal\Tests\Component\FileCache\StaticFileCacheBackend', ['bin' => 'llama']);
    $this->staticFileCache = new StaticFileCacheBackend(['bin' => 'llama']);
  }

  /**
   * @covers ::get
   * @covers ::__construct
   */
  public function testGet() {
    // Test a cache miss.
    $result = $this->fileCache->get(__DIR__ . DIRECTORY_SEPARATOR . 'Fixtures' . DIRECTORY_SEPARATOR . 'no-llama-42.yml');
    $this->assertNull($result);

    // Test a cache hit.
    $filename = __DIR__ . DIRECTORY_SEPARATOR . 'Fixtures' . DIRECTORY_SEPARATOR . 'llama-42.txt';
    $realpath = realpath($filename);
    $cid = 'prefix:test:' . $realpath;
    $data = [
      'mtime' => filemtime($realpath),
      'filepath' => $realpath,
      'data' => 42,
    ];

    $this->staticFileCache->store($cid, $data);

    $result = $this->fileCache->get($filename);
    $this->assertEquals(42, $result);

    // Cleanup static caches.
    $this->fileCache->delete($filename);
  }

  /**
   * @covers ::getMultiple
   */
  public function testGetMultiple() {
    // Test a cache miss.
    $result = $this->fileCache->getMultiple([__DIR__ . DIRECTORY_SEPARATOR . 'Fixtures' . DIRECTORY_SEPARATOR . 'no-llama-42.yml']);
    $this->assertEmpty($result);

    // Test a cache hit.
    $filename = __DIR__ . DIRECTORY_SEPARATOR . 'Fixtures' . DIRECTORY_SEPARATOR . 'llama-42.txt';
    $realpath = realpath($filename);
    $cid = 'prefix:test:' . $realpath;
    $data = [
      'mtime' => filemtime($realpath),
      'filepath' => $realpath,
      'data' => 42,
    ];

    $this->staticFileCache->store($cid, $data);

    $result = $this->fileCache->getMultiple([$filename]);
    $this->assertEquals([$filename => 42], $result);

    // Test a static cache hit.
    $file2 = __DIR__ . DIRECTORY_SEPARATOR . 'Fixtures' . DIRECTORY_SEPARATOR . 'llama-23.txt';
    $this->fileCache->set($file2, 23);

    $result = $this->fileCache->getMultiple([$filename, $file2]);
    $this->assertEquals([$filename => 42, $file2 => 23], $result);

    // Cleanup static caches.
    $this->fileCache->delete($filename);
    $this->fileCache->delete($file2);
  }

  /**
   * @covers ::set
   */
  public function testSet() {
    $filename = __DIR__ . DIRECTORY_SEPARATOR . 'Fixtures' . DIRECTORY_SEPARATOR . 'llama-23.txt';
    $realpath = realpath($filename);
    $cid = 'prefix:test:' . $realpath;
    $data = [
      'mtime' => filemtime($realpath),
      'filepath' => $realpath,
      'data' => 23,
    ];

    $this->fileCache->set($filename, 23);
    $result = $this->staticFileCache->fetch([$cid]);
    $this->assertEquals([$cid => $data], $result);

    // Cleanup static caches.
    $this->fileCache->delete($filename);
  }

  /**
   * @covers ::delete
   */
  public function testDelete() {
    $filename = __DIR__ . DIRECTORY_SEPARATOR . 'Fixtures' . DIRECTORY_SEPARATOR . 'llama-23.txt';
    $realpath = realpath($filename);
    $cid = 'prefix:test:' . $realpath;

    $this->fileCache->set($filename, 23);

    // Ensure data is removed after deletion.
    $this->fileCache->delete($filename);

    $result = $this->staticFileCache->fetch([$cid]);
    $this->assertEquals([], $result);

    $result = $this->fileCache->get($filename);
    $this->assertNull($result);
  }

}
