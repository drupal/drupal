<?php

/**
 * @file
 * Contains \Drupal\Core\Access\CsrfTokenGenerator.
 */

namespace Drupal\Core\Access;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\PrivateKey;
use Drupal\Core\Session\AccountInterface;

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
   * Constructs the token generator.
   *
   * @param \Drupal\Core\PrivateKey $private_key
   *   The private key service.
   */
  public function __construct(PrivateKey $private_key) {
    $this->privateKey = $private_key;
  }

  /**
   * Generates a token based on $value, the user session, and the private key.
   *
   * The generated token is based on the session ID of the current user. Normally,
   * anonymous users do not have a session, so the generated token will be
   * different on every page request. To generate a token for users without a
   * session, manually start a session prior to calling this function.
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
   * @see \Drupal\Core\Session\SessionManager::start()
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
   *
   * @return bool
   *   TRUE for a valid token, FALSE for an invalid token.
   */
  public function validate($token, $value = '') {
    return $token === $this->get($value);
  }

}
