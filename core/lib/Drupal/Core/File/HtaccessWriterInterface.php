<?php

namespace Drupal\Core\File;

/**
 * Interface for managing Apache .htaccess files.
 */
interface HtaccessWriterInterface {

  /**
   * Creates a .htaccess file in each Drupal files directory if it is missing.
   */
  public function ensure();

  /**
   * Creates a .htaccess file in the given directory.
   *
   * @param string $directory
   *   The directory.
   * @param bool $private
   *   (Optional) FALSE indicates that $directory should be a web-accessible
   *   directory. Defaults to TRUE which indicates a private directory.
   * @param bool $forceOverwrite
   *   (Optional) Set to TRUE to attempt to overwrite the existing .htaccess
   *   file if one is already present. Defaults to FALSE.
   *
   * @return bool
   *   TRUE if the .htaccess file was saved or already exists, FALSE otherwise.
   */
  public function save($directory, $private = TRUE, $forceOverwrite = FALSE);

  /**
   * Returns the list of system .htaccess files.
   *
   * @return array
   *   An associative array of htaccess files keyed by path with following keys:
   *   - title: The title of the location
   *   - directory: The directory of the location
   *   - private: Whether the .htaccess is placed in a directory considered to
   *     be private.
   */
  public function getHtaccessFiles();

}
