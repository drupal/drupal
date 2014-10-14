<?php

/**
 * @file
 * Definition of Drupal\Core\StreamWrapper\PrivateStream.
 */

namespace Drupal\Core\StreamWrapper;

use Drupal\Core\Routing\UrlGeneratorTrait;

/**
 * Drupal private (private://) stream wrapper class.
 *
 * Provides support for storing privately accessible files with the Drupal file
 * interface.
 */
class PrivateStream extends LocalStream {

  use UrlGeneratorTrait;

  /**
   * {@inheritdoc}
   */
  public static function getType() {
    return StreamWrapperInterface::LOCAL_NORMAL;
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return t('Private files');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return t('Private local files served by Drupal.');
  }

  /**
   * Implements Drupal\Core\StreamWrapper\LocalStream::getDirectoryPath()
   */
  public function getDirectoryPath() {
    return \Drupal::config('system.file')->get('path.private');
  }

  /**
   * Implements Drupal\Core\StreamWrapper\StreamWrapperInterface::getExternalUrl().
   *
   * @return string
   *   Returns the HTML URI of a private file.
   */
  function getExternalUrl() {
    $path = str_replace('\\', '/', $this->getTarget());
    return $this->url('system.private_file_download', ['filepath' => $path], ['absolute' => TRUE]);
  }
}
