<?php

declare(strict_types=1);

namespace Drupal\Tests;

use Drupal\TestTools\Random;

/**
 * Provides random generator utility methods.
 */
trait RandomGeneratorTrait {

  /**
   * Generates a pseudo-random string of ASCII characters of codes 32 to 126.
   *
   * Do not use this method when special characters are not possible (e.g., in
   * machine or file names that have already been validated); instead, use
   * \Drupal\Tests\RandomGeneratorTrait::randomMachineName(). If $length is
   * greater than 3 the random string will include at least one ampersand ('&')
   * and at least one greater than ('>') character to ensure coverage for
   * special characters and avoid the introduction of random test failures.
   *
   * @param int $length
   *   Length of random string to generate.
   *
   * @return string
   *   Pseudo-randomly generated unique string including special characters.
   *
   * @see \Drupal\Component\Utility\Random::string()
   */
  public function randomString($length = 8) {
    return Random::string($length);
  }

  /**
   * Callback for random string validation.
   *
   * @see \Drupal\Component\Utility\Random::string()
   *
   * @param string $string
   *   The random string to validate.
   *
   * @return bool
   *   TRUE if the random string is valid, FALSE if not.
   *
   * @deprecated in drupal:10.2.0 and is removed from drupal:11.0.0.
   *   Use \Drupal\TestTools\Random::stringValidate() instead.
   *
   * @see https://www.drupal.org/node/3358389
   */
  public function randomStringValidate($string) {
    @trigger_error(__METHOD__ . "() is deprecated in drupal:10.2.0 and is removed from drupal:11.0.0. Use \Drupal\TestTools\Random::stringValidate() instead. See https://www.drupal.org/node/3358389", E_USER_DEPRECATED);

    return Random::stringValidate($string);
  }

  /**
   * Generates a unique random string containing letters and numbers.
   *
   * Do not use this method when testing non validated user input. Instead, use
   * \Drupal\Tests\RandomGeneratorTrait::randomString().
   *
   * @param int $length
   *   Length of random string to generate.
   *
   * @return string
   *   Randomly generated unique string.
   *
   * @see \Drupal\Component\Utility\Random::name()
   */
  protected function randomMachineName($length = 8): string {
    return Random::machineName($length);
  }

  /**
   * Generates a random PHP object.
   *
   * @param int $size
   *   The number of random keys to add to the object.
   *
   * @return object
   *   The generated object, with the specified number of random keys. Each key
   *   has a random string value.
   *
   * @see \Drupal\Component\Utility\Random::object()
   */
  public function randomObject($size = 4) {
    return Random::object($size);
  }

  /**
   * Gets the random generator for the utility methods.
   *
   * @return \Drupal\Component\Utility\Random
   *   The random generator.
   */
  protected function getRandomGenerator() {
    return Random::getGenerator();
  }

}
