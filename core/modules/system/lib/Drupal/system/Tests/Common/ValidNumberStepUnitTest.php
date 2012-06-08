<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Common\ValidNumberStepUnitTest.
 */

namespace Drupal\system\Tests\Common;

use Drupal\simpletest\UnitTestBase;

/**
 * Tests number step validation by valid_number_step().
 */
class ValidNumberStepUnitTest extends UnitTestBase {
  public static function getInfo() {
    return array(
      'name' => 'Number step validation',
      'description' => 'Tests number step validation by valid_number_step()',
      'group' => 'Common',
    );
  }

  /**
   * Tests valid_number_step() without offset.
   */
  function testNumberStep() {
    // Value and step equal.
    $this->assertTrue(valid_number_step(10.3, 10.3));

    // Valid integer steps.
    $this->assertTrue(valid_number_step(42, 21));
    $this->assertTrue(valid_number_step(42, 3));

    // Valid float steps.
    $this->assertTrue(valid_number_step(42, 10.5));
    $this->assertTrue(valid_number_step(1, 1/3));
    $this->assertTrue(valid_number_step(-100, 100/7));
    $this->assertTrue(valid_number_step(1000, -10));

    // Valid and very small float steps.
    $this->assertTrue(valid_number_step(1000.12345, 1e-10));
    $this->assertTrue(valid_number_step(3.9999999999999, 1e-13));

    // Invalid integer steps.
    $this->assertFalse(valid_number_step(100, 30));
    $this->assertFalse(valid_number_step(-10, 4));

    // Invalid float steps.
    $this->assertFalse(valid_number_step(6, 5/7));
    $this->assertFalse(valid_number_step(10.3, 10.25));

    // Step mismatches very close to beeing valid.
    $this->assertFalse(valid_number_step(70 + 9e-7, 10 + 9e-7));
    $this->assertFalse(valid_number_step(1936.5, 3e-8));
  }

  /**
   * Tests valid_number_step() with offset.
   */
  function testNumberStepOffset() {
    // Try obvious fits.
    $this->assertTrue(valid_number_step(11.3, 10.3, 1));
    $this->assertTrue(valid_number_step(100, 10, 50));
    $this->assertTrue(valid_number_step(-100, 90/7, -10));
    $this->assertTrue(valid_number_step(2/7 + 5/9, 1/7, 5/9));

    // Ensure a small offset is still invalid.
    $this->assertFalse(valid_number_step(10.3, 10.3, 0.0001));
    $this->assertFalse(valid_number_step(1/5, 1/7, 1/11));

    // Try negative values and offsets.
    $this->assertFalse(valid_number_step(1000, 10, -5));
    $this->assertFalse(valid_number_step(-10, 4, 0));
    $this->assertFalse(valid_number_step(-10, 4, -4));
  }
}
