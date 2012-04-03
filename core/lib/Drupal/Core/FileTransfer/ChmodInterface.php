<?php

/**
 * @file
 * Definition of Drupal\Core\FileTransfer\ChmodInterface.
 */

namespace Drupal\Core\FileTransfer;

/**
 * Defines an interface to chmod files.
 */
interface ChmodInterface {

  /**
   * Changes the permissions of the file / directory specified in $path
   *
   * @param string $path
   *   Path to change permissions of.
   * @param int $mode
   *   See the $mode argument from http://php.net/chmod.
   * @param bool $recursive
   *   Pass TRUE to recursively chmod the entire directory specified in $path.
   *
   * @see http://php.net/chmod
   */
  function chmodJailed($path, $mode, $recursive);
}
