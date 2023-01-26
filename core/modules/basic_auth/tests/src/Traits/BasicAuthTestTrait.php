<?php

namespace Drupal\Tests\basic_auth\Traits;

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
   *   (optional) Options to be forwarded to the URL generator.
   *
   * @return string
   *   The retrieved HTML string, also available as $this->getRawContent().
   */
  protected function basicAuthGet($path, $username, $password, array $options = []) {
    return $this->drupalGet($path, $options, $this->getBasicAuthHeaders($username, $password));
  }

  /**
   * Returns HTTP headers that can be used for basic authentication in Curl.
   *
   * @param string $username
   *   The username to use for basic authentication.
   * @param string $password
   *   The password to use for basic authentication.
   *
   * @return array
   *   An array of raw request headers as used by curl_setopt().
   */
  protected function getBasicAuthHeaders($username, $password) {
    // Set up Curl to use basic authentication with the test user's credentials.
    return ['Authorization' => 'Basic ' . base64_encode("$username:$password")];
  }

}
