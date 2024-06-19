<?php

declare(strict_types=1);

namespace Drupal\Tests\Component\Discovery;

use Drupal\Component\Discovery\YamlDiscovery;
use Drupal\Component\FileCache\FileCacheFactory;
use Drupal\Component\Serialization\Exception\InvalidDataTypeException;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use org\bovigo\vfs\vfsStreamWrapper;
use PHPUnit\Framework\TestCase;

/**
 * YamlDiscovery component unit tests.
 *
 * @group Discovery
 */
class YamlDiscoveryTest extends TestCase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    // Ensure that FileCacheFactory has a prefix.
    FileCacheFactory::setPrefix('prefix');
  }

  /**
   * Tests the YAML file discovery.
   */
  public function testDiscovery(): void {
    vfsStreamWrapper::register();
    $root = new vfsStreamDirectory('modules');
    vfsStreamWrapper::setRoot($root);
    $url = vfsStream::url('modules');

    mkdir($url . '/test_1');
    file_put_contents($url . '/test_1/test_1.test.yml', 'name: test');
    file_put_contents($url . '/test_1/test_2.test.yml', 'name: test');

    mkdir($url . '/test_2');
    file_put_contents($url . '/test_2/test_3.test.yml', 'name: test');
    // Write an empty YAML file.
    file_put_contents($url . '/test_2/test_4.test.yml', '');

    // Set up the directories to search.
    $directories = [
      'test_1' => $url . '/test_1',
      'test_2' => $url . '/test_1',
      'test_3' => $url . '/test_2',
      'test_4' => $url . '/test_2',
    ];

    $discovery = new YamlDiscovery('test', $directories);
    $data = $discovery->findAll();

    $this->assertCount(4, $data);
    $this->assertArrayHasKey('test_1', $data);
    $this->assertArrayHasKey('test_2', $data);
    $this->assertArrayHasKey('test_3', $data);
    $this->assertArrayHasKey('test_4', $data);

    foreach (['test_1', 'test_2', 'test_3'] as $key) {
      $this->assertArrayHasKey('name', $data[$key]);
      $this->assertEquals('test', $data[$key]['name']);
    }

    $this->assertSame([], $data['test_4']);
  }

  /**
   * Tests if filename is output for a broken YAML file.
   */
  public function testForBrokenYml(): void {
    vfsStreamWrapper::register();
    $root = new vfsStreamDirectory('modules');
    vfsStreamWrapper::setRoot($root);
    $url = vfsStream::url('modules');

    mkdir($url . '/test_broken');
    file_put_contents($url . '/test_broken/test_broken.test.yml', "broken:\n:");

    $this->expectException(InvalidDataTypeException::class);
    $this->expectExceptionMessage('vfs://modules/test_broken/test_broken.test.yml');

    $directories = ['test_broken' => $url . '/test_broken'];
    $discovery = new YamlDiscovery('test', $directories);
    $discovery->findAll();
  }

}
