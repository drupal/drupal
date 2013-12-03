<?php

/**
 * @file
 * Contains \Drupal\Component\Utility\Number.
 */
namespace Drupal\Component\Utility;

/**
 * Provides helper methods for manipulating numbers.
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
   * @param numeric $value
   *   The value that needs to be checked.
   * @param numeric $step
   *   The step scale factor. Must be positive.
   * @param numeric $offset
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
    // fractional part of a float has 24 bits. That means remainders smaller than
    // $step * 2^-24 are acceptable.
    $computed_acceptable_error = (double)($step / pow(2.0, 24));

    return $computed_acceptable_error >= $remainder || $remainder >= ($step - $computed_acceptable_error);
  }

}
