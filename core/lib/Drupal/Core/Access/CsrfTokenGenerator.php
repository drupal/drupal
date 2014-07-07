<?php

/**
 * @file
 * Contains \Drupal\Core\Access\CsrfTokenGenerator.
 */

namespace Drupal\Core\Access;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\PrivateKey;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Site\Settings;

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
   * The generated token is based on the session of the current user. Normally,
   * anonymous users do not have a session, so the generated token will be
   * different on every page request. To generate a token for users without a
   * session, manually start a session prior to calling this function.
   *
   * @param string $value
   *   (optional) An additional value to base the token on.
   *
   * @return string
   *   A 43-character URL-safe token for validation, based on the token seed,
   *   the hash salt provided by Settings::getHashSalt(), and the
   *   'drupal_private_key' configuration variable.
   *
   * @see \Drupal\Core\Site\Settings::getHashSalt()
   * @see \Drupal\Core\Session\SessionManager::start()
   */
  public function get($value = '') {
    if (empty($_SESSION['csrf_token_seed'])) {
      $_SESSION['csrf_token_seed'] = Crypt::randomBytesBase64();
    }

    return $this->computeToken($_SESSION['csrf_token_seed'], $value);
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
    if (empty($_SESSION['csrf_token_seed'])) {
      return FALSE;
    }

    return $token === $this->computeToken($_SESSION['csrf_token_seed'], $value);
  }

  /**
   * Generates a token based on $value, the token seed, and the private key.
   *
   * @param string $seed
   *   The per-session token seed.
   * @param string $value
   *   (optional) An additional value to base the token on.
   *
   * @return string
   *   A 43-character URL-safe token for validation, based on the token seed,
   *   the hash salt provided by Settings::getHashSalt(), and the
   *   'drupal_private_key' configuration variable.
   *
   * @see \Drupal\Core\Site\Settings::getHashSalt()
   */
  protected function computeToken($seed, $value = '') {
    return Crypt::hmacBase64($value, $seed . $this->privateKey->get() . Settings::getHashSalt());
  }

}
