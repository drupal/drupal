<?php

/**
 * @file
 * Contains \Drupal\Tests\Component\Utility\NumberAlphadecimalTest.
 */

namespace Drupal\Tests\Component\Utility;

use Drupal\Component\Utility\Number;
use Drupal\Tests\UnitTestCase;

/**
 * Unit tests for the number alphadecimal functions.
 *
 * @see \Drupal\Component\Utility\Number
 *
 * @group Drupal
 */
class NumberAlphadecimalTest extends UnitTestCase {

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'name' => 'Number alphadecimal functions',
      'description' => 'Tests alphadecimal number conversion functions.',
      'group' => 'Common',
    );
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
