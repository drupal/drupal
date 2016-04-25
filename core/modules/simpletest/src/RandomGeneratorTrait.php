<?php

namespace Drupal\simpletest;

use Drupal\Component\Utility\Random;

/**
 * Provides random generator utility methods.
 */
trait RandomGeneratorTrait {

  /**
   * The random generator.
   *
   * @var \Drupal\Component\Utility\Random
   */
  protected $randomGenerator;

  /**
   * Generates a pseudo-random string of ASCII characters of codes 32 to 126.
   *
   * Do not use this method when special characters are not possible (e.g., in
   * machine or file names that have already been validated); instead, use
   * \Drupal\simpletest\TestBase::randomMachineName(). If $length is greater
   * than 3 the random string will include at least one ampersand ('&') and
   * at least one greater than ('>') character to ensure coverage for special
   * characters and avoid the introduction of random test failures.
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
    if ($length < 4) {
      return $this->getRandomGenerator()->string($length, TRUE, array($this, 'randomStringValidate'));
    }

    // To prevent the introduction of random test failures, ensure that the
    // returned string contains a character that needs to be escaped in HTML by
    // injecting an ampersand into it.
    $replacement_pos = floor($length / 2);
    // Remove 2 from the length to account for the ampersand and greater than
    // characters.
    $string = $this->getRandomGenerator()->string($length - 2, TRUE, array($this, 'randomStringValidate'));
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
  public function randomStringValidate($string) {
    // Consecutive spaces causes issues for
    // \Drupal\simpletest\WebTestBase::assertLink().
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
   * \Drupal\simpletest\TestBase::randomString().
   *
   * @param int $length
   *   Length of random string to generate.
   *
   * @return string
   *   Randomly generated unique string.
   *
   * @see \Drupal\Component\Utility\Random::name()
   */
  protected function randomMachineName($length = 8) {
    return $this->getRandomGenerator()->name($length, TRUE);
  }

  /**
   * Generates a random PHP object.
   *
   * @param int $size
   *   The number of random keys to add to the object.
   *
   * @return \stdClass
   *   The generated object, with the specified number of random keys. Each key
   *   has a random string value.
   *
   * @see \Drupal\Component\Utility\Random::object()
   */
  public function randomObject($size = 4) {
    return $this->getRandomGenerator()->object($size);
  }

  /**
   * Gets the random generator for the utility methods.
   *
   * @return \Drupal\Component\Utility\Random
   *   The random generator.
   */
  protected function getRandomGenerator() {
    if (!is_object($this->randomGenerator)) {
      $this->randomGenerator = new Random();
    }
    return $this->randomGenerator;
  }

}
