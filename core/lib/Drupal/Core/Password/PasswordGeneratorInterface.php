<?php

namespace Drupal\Core\Password;

/**
 * Interface for generating passwords.
 */
interface PasswordGeneratorInterface {

  /**
   * Generates a password.
   *
   * @param int $length
   *   (optional) The length of the password.
   *
   * @return string
   *   The password.
   */
  public function generate(int $length = 10): string;

}
