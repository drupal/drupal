<?php

declare(strict_types=1);

namespace Drupal\Tests\Component\Utility;

use Drupal\Component\Utility\Number;
use Drupal\TestTools\Extension\DeprecationBridge\ExpectDeprecationTrait;
use PHPUnit\Framework\TestCase;

/**
 * Tests number manipulation utilities.
 *
 * @group Utility
 *
 * @coversDefaultClass \Drupal\Component\Utility\Number
 *
 * @see \Drupal\Component\Utility\Number
 */
class NumberTest extends TestCase {

  use ExpectDeprecationTrait;

  /**
   * Tests Number::validStep() without offset.
   *
   * @param numeric $value
   *   The value argument for Number::validStep().
   * @param numeric $step
   *   The step argument for Number::validStep().
   * @param bool $expected
   *   Expected return value from Number::validStep().
   *
   * @dataProvider providerTestValidStep
   * @covers ::validStep
   */
  public function testValidStep($value, $step, $expected): void {
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
   * @param bool $expected
   *   Expected return value from Number::validStep().
   *
   * @dataProvider providerTestValidStepOffset
   * @covers ::validStep
   */
  public function testValidStepOffset($value, $step, $offset, $expected): void {
    $return = Number::validStep($value, $step, $offset);
    $this->assertEquals($expected, $return);
  }

  /**
   * Provides data for self::testNumberStep().
   *
   * @see \Drupal\Tests\Component\Utility\Number::testValidStep
   */
  public static function providerTestValidStep() {
    return [
      // Value and step equal.
      [10.3, 10.3, TRUE],

      // Valid integer steps.
      [42, 21, TRUE],
      [42, 3, TRUE],

      // Valid float steps.
      [42, 10.5, TRUE],
      [1, 1 / 3, TRUE],
      [-100, 100 / 7, TRUE],
      [1000, -10, TRUE],

      // Valid and very small float steps.
      [1000.12345, 1e-10, TRUE],
      [3.9999999999999, 1e-13, TRUE],

      // Invalid integer steps.
      [100, 30, FALSE],
      [-10, 4, FALSE],

      // Invalid float steps.
      [6, 5 / 7, FALSE],
      [10.3, 10.25, FALSE],

      // Step mismatches very close to being valid.
      [70 + 9e-7, 10 + 9e-7, FALSE],
      [1936.5, 3e-8, FALSE],
    ];
  }

  /**
   * Data provider for testValidStepOffset().
   *
   * @see \Drupal\Tests\Component\Utility\NumberTest::testValidStepOffset()
   */
  public static function providerTestValidStepOffset() {
    return [
      // Try obvious fits.
      [11.3, 10.3, 1, TRUE],
      [100, 10, 50, TRUE],
      [-100, 90 / 7, -10, TRUE],
      [2 / 7 + 5 / 9, 1 / 7, 5 / 9, TRUE],

      // Ensure a small offset is still invalid.
      [10.3, 10.3, 0.0001, FALSE],
      [1 / 5, 1 / 7, 1 / 11, FALSE],

      // Try negative values and offsets.
      [1000, 10, -5, FALSE],
      [-10, 4, 0, FALSE],
      [-10, 4, -4, FALSE],
    ];
  }

  /**
   * Tests the alphadecimal conversion functions.
   *
   * @param int $value
   *   The integer value.
   * @param string $expected
   *   The expected alphadecimal value.
   *
   * @dataProvider providerTestConversions
   * @covers ::intToAlphadecimal
   * @covers ::alphadecimalToInt
   */
  public function testConversions($value, $expected): void {
    $this->assertSame(Number::intToAlphadecimal($value), $expected);
    $this->assertSame($value, Number::alphadecimalToInt($expected));
  }

  /**
   * Data provider for testConversions().
   *
   * @return array
   *   An array containing:
   *     - The integer value.
   *     - The alphadecimal value.
   *
   * @see testConversions()
   */
  public static function providerTestConversions() {
    return [
      [0, '00'],
      [1, '01'],
      [10, '0a'],
      [20, '0k'],
      [35, '0z'],
      [36, '110'],
      [100, '12s'],
    ];
  }

  /**
   * Tests the alphadecimal conversion function input parameter checking.
   *
   * Number::alphadecimalToInt() must throw an exception
   * when non-alphanumeric characters are passed as input.
   *
   * @covers ::alphadecimalToInt
   */
  public function testAlphadecimalToIntThrowsExceptionWithMalformedStrings(): void {
    $this->expectException(\InvalidArgumentException::class);
    $nonAlphanumericChar = '#';
    Number::alphadecimalToInt($nonAlphanumericChar);
  }

  /**
   * Tests the alphadecimal conversion function keeps backward compatibility.
   *
   * Many tests and code rely on Number::alphadecimalToInt() returning 0
   * for degenerate values '' and NULL. We must ensure they are accepted.
   *
   * @group legacy
   * @covers ::alphadecimalToInt
   */
  public function testAlphadecimalToIntReturnsZeroWithNullAndEmptyString(): void {
    $deprecationMessage = 'Passing NULL or an empty string to Drupal\Component\Utility\Number::alphadecimalToInt() is deprecated in drupal:11.2.0 and will be removed in drupal:12.0.0. See https://www.drupal.org/node/3494472';
    $this->expectDeprecation($deprecationMessage);
    $this->assertSame(0, Number::alphadecimalToInt(NULL));
    $this->expectDeprecation($deprecationMessage);
    $this->assertSame(0, Number::alphadecimalToInt(''));
  }

}
