<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Discovery;

use Drupal\Component\FileCache\FileCacheFactory;
use Drupal\Component\Serialization\Exception\InvalidDataTypeException;
use Drupal\Core\Discovery\YamlDiscovery;
use Drupal\Tests\UnitTestCase;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use org\bovigo\vfs\vfsStreamWrapper;
use PHPUnit\Framework\Attributes\Group;

/**
 * YamlDiscovery component unit tests.
 */
#[Group('Discovery')]
class YamlDiscoveryTest extends UnitTestCase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    // Ensure that FileCacheFactory has a prefix.
    FileCacheFactory::setPrefix('prefix');
  }

  /**
   * Tests if filename is output for a broken YAML file.
   */
  public function testFilenameForBrokenYml(): void {
    vfsStreamWrapper::register();
    $root = new vfsStreamDirectory('modules');
    vfsStreamWrapper::setRoot($root);
    $url = vfsStream::url('modules');

    mkdir($url . '/test_broken');
    file_put_contents($url . '/test_broken/test_broken.test.yml', "broken:\n:");

    $this->expectException(InvalidDataTypeException::class);
    $this->expectExceptionMessageIs('vfs://modules/test_broken/test_broken.test.yml: Unable to parse at line 2 (near ":").');

    $directories = ['test_broken' => $url . '/test_broken'];
    $discovery = new YamlDiscovery('test', $directories);
    $discovery->findAll();
  }

}
