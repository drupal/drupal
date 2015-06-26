<?php

/**
 * @file
 * Contains \Drupal\Component\Utility\Color.
 */

namespace Drupal\Component\Utility;

/**
 * Performs color conversions.
 */
class Color {

  /**
   * Validates whether a hexadecimal color value is syntactically correct.
   *
   * @param $hex
   *   The hexadecimal string to validate. May contain a leading '#'. May use
   *   the shorthand notation (e.g., '123' for '112233').
   *
   * @return bool
   *   TRUE if $hex is valid or FALSE if it is not.
   */
  public static function validateHex($hex) {
    // Must be a string.
    $valid = is_string($hex);
    // Hash prefix is optional.
    $hex = ltrim($hex, '#');
    // Must be either RGB or RRGGBB.
    $length = Unicode::strlen($hex);
    $valid = $valid && ($length === 3 || $length === 6);
    // Must be a valid hex value.
    $valid = $valid && ctype_xdigit($hex);
    return $valid;
  }

  /**
   * Parses a hexadecimal color string like '#abc' or '#aabbcc'.
   *
   * @param string $hex
   *   The hexadecimal color string to parse.
   *
   * @return array
   *   An array containing the values for 'red', 'green', 'blue'.
   *
   * @throws \InvalidArgumentException
   */
  public static function hexToRgb($hex) {
    if (!self::validateHex($hex)) {
      throw new \InvalidArgumentException("'$hex' is not a valid hex value.");
    }

    // Ignore '#' prefixes.
    $hex = ltrim($hex, '#');

    // Convert shorthands like '#abc' to '#aabbcc'.
    if (strlen($hex) == 3) {
      $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
    }

    $c = hexdec($hex);

    return array(
      'red' => $c >> 16 & 0xFF,
      'green' => $c >> 8 & 0xFF,
      'blue' => $c & 0xFF,
    );
  }

  /**
   * Converts RGB color arrays and RGB strings in CSS notation to lowercase
   * simple colors like '#aabbcc'.
   *
   * @param array|string $input
   *   The value to convert. If the value is an array the first three elements
   *   will be used as the red, green and blue components. String values in CSS
   *   notation like '10, 20, 30' are also supported.
   *
   * @return string
   *   The lowercase simple color representation of the given color.
   */
  public static function rgbToHex($input) {
    // Remove named array keys if input comes from Color::hex2rgb().
    if (is_array($input)) {
      $rgb = array_values($input);
    }
    // Parse string input in CSS notation ('10, 20, 30').
    elseif (is_string($input)) {
      preg_match('/(\d+), ?(\d+), ?(\d+)/', $input, $rgb);
      array_shift($rgb);
    }

    $out = 0;
    foreach ($rgb as $k => $v) {
      $out |= $v << (16 - $k * 8);
    }

    return '#' . str_pad(dechex($out), 6, 0, STR_PAD_LEFT);
  }

}
