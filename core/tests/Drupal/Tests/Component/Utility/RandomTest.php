<?php

/**
 * @file
 * Contains \Drupal\Tests\Component\Utility\RandomTest.
 */

namespace Drupal\Tests\Component\Utility;

use Drupal\Component\Utility\Random;
use Drupal\Component\Utility\String;
use Drupal\Tests\UnitTestCase;

/**
 * Tests random data generation.
 *
 * @see \Drupal\Component\Utility\Random
 */
class RandomTest extends UnitTestCase {

  public static function getInfo() {
    return array(
      'name' => 'Random data generation tests',
      'description' => 'Confirm that Random::name() and Random::string() work correctly.',
      'group' => 'Common',
    );
  }

  /**
   * Tests unique random name generation.
   *
   * @see \Drupal\Component\Utility\Random::name()
   */
  public function testRandomStringUniqueness() {
    $strings = array();
    for ($i = 0; $i <= 50; $i++) {
      $str = Random::string(1, TRUE);
      $this->assertFalse(isset($strings[$str]), String::format('Generated duplicate random string !string', array('!string' => $str)));
      $strings[$str] = TRUE;
    }
  }

  /**
   * Tests unique random string generation.
   *
   * @see \Drupal\Component\Utility\Random::string()
   */
  public function testRandomNamesUniqueness() {
    $names = array();
    for ($i = 0; $i <= 10; $i++) {
      $str = Random::name(1, TRUE);
      $this->assertFalse(isset($names[$str]), String::format('Generated duplicate random name !name', array('!name' => $str)));
      $names[$str] = TRUE;
    }
  }

  /**
   * Tests infinite loop prevention whilst generating random names.
   *
   * @see \Drupal\Component\Utility\Random::name()
   *
   * @expectedException RuntimeException
   */
  public function testRandomNameException() {
    // There are fewer than 100 possibilities so an exception should occur to
    // prevent infinite loops.
    for ($i = 0; $i <= 100; $i++) {
      $str = Random::name(1, TRUE);
      $names[$str] = TRUE;
    }
  }

  /**
   * Tests infinite loop prevention whilst generating random strings.
   *
   * @see \Drupal\Component\Utility\Random::string()
   *
   * @expectedException RuntimeException
   */
  public function testRandomStringException() {
    // There are fewer than 100 possibilities so an exception should occur to
    // prevent infinite loops.
    for ($i = 0; $i <= 100; $i++) {
      $str = Random::string(1, TRUE);
      $names[$str] = TRUE;
    }
  }

  /**
   * Tests random name generation if uniqueness is not enforced.
   *
   * @see \Drupal\Component\Utility\Random::name()
   */
  public function testRandomNameNonUnique() {
    // There are fewer than 100 possibilities if we were forcing uniqueness so
    // exception would occur.
    for ($i = 0; $i <= 100; $i++) {
      Random::name(1);
    }
    $this->assertTrue(TRUE, 'No exception thrown when uniqueness is not enforced.');
  }

  /**
   * Tests random string if uniqueness is not enforced.
   *
   * @see \Drupal\Component\Utility\Random::string()
   */
  public function testRandomStringNonUnique() {
    // There are fewer than 100 possibilities if we were forcing uniqueness so
    // exception would occur.
    for ($i = 0; $i <= 100; $i++) {
      Random::string(1);
    }
    $this->assertTrue(TRUE, 'No exception thrown when uniqueness is not enforced.');
  }

  /**
   * Tests random object generation to ensure the expected number of properties.
   *
   * @see \Drupal\Component\Utility\Random::object()
   */
  public function testRandomObject() {
    // For values of 0 and 1 \Drupal\Component\Utility\Random::object() will
    // have different execution paths.
    for ($i = 0; $i <= 1; $i++) {
      $obj = Random::object($i);
      $this->assertEquals($i, count(get_object_vars($obj)), 'Generated random object has expected number of properties');
    }
  }

}
