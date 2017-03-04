<?php

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
   * Returns the standard .htaccess lines that Drupal writes to file directories.
   *
   * @param bool $private
   *   (optional) Set to FALSE to return the .htaccess lines for an open and
   *   public directory. The default is TRUE, which returns the .htaccess lines
   *   for a private and protected directory.
   *
   * @return string
   *   The desired contents of the .htaccess file.
   *
   * @see file_create_htaccess()
   */
  public static function htaccessLines($private = TRUE) {
    $lines = <<<EOF
# Turn off all options we don't need.
Options -Indexes -ExecCGI -Includes -MultiViews

# Set the catch-all handler to prevent scripts from being executed.
SetHandler Drupal_Security_Do_Not_Remove_See_SA_2006_006
<Files *>
  # Override the handler again if we're run later in the evaluation list.
  SetHandler Drupal_Security_Do_Not_Remove_See_SA_2013_003
</Files>

# If we know how to do it safely, disable the PHP engine entirely.
<IfModule mod_php5.c>
  php_flag engine off
</IfModule>
EOF;

    if ($private) {
      $lines = <<<EOF
# Deny all requests from Apache 2.4+.
<IfModule mod_authz_core.c>
  Require all denied
</IfModule>

# Deny all requests from Apache 2.0-2.2.
<IfModule !mod_authz_core.c>
  Deny from all
</IfModule>
$lines
EOF;
    }

    return $lines;
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
      $htaccess_path = $directory . '/.htaccess';
      if (!file_exists($htaccess_path) && file_put_contents($htaccess_path, static::htaccessLines())) {
        @chmod($htaccess_path, 0444);
      }
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
   * @param bool $is_backwards_recursive
   *   Internal use only.
   *
   * @return bool
   *   TRUE if the directory exists or has been created, FALSE otherwise.
   */
  protected function createDirectory($directory, $mode = 0777, $is_backwards_recursive = FALSE) {
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
    return $this->createDirectory($parent, $mode, TRUE) && mkdir($directory) && chmod($directory, $mode);
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
  public function writeable() {
    return TRUE;
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
