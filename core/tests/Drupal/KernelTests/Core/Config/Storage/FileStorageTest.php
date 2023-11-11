<?php

namespace Drupal\KernelTests\Core\Config\Storage;

use Drupal\Core\Config\FileStorage;
use Drupal\Core\Config\UnsupportedDataTypeConfigException;
use Drupal\Core\Serialization\Yaml;
use Drupal\Core\StreamWrapper\PublicStream;

/**
 * Tests FileStorage operations.
 *
 * @group config
 */
class FileStorageTest extends ConfigStorageTestBase {

  /**
   * A directory to store configuration in.
   *
   * @var string
   */
  protected $directory;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    // Create a directory.
    $this->directory = PublicStream::basePath() . '/config';
    $this->storage = new FileStorage($this->directory);
    $this->invalidStorage = new FileStorage($this->directory . '/nonexisting');

    // FileStorage::listAll() requires other configuration data to exist.
    $this->storage->write('system.performance', $this->config('system.performance')->get());
    $this->storage->write('core.extension', ['module' => []]);
  }

  protected function read($name) {
    $data = file_get_contents($this->storage->getFilePath($name));
    return Yaml::decode($data);
  }

  protected function insert($name, $data) {
    file_put_contents($this->storage->getFilePath($name), $data);
  }

  protected function update($name, $data) {
    file_put_contents($this->storage->getFilePath($name), $data);
  }

  protected function delete($name) {
    unlink($this->storage->getFilePath($name));
  }

  /**
   * Tests the FileStorage::listAll method with a relative and absolute path.
   */
  public function testListAll() {
    $expected_files = [
      'core.extension',
      'system.performance',
    ];

    $config_files = $this->storage->listAll();
    $this->assertSame($expected_files, $config_files, 'Relative path, two config files found.');

    // @todo https://www.drupal.org/node/2666954 FileStorage::listAll() is
    //   case-sensitive. However, \Drupal\Core\Config\DatabaseStorage::listAll()
    //   is case-insensitive.
    $this->assertSame(['system.performance'], $this->storage->listAll('system'), 'The FileStorage::listAll() with prefix works.');
    $this->assertSame([], $this->storage->listAll('System'), 'The FileStorage::listAll() is case sensitive.');
  }

  /**
   * Tests UnsupportedDataTypeConfigException.
   */
  public function testUnsupportedDataTypeConfigException() {
    $name = 'core.extension';
    $path = $this->storage->getFilePath($name);
    $this->expectException(UnsupportedDataTypeConfigException::class);
    $this->expectExceptionMessageMatches("@Invalid data type in config $name, found in file $path: @");
    file_put_contents($path, PHP_EOL . 'foo : @bar', FILE_APPEND);
    $this->storage->read($name);
  }

}
