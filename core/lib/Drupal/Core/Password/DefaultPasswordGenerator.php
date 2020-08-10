<?php

namespace Drupal\Core\Password;

/**
 * Provides a default password generator.
 */
class DefaultPasswordGenerator implements PasswordGeneratorInterface {

  /**
   * The allowed characters for the password.
   *
   * Note that the number 0 and the letter 'O' have been removed to avoid
   * confusion between the two. The same is true of 'I', 1, and 'l'.
   *
   * @var string
   */
  protected $allowedChars = 'abcdefghijkmnopqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789';

  /**
   * Generates a password.
   *
   * @param int $length
   *   (optional) The length of the password.
   *
   * @return string
   *   The password.
   */
  public function generate(int $length = 10): string {
    // The maximum integer we want from random_int().
    $max = strlen($this->allowedChars) - 1;

    $pass = '';

    for ($i = 0; $i < $length; $i++) {
      $pass .= $this->allowedChars[random_int(0, $max)];
    }

    return $pass;
  }

}
