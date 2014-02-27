<?php

/**
 * @file
 * Contains \Drupal\Component\PhpStorage\MTimeProtectedFastFileStorage.
 */

namespace Drupal\Component\PhpStorage;

/**
 * Stores PHP code in files with securely hashed names.
 *
 * The goal of this class is to ensure that if a PHP file is replaced with
 * an untrusted one, it does not get loaded. Since mtime granularity is 1
 * second, we cannot prevent an attack that happens within one second of the
 * initial save(). However, it is very unlikely for an attacker exploiting an
 * upload or file write vulnerability to also know when a legitimate file is
 * being saved, discover its hash, undo its file permissions, and override the
 * file with an upload all within a single second. Being able to accomplish
 * that would indicate a site very likely vulnerable to many other attack
 * vectors.
 *
 * Each file is stored in its own unique containing directory. The hash is based
 * on the virtual file name, the containing directory's mtime, and a
 * cryptographically hard to guess secret string. Thus, even if the hashed file
 * name is discovered and replaced by an untrusted file (e.g., via a
 * move_uploaded_file() invocation by a script that performs insufficient
 * validation), the directory's mtime gets updated in the process, invalidating
 * the hash and preventing the untrusted file from getting loaded.
 *
 * This class does not protect against overwriting a file in-place (e.g. a
 * malicious module that does a file_put_contents()) since this will not change
 * the mtime of the directory. MTimeProtectedFileStorage protects against this
 * at the cost of an additional system call for every load() and exists().
 *
 * The containing directory is created with the same name as the virtual file
 * name (slashes removed) to assist with debugging, since the file itself is
 * stored with a name that's meaningless to humans.
 */
class MTimeProtectedFastFileStorage extends FileStorage {

  /**
   * The secret used in the HMAC.
   *
   * @var string
   */
  protected $secret;

  /**
   * Constructs this MTimeProtectedFastFileStorage object.
   *
   * @param array $configuration
   *   An associated array, containing at least these keys (the rest are
   *   ignored):
   *   - directory: The directory where the files should be stored.
   *   - secret: A cryptographically hard to guess secret string.
   *   -bin. The storage bin. Multiple storage objects can be instantiated with
   *   the same configuration, but for different bins.
   */
  public function __construct(array $configuration) {
    parent::__construct($configuration);
    $this->secret = $configuration['secret'];
  }

  /**
   * Implements Drupal\Component\PhpStorage\PhpStorageInterface::save().
   */
  public function save($name, $data) {
    $this->ensureDirectory($this->directory);

    // Write the file out to a temporary location. Prepend with a '.' to keep it
    // hidden from listings and web servers.
    $temporary_path = $this->directory . '/.' . str_replace('/', '#', $name);
    if (!@file_put_contents($temporary_path, $data)) {
      return FALSE;
    }
    // The file will not be chmod() in the future so this is the final
    // permission.
    chmod($temporary_path, 0444);

    // Prepare a directory dedicated for just this file. Ensure it has a current
    // mtime so that when the file (hashed on that mtime) is moved into it, the
    // mtime remains the same (unless the clock ticks to the next second during
    // the rename, in which case we'll try again).
    $directory = $this->getContainingDirectoryFullPath($name);
    if (file_exists($directory)) {
      $this->unlink($directory);
    }
    $this->ensureDirectory($directory);

    // Move the file to its final place. The mtime of a directory is the time of
    // the last file create or delete in the directory. So the moving will
    // update the directory mtime. However, this update will very likely not
    // show up, because it has a coarse, one second granularity and typical
    // moves takes significantly less than that. In the unlucky case the clock
    // ticks during the move, we need to keep trying until the mtime we hashed
    // on and the updated mtime match.
    $previous_mtime = 0;
    $i = 0;
    while (($mtime = $this->getUncachedMTime($directory)) && ($mtime != $previous_mtime)) {
      $previous_mtime = $mtime;
      // Reset the file back in the temporary location if this is not the first
      // iteration.
      if ($i > 0) {
        rename($full_path, $temporary_path);
        // Make sure to not loop infinitely on a hopelessly slow filesystem.
        if ($i > 10) {
          $this->unlink($temporary_path);
          return FALSE;
        }
      }
      $full_path = $this->getFullPath($name, $directory, $mtime);
      rename($temporary_path, $full_path);
      $i++;
    }
    return TRUE;
  }

  /**
   * Returns the full path where the file is or should be stored.
   *
   * This function creates a file path that includes a unique containing
   * directory for the file and a file name that is a hash of the virtual file
   * name, a cryptographic secret, and the containing directory mtime. If the
   * file is overridden by an insecure upload script, the directory mtime gets
   * modified, invalidating the file, thus protecting against untrusted code
   * getting executed.
   *
   * @param string $name
   *   The virtual file name. Can be a relative path.
   * @param string $directory
   *   (optional) The directory containing the file. If not passed, this is
   *   retrieved by calling getContainingDirectoryFullPath().
   * @param int $directory_mtime
   *   (optional) The mtime of $directory. Can be passed to avoid an extra
   *   filesystem call when the mtime of the directory is already known.
   *
   * @return string
   *   The full path where the file is or should be stored.
   */
  protected function getFullPath($name, &$directory = NULL, &$directory_mtime = NULL) {
    if (!isset($directory)) {
      $directory = $this->getContainingDirectoryFullPath($name);
    }
    if (!isset($directory_mtime)) {
      $directory_mtime = file_exists($directory) ? filemtime($directory) : 0;
    }
    return $directory . '/' . hash_hmac('sha256', $name, $this->secret . $directory_mtime) . '.php';
  }

  /**
   * Returns the full path of the containing directory where the file is or should be stored.
   */
  protected function getContainingDirectoryFullPath($name) {
    return $this->directory . '/' . str_replace('/', '#', $name);
  }

  /**
   * Clears PHP's stat cache and returns the directory's mtime.
   */
  protected function getUncachedMTime($directory) {
    clearstatcache(TRUE, $directory);
    return filemtime($directory);
  }

}
