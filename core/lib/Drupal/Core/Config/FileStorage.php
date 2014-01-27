<?php

/**
 * @file
 * Definition of Drupal\Core\Config\FileStorage.
 */

namespace Drupal\Core\Config;

use Drupal\Component\Utility\String;
use Symfony\Component\Yaml\Dumper;
use Symfony\Component\Yaml\Exception\DumpException;
use Symfony\Component\Yaml\Parser;

/**
 * Defines the file storage controller.
 */
class FileStorage implements StorageInterface {

  /**
   * The filesystem path for configuration objects.
   *
   * @var string
   */
  protected $directory = '';

  /**
   * A shared YAML dumper instance.
   *
   * @var Symfony\Component\Yaml\Dumper
   */
  protected $dumper;

  /**
   * A shared YAML parser instance.
   *
   * @var Symfony\Component\Yaml\Parser
   */
  protected $parser;

  /**
   * Constructs a new FileStorage controller.
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
   * Implements Drupal\Core\Config\StorageInterface::exists().
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
   * Implements Drupal\Core\Config\StorageInterface::write().
   *
   * @throws \Drupal\Core\Config\UnsupportedDataTypeConfigException
   * @throws \Drupal\Core\Config\StorageException
   */
  public function write($name, array $data) {
    try {
      $data = $this->encode($data);
    }
    catch(DumpException $e) {
      throw new UnsupportedDataTypeConfigException(String::format('Invalid data type for used in config: @name', array('@name' => $name)));
    }

    $target = $this->getFilePath($name);
    $status = @file_put_contents($target, $data);
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
   * Gets the YAML dumper instance.
   *
   * @return Symfony\Component\Yaml\Dumper
   */
  protected function getDumper() {
    if (!isset($this->dumper)) {
      $this->dumper = new Dumper();
      // Set Yaml\Dumper's default indentation for nested nodes/collections to
      // 2 spaces for consistency with Drupal coding standards.
      $this->dumper->setIndentation(2);
    }
    return $this->dumper;
  }

  /**
   * Gets the YAML parser instance.
   *
   * @return Symfony\Component\Yaml\Parser
   */
  protected function getParser() {
    if (!isset($this->parser)) {
      $this->parser = new Parser();
    }
    return $this->parser;
  }

  /**
   * Implements Drupal\Core\Config\StorageInterface::encode().
   *
   * @throws Symfony\Component\Yaml\Exception\DumpException
   */
  public function encode($data) {
    // The level where you switch to inline YAML is set to PHP_INT_MAX to ensure
    // this does not occur. Also set the exceptionOnInvalidType parameter to
    // TRUE, so exceptions are thrown for an invalid data type.
    return $this->getDumper()->dump($data, PHP_INT_MAX, 0, TRUE);
  }

  /**
   * Implements Drupal\Core\Config\StorageInterface::decode().
   *
   * @throws Symfony\Component\Yaml\Exception\ParseException
   */
  public function decode($raw) {
    $data = $this->getParser()->parse($raw);
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
      throw new StorageException($this->directory . '/ not found.');
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
