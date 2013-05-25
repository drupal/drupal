<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Utility\ColorTest.
 */

namespace Drupal\Tests\Core\Utility;

use Drupal\Core\Utility\Color;
use Drupal\Tests\UnitTestCase;

/**
 * Tests color conversion functions.
 */
class ColorTest extends UnitTestCase {

  public static function getInfo() {
    return array(
      'name' => 'Color conversion',
      'description' => 'Tests Color utility class conversions.',
      'group' => 'Common',
    );
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
  public function testHexToRgb($value, $expected, $invalid = FALSE) {
    if ($invalid) {
      $this->setExpectedException('InvalidArgumentException');
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
    $invalid = array();
    // Any invalid arguments should throw an exception.
    foreach (array('', '-1', '1', '12', '12345', '1234567', '123456789', '123456789a', 'foo') as $value) {
      $invalid[] = array($value, '', TRUE);
    }
    // Duplicate all invalid value tests with additional '#' prefix.
    // The '#' prefix inherently turns the data type into a string.
    foreach ($invalid as $value) {
      $invalid[] = array('#' . $value[0], '', TRUE);
    }
    // Add invalid data types (hex value must be a string).
    foreach (array(
      1, 12, 1234, 12345, 123456, 1234567, 12345678, 123456789, 123456789,
      -1, PHP_INT_MAX, PHP_INT_MAX + 1, -PHP_INT_MAX, 0x0, 0x010
    ) as $value) {
      $invalid[] = array($value, '', TRUE);
    }
    // And some valid values.
    $valid = array(
      // Shorthands without alpha.
      array('hex' => '#000', 'rgb' => array('red' => 0, 'green' => 0, 'blue' => 0)),
      array('hex' => '#fff', 'rgb' => array('red' => 255, 'green' => 255, 'blue' => 255)),
      array('hex' => '#abc', 'rgb' => array('red' => 170, 'green' => 187, 'blue' => 204)),
      array('hex' => 'cba', 'rgb' => array('red' => 204, 'green' => 187, 'blue' => 170)),
      // Full without alpha.
      array('hex' => '#000000', 'rgb' => array('red' => 0, 'green' => 0, 'blue' => 0)),
      array('hex' => '#ffffff', 'rgb' => array('red' => 255, 'green' => 255, 'blue' => 255)),
      array('hex' => '#010203', 'rgb' => array('red' => 1, 'green' => 2, 'blue' => 3)),
    );
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
    $tests = array(
      array(array('red' => 0, 'green' => 0, 'blue' => 0), '#000000'),
      array(array('red' => 255, 'green' => 255, 'blue' => 255), '#ffffff'),
      array(array('red' => 119, 'green' => 119, 'blue' => 119), '#777777'),
      array(array('red' => 1, 'green' => 2, 'blue' => 3), '#010203'),
    );
    // Input using indexed RGB array (e.g.: array(10, 10, 10)).
    foreach ($tests as $test) {
      $tests[] = array(array_values($test[0]), $test[1]);
    }
    // Input using CSS RGB string notation (e.g.: 10, 10, 10).
    foreach ($tests as $test) {
      $tests[] = array(implode(', ', $test[0]), $test[1]);
    }
    return $tests;
  }

}
