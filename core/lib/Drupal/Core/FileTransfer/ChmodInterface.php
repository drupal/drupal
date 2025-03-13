<?php

namespace Drupal\Core\FileTransfer;

@trigger_error('The ' . __NAMESPACE__ . '\ChmodInterface is deprecated in drupal:11.2.0 and is removed from drupal:12.0.0. There is no replacement. Use composer to manage the code for your site. See https://www.drupal.org/node/3512364', E_USER_DEPRECATED);

/**
 * Defines an interface to chmod files.
 *
 * @deprecated in drupal:11.2.0 and is removed from drupal:12.0.0. There is no
 *   replacement. Use composer to manage the code for your site.
 *
 * @see https://www.drupal.org/node/3512364
 */
interface ChmodInterface {

  /**
   * Changes the permissions of the file / directory specified in $path.
   *
   * @param string $path
   *   Path to change permissions of.
   * @param int $mode
   *   The new file permission mode to be passed to chmod().
   * @param bool $recursive
   *   Pass TRUE to recursively chmod the entire directory specified in $path.
   *
   * @see http://php.net/chmod
   */
  public function chmodJailed($path, $mode, $recursive);

}
