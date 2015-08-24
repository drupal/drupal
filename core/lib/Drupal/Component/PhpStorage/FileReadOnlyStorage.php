<?php

/**
 * @file
 * Contains \Drupal\Component\PhpStorage\FileReadOnlyStorage.
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
   * {@inheritdoc}
   */
  public function getFullPath($name) {
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

  /**
   * {@inheritdoc}
   */
  public function listAll() {
    $names = array();
    if (file_exists($this->directory)) {
      foreach (new \DirectoryIterator($this->directory) as $fileinfo) {
        if (!$fileinfo->isDot()) {
          $name = $fileinfo->getFilename();
          if ($name != '.htaccess') {
            $names[] = $name;
          }
        }
      }
    }
    return $names;
  }

  /**
   * {@inheritdoc}
   */
  public function garbageCollection() {
  }

}
