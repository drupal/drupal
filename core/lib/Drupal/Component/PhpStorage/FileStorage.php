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
   * Ensures the requested directory exists and has the right permissions.
   *
   * For compatibility with open_basedir, the requested directory is created
   * using a recursion logic that is based on the relative directory path/tree:
   * It works from the end of the path recursively back towards the root
   * directory, until an existing parent directory is found. From there, the
   * subdirectories are created.
   *
   * @param string $directory
   *   The directory path.
   * @param int $mode
   *   The mode, permissions, the directory should have.
   * @param bool $is_backwards_recursive
   *   Internal use only.
   *
   * @return bool
   *   TRUE if the directory exists or has been created, FALSE otherwise.
   */
  protected function ensureDirectory($directory, $mode = 0777, $is_backwards_recursive = FALSE) {
    // If the directory exists already, there's nothing to do.
    if (is_dir($directory)) {
      return TRUE;
    }
    // Otherwise, try to create the directory and ensure to set its permissions,
    // because mkdir() obeys the umask of the current process.
    if (is_dir($parent = dirname($directory))) {
      // If the parent directory exists, then the backwards recursion must end,
      // regardless of whether the subdirectory could be created.
      if ($status = mkdir($directory)) {
        // Only try to chmod() if the subdirectory could be created.
        $status = chmod($directory, $mode);
      }
      return $is_backwards_recursive ? TRUE : $status;
    }
    // If the parent directory and the requested directory does not exist and
    // could not be created above, walk the requested directory path back up
    // until an existing directory is hit, and from there, recursively create
    // the sub-directories. Only if that recursion succeeds, create the final,
    // originally requested subdirectory.
    return $this->ensureDirectory($parent, $mode, TRUE) && mkdir($directory) && chmod($directory, $mode);
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
