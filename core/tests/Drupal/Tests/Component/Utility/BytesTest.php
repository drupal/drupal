<?php

/**
 * @file
 * Contains \Drupal\Tests\Component\Utility\BytesTest.
 */

namespace Drupal\Tests\Component\Utility;

use Drupal\Component\Utility\Bytes;
use Drupal\Tests\UnitTestCase;

/**
 * Tests bytes size parsing helper methods.
 *
 * @group Drupal
 * @group Utility
 * @coversDefaultClass \Drupal\Component\Utility\Bytes
 */
class BytesTest extends UnitTestCase {

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'name' => 'Bytes utility helpers',
      'description' => '',
      'group' => 'Utility',
    );
  }

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
    return array(
      array('1 byte', 1),
      array('1 KB'  , Bytes::KILOBYTE),
      array('1 MB'  , pow(Bytes::KILOBYTE, 2)),
      array('1 GB'  , pow(Bytes::KILOBYTE, 3)),
      array('1 TB'  , pow(Bytes::KILOBYTE, 4)),
      array('1 PB'  , pow(Bytes::KILOBYTE, 5)),
      array('1 EB'  , pow(Bytes::KILOBYTE, 6)),
      array('1 ZB'  , pow(Bytes::KILOBYTE, 7)),
      array('1 YB'  , pow(Bytes::KILOBYTE, 8)),
      array('23476892 bytes', 23476892),
      array('76MRandomStringThatShouldBeIgnoredByParseSize.', 79691776), // 76 MB
      array('76.24 Giggabyte', 81862076662), // 76.24 GB (with typo)
    );
  }

}
