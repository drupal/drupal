<?php

/**
 * @file
 * Definition of Drupal\Core\StreamWrapper\PublicStream.
 */

namespace Drupal\Core\StreamWrapper;

/**
 * Defines a Drupal public (public://) stream wrapper class.
 *
 * Provides support for storing publicly accessible files with the Drupal file
 * interface.
 */
class PublicStream extends LocalStream {

  /**
   * Implements Drupal\Core\StreamWrapper\LocalStream::getDirectoryPath()
   */
  public function getDirectoryPath() {
    return variable_get('file_public_path', conf_path() . '/files');
  }

  /**
   * Implements Drupal\Core\StreamWrapper\StreamWrapperInterface::getExternalUrl().
   *
   * @return string
   *   Returns the HTML URI of a public file.
   */
  function getExternalUrl() {
    $path = str_replace('\\', '/', $this->getTarget());
    return $GLOBALS['base_url'] . '/' . self::getDirectoryPath() . '/' . drupal_encode_path($path);
  }
}
