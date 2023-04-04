<?php

namespace Drupal\Core\Password;

/**
 * Secure PHP password hashing functions.
 *
 * @see https://www.php.net/manual/en/book.password.php
 */
class PhpPassword implements PasswordInterface {

  /**
   * Constructs a new password hashing instance.
   *
   * @param string $algorithm
   *   The hashing algorithm to use. Defaults to PHP default.
   * @param array $options
   *   List of options. Refer to password_hash() for available options.
   *
   * @see https://www.php.net/password_hash
   */
  public function __construct(
    protected string $algorithm = PASSWORD_DEFAULT,
    protected array $options = []
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public function hash(#[\SensitiveParameter] $password) {
    // Prevent DoS attacks by refusing to hash large passwords.
    if (strlen($password) > static::PASSWORD_MAX_LENGTH) {
      return FALSE;
    }

    return password_hash($password, $this->algorithm, $this->options);
  }

  /**
   * {@inheritdoc}
   */
  public function check(#[\SensitiveParameter] $password, #[\SensitiveParameter] $hash) {
    // Prevent DoS attacks by refusing to check large passwords.
    if (strlen($password) > static::PASSWORD_MAX_LENGTH) {
      return FALSE;
    }

    return password_verify($password, $hash);
  }

  /**
   * {@inheritdoc}
   */
  public function needsRehash(#[\SensitiveParameter] $hash) {
    return password_needs_rehash($hash, $this->algorithm, $this->options);
  }

}
