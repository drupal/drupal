<?php

/**
 * @file
 * Contains \Drupal\Tests\Common\Utility\NumberTest.
 *
 * @see \Drupal\Component\Utility\Number
 */

namespace Drupal\Tests\Component\Utility;

use Drupal\Component\Utility\Number;
use Drupal\Tests\UnitTestCase;

/**
 * Tests number step validation by Number::validStep().
 */
class NumberTest extends UnitTestCase {

  public static function getInfo() {
    return array(
      'name' => 'Number step validation',
      'description' => 'Tests number step validation by Number::validStep()',
      'group' => 'Common',
    );
  }

  /**
   * Tests Number::validStep() without offset.
   *
   * @param numeric $value
   *   The value argument for Number::validStep().
   * @param numeric $step
   *   The step argument for Number::validStep().
   * @param boolean $expected
   *   Expected return value from Number::validStep().
   *
   * @dataProvider providerTestValidStep
   */
  public function testValidStep($value, $step, $expected) {
    $return = Number::validStep($value, $step);
    $this->assertEquals($expected, $return);
  }

  /**
   * Tests Number::validStep() with offset.
   *
   * @param numeric $value
   *   The value argument for Number::validStep().
   * @param numeric $step
   *   The step argument for Number::validStep().
   * @param numeric $offset
   *   The offset argument for Number::validStep().
   * @param boolean $expected
   *   Expected return value from Number::validStep().
   *
   * @dataProvider providerTestValidStepOffset
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

      // Step mismatches very close to beeing valid.
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

}
