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
    return $this->drupalGet($path, $options, $this->getBasicAuthHeaders($username, $password));
  }

  /**
   * Executes a form submission using basic authentication.
   *
   * @param string $path
   *   Location of the post form.
   * @param array $edit
   *   Field data in an associative array.
   * @param string $submit
   *   Value of the submit button whose click is to be emulated.
   * @param string $username
   *   The username to use for basic authentication.
   * @param string $password
   *   The password to use for basic authentication.
   * @param array $options
   *   Options to be forwarded to the url generator.
   * @param string $form_html_id
   *   (optional) HTML ID of the form to be submitted.
   * @param string $extra_post
   *   (optional) A string of additional data to append to the POST submission.
   *
   * @return string
   *   The retrieved HTML string.
   *
   * @see \Drupal\simpletest\WebTestBase::drupalPostForm()
   */
  protected function basicAuthPostForm($path, $edit, $submit, $username, $password, array $options = array(), $form_html_id = NULL, $extra_post = NULL) {
    return $this->drupalPostForm($path, $edit, $submit, $options, $this->getBasicAuthHeaders($username, $password), $form_html_id, $extra_post);
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
    return ['Authorization: Basic ' . base64_encode("$username:$password")];
  }

}
