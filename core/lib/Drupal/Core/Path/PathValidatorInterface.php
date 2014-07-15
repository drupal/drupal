<?php

/**
 * @file
 * Contains Drupal\Core\Path\PathValidatorInterface
 */

namespace Drupal\Core\Path;

/**
 * Provides an interface for url path validators.
 */
interface PathValidatorInterface {

  /**
   * Checks if the URL path is valid and accessible by the current user.
   *
   * @param string $path
   *   The path to check.
   *
   * @return bool
   *   TRUE if the path is valid.
   */
  public function isValid($path);

}
