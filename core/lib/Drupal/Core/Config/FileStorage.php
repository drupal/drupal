<?php

namespace Drupal\Core\Config;

use Symfony\Component\Yaml\Yaml;

/**
 * Represents the file storage controller.
 *
 * @todo Implement StorageInterface after removing DrupalConfig methods.
 * @todo Consider to extend StorageBase.
 */
class FileStorage {

  /**
   * The name of the configuration object.
   *
   * @var string
   */
  protected $name;

  /**
   * The filesystem path containing the configuration object.
   *
   * @var string
   */
  protected $path;

  /**
   * Implements StorageInterface::__construct().
   */
  public function __construct($name = NULL) {
    $this->name = $name;
  }

  /**
   * Returns the path containing the configuration file.
   *
   * @return string
   *   The relative path to the configuration object.
   */
  public function getPath() {
    // If the path has not been set yet, retrieve and assign the default path
    // for configuration files.
    if (!isset($this->path)) {
      $this->setPath(config_get_config_directory());
    }
    return $this->path;
  }

  /**
   * Sets the path containing the configuration file.
   */
  public function setPath($directory) {
    $this->path = $directory;
    return $this;
  }

  /**
   * Returns the path to the configuration file.
   *
   * @return string
   *   The path to the configuration file.
   */
  public function getFilePath() {
    return $this->getPath() . '/' . $this->getName() . '.' . self::getFileExtension();
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
   * Returns whether the configuration file exists.
   *
   * @return bool
   *   TRUE if the configuration file exists, FALSE otherwise.
   */
  protected function exists() {
    return file_exists($this->getFilePath());
  }

  /**
   * Implements StorageInterface::write().
   *
   * @throws FileStorageException
   */
  public function write($data) {
    $data = $this->encode($data);
    if (!file_put_contents($this->getFilePath(), $data)) {
      throw new FileStorageException('Failed to write configuration file: ' . $this->getFilePath());
    }
  }

  /**
   * Implements StorageInterface::read().
   *
   * @throws FileStorageReadException
   */
  public function read() {
    if (!$this->exists()) {
      throw new FileStorageReadException('Configuration file does not exist.');
    }

    $data = file_get_contents($this->getFilePath());
    $data = $this->decode($data);
    if ($data === FALSE) {
      throw new FileStorageReadException('Unable to decode configuration file.');
    }
    return $data;
  }

  /**
   * Deletes a configuration file.
   */
  public function delete() {
    // Needs error handling and etc.
    @drupal_unlink($this->getFilePath());
  }

  /**
   * Implements StorageInterface::encode().
   */
  public static function encode($data) {
    // The level where you switch to inline YAML is set to PHP_INT_MAX to ensure
    // this does not occur.
    return Yaml::dump($data, PHP_INT_MAX);
  }

  /**
   * Implements StorageInterface::decode().
   */
  public static function decode($raw) {
    if (empty($raw)) {
      return array();
    }
    return Yaml::parse($raw);
  }

  /**
   * Implements StorageInterface::getName().
   */
  public function getName() {
    return $this->name;
  }

  /**
   * Implements StorageInterface::setName().
   */
  public function setName($name) {
    $this->name = $name;
  }

  /**
   * Implements StorageInterface::getNamesWithPrefix().
   */
  public static function getNamesWithPrefix($prefix = '') {
    // @todo Use $this->getPath() to allow for contextual search of files in
    //   custom paths.
    $files = glob(config_get_config_directory() . '/' . $prefix . '*.' . FileStorage::getFileExtension());
    $clean_name = function ($value) {
      return basename($value, '.' . FileStorage::getFileExtension());
    };
    return array_map($clean_name, $files);
  }
}
