<?php

/**
 * @file
 * Contains \Drupal\config\Tests\Storage\FileStorageTest.
 */

namespace Drupal\config\Tests\Storage;

use Drupal\Component\Serialization\Yaml;
use Drupal\Core\Config\FileStorage;

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
  protected function setUp() {
    parent::setUp();
    // Create a directory.
    $this->directory = $this->publicFilesDirectory . '/config';
    mkdir($this->directory);
    $this->storage = new FileStorage($this->directory);
    $this->invalidStorage = new FileStorage($this->directory . '/nonexisting');

    // FileStorage::listAll() requires other configuration data to exist.
    $this->storage->write('system.performance', $this->config('system.performance')->get());
    $this->storage->write('core.extension', array('module' => array()));
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
  public function testlistAll() {
    $expected_files = array(
      'core.extension',
      'system.performance',
    );

    $config_files = $this->storage->listAll();
    $this->assertIdentical($config_files, $expected_files, 'Relative path, two config files found.');

    // Initialize FileStorage with absolute file path.
    $absolute_path = realpath($this->directory);
    $storage_absolute_path = new FileStorage($absolute_path);
    $config_files = $storage_absolute_path->listAll();
    $this->assertIdentical($config_files, $expected_files, 'Absolute path, two config files found.');
  }

}
