<?php

namespace Drupal\Tests\Core\Discovery;

use Drupal\Component\FileCache\FileCacheFactory;
use Drupal\Component\Serialization\Exception\InvalidDataTypeException;
use Drupal\Core\Discovery\YamlDiscovery;
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
  protected function setUp() {
    // Ensure that FileCacheFactory has a prefix.
    FileCacheFactory::setPrefix('prefix');
  }

  /**
   * Tests if filename is output for a broken YAML file.
   */
  public function testFilenameForBrokenYml() {
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
