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
   * @param array $configuration
   *   An associative array, containing at least these two keys:
   *   - directory: The directory where the files should be stored.
   *   - bin: The storage bin. Multiple storage objects can be instantiated with
   *     the same configuration, but for different bins..
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
    $this->ensureDirectory(dirname($path));
    return (bool) file_put_contents($path, $code);
  }

  /**
   * Ensures the root directory exists and has the right permissions.
   *
   * @param string $directory
   *   The directory path.
   *
   * @param int $mode
   *   The mode, permissions, the directory should have.
   */
  protected function ensureDirectory($directory, $mode = 0777) {
    if (!file_exists($directory)) {
      // mkdir() obeys umask() so we need to mkdir() and chmod() manually.
      $parts = explode('/', $directory);
      $path = '';
      $delimiter = '';
      do {
        $part = array_shift($parts);
        $path .= $delimiter . $part;
        $delimiter = '/';
        // For absolute paths the first part will be empty.
        if ($part && !file_exists($path)) {
          mkdir($path);
          chmod($path, $mode);
        }
      } while ($parts);
    }
  }

  /**
   * Implements Drupal\Component\PhpStorage\PhpStorageInterface::delete().
   */
  public function delete($name) {
    $path = $this->getFullPath($name);
    if (file_exists($path)) {
      return $this->unlink($path);
    }
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
  public function writeable() {
    return TRUE;
  }

  /**
   * Implements Drupal\Component\PhpStorage\PhpStorageInterface::deleteAll().
   */
  public function deleteAll() {
    return $this->unlink($this->directory);
  }

  /**
   * Deletes files and/or directories in the specified path.
   *
   * If the specified path is a directory the method will
   * call itself recursively to process the contents. Once the contents have
   * been removed the directory will also be removed.
   *
   * @param string $path
   *   A string containing either a file or directory path.
   *
   * @return boolean
   *   TRUE for success or if path does not exist, FALSE in the event of an
   *   error.
   */
  protected function unlink($path) {
    if (file_exists($path)) {
      if (is_dir($path)) {
        // Ensure the folder is writable.
        @chmod($path, 0777);
        foreach (new \DirectoryIterator($path) as $fileinfo) {
          if (!$fileinfo->isDot()) {
            $this->unlink($fileinfo->getPathName());
          }
        }
        return @rmdir($path);
      }
      // Windows needs the file to be writable.
      @chmod($path, 0700);
      return @unlink($path);
    }
    // If there's nothing to delete return TRUE anyway.
    return TRUE;
  }
}
