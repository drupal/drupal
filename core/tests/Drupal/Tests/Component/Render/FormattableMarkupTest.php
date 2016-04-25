<?php

namespace Drupal\Tests\Component\Render;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the TranslatableMarkup class.
 *
 * @coversDefaultClass \Drupal\Component\Render\FormattableMarkup
 * @group utility
 */
class FormattableMarkupTest extends UnitTestCase {

  /**
   * @covers ::__toString
   * @covers ::jsonSerialize
   */
  public function testToString() {
    $string = 'Can I please have a @replacement';
    $formattable_string = new FormattableMarkup($string, ['@replacement' => 'kitten']);
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
    $formattable_string = new FormattableMarkup($string, ['@replacement' => 'kitten']);
    $this->assertEquals(strlen($string), $formattable_string->count());
  }

}
