<?php

declare(strict_types=1);

namespace Drupal\Tests\Component\Utility;

use Drupal\Component\Utility\Random;
use PHPUnit\Framework\TestCase;

/**
 * Tests random data generation.
 *
 * @group Utility
 *
 * @coversDefaultClass \Drupal\Component\Utility\Random
 */
class RandomTest extends TestCase {

  /**
   * The first random string passed to the test callback.
   *
   * @var string
   *
   * @see \Drupal\Tests\Component\Utility\RandomTest::_RandomStringValidate()
   */
  protected $firstStringGenerated = '';

  /**
   * Tests unique random string generation.
   *
   * @covers ::string
   */
  public function testRandomStringUniqueness(): void {
    $strings = [];
    $random = new Random();
    for ($i = 0; $i <= 50; $i++) {
      $str = $random->string(1, TRUE);
      $this->assertFalse(isset($strings[$str]), 'Generated duplicate random string ' . $str);
      $strings[$str] = TRUE;
    }
  }

  /**
   * Tests unique random name generation.
   *
   * @covers ::name
   */
  public function testRandomNamesUniqueness(): void {
    $names = [];
    $random = new Random();
    for ($i = 0; $i <= 10; $i++) {
      $str = $random->name(1, TRUE);
      $this->assertFalse(isset($names[$str]), 'Generated duplicate random name ' . $str);
      $names[$str] = TRUE;
    }
  }

  /**
   * Tests infinite loop prevention whilst generating random names.
   *
   * @covers ::name
   */
  public function testRandomNameException(): void {
    // There are fewer than 100 possibilities so an exception should occur to
    // prevent infinite loops.
    $random = new Random();
    $this->expectException(\RuntimeException::class);
    for ($i = 0; $i <= 100; $i++) {
      $str = $random->name(1, TRUE);
      $names[$str] = TRUE;
    }
  }

  /**
   * Tests infinite loop prevention whilst generating random strings.
   *
   * @covers ::string
   */
  public function testRandomStringException(): void {
    // There are fewer than 100 possibilities so an exception should occur to
    // prevent infinite loops.
    $random = new Random();
    $this->expectException(\RuntimeException::class);
    for ($i = 0; $i <= 100; $i++) {
      $str = $random->string(1, TRUE);
      $names[$str] = TRUE;
    }
  }

  /**
   * Tests random name generation if uniqueness is not enforced.
   *
   * @covers ::name
   */
  public function testRandomNameNonUnique(): void {
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
   * @covers ::string
   */
  public function testRandomStringNonUnique(): void {
    // There are fewer than 100 possibilities if we were forcing uniqueness so
    // exception would occur.
    $random = new Random();
    for ($i = 0; $i <= 100; $i++) {
      $random->string(1);
    }
    $this->assertTrue(TRUE, 'No exception thrown when uniqueness is not enforced.');
  }

  /**
   * Tests unique random name generation.
   *
   * @covers ::machineName
   */
  public function testRandomMachineNamesUniqueness(): void {
    $names = [];
    $random = new Random();
    for ($i = 0; $i <= 10; $i++) {
      $str = $random->machineName(1, TRUE);
      $this->assertArrayNotHasKey($str, $names, 'Generated duplicate random name ' . $str);
      $names[$str] = TRUE;
    }
  }

  /**
   * Tests infinite loop prevention whilst generating random names.
   *
   * @covers ::machineName
   */
  public function testRandomMachineNameException(): void {
    // There are fewer than 100 possibilities so an exception should occur to
    // prevent infinite loops.
    $this->expectException(\RuntimeException::class);
    $random = new Random();
    for ($i = 0; $i <= 100; $i++) {
      $random->machineName(1, TRUE);
    }
  }

  /**
   * Tests random name generation if uniqueness is not enforced.
   *
   * @covers ::machineName
   */
  public function testRandomMachineNameNonUnique(): void {
    // There are fewer than 100 possibilities meaning if uniqueness was
    // enforced, there would be an exception.
    $random = new Random();
    for ($i = 0; $i <= 100; $i++) {
      $random->machineName(1);
    }
    $this->expectNotToPerformAssertions();
  }

  /**
   * Tests random object generation to ensure the expected number of properties.
   *
   * @covers ::object
   */
  public function testRandomObject(): void {
    // For values of 0 and 1 \Drupal\Component\Utility\Random::object() will
    // have different execution paths.
    $random = new Random();
    for ($i = 0; $i <= 1; $i++) {
      $obj = $random->object($i);
      $this->assertCount($i, get_object_vars($obj), 'Generated random object has expected number of properties');
    }
  }

  /**
   * Tests random string validation callbacks.
   *
   * @covers ::string
   */
  public function testRandomStringValidator(): void {
    $random = new Random();
    $this->firstStringGenerated = '';
    $str = $random->string(1, TRUE, [$this, '_RandomStringValidate']);
    $this->assertNotEquals($this->firstStringGenerated, $str);
  }

  /**
   * Tests random word.
   *
   * @covers ::word
   */
  public function testRandomWordValidator(): void {
    $random = new Random();
    $this->assertNotEquals($random->word(20), $random->word(20));
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
