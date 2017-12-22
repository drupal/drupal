<?php

namespace Drupal\Tests\Component\Utility;

use Drupal\Component\Utility\Color;
use PHPUnit\Framework\TestCase;

/**
 * Tests Color utility class conversions.
 *
 * @group Utility
 */
class ColorTest extends TestCase {

  /**
   * Tests Color::hexToRgb().
   *
   * @param string $value
   *   The hex color value.
   * @param string $expected
   *   The expected rgb color value.
   * @param bool $invalid
   *   Whether this value is invalid and exception should be expected.
   *
   * @dataProvider providerTestHexToRgb
   */
  public function testHexToRgb($value, $expected, $invalid = FALSE) {
    if ($invalid) {
      if (method_exists($this, 'expectException')) {
        $this->expectException('InvalidArgumentException');
      }
      else {
        $this->setExpectedException('InvalidArgumentException');
      }
    }
    $this->assertSame($expected, Color::hexToRgb($value));
  }

  /**
   * Data provider for testHexToRgb().
   *
   * @see testHexToRgb()
   *
   * @return array
   *   An array of arrays containing:
   *     - The hex color value.
   *     - The rgb color array value.
   *     - (optional) Boolean indicating invalid status. Defaults to FALSE.
   */
  public function providerTestHexToRgb() {
    $invalid = [];
    // Any invalid arguments should throw an exception.
    foreach (['', '-1', '1', '12', '12345', '1234567', '123456789', '123456789a', 'foo'] as $value) {
      $invalid[] = [$value, '', TRUE];
    }
    // Duplicate all invalid value tests with additional '#' prefix.
    // The '#' prefix inherently turns the data type into a string.
    foreach ($invalid as $value) {
      $invalid[] = ['#' . $value[0], '', TRUE];
    }
    // Add invalid data types (hex value must be a string).
    foreach ([
      1, 12, 1234, 12345, 123456, 1234567, 12345678, 123456789, 123456789,
      -1, PHP_INT_MAX, PHP_INT_MAX + 1, -PHP_INT_MAX, 0x0, 0x010
    ] as $value) {
      $invalid[] = [$value, '', TRUE];
    }
    // And some valid values.
    $valid = [
      // Shorthands without alpha.
      ['hex' => '#000', 'rgb' => ['red' => 0, 'green' => 0, 'blue' => 0]],
      ['hex' => '#fff', 'rgb' => ['red' => 255, 'green' => 255, 'blue' => 255]],
      ['hex' => '#abc', 'rgb' => ['red' => 170, 'green' => 187, 'blue' => 204]],
      ['hex' => 'cba', 'rgb' => ['red' => 204, 'green' => 187, 'blue' => 170]],
      // Full without alpha.
      ['hex' => '#000000', 'rgb' => ['red' => 0, 'green' => 0, 'blue' => 0]],
      ['hex' => '#ffffff', 'rgb' => ['red' => 255, 'green' => 255, 'blue' => 255]],
      ['hex' => '#010203', 'rgb' => ['red' => 1, 'green' => 2, 'blue' => 3]],
    ];
    return array_merge($invalid, $valid);
  }

  /**
   * Tests Color::rgbToHex().
   *
   * @param string $value
   *   The rgb color value.
   * @param string $expected
   *   The expected hex color value.
   *
   * @dataProvider providerTestRbgToHex
   */
  public function testRgbToHex($value, $expected) {
    $this->assertSame($expected, Color::rgbToHex($value));
  }

  /**
   * Data provider for testRgbToHex().
   *
   * @see testRgbToHex()
   *
   * @return array
   *   An array of arrays containing:
   *     - The rgb color array value.
   *     - The hex color value.
   */
  public function providerTestRbgToHex() {
    // Input using named RGB array (e.g., as returned by Color::hexToRgb()).
    $tests = [
      [['red' => 0, 'green' => 0, 'blue' => 0], '#000000'],
      [['red' => 255, 'green' => 255, 'blue' => 255], '#ffffff'],
      [['red' => 119, 'green' => 119, 'blue' => 119], '#777777'],
      [['red' => 1, 'green' => 2, 'blue' => 3], '#010203'],
    ];
    // Input using indexed RGB array (e.g.: array(10, 10, 10)).
    foreach ($tests as $test) {
      $tests[] = [array_values($test[0]), $test[1]];
    }
    // Input using CSS RGB string notation (e.g.: 10, 10, 10).
    foreach ($tests as $test) {
      $tests[] = [implode(', ', $test[0]), $test[1]];
    }
    return $tests;
  }

}
