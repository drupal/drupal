<?php

/**
 * @file
 * Contains \Drupal\basic_auth\Tests\BasicAuthTestTrait.
 */

namespace Drupal\basic_auth\Tests;

/**
 * Provides common functionality for Basic Authentication test classes.
 */
trait BasicAuthTestTrait {

  /**
   * Retrieves a Drupal path or an absolute path using basic authentication.
   *
   * @param \Drupal\Core\Url|string $path
   *   Drupal path or URL to load into the internal browser.
   * @param string $username
   *   The username to use for basic authentication.
   * @param string $password
   *   The password to use for basic authentication.
   * @param array $options
   *   (optional) Options to be forwarded to the url generator.
   *
   * @return string
   *   The retrieved HTML string, also available as $this->getRawContent().
   */
  protected function basicAuthGet($path, $username, $password, array $options = []) {
    // Set up Curl to use basic authentication with the test user's credentials.
    $headers = ['Authorization: Basic ' . base64_encode("$username:$password")];

    return $this->drupalGet($path, $options, $headers);
  }

}
