<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Common\JsonUnitTest.
 */

namespace Drupal\system\Tests\Common;

use Drupal\simpletest\UnitTestBase;

/**
 * Tests the drupal_json_encode() and drupal_json_decode() functions.
 */
class JsonUnitTest extends UnitTestBase {
  public static function getInfo() {
    return array(
      'name' => 'JSON',
      'description' => 'Tests the drupal_json_encode() and drupal_json_decode() functions to convert PHP variables to JSON strings and back.',
      'group' => 'Common',
    );
  }

  /**
   * Tests converting PHP variables to JSON strings and back.
   */
  function testJSON() {
    // Setup a string with the full ASCII table.
    // @todo: Add tests for non-ASCII characters and Unicode.
    $str = '';
    for ($i=0; $i < 128; $i++) {
      $str .= chr($i);
    }
    // Characters that must be escaped.
    // We check for unescaped " separately.
    $html_unsafe = array('<', '>', '\'', '&');
    // The following are the encoded forms of: < > ' & "
    $html_unsafe_escaped = array('\u003C', '\u003E', '\u0027', '\u0026', '\u0022');

    // Verify there aren't character encoding problems with the source string.
    $this->assertIdentical(strlen($str), 128, t('A string with the full ASCII table has the correct length.'));
    foreach ($html_unsafe as $char) {
      $this->assertTrue(strpos($str, $char) > 0, t('A string with the full ASCII table includes @s.', array('@s' => $char)));
    }

    // Verify that JSON encoding produces a string with all of the characters.
    $json = drupal_json_encode($str);
    $this->assertTrue(strlen($json) > strlen($str), t('A JSON encoded string is larger than the source string.'));

    // The first and last characters should be ", and no others.
    $this->assertTrue($json[0] == '"', t('A JSON encoded string begins with ".'));
    $this->assertTrue($json[strlen($json) - 1] == '"', t('A JSON encoded string ends with ".'));
    $this->assertTrue(substr_count($json, '"') == 2, t('A JSON encoded string contains exactly two ".'));

    // Verify that encoding/decoding is reversible.
    $json_decoded = drupal_json_decode($json);
    $this->assertIdentical($str, $json_decoded, t('Encoding a string to JSON and decoding back results in the original string.'));

    // Verify reversibility for structured data. Also verify that necessary
    // characters are escaped.
    $source = array(TRUE, FALSE, 0, 1, '0', '1', $str, array('key1' => $str, 'key2' => array('nested' => TRUE)));
    $json = drupal_json_encode($source);
    foreach ($html_unsafe as $char) {
      $this->assertTrue(strpos($json, $char) === FALSE, t('A JSON encoded string does not contain @s.', array('@s' => $char)));
    }
    // Verify that JSON encoding escapes the HTML unsafe characters
    foreach ($html_unsafe_escaped as $char) {
      $this->assertTrue(strpos($json, $char) > 0, t('A JSON encoded string contains @s.', array('@s' => $char)));
    }
    $json_decoded = drupal_json_decode($json);
    $this->assertNotIdentical($source, $json, t('An array encoded in JSON is not identical to the source.'));
    $this->assertIdentical($source, $json_decoded, t('Encoding structured data to JSON and decoding back results in the original data.'));
  }
}
