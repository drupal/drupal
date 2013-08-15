<?php

/**
 * @file
 * Contains \Drupal\Core\Access\CsrfTokenGenerator.
 */

namespace Drupal\Core\Access;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\PrivateKey;
use Symfony\Component\HttpFoundation\Request;

/**
 * Generates and validates CSRF tokens.
 *
 * @see \Drupal\Tests\Core\Access\CsrfTokenGeneratorTest
 */
class CsrfTokenGenerator {

  /**
   * The private key service.
   *
   * @var \Drupal\Core\PrivateKey
   */
  protected $privateKey;

  /**
   * The current request object.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * Constructs the token generator.
   *
   * @param \Drupal\Core\PrivateKey $private_key
   *   The private key service.
   */
  public function __construct(PrivateKey $private_key) {
    $this->privateKey = $private_key;
  }

  /**
   * Sets the $request property.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The HttpRequest object representing the current request.
   */
  public function setRequest(Request $request) {
    $this->request = $request;
  }

  /**
   * Generates a token based on $value, the user session, and the private key.
   *
   * @param string $value
   *   (optional) An additional value to base the token on.
   *
   * @return string
   *   A 43-character URL-safe token for validation, based on the user session
   *   ID, the hash salt provided by drupal_get_hash_salt(), and the
   *   'drupal_private_key' configuration variable.
   *
   * @see drupal_get_hash_salt()
   */
  public function get($value = '') {
    return Crypt::hmacBase64($value, session_id() . $this->privateKey->get() . drupal_get_hash_salt());
  }

  /**
   * Validates a token based on $value, the user session, and the private key.
   *
   * @param string $token
   *   The token to be validated.
   * @param string $value
   *   (optional) An additional value to base the token on.
   * @param bool $skip_anonymous
   *   (optional) Set to TRUE to skip token validation for anonymous users.
   *
   * @return bool
   *   TRUE for a valid token, FALSE for an invalid token. When $skip_anonymous
   *   is TRUE, the return value will always be TRUE for anonymous users.
   */
  public function validate($token, $value = '', $skip_anonymous = FALSE) {
    $user = $this->request->attributes->get('_account');

    return ($skip_anonymous && $user->isAnonymous()) || ($token == $this->get($value));
  }

}
