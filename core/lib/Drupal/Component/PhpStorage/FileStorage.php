<?php

namespace Drupal\Component\PhpStorage;

use Drupal\Component\FileSecurity\FileSecurity;

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
   * {@inheritdoc}
   */
  public function exists($name) {
    return file_exists($this->getFullPath($name));
  }

  /**
   * {@inheritdoc}
   */
  public function load($name) {
    // The FALSE returned on failure is enough for the caller to handle this,
    // we do not want a warning too.
    return (@include_once $this->getFullPath($name)) !== FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function save($name, $code) {
    $path = $this->getFullPath($name);
    $directory = dirname($path);
    $this->ensureDirectory($directory);
    return (bool) file_put_contents($path, $code);
  }

  /**
   * Ensures the directory exists, has the right permissions, and a .htaccess.
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
   */
  protected function ensureDirectory($directory, $mode = 0777) {
    if ($this->createDirectory($directory, $mode)) {
      FileSecurity::writeHtaccess($directory);
    }
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
   *
   * @return bool
   *   TRUE if the directory exists or has been created, FALSE otherwise.
   */
  protected function createDirectory($directory, $mode = 0777) {
    // If the directory exists already, there's nothing to do.
    if (is_dir($directory)) {
      return TRUE;
    }

    // If the parent directory doesn't exist, try to create it.
    $parent_exists = is_dir($parent = dirname($directory));
    if (!$parent_exists) {
      $parent_exists = $this->createDirectory($parent, $mode);
    }

    // If parent exists, try to create the directory and ensure to set its
    // permissions, because mkdir() obeys the umask of the current process.
    if ($parent_exists) {
      // We hide warnings and ignore the return because there may have been a
      // race getting here and the directory could already exist.
      @mkdir($directory);
      // Only try to chmod() if the subdirectory could be created.
      if (is_dir($directory)) {
        // Avoid writing permissions if possible.
        if (fileperms($directory) !== $mode) {
          return chmod($directory, $mode);
        }
        return TRUE;
      }
      else {
        // Something failed and the directory doesn't exist.
        trigger_error('mkdir(): Permission Denied', E_USER_WARNING);
      }
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function delete($name) {
    $path = $this->getFullPath($name);
    if (file_exists($path)) {
      return $this->unlink($path);
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getFullPath($name) {
    return $this->directory . '/' . $name;
  }

  /**
   * {@inheritdoc}
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
   * @return bool
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

  /**
   * {@inheritdoc}
   */
  public function listAll() {
    $names = [];
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
