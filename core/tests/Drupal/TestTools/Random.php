<?php

namespace Drupal\TestTools;

use Drupal\Component\Utility\Random as RandomUtility;

/**
 * Provides random generator utility static methods.
 */
abstract class Random {

  /**
   * The random generator.
   */
  protected static RandomUtility $randomGenerator;

  /**
   * Gets the random generator for the utility methods.
   *
   * @return \Drupal\Component\Utility\Random
   *   The random generator.
   */
  public static function getGenerator(): RandomUtility {
    if (!isset(static::$randomGenerator)) {
      static::$randomGenerator = new RandomUtility();
    }
    return static::$randomGenerator;
  }

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
  public static function string(int $length = 8): string {
    if ($length < 4) {
      return static::getGenerator()->string($length, TRUE, [static::class, 'stringValidate']);
    }

    // To prevent the introduction of random test failures, ensure that the
    // returned string contains a character that needs to be escaped in HTML by
    // injecting an ampersand into it.
    $replacement_pos = floor($length / 2);
    // Remove 2 from the length to account for the ampersand and greater than
    // characters.
    $string = static::getGenerator()->string($length - 2, TRUE, [static::class, 'stringValidate']);
    return substr_replace($string, '>&', $replacement_pos, 0);
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
   */
  public static function stringValidate(string $string): bool {
    // Consecutive spaces causes issues for link validation.
    if (preg_match('/\s{2,}/', $string)) {
      return FALSE;
    }

    // Starting or ending with a space means that length might not be what is
    // expected.
    if (preg_match('/^\s|\s$/', $string)) {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Generates a unique random string containing letters and numbers.
   *
   * Do not use this method when testing unvalidated user input. Instead, use
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
  public static function machineName(int $length = 8): string {
    return static::getGenerator()->machineName($length, TRUE);
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
  public static function object(int $size = 4): \stdClass {
    return static::getGenerator()->object($size);
  }

}
