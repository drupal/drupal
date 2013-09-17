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

  /**
   * The first random string passed to the test callback.
   *
   * @see \Drupal\Tests\Component\Utility\RandomTest::_RandomStringValidate()
   *
   * @var string
   */
  protected $firstStringGenerated = '';

  public static function getInfo() {
    return array(
      'name' => 'Random data generation tests',
      'description' => 'Confirm that Random::name() and Random::string() work correctly.',
      'group' => 'Common',
    );
  }

  /**
   * Tests unique random string generation.
   *
   * @see \Drupal\Component\Utility\Random::string()
   */
  public function testRandomStringUniqueness() {
    $strings = array();
    $random = new Random();
    for ($i = 0; $i <= 50; $i++) {
      $str = $random->string(1, TRUE);
      $this->assertFalse(isset($strings[$str]), String::format('Generated duplicate random string !string', array('!string' => $str)));
      $strings[$str] = TRUE;
    }
  }

  /**
   * Tests unique random name generation.
   *
   * @see \Drupal\Component\Utility\Random::name()
   */
  public function testRandomNamesUniqueness() {
    $names = array();
    $random = new Random();
    for ($i = 0; $i <= 10; $i++) {
      $str = $random->name(1, TRUE);
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
    $random = new Random();
    for ($i = 0; $i <= 100; $i++) {
      $str = $random->name(1, TRUE);
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
    $random = new Random();
    for ($i = 0; $i <= 100; $i++) {
      $str = $random->string(1, TRUE);
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
    $random = new Random();
    for ($i = 0; $i <= 100; $i++) {
      $random->name(1);
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
    $random = new Random();
    for ($i = 0; $i <= 100; $i++) {
      $random->string(1);
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
    $random = new Random();
    for ($i = 0; $i <= 1; $i++) {
      $obj = $random->object($i);
      $this->assertEquals($i, count(get_object_vars($obj)), 'Generated random object has expected number of properties');
    }
  }

  /**
   * Tests random string validation callbacks.
   *
   * @see \Drupal\Component\Utility\Random::name()
   */
  public function testRandomStringValidator() {
    $random = new Random();
    $this->firstStringGenerated = '';
    $str = $random->string(1, TRUE, array($this, '_RandomStringValidate'));
    $this->assertNotEquals($this->firstStringGenerated, $str);
  }

  /**
   * Callback for random string validation.
   *
   * @see \Drupal\Component\Utility\Random::name()
   * @see \Drupal\Tests\Component\Utility\RandomTest::testRandomStringValidator()
   *
   * @param string $string
   *   The random string to validate.
   *
   * @return bool
   *   TRUE if the random string is valid, FALSE if not.
   */
  public function _RandomStringValidate($string) {
    // Return FALSE for the first generated string and any string that is the
    // same, as the test expects a different string to be returned.
    if (empty($this->firstStringGenerated) || $string == $this->firstStringGenerated) {
      $this->firstStringGenerated = $string;
      return FALSE;
    }
    return TRUE;
  }
}
