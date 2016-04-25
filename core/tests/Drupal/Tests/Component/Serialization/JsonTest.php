<?php

namespace Drupal\Tests\Component\Serialization;

use Drupal\Component\Serialization\Json;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\Component\Serialization\Json
 * @group Serialization
 */
class JsonTest extends UnitTestCase {

  /**
   * A test string with the full ASCII table.
   *
   * @var string
   */
  protected $string;

  /**
   * An array of unsafe html characters which has to be encoded.
   *
   * @var array
   */
  protected $htmlUnsafe;

  /**
   * An array of unsafe html characters which are already escaped.
   *
   * @var array
   */
  protected $htmlUnsafeEscaped;


  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Setup a string with the full ASCII table.
    // @todo: Add tests for non-ASCII characters and Unicode.
    $this->string = '';
    for ($i = 1; $i < 128; $i++) {
      $this->string .= chr($i);
    }

    // Characters that must be escaped.
    // We check for unescaped " separately.
    $this->htmlUnsafe = array('<', '>', '\'', '&');
    // The following are the encoded forms of: < > ' & "
    $this->htmlUnsafeEscaped = array('\u003C', '\u003E', '\u0027', '\u0026', '\u0022');
  }

  /**
   * Tests encoding for every ASCII character.
   */
  public function testEncodingAscii() {
    // Verify there aren't character encoding problems with the source string.
    $this->assertSame(strlen($this->string), 127, 'A string with the full ASCII table has the correct length.');
    foreach ($this->htmlUnsafe as $char) {
      $this->assertTrue(strpos($this->string, $char) > 0, sprintf('A string with the full ASCII table includes %s.', $char));
    }
  }

  /**
   * Tests encoding length.
   */
  public function testEncodingLength() {
    // Verify that JSON encoding produces a string with all of the characters.
    $json = Json::encode($this->string);
    $this->assertTrue(strlen($json) > strlen($this->string), 'A JSON encoded string is larger than the source string.');
  }

  /**
   * Tests end and start of the encoded string.
   */
  public function testEncodingStartEnd() {
    $json = Json::encode($this->string);
    // The first and last characters should be ", and no others.
    $this->assertTrue($json[0] == '"', 'A JSON encoded string begins with ".');
    $this->assertTrue($json[strlen($json) - 1] == '"', 'A JSON encoded string ends with ".');
    $this->assertTrue(substr_count($json, '"') == 2, 'A JSON encoded string contains exactly two ".');
  }

  /**
   * Tests converting PHP variables to JSON strings and back.
   */
  public function testReversibility() {
    $json = Json::encode($this->string);
    // Verify that encoding/decoding is reversible.
    $json_decoded = Json::decode($json);
    $this->assertSame($this->string, $json_decoded, 'Encoding a string to JSON and decoding back results in the original string.');
  }

  /**
   * Test the reversibility of structured data
   */
  public function testStructuredReversibility() {
    // Verify reversibility for structured data. Also verify that necessary
    // characters are escaped.
    $source = array(TRUE, FALSE, 0, 1, '0', '1', $this->string, array('key1' => $this->string, 'key2' => array('nested' => TRUE)));
    $json = Json::encode($source);
    foreach ($this->htmlUnsafe as $char) {
      $this->assertTrue(strpos($json, $char) === FALSE, sprintf('A JSON encoded string does not contain %s.', $char));
    }
    // Verify that JSON encoding escapes the HTML unsafe characters
    foreach ($this->htmlUnsafeEscaped as $char) {
      $this->assertTrue(strpos($json, $char) > 0, sprintf('A JSON encoded string contains %s.', $char));
    }
    $json_decoded = Json::decode($json);
    $this->assertNotSame($source, $json, 'An array encoded in JSON is identical to the source.');
    $this->assertSame($source, $json_decoded, 'Encoding structured data to JSON and decoding back not results in the original data.');
  }

}
