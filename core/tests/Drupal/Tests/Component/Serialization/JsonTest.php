<?php

declare(strict_types=1);

namespace Drupal\Tests\Component\Serialization;

use Drupal\Component\Serialization\Json;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \Drupal\Component\Serialization\Json
 * @group Serialization
 */
class JsonTest extends TestCase {

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
  protected function setUp(): void {
    parent::setUp();

    // Setup a string with the full ASCII table.
    // @todo: Add tests for non-ASCII characters and Unicode.
    $this->string = '';
    for ($i = 1; $i < 128; $i++) {
      $this->string .= chr($i);
    }

    // Characters that must be escaped.
    // We check for unescaped " separately.
    $this->htmlUnsafe = ['<', '>', '\'', '&'];
    // The following are the encoded forms of: < > ' & "
    $this->htmlUnsafeEscaped = ['\u003C', '\u003E', '\u0027', '\u0026', '\u0022'];
  }

  /**
   * Tests encoding for every ASCII character.
   */
  public function testEncodingAscii() {
    // Verify there aren't character encoding problems with the source string.
    $this->assertSame(127, strlen($this->string), 'A string with the full ASCII table has the correct length.');
    foreach ($this->htmlUnsafe as $char) {
      $this->assertStringContainsString($char, $this->string, sprintf('A string with the full ASCII table includes %s.', $char));
    }
  }

  /**
   * Tests encoding length.
   */
  public function testEncodingLength() {
    // Verify that JSON encoding produces a string with all of the characters.
    $json = Json::encode($this->string);
    // Verify that a JSON-encoded string is larger than the source string.
    $this->assertGreaterThan(strlen($this->string), strlen($json));
  }

  /**
   * Tests end and start of the encoded string.
   */
  public function testEncodingStartEnd() {
    $json = Json::encode($this->string);
    // The first and last characters should be ", and no others.
    $this->assertStringStartsWith('"', $json, 'A JSON encoded string begins with ".');
    $this->assertStringEndsWith('"', $json, 'A JSON encoded string ends with ".');
    $this->assertSame(2, substr_count($json, '"'), 'A JSON encoded string contains exactly two ".');
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
   * Tests the reversibility of structured data.
   */
  public function testStructuredReversibility() {
    // Verify reversibility for structured data. Also verify that necessary
    // characters are escaped.
    $source = [TRUE, FALSE, 0, 1, '0', '1', $this->string, ['key1' => $this->string, 'key2' => ['nested' => TRUE]]];
    $json = Json::encode($source);
    foreach ($this->htmlUnsafe as $char) {
      $this->assertStringNotContainsString($char, $json, sprintf('A JSON encoded string does not contain %s.', $char));
    }
    // Verify that JSON encoding escapes the HTML unsafe characters
    foreach ($this->htmlUnsafeEscaped as $char) {
      $this->assertStringContainsString($char, $json, sprintf('A JSON encoded string contains %s.', $char));
    }
    $json_decoded = Json::decode($json);
    $this->assertNotSame($source, $json, 'An array encoded in JSON is identical to the source.');
    $this->assertSame($source, $json_decoded, 'Encoding structured data to JSON and decoding back not results in the original data.');
  }

}
