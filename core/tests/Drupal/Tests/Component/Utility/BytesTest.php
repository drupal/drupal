<?php

namespace Drupal\Tests\Component\Utility;

use Drupal\Component\Utility\Bytes;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\PhpUnit\ExpectDeprecationTrait;

// cspell:ignore zettabytes

/**
 * Tests bytes size parsing helper methods.
 *
 * @group Utility
 *
 * @coversDefaultClass \Drupal\Component\Utility\Bytes
 */
class BytesTest extends TestCase {

  use ExpectDeprecationTrait;

  /**
   * Tests \Drupal\Component\Utility\Bytes::toInt().
   *
   * @param int $size
   *   The value for the size argument for
   *   \Drupal\Component\Utility\Bytes::toInt().
   * @param int $expected_int
   *   The expected return value from
   *   \Drupal\Component\Utility\Bytes::toInt().
   *
   * @dataProvider providerTestToNumber
   * @covers ::toInt
   *
   * @group legacy
   */
  public function testToInt($size, $expected_int) {
    $this->expectDeprecation('\Drupal\Component\Utility\Bytes::toInt() is deprecated in drupal:9.1.0 and is removed from drupal:10.0.0. Use \Drupal\Component\Utility\Bytes::toNumber() instead. See https://www.drupal.org/node/3162663');
    $this->assertEquals($expected_int, Bytes::toInt($size));
  }

  /**
   * Tests \Drupal\Component\Utility\Bytes::toNumber().
   *
   * @param string $size
   *   The value for the size argument for
   *   \Drupal\Component\Utility\Bytes::toNumber().
   * @param float $expected_number
   *   The expected return value from
   *   \Drupal\Component\Utility\Bytes::toNumber().
   *
   * @dataProvider providerTestToNumber
   * @covers ::toNumber
   */
  public function testToNumber($size, float $expected_number): void {
    $this->assertSame($expected_number, Bytes::toNumber($size));
  }

  /**
   * Provides data for testToNumber().
   *
   * @return array
   *   An array of arrays, each containing the argument for
   *   \Drupal\Component\Utility\Bytes::toNumber(): size, and the expected
   *   return value with the expected type (float).
   */
  public function providerTestToNumber(): array {
    return [
      ['1', 1.0],
      ['1 byte', 1.0],
      ['1 KB'  , (float) Bytes::KILOBYTE],
      ['1 MB'  , (float) pow(Bytes::KILOBYTE, 2)],
      ['1 GB'  , (float) pow(Bytes::KILOBYTE, 3)],
      ['1 TB'  , (float) pow(Bytes::KILOBYTE, 4)],
      ['1 PB'  , (float) pow(Bytes::KILOBYTE, 5)],
      ['1 EB'  , (float) pow(Bytes::KILOBYTE, 6)],
      // Zettabytes and yottabytes cannot be represented by integers on 64-bit
      // systems, so pow() returns a float.
      ['1 ZB'  , pow(Bytes::KILOBYTE, 7)],
      ['1 YB'  , pow(Bytes::KILOBYTE, 8)],
      ['23476892 bytes', 23476892.0],
      // 76 MB.
      ['76MRandomStringThatShouldBeIgnoredByParseSize.', 79691776.0],
      // 76.24 GB (with typo).
      ['76.24 Giggabyte', 81862076662.0],
      ['1.5', 2.0],
      [1.5, 2.0],
      ['2.4', 2.0],
      [2.4, 2.0],
      ['', 0.0],
      ['9223372036854775807', 9223372036854775807.0],
    ];
  }

}
