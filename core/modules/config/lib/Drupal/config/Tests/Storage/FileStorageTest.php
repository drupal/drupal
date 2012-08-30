<?php

/**
 * @file
 * Definition of Drupal\config\Tests\Storage\FileStorageTest.
 */

namespace Drupal\config\Tests\Storage;

use Drupal\Core\Config\FileStorage;
use Symfony\Component\Yaml\Yaml;

/**
 * Tests FileStorage controller operations.
 */
class FileStorageTest extends ConfigStorageTestBase {
  public static function getInfo() {
    return array(
      'name' => 'FileStorage controller operations',
      'description' => 'Tests FileStorage controller operations.',
      'group' => 'Configuration',
    );
  }

  function setUp() {
    parent::setUp();
    $this->storage = new FileStorage();
    $this->invalidStorage = new FileStorage(array('directory' => $this->configDirectories[CONFIG_ACTIVE_DIRECTORY] . '/nonexisting'));

    // FileStorage::listAll() requires other configuration data to exist.
    $this->storage->write('system.performance', config('system.performance')->get());
  }

  protected function read($name) {
    $data = file_get_contents($this->storage->getFilePath($name));
    return Yaml::parse($data);
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
}
