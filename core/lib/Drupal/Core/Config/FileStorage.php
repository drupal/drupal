<?php

/**
 * @file
 * Definition of Drupal\Core\Config\FileStorage.
 */

namespace Drupal\Core\Config;

use Symfony\Component\Yaml\Yaml;

/**
 * Defines the file storage controller.
 */
class FileStorage implements StorageInterface {

  /**
   * Configuration options for this storage controller.
   *
   * - directory: The filesystem path for configuration objects.
   *
   * @var array
   */
  protected $options;

  /**
   * Implements Drupal\Core\Config\StorageInterface::__construct().
   */
  public function __construct(array $options = array()) {
    if (!isset($options['directory'])) {
      $options['directory'] = config_get_config_directory();
    }
    $this->options = $options;
  }

  /**
   * Returns the path to the configuration file.
   *
   * @return string
   *   The path to the configuration file.
   */
  public function getFilePath($name) {
    return $this->options['directory'] . '/' . $name . '.' . self::getFileExtension();
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
  public function exists($name) {
    return file_exists($this->getFilePath($name));
  }

  /**
   * Implements Drupal\Core\Config\StorageInterface::read().
   *
   * @throws Symfony\Component\Yaml\Exception\ParseException
   */
  public function read($name) {
    if (!$this->exists($name)) {
      return FALSE;
    }
    $data = file_get_contents($this->getFilePath($name));
    // @todo Yaml throws a ParseException on invalid data. Is it expected to be
    //   caught or not?
    $data = $this->decode($data);
    return $data;
  }

  /**
   * Implements Drupal\Core\Config\StorageInterface::write().
   *
   * @throws Symfony\Component\Yaml\Exception\DumpException
   * @throws Drupal\Core\Config\StorageException
   */
  public function write($name, array $data) {
    $data = $this->encode($data);
    $status = @file_put_contents($this->getFilePath($name), $data);
    if ($status === FALSE) {
      throw new StorageException('Failed to write configuration file: ' . $this->getFilePath($name));
    }
    return TRUE;
  }

  /**
   * Implements Drupal\Core\Config\StorageInterface::delete().
   */
  public function delete($name) {
    if (!$this->exists($name)) {
      if (!file_exists($this->options['directory'])) {
        throw new StorageException($this->options['directory'] . '/ not found.');
      }
      return FALSE;
    }
    return drupal_unlink($this->getFilePath($name));
  }

  /**
   * Implements Drupal\Core\Config\StorageInterface::encode().
   *
   * @throws Symfony\Component\Yaml\Exception\DumpException
   */
  public static function encode($data) {
    // The level where you switch to inline YAML is set to PHP_INT_MAX to ensure
    // this does not occur.
    return Yaml::dump($data, PHP_INT_MAX);
  }

  /**
   * Implements Drupal\Core\Config\StorageInterface::decode().
   *
   * @throws Symfony\Component\Yaml\Exception\ParseException
   */
  public static function decode($raw) {
    $data = Yaml::parse($raw);
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
    if (!file_exists($this->options['directory'])) {
      throw new StorageException($this->options['directory'] . '/ not found.');
    }
    $extension = '.' . self::getFileExtension();
    $files = glob($this->options['directory'] . '/' . $prefix . '*' . $extension);
    $clean_name = function ($value) use ($extension) {
      return basename($value, $extension);
    };
    return array_map($clean_name, $files);
  }
}
