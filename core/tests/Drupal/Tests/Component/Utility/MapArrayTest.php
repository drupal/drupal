<?php

/**
 * @file
 * Contains \Drupal\Tests\Component\Utility\MapArrayTest.
 */

namespace Drupal\Tests\Component\Utility;

use Drupal\Tests\UnitTestCase;
use Drupal\Component\Utility\MapArray;

/**
 * Tests the MapArray system.
 *
 * @see \Drupal\Component\Utility\MapArray
 */
class MapArrayTest extends UnitTestCase {

  public static function getInfo() {
    return array(
      'name' => 'MapArray test',
      'description' => 'Test that the MapArray functions work properly.',
      'group' => 'Bootstrap',
    );
  }

  /**
   * Tests MapArray::copyValuesToKey() input against expected output.
   *
   * @dataProvider providerCopyValuesToKey
   *
   * @param array $input
   *   The input array for the MapArray::copyValuesToKey() method.
   * @param array $expected
   *   The expected output from calling the method.
   * @param callable $callable
   *   The optional callable.
   *
   * @see Drupal\Component\Utility\MapArray::copyValuesToKey()
   * @see Drupal\Tests\Component\Utility\MapArrayTest::providerCopyValuesToKey()
   */
  public function testCopyValuesToKey(array $input, array $expected, $callable = NULL) {
    $output = MapArray::copyValuesToKeys($input, $callable);
    $this->assertEquals($expected, $output);
  }

  /**
   * Data provider for MapArray::copyValuesToKey().
   *
   * @return array
   *   An array of tests, matching the parameter inputs for testCopyValuesToKey.
   *
   * @see Drupal\Component\Utility\MapArray::copyValuesToKey()
   * @see Drupal\Tests\Component\Utility\MapArrayTest::testCopyValuesToKey()
   */
  public function providerCopyValuesToKey() {
    // Test an empty array.
    $tests[] = array(
      array(),
      array()
    );

    // Tests the creation of an associative array.
    $tests[] = array(
      array('foobar'),
      array('foobar' => 'foobar')
    );

    // Tests overwriting indexes with their value.
    $tests[] = array(
      array('foo' => 'bar'),
      array('bar' => 'bar')
    );

    // Tests using the callback function.
    $tests[] = array(
      array(1, 2, 3, 4, 5),
      array(
        1 => 2,
        2 => 4,
        3 => 6,
        4 => 8,
        5 => 10,
      ),
      'Drupal\Tests\Component\Utility\MapArrayTest::providerCopyValuesToKeyCallback',
    );

    return $tests;
  }

  /**
   * Callback for a test in providerCopyValuesToKey(), which doubles the value.
   *
   * @param int $n
   *   The value passed in from array_map().
   *
   * @return int
   *   The doubled integer value.
   */
  public static function providerCopyValuesToKeyCallback($n) {
     return $n * 2;
  }

}
