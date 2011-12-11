<?php

/**
 * @file
 * Provides a helper to properly encode HTML-safe JSON prior to PHP 5.3.0.
 */

/**
 * Encodes a PHP variable to HTML-safe JSON for PHP versions below 5.3.0.
 *
 * @see drupal_json_encode()
 */
function drupal_json_encode_helper($var) {
  switch (gettype($var)) {
    case 'boolean':
      return $var ? 'true' : 'false'; // Lowercase necessary!

    case 'integer':
    case 'double':
      return $var;

    case 'resource':
    case 'string':
      // Always use Unicode escape sequences (\u0022) over JSON escape
      // sequences (\") to prevent browsers interpreting these as
      // special characters.
      $replace_pairs = array(
        // ", \ and U+0000 - U+001F must be escaped according to RFC 4627.
        '\\' => '\u005C',
        '"' => '\u0022',
        "\x00" => '\u0000',
        "\x01" => '\u0001',
        "\x02" => '\u0002',
        "\x03" => '\u0003',
        "\x04" => '\u0004',
        "\x05" => '\u0005',
        "\x06" => '\u0006',
        "\x07" => '\u0007',
        "\x08" => '\u0008',
        "\x09" => '\u0009',
        "\x0a" => '\u000A',
        "\x0b" => '\u000B',
        "\x0c" => '\u000C',
        "\x0d" => '\u000D',
        "\x0e" => '\u000E',
        "\x0f" => '\u000F',
        "\x10" => '\u0010',
        "\x11" => '\u0011',
        "\x12" => '\u0012',
        "\x13" => '\u0013',
        "\x14" => '\u0014',
        "\x15" => '\u0015',
        "\x16" => '\u0016',
        "\x17" => '\u0017',
        "\x18" => '\u0018',
        "\x19" => '\u0019',
        "\x1a" => '\u001A',
        "\x1b" => '\u001B',
        "\x1c" => '\u001C',
        "\x1d" => '\u001D',
        "\x1e" => '\u001E',
        "\x1f" => '\u001F',
        // Prevent browsers from interpreting these as as special.
        "'" => '\u0027',
        '<' => '\u003C',
        '>' => '\u003E',
        '&' => '\u0026',
        // Prevent browsers from interpreting the solidus as special and
        // non-compliant JSON parsers from interpreting // as a comment.
        '/' => '\u002F',
        // While these are allowed unescaped according to ECMA-262, section
        // 15.12.2, they cause problems in some JSON parsers.
        "\xe2\x80\xa8" => '\u2028', // U+2028, Line Separator.
        "\xe2\x80\xa9" => '\u2029', // U+2029, Paragraph Separator.
      );

      return '"' . strtr($var, $replace_pairs) . '"';

    case 'array':
      // Arrays in JSON can't be associative. If the array is empty or if it
      // has sequential whole number keys starting with 0, it's not associative
      // so we can go ahead and convert it as an array.
      if (empty($var) || array_keys($var) === range(0, sizeof($var) - 1)) {
        $output = array();
        foreach ($var as $v) {
          $output[] = drupal_json_encode_helper($v);
        }
        return '[ ' . implode(', ', $output) . ' ]';
      }
      // Otherwise, fall through to convert the array as an object.

    case 'object':
      $output = array();
      foreach ($var as $k => $v) {
        $output[] = drupal_json_encode_helper(strval($k)) . ':' . drupal_json_encode_helper($v);
      }
      return '{' . implode(', ', $output) . '}';

    default:
      return 'null';
  }
}
