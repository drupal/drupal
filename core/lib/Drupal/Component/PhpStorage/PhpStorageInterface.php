<?php

/**
 * @file
 * Contains \Drupal\Component\PhpStorage\PhpStorageInterface.
 */

namespace Drupal\Component\PhpStorage;

/**
 * Stores and loads PHP code.
 *
 * Each interface function takes $name as a parameter. This is a virtual file
 * name: for example, 'foo.php' or 'some/relative/path/to/foo.php'. The
 * storage implementation may store these as files within the local file system,
 * use a remote stream, combine multiple virtual files into an archive, store
 * them in database records, or use some other storage technique.
 */
interface PhpStorageInterface {

  /**
   * Checks whether the PHP code exists in storage.
   *
   * @param string $name
   *   The virtual file name. Can be a relative path.
   *
   * @return bool
   *   TRUE if the virtual file exists, FALSE otherwise.
   */
  public function exists($name);

  /**
   * Loads PHP code from storage.
   *
   * Depending on storage implementation, exists() checks can be expensive, so
   * this function may be called for a file that doesn't exist, and that should
   * not result in errors. This function does not return anything, so it is
   * up to the caller to determine if any code was loaded (for example, check
   * class_exists() or function_exists() for what was expected in the code).
   *
   * @param string $name
   *   The virtual file name. Can be a relative path.
   */
  public function load($name);

  /**
   * Saves PHP code to storage.
   *
   * @param string $name
   *   The virtual file name. Can be a relative path.
   * @param string $code
   *    The PHP code to be saved.
   *
   * @return bool
   *   TRUE if the save succeeded, FALSE if it failed.
   */
  public function save($name, $code);

  /**
   * Whether this is a writeable storage.
   *
   * @return bool
   */
  public function writeable();

  /**
   * Deletes PHP code from storage.
   *
   * @param string $name
   *   The virtual file name. Can be a relative path.
   *
   * @return bool
   *   TRUE if the delete succeeded, FALSE if it failed.
   */
  public function delete($name);

  /**
   * Removes all files in this bin.
   */
  public function deleteAll();

  /**
   * Gets the full file path.
   *
   * @param string $name
   *   The virtual file name. Can be a relative path.
   *
   * @return string|FALSE
   *   The full file path for the provided name. Return FALSE if the
   *   implementation needs to prevent access to the file.
   */
  public function getFullPath($name);

  /**
   * Lists all the files in the storage.
   *
   * @return array
   *   Array of filenames.
   */
  public function listAll();

  /**
   * Performs garbage collection on the storage.
   *
   * The storage may choose to delete expired or invalidated items.
   */
  public function garbageCollection();

}
