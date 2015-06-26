<?php

/**
 * @file
 * Contains \Drupal\Core\StreamWrapper\PrivateStream.
 */

namespace Drupal\Core\StreamWrapper;

use Drupal\Core\Routing\UrlGeneratorTrait;
use Drupal\Core\Site\Settings;

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
   * {@inheritdoc}
   */
  public function getDirectoryPath() {
    return static::basePath();
  }

  /**
   * {@inheritdoc}
   */
  public function getExternalUrl() {
    $path = str_replace('\\', '/', $this->getTarget());
    return $this->url('system.private_file_download', ['filepath' => $path], ['absolute' => TRUE]);
  }

  /**
   * Returns the base path for private://.
   *
   * @return string
   *   The base path for private://.
   */
  public static function basePath() {
    return Settings::get('file_private_path');
  }

}
