<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Common\ColorTest.
 */

namespace Drupal\system\Tests\Common;

use Drupal\Core\Utility\Color;
use Drupal\simpletest\UnitTestBase;

/**
 * Tests color conversion functions.
 */
class ColorTest extends UnitTestBase {

  public static function getInfo() {
    return array(
      'name' => 'Color conversion',
      'description' => 'Tests Color utility class conversions.',
      'group' => 'Common',
    );
  }

  /**
   * Tests Color::hexToRgb().
   */
  function testHexToRgb() {
    // Any invalid arguments should throw an exception.
    $values = array('', '-1', '1', '12', '12345', '1234567', '123456789', '123456789a', 'foo');
    // Duplicate all invalid value tests with additional '#' prefix.
    // The '#' prefix inherently turns the data type into a string.
    foreach ($values as $value) {
      $values[] = '#' . $value;
    }
    // Add invalid data types (hex value must be a string).
    $values = array_merge($values, array(
      1, 12, 1234, 12345, 123456, 1234567, 12345678, 123456789, 123456789,
      -1, PHP_INT_MAX, PHP_INT_MAX + 1, -PHP_INT_MAX,
      0x0, 0x010,
    ));

    foreach ($values as $test) {
      $this->assertFalse(Color::validateHex($test), var_export($test, TRUE) . ' is invalid.');
      try {
        Color::hexToRgb($test);
        $this->fail('Color::hexToRgb(' . var_export($test, TRUE) . ') did not throw an exception.');
      }
      catch (\InvalidArgumentException $e) {
        $this->pass('Color::hexToRgb(' . var_export($test, TRUE) . ') threw an exception.');
      }
    }

    // PHP automatically casts a numeric array key into an integer.
    // Since hex values may consist of 0-9 only, they need to be defined as
    // array values.
    $tests = array(
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
    foreach ($tests as $test) {
      $result = Color::hexToRgb($test['hex']);
      $this->assertIdentical($result, $test['rgb']);
    }
  }

  /**
   * Tests Color::rgbToHex().
   */
  function testRgbToHex() {
    $tests = array(
      '#000000' => array('red' => 0, 'green' => 0, 'blue' => 0),
      '#ffffff' => array('red' => 255, 'green' => 255, 'blue' => 255),
      '#777777' => array('red' => 119, 'green' => 119, 'blue' => 119),
      '#010203' => array('red' => 1, 'green' => 2, 'blue' => 3),
    );
    // Input using named RGB array (e.g., as returned by Color::hexToRgb()).
    foreach ($tests as $expected => $rgb) {
      $this->assertIdentical(Color::rgbToHex($rgb), $expected);
    }
    // Input using indexed RGB array (e.g.: array(10, 10, 10)).
    foreach ($tests as $expected => $rgb) {
      $rgb = array_values($rgb);
      $this->assertIdentical(Color::rgbToHex($rgb), $expected);
    }
    // Input using CSS RGB string notation (e.g.: 10, 10, 10).
    foreach ($tests as $expected => $rgb) {
      $rgb = implode(', ', $rgb);
      $this->assertIdentical(Color::rgbToHex($rgb), $expected);
    }
  }
}
