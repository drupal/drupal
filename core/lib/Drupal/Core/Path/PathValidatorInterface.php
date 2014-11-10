<?php

/**
 * @file
 * Contains \Drupal\Core\Path\PathValidatorInterface
 */

namespace Drupal\Core\Path;

/**
 * Provides an interface for url path validators.
 */
interface PathValidatorInterface {

  /**
   * Returns an URL object, if the path is valid and accessible.
   *
   * @param string $path
   *   The path to check.
   *
   * @return \Drupal\Core\Url|false
   *   The url object, or FALSE if the path is not valid.
   */
  public function getUrlIfValid($path);

  /**
   * Returns an URL object, if the path is valid.
   *
   * Unlike getUrlIfValid(), access check is not performed. Do not use this
   * method if the $path is about to be presented to a user.
   *
   * @param string $path
   *   The path to check.
   *
   * @return \Drupal\Core\Url|false
   *   The url object, or FALSE if the path is not valid.
   */
  public function getUrlIfValidWithoutAccessCheck($path);

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
