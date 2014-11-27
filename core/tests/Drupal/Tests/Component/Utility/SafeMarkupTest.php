<?php

/**
 * @file
 * Contains \Drupal\Tests\Component\Utility\SafeMarkupTest.
 */

namespace Drupal\Tests\Component\Utility;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\Tests\UnitTestCase;

/**
 * Tests marking strings as safe.
 *
 * @group Utility
 * @coversDefaultClass \Drupal\Component\Utility\SafeMarkup
 */
class SafeMarkupTest extends UnitTestCase {

  /**
   * Tests SafeMarkup::set() and SafeMarkup::isSafe().
   *
   * @dataProvider providerSet
   *
   * @param string $text
   *   The text or object to provide to SafeMarkup::set().
   * @param string $message
   *   The message to provide as output for the test.
   *
   * @covers ::set
   */
  public function testSet($text, $message) {
    $returned = SafeMarkup::set($text);
    $this->assertTrue(is_string($returned), 'The return value of SafeMarkup::set() is really a string');
    $this->assertEquals($returned, $text, 'The passed in value should be equal to the string value according to PHP');
    $this->assertTrue(SafeMarkup::isSafe($text), $message);
    $this->assertTrue(SafeMarkup::isSafe($returned), 'The return value has been marked as safe');
  }

  /**
   * Data provider for testSet().
   *
   * @see testSet()
   */
  public function providerSet() {
    // Checks that invalid multi-byte sequences are rejected.
    $tests[] = array("Foo\xC0barbaz", '', 'String::checkPlain() rejects invalid sequence "Foo\xC0barbaz"', TRUE);
    $tests[] = array("Fooÿñ", 'SafeMarkup::set() accepts valid sequence "Fooÿñ"');
    $tests[] = array(new TextWrapper("Fooÿñ"), 'SafeMarkup::set() accepts valid sequence "Fooÿñ" in an object implementing __toString()');
    $tests[] = array("<div>", 'SafeMarkup::set() accepts HTML');

    return $tests;
  }

  /**
   * Tests SafeMarkup::set() and SafeMarkup::isSafe() with different providers.
   *
   * @covers ::isSafe
   */
  public function testStrategy() {
    $returned = SafeMarkup::set('string0', 'html');
    $this->assertTrue(SafeMarkup::isSafe($returned), 'String set with "html" provider is safe for default (html)');
    $returned = SafeMarkup::set('string1', 'all');
    $this->assertTrue(SafeMarkup::isSafe($returned), 'String set with "all" provider is safe for default (html)');
    $returned = SafeMarkup::set('string2', 'css');
    $this->assertFalse(SafeMarkup::isSafe($returned), 'String set with "css" provider is not safe for default (html)');
    $returned = SafeMarkup::set('string3');
    $this->assertFalse(SafeMarkup::isSafe($returned, 'all'), 'String set with "html" provider is not safe for "all"');
  }

  /**
   * Tests SafeMarkup::setMultiple().
   *
   * @covers ::setMultiple
   */
  public function testSetMultiple() {
    $texts = array(
      'multistring0' => array('html' => TRUE),
      'multistring1' => array('all' => TRUE),
    );
    SafeMarkup::setMultiple($texts);
    foreach ($texts as $string => $providers) {
      $this->assertTrue(SafeMarkup::isSafe($string), 'The value has been marked as safe for html');
    }
  }

  /**
   * Tests SafeMarkup::setMultiple().
   *
   * Only TRUE may be passed in as the value.
   *
   * @covers ::setMultiple
   *
   * @expectedException \UnexpectedValueException
   */
  public function testInvalidSetMultiple() {
    $texts = array(
      'invalidstring0' => array('html' => 1),
    );
    SafeMarkup::setMultiple($texts);
  }

}
