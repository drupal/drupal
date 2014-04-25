<?php

/**
 * @file
 * Definition of Drupal\Core\StreamWrapper\PublicStream.
 */

namespace Drupal\Core\StreamWrapper;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Site\Settings;

/**
 * Defines a Drupal public (public://) stream wrapper class.
 *
 * Provides support for storing publicly accessible files with the Drupal file
 * interface.
 */
class PublicStream extends LocalStream {

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
    return $GLOBALS['base_url'] . '/' . self::getDirectoryPath() . '/' . UrlHelper::encodePath($path);
  }

  /**
   * Returns the base path for public://.
   *
   * @return string
   *   The base path for public:// typically sites/default/files.
   */
  public static function basePath() {
    $base_path = Settings::get('file_public_path', conf_path() . '/files');
    return $base_path;
  }

}
