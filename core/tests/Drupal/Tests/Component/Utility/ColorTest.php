<?php

declare(strict_types=1);

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
   * @covers \Drupal\Component\Utility\Color::validateHex
   *
   * @param bool $expected
   *   The expected result of validation.
   * @param string $value
   *   The hex color value.
   *
   * @dataProvider providerTestValidateHex
   */
  public function testValidateHex($expected, $value): void {
    $this->assertSame($expected, Color::validateHex($value));
  }

  /**
   * Provides data for testValidateHex().
   */
  public static function providerTestValidateHex() {
    return [
      // Tests length.
      [FALSE, ''],
      [FALSE, '#'],
      [FALSE, '1'],
      [FALSE, '#1'],
      [FALSE, '12'],
      [FALSE, '#12'],
      [TRUE, '123'],
      [TRUE, '#123'],
      [FALSE, '1234'],
      [FALSE, '#1234'],
      [FALSE, '12345'],
      [FALSE, '#12345'],
      [TRUE, '123456'],
      [TRUE, '#123456'],
      [FALSE, '1234567'],
      [FALSE, '#1234567'],
      // Tests valid hex value.
      [TRUE, 'abcdef'],
      [TRUE, 'ABCDEF'],
      [TRUE, 'A0F1B1'],
      [FALSE, 'WWW'],
      [FALSE, '#123##'],
      [FALSE, '@a0055'],
      // Tests the data type.
      [FALSE, 123456],
      // Tests multiple hash prefix.
      [FALSE, '###F00'],
      // Tests spaces.
      [FALSE, ' #123456'],
      [FALSE, '123456 '],
      [FALSE, '#12 3456'],
    ];
  }

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
  public function testHexToRgb($value, $expected, $invalid = FALSE): void {
    if ($invalid) {
      $this->expectException('InvalidArgumentException');
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
  public static function providerTestHexToRgb() {
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
      -1, PHP_INT_MAX, PHP_INT_MAX + 1, -PHP_INT_MAX, 0x0, 0x010,
    ] as $value) {
      $invalid[] = [$value, '', TRUE];
    }
    // And some valid values.
    $valid = [
      // Shorthands without alpha.
      ['value' => '#000', 'expected' => ['red' => 0, 'green' => 0, 'blue' => 0]],
      ['value' => '#fff', 'expected' => ['red' => 255, 'green' => 255, 'blue' => 255]],
      ['value' => '#abc', 'expected' => ['red' => 170, 'green' => 187, 'blue' => 204]],
      ['value' => 'cba', 'expected' => ['red' => 204, 'green' => 187, 'blue' => 170]],
      // Full without alpha.
      ['value' => '#000000', 'expected' => ['red' => 0, 'green' => 0, 'blue' => 0]],
      ['value' => '#ffffff', 'expected' => ['red' => 255, 'green' => 255, 'blue' => 255]],
      ['value' => '#010203', 'expected' => ['red' => 1, 'green' => 2, 'blue' => 3]],
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
  public function testRgbToHex($value, $expected): void {
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
  public static function providerTestRbgToHex() {
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

  /**
   * Data provider for testNormalizeHexLength().
   *
   * @see testNormalizeHexLength()
   *
   * @return array
   *   An array of arrays containing:
   *     - The hex color value.
   *     - The 6 character length hex color value.
   */
  public static function providerTestNormalizeHexLength() {
    $data = [
      ['#000', '#000000'],
      ['#FFF', '#FFFFFF'],
      ['#abc', '#aabbcc'],
      ['cba', '#ccbbaa'],
      ['#000000', '#000000'],
      ['ffffff', '#ffffff'],
      ['#010203', '#010203'],
    ];

    return $data;
  }

  /**
   * Tests Color::normalizeHexLength().
   *
   * @param string $value
   *   The input hex color value.
   * @param string $expected
   *   The expected normalized hex color value.
   *
   * @dataProvider providerTestNormalizeHexLength
   */
  public function testNormalizeHexLength($value, $expected): void {
    $this->assertSame($expected, Color::normalizeHexLength($value));
  }

}
