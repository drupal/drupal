<?php

/**
 * @file
 * Contains \Drupal\Tests\Component\Utility\FormattableStringTest.
 */

namespace Drupal\Tests\Component\Utility;

use Drupal\Component\Utility\FormattableString;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the TranslatableString class.
 *
 * @coversDefaultClass \Drupal\Component\Utility\FormattableString
 * @group utility
 */
class FormattableStringTest extends UnitTestCase {

  /**
   * @covers ::__toString
   * @covers ::jsonSerialize
   */
  public function testToString() {
    $string = 'Can I please have a @replacement';
    $formattable_string = new FormattableString($string, ['@replacement' => 'kitten']);
    $text = (string) $formattable_string;
    $this->assertEquals('Can I please have a kitten', $text);
    $text = $formattable_string->jsonSerialize();
    $this->assertEquals('Can I please have a kitten', $text);
  }

  /**
   * @covers ::count
   */
  public function testCount() {
    $string = 'Can I please have a @replacement';
    $formattable_string = new FormattableString($string, ['@replacement' => 'kitten']);
    $this->assertEquals(strlen($string), $formattable_string->count());
  }

}
