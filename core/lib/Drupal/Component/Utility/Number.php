<?php

namespace Drupal\Component\Utility;

/**
 * Provides helper methods for manipulating numbers.
 *
 * @ingroup utility
 */
class Number {

  /**
   * Verifies that a number is a multiple of a given step.
   *
   * The implementation assumes it is dealing with IEEE 754 double precision
   * floating point numbers that are used by PHP on most systems.
   *
   * This is based on the number/range verification methods of webkit.
   *
   * @param float $value
   *   The value that needs to be checked.
   * @param float $step
   *   The step scale factor. Must be positive.
   * @param float $offset
   *   (optional) An offset, to which the difference must be a multiple of the
   *   given step.
   *
   * @return bool
   *   TRUE if no step mismatch has occurred, or FALSE otherwise.
   *
   * @see http://opensource.apple.com/source/WebCore/WebCore-1298/html/NumberInputType.cpp
   */
  public static function validStep($value, $step, $offset = 0.0) {
    $double_value = (double) abs($value - $offset);

    // The fractional part of a double has 53 bits. The greatest number that
    // could be represented with that is 2^53. If the given value is even bigger
    // than $step * 2^53, then dividing by $step will result in a very small
    // remainder. Since that remainder can't even be represented with a single
    // precision float the following computation of the remainder makes no sense
    // and we can safely ignore it instead.
    if ($double_value / pow(2.0, 53) > $step) {
      return TRUE;
    }

    // Now compute that remainder of a division by $step.
    $remainder = (double) abs($double_value - $step * round($double_value / $step));

    // $remainder is a double precision floating point number. Remainders that
    // can't be represented with single precision floats are acceptable. The
    // fractional part of a float has 24 bits. That means remainders smaller
    // than $step * 2^-24 are acceptable.
    $computed_acceptable_error = (double) ($step / pow(2.0, 24));

    return $computed_acceptable_error >= $remainder || $remainder >= ($step - $computed_acceptable_error);
  }

  /**
   * Generates a sorting code from an integer.
   *
   * Consists of a leading character indicating length, followed by N digits
   * with a numerical value in base 36 (alphadecimal). These codes can be sorted
   * as strings without altering numerical order.
   *
   * It goes:
   * 00, 01, 02, ..., 0y, 0z,
   * 110, 111, ... , 1zy, 1zz,
   * 2100, 2101, ..., 2zzy, 2zzz,
   * 31000, 31001, ...
   *
   * @param int $i
   *   The integer value to convert.
   *
   * @return string
   *   The alpha decimal value.
   *
   * @see \Drupal\Component\Utility\Number::alphadecimalToInt
   */
  public static function intToAlphadecimal($i = 0) {
    $num = base_convert((string) $i, 10, 36);
    $length = strlen($num);

    return chr($length + ord('0') - 1) . $num;
  }

  /**
   * Decodes a sorting code back to an integer.
   *
   * @param string $string
   *   The alpha decimal value to convert
   *
   * @return int
   *   The integer value.
   *
   * @throws \InvalidArgumentException
   *   If $string contains invalid characters, throw an exception.
   *
   * @see \Drupal\Component\Utility\Number::intToAlphadecimal
   */
  public static function alphadecimalToInt($string = '00') {
    // For backwards compatibility, we must accept NULL
    // and the empty string, returning 0,
    // like (int) base_convert(substr($string, 1), 36, 10) always did.
    if ('' === $string || NULL === $string) {
      @trigger_error('Passing NULL or an empty string to ' . __METHOD__ . '() is deprecated in drupal:11.2.0 and will be removed in drupal:12.0.0. See https://www.drupal.org/node/3494472', E_USER_DEPRECATED);
      return 0;
    }
    $alpha_decimal_substring = substr($string, 1);
    if (!ctype_alnum($alpha_decimal_substring)) {
      throw new \InvalidArgumentException("Invalid characters passed for attempted conversion: $string");
    }
    return (int) base_convert($alpha_decimal_substring, 36, 10);
  }

}
