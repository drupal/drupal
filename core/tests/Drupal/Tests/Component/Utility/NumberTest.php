<?php

namespace Drupal\Tests\Component\Utility;

use Drupal\Component\Utility\Number;
use Drupal\Tests\UnitTestCase;

/**
 * Tests number manipulation utilities.
 *
 * @group Utility
 *
 * @coversDefaultClass \Drupal\Component\Utility\Number
 *
 * @see \Drupal\Component\Utility\Number
 */
class NumberTest extends UnitTestCase {

  /**
   * Tests Number::validStep() without offset.
   *
   * @dataProvider providerTestValidStep
   * @covers ::validStep
   *
   * @param numeric $value
   *   The value argument for Number::validStep().
   * @param numeric $step
   *   The step argument for Number::validStep().
   * @param bool $expected
   *   Expected return value from Number::validStep().
   */
  public function testValidStep($value, $step, $expected) {
    $return = Number::validStep($value, $step);
    $this->assertEquals($expected, $return);
  }

  /**
   * Tests Number::validStep() with offset.
   *
   * @dataProvider providerTestValidStepOffset
   * @covers ::validStep
   *
   * @param numeric $value
   *   The value argument for Number::validStep().
   * @param numeric $step
   *   The step argument for Number::validStep().
   * @param numeric $offset
   *   The offset argument for Number::validStep().
   * @param bool $expected
   *   Expected return value from Number::validStep().
   */
  public function testValidStepOffset($value, $step, $offset, $expected) {
    $return = Number::validStep($value, $step, $offset);
    $this->assertEquals($expected, $return);
  }

  /**
   * Provides data for self::testNumberStep().
   *
   * @see \Drupal\Tests\Component\Utility\Number::testValidStep
   */
  public static function providerTestValidStep() {
    return array(
      // Value and step equal.
      array(10.3, 10.3, TRUE),

      // Valid integer steps.
      array(42, 21, TRUE),
      array(42, 3, TRUE),

      // Valid float steps.
      array(42, 10.5, TRUE),
      array(1, 1/3, TRUE),
      array(-100, 100/7, TRUE),
      array(1000, -10, TRUE),

      // Valid and very small float steps.
      array(1000.12345, 1e-10, TRUE),
      array(3.9999999999999, 1e-13, TRUE),

      // Invalid integer steps.
      array(100, 30, FALSE),
      array(-10, 4, FALSE),

      // Invalid float steps.
      array(6, 5/7, FALSE),
      array(10.3, 10.25, FALSE),

      // Step mismatches very close to being valid.
      array(70 + 9e-7, 10 + 9e-7, FALSE),
      array(1936.5, 3e-8, FALSE),
    );
  }

  /**
   * Data provider for \Drupal\Test\Component\Utility\NumberTest::testValidStepOffset().
   *
   * @see \Drupal\Test\Component\Utility\NumberTest::testValidStepOffset()
   */
  public static function providerTestValidStepOffset() {
    return array(
      // Try obvious fits.
      array(11.3, 10.3, 1, TRUE),
      array(100, 10, 50, TRUE),
      array(-100, 90/7, -10, TRUE),
      array(2/7 + 5/9, 1/7, 5/9, TRUE),

      // Ensure a small offset is still invalid.
      array(10.3, 10.3, 0.0001, FALSE),
      array(1/5, 1/7, 1/11, FALSE),

      // Try negative values and offsets.
      array(1000, 10, -5, FALSE),
      array(-10, 4, 0, FALSE),
      array(-10, 4, -4, FALSE),
    );
  }

  /**
   * Tests the alphadecimal conversion functions.
   *
   * @dataProvider providerTestConversions
   * @covers ::intToAlphadecimal
   * @covers ::alphadecimalToInt
   *
   * @param int $value
   *   The integer value.
   * @param string $expected
   *   The expected alphadecimal value.
   */
  public function testConversions($value, $expected) {
    $this->assertSame(Number::intToAlphadecimal($value), $expected);
    $this->assertSame($value, Number::alphadecimalToInt($expected));
  }

  /**
   * Data provider for testConversions().
   *
   * @see testConversions()
   *
   * @return array
   *   An array containing:
   *     - The integer value.
   *     - The alphadecimal value.
   */
  public function providerTestConversions() {
    return array(
      array(0, '00'),
      array(1, '01'),
      array(10, '0a'),
      array(20, '0k'),
      array(35, '0z'),
      array(36, '110'),
      array(100, '12s'),
    );
  }

}
