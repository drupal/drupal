<?php

/**
 * @file
 * Definition of Drupal\Core\StreamWrapper\TemporaryStream.
 */

namespace Drupal\Core\StreamWrapper;

/**
 * Defines a Drupal temporary (temporary://) stream wrapper class.
 *
 * Provides support for storing temporarily accessible files with the Drupal
 * file interface.
 */
class TemporaryStream extends LocalStream {

  /**
   * Implements Drupal\Core\StreamWrapper\LocalStream::getDirectoryPath()
   */
  public function getDirectoryPath() {
    return file_directory_temp();
  }

  /**
   * Implements Drupal\Core\StreamWrapper\StreamWrapperInterface::getExternalUrl().
   */
  public function getExternalUrl() {
    $path = str_replace('\\', '/', $this->getTarget());
    return url('system/temporary/' . $path, array('absolute' => TRUE));
  }
}
