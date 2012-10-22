<?php

/**
 * @file
 * Definition of Drupal\Component\PhpStorage\FileStorage.
 */

namespace Drupal\Component\PhpStorage;

/**
 * Reads code as regular PHP files, but won't write them.
 */
class FileReadOnlyStorage implements PhpStorageInterface {

  /**
   * The directory where the files should be stored.
   *
   * @var string
   */
  protected $directory;

  /**
   * Constructs this FileStorage object.
   *
   * @param $configuration
   *   An associative array, containing at least two keys (the rest are ignored):
   *   - directory: The directory where the files should be stored.
   *   - bin: The storage bin. Multiple storage objects can be instantiated with
   *   the same configuration, but for different bins.
   */
  public function __construct(array $configuration) {

    $this->directory = $configuration['directory'] . '/' . $configuration['bin'];
  }

  /**
   * Implements Drupal\Component\PhpStorage\PhpStorageInterface::exists().
   */
  public function exists($name) {
    return file_exists($this->getFullPath($name));
  }

  /**
   * Implements Drupal\Component\PhpStorage\PhpStorageInterface::load().
   */
  public function load($name) {
    // The FALSE returned on failure is enough for the caller to handle this,
    // we do not want a warning too.
    return (@include_once $this->getFullPath($name)) !== FALSE;
  }

  /**
   * Implements Drupal\Component\PhpStorage\PhpStorageInterface::save().
   */
  public function save($name, $code) {
    return FALSE;
  }

  /**
   * Implements Drupal\Component\PhpStorage\PhpStorageInterface::delete().
   */
  public function delete($name) {
    return FALSE;
  }

  /**
   * Returns the full path where the file is or should be stored.
   */
  protected function getFullPath($name) {
    return $this->directory . '/' . $name;
  }

  /**
   * Implements Drupal\Component\PhpStorage\PhpStorageInterface::writeable().
   */
  function writeable() {
    return FALSE;
  }

  /**
   * Implements Drupal\Component\PhpStorage\PhpStorageInterface::deleteAll().
   */
  public function deleteAll() {
    return FALSE;
  }
}
