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
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

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
   * Sets the current user.
   *
   * @param \Drupal\Core\Session\AccountInterface|null $current_user
   *  The current user service.
   */
  public function setCurrentUser(AccountInterface $current_user = NULL) {
    $this->currentUser = $current_user;
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
   * @see drupal_session_start()
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
    return ($skip_anonymous && $this->currentUser->isAnonymous()) || ($token === $this->get($value));
  }

}
