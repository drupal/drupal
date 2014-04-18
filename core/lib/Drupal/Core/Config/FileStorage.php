<?php

/**
 * @file
 * Definition of Drupal\Core\Config\FileStorage.
 */

namespace Drupal\Core\Config;

use Drupal\Component\Serialization\Yaml;
use Drupal\Component\Serialization\Exception\InvalidDataTypeException;
use Drupal\Component\Utility\String;

/**
 * Defines the file storage.
 */
class FileStorage implements StorageInterface {

  /**
   * The filesystem path for configuration objects.
   *
   * @var string
   */
  protected $directory = '';

  /**
   * Constructs a new FileStorage.
   *
   * @param string $directory
   *   A directory path to use for reading and writing of configuration files.
   */
  public function __construct($directory) {
    $this->directory = $directory;
  }

  /**
   * Returns the path to the configuration file.
   *
   * @return string
   *   The path to the configuration file.
   */
  public function getFilePath($name) {
    return $this->directory . '/' . $name . '.' . static::getFileExtension();
  }

  /**
   * Returns the file extension used by the file storage for all configuration files.
   *
   * @return string
   *   The file extension.
   */
  public static function getFileExtension() {
    return 'yml';
  }

  /**
   * Check if the directory exists and create it if not.
   */
  protected function ensureStorage() {
    $success = file_prepare_directory($this->directory, FILE_CREATE_DIRECTORY | FILE_MODIFY_PERMISSIONS);
    $success = $success && file_save_htaccess($this->directory, TRUE, TRUE);
    if (!$success) {
      throw new StorageException("Failed to create config directory {$this->directory}");
    }
    return $this;
  }

  /**
   * Implements Drupal\Core\Config\StorageInterface::exists().
   */
  public function exists($name) {
    return file_exists($this->getFilePath($name));
  }

  /**
   * Implements Drupal\Core\Config\StorageInterface::read().
   *
   * @throws \Drupal\Core\Config\UnsupportedDataTypeConfigException
   */
  public function read($name) {
    if (!$this->exists($name)) {
      return FALSE;
    }
    $data = file_get_contents($this->getFilePath($name));
    try {
      $data = $this->decode($data);
    }
    catch (InvalidDataTypeException $e) {
      throw new UnsupportedDataTypeConfigException(String::format('Invalid data type in config @name: !message', array(
        '@name' => $name,
        '!message' => $e->getMessage(),
      )));
    }
    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function readMultiple(array $names) {
    $list = array();
    foreach ($names as $name) {
      if ($data = $this->read($name)) {
        $list[$name] = $data;
      }
    }
    return $list;
  }

  /**
   * {@inheritdoc}
   */
  public function write($name, array $data) {
    try {
      $data = $this->encode($data);
    }
    catch (InvalidDataTypeException $e) {
      throw new StorageException(String::format('Invalid data type in config @name: !message', array(
        '@name' => $name,
        '!message' => $e->getMessage(),
      )));
    }

    $target = $this->getFilePath($name);
    $status = @file_put_contents($target, $data);
    if ($status === FALSE) {
      // Try to make sure the directory exists and try witing again.
      $this->ensureStorage();
      $status = @file_put_contents($target, $data);
    }
    if ($status === FALSE) {
      throw new StorageException('Failed to write configuration file: ' . $this->getFilePath($name));
    }
    else {
      drupal_chmod($target);
    }
    return TRUE;
  }

  /**
   * Implements Drupal\Core\Config\StorageInterface::delete().
   */
  public function delete($name) {
    if (!$this->exists($name)) {
      if (!file_exists($this->directory)) {
        throw new StorageException($this->directory . '/ not found.');
      }
      return FALSE;
    }
    return drupal_unlink($this->getFilePath($name));
  }

  /**
   * Implements Drupal\Core\Config\StorageInterface::rename().
   */
  public function rename($name, $new_name) {
    $status = @rename($this->getFilePath($name), $this->getFilePath($new_name));
    if ($status === FALSE) {
      throw new StorageException('Failed to rename configuration file from: ' . $this->getFilePath($name) . ' to: ' . $this->getFilePath($new_name));
    }
    return TRUE;
  }

  /**
   * Implements Drupal\Core\Config\StorageInterface::encode().
   */
  public function encode($data) {
    return Yaml::encode($data);
  }

  /**
   * Implements Drupal\Core\Config\StorageInterface::decode().
   */
  public function decode($raw) {
    $data = Yaml::decode($raw);
    // A simple string is valid YAML for any reason.
    if (!is_array($data)) {
      return FALSE;
    }
    return $data;
  }

  /**
   * Implements Drupal\Core\Config\StorageInterface::listAll().
   */
  public function listAll($prefix = '') {
    // glob() silently ignores the error of a non-existing search directory,
    // even with the GLOB_ERR flag.
    if (!file_exists($this->directory)) {
      return array();
    }
    $extension = '.' . static::getFileExtension();
    // \GlobIterator on Windows requires an absolute path.
    $files = new \GlobIterator(realpath($this->directory) . '/' . $prefix . '*' . $extension);

    $names = array();
    foreach ($files as $file) {
      $names[] = $file->getBasename($extension);
    }

    return $names;
  }

  /**
   * Implements Drupal\Core\Config\StorageInterface::deleteAll().
   */
  public function deleteAll($prefix = '') {
    $success = TRUE;
    $files = $this->listAll($prefix);
    foreach ($files as $name) {
      if (!$this->delete($name) && $success) {
        $success = FALSE;
      }
    }

    return $success;
  }
}
