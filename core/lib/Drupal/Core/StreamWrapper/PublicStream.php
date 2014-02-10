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
    return $GLOBALS['base_url'] . '/' . self::getDirectoryPath() . '/' . drupal_encode_path($path);
  }

  /**
   * Returns the base path for public://.
   *
   * @return string
   *   The base path for public:// typically sites/default/files.
   */
  public static function basePath() {
    $base_path = settings()->get('file_public_path', conf_path() . '/files');
    if ($test_prefix = drupal_valid_test_ua()) {
      // Append the testing suffix unless already given.
      // @see \Drupal\simpletest\WebTestBase::setUp()
      if (strpos($base_path, '/simpletest/' . substr($test_prefix, 10)) === FALSE) {
        return $base_path . '/simpletest/' . substr($test_prefix, 10);
      }
    }
    return $base_path;
  }

}
