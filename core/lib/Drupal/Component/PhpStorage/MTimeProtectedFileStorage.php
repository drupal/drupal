<?php

/**
 * @file
 * Contains \Drupal\Component\PhpStorage\MTimeProtectedFileStorage.
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
 * Each file is stored in its own unique containing directory. The hash is
 * based on the virtual file name, the containing directory's mtime, and a
 * cryptographically hard to guess secret string. Thus, even if the hashed file
 * name is discovered and replaced by an untrusted file (e.g., via a
 * move_uploaded_file() invocation by a script that performs insufficient
 * validation), the directory's mtime gets updated in the process, invalidating
 * the hash and preventing the untrusted file from getting loaded. Also, the
 * file mtime will be checked providing security against overwriting in-place,
 * at the cost of an additional system call for every load() and exists().
 *
 * The containing directory is created with the same name as the virtual file
 * name (slashes replaced with hashmarks) to assist with debugging, since the
 * file itself is stored with a name that's meaningless to humans.
 */
class MTimeProtectedFileStorage extends MTimeProtectedFastFileStorage {

  /**
   * Implements Drupal\Component\PhpStorage\PhpStorageInterface::load().
   */
  public function load($name) {
    if (($filename = $this->checkFile($name)) !== FALSE) {
      // Inline parent::load() to avoid an expensive getFullPath() call.
      return (@include_once $filename) !== FALSE;
    }
    return FALSE;
  }

  /**
   * Implements Drupal\Component\PhpStorage\PhpStorageInterface::exists().
   */
  public function exists($name) {
    return $this->checkFile($name) !== FALSE;
  }

  /**
   * Determines whether a protected file exists and sets the filename too.
   *
   * @param string $name
   *   The virtual file name. Can be a relative path.
   * return string
   *   The full path where the file is if it is valid, FALSE otherwise.
   */
  protected function checkFile($name) {
    $filename = $this->getFullPath($name, $directory, $directory_mtime);
    return file_exists($filename) && filemtime($filename) <= $directory_mtime ? $filename : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getPath($name) {
    return $this->checkFile($name);
  }

}
