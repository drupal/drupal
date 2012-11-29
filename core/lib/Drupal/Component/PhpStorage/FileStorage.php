<?php

/**
 * @file
 * Definition of Drupal\Component\PhpStorage\FileStorage.
 */

namespace Drupal\Component\PhpStorage;

/**
 * Stores the code as regular PHP files.
 */
class FileStorage implements PhpStorageInterface {

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
   *   An associative array, containing at least these two keys:
   *   - directory: The directory where the files should be stored.
   *   - bin: The storage bin. Multiple storage objects can be instantiated with the
   *     same configuration, but for different bins..
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
    $path = $this->getFullPath($name);
    mkdir(dirname($path), 0700, TRUE);
    return (bool) file_put_contents($path, $code);
  }

  /**
   * Implements Drupal\Component\PhpStorage\PhpStorageInterface::delete().
   */
  public function delete($name) {
    $path = $this->getFullPath($name);
    return @unlink($path);
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
    return TRUE;
  }

  /**
   * Implements Drupal\Component\PhpStorage\PhpStorageInterface::deleteAll().
   */
  function deleteAll() {
    return file_unmanaged_delete_recursive($this->directory, array(__CLASS__, 'filePreDeleteCallback'));
  }

  /**
   * Ensures files and directories are deletable.
   */
  public static function filePreDeleteCallback($path) {
    if (file_exists($path)) {
      chmod($path, 0700);
    }
  }
}
