<?php

namespace Drupal\Tests\Component\Utility;

use Drupal\Component\Utility\Bytes;
use PHPUnit\Framework\TestCase;

/**
 * Tests bytes size parsing helper methods.
 *
 * @group Utility
 *
 * @coversDefaultClass \Drupal\Component\Utility\Bytes
 */
class BytesTest extends TestCase {

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
   * @dataProvider providerTestToInt
   * @covers ::toInt
   */
  public function testToInt($size, $expected_int) {
    $this->assertEquals($expected_int, Bytes::toInt($size));
  }

  /**
   * Provides data for testToInt.
   *
   * @return array
   *   An array of arrays, each containing the argument for
   *   \Drupal\Component\Utility\Bytes::toInt(): size, and the expected return
   *   value.
   */
  public function providerTestToInt() {
    return [
      ['1', 1],
      ['1 byte', 1],
      ['1 KB'  , Bytes::KILOBYTE],
      ['1 MB'  , pow(Bytes::KILOBYTE, 2)],
      ['1 GB'  , pow(Bytes::KILOBYTE, 3)],
      ['1 TB'  , pow(Bytes::KILOBYTE, 4)],
      ['1 PB'  , pow(Bytes::KILOBYTE, 5)],
      ['1 EB'  , pow(Bytes::KILOBYTE, 6)],
      ['1 ZB'  , pow(Bytes::KILOBYTE, 7)],
      ['1 YB'  , pow(Bytes::KILOBYTE, 8)],
      ['23476892 bytes', 23476892],
      // 76 MB.
      ['76MRandomStringThatShouldBeIgnoredByParseSize.', 79691776],
      // 76.24 GB (with typo).
      ['76.24 Giggabyte', 81862076662],
    ];
  }

}
