<?php

/**
 * @file
 * Contains \Drupal\user\MigratePassword.
 */

namespace Drupal\user;

use Drupal\Core\Password\PasswordInterface;

/**
 * Replaces the original 'password' service in order to prefix the MD5 re-hashed
 * passwords with the 'U' flag. The new salted hash is recreated on first login
 * similarly to the D6->D7 upgrade path.
 */
class MigratePassword implements PasswordInterface {

  /**
   * The original password service.
   *
   * @var \Drupal\Core\Password\PasswordInterface
   */
  protected $originalPassword;

  /**
   * Indicates if MD5 password prefixing is enabled.
   */
  protected $enabled = FALSE;

  /**
   * Builds the replacement password service class.
   *
   * @param \Drupal\Core\Password\PasswordInterface $original_password
   *   The password object.
   */
  public function __construct(PasswordInterface $original_password) {
    $this->originalPassword = $original_password;
  }

  /**
   * {@inheritdoc}
   */
  public function check($password, $hash) {
    return $this->originalPassword->check($password, $hash);
  }

  /**
   * {@inheritdoc}
   */
  public function needsRehash($hash) {
    return $this->originalPassword->needsRehash($hash);
  }

  /**
   * {@inheritdoc}
   */
  public function hash($password) {
    $hash = $this->originalPassword->hash($password);

    // Allow prefixing only if the service was asked to prefix. Check also if
    // the $password pattern is conforming to a MD5 result.
    if ($this->enabled && preg_match('/^[0-9a-f]{32}$/', $password)) {
      $hash = 'U' . $hash;
    }

    return $hash;
  }

  /**
   * Enables the MD5 password prefixing.
   */
  public function enableMd5Prefixing() {
    $this->enabled = TRUE;
  }

  /**
   * Disables the MD5 password prefixing.
   */
  public function disableMd5Prefixing() {
    $this->enabled = FALSE;
  }

  /**
   * Implements the PhpassHashedPassword::getCountLog2() method.
   *
   * @todo: Revisit this whole alternate password service:
   *   https://www.drupal.org/node/2540594.
   */
  public function getCountLog2($setting) {
    return $this->originalPassword->getCountLog2($setting);
  }

}
