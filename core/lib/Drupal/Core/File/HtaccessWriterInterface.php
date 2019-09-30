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
   * Returns a list of the default protected directories.
   *
   * @return \Drupal\Core\File\ProtectedDirectory[]
   *   The default protected directories.
   */
  public function defaultProtectedDirs();

}
