<?php

declare(strict_types=1);

namespace Drupal\Tests\serialization\Unit\Encoder;

use Drupal\serialization\Encoder\JsonEncoder;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests Drupal\serialization\Encoder\JsonEncoder.
 */
#[CoversClass(JsonEncoder::class)]
#[Group('serialization')]
class JsonEncoderTest extends UnitTestCase {

  /**
   * The encoder under test.
   */
  protected JsonEncoder $encoder;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->encoder = new JsonEncoder();
  }

  /**
   * Tests the supportsEncoding() method.
   */
  public function testSupportsEncoding(): void {
    $this->assertTrue($this->encoder->supportsEncoding('json'));
    $this->assertTrue($this->encoder->supportsEncoding('ajax'));
    $this->assertFalse($this->encoder->supportsEncoding('xml'));
  }

  /**
   * Tests that invalid UTF-8 is handled via JSON_INVALID_UTF8_SUBSTITUTE.
   *
   * @see https://www.drupal.org/project/drupal/issues/3549107
   */
  public function testEncodeInvalidUtf8IsSubstituted(): void {
    // A representative invalid UTF-8 sequence that would previously cause
    // json_encode() to fail.
    $input = "Test\x80Data";
    $encoded = $this->encoder->encode($input, 'json');

    // Verify it's valid JSON (would fail without the flag).
    $this->assertJson($encoded, 'Encoded output should be valid JSON even with invalid UTF-8.');

    // Verify the replacement character is present.
    $this->assertStringContainsString('\\ufffd', $encoded, 'Invalid UTF-8 should be replaced with U+FFFD.');

    // Verify it can be decoded.
    json_decode($encoded);
    $this->assertSame(JSON_ERROR_NONE, json_last_error(), 'Encoded JSON should be decodable without errors.');
  }

  /**
   * Tests that HTML-unsafe characters are still escaped.
   *
   * This ensures that existing JSON_HEX_* behavior is preserved after adding
   * JSON_INVALID_UTF8_SUBSTITUTE.
   */
  public function testHtmlUnsafeCharactersAreEscaped(): void {
    $input = "<script>alert('test & \"hack\"');</script>";
    $encoded = $this->encoder->encode($input, 'json');

    // Verify it's valid JSON.
    $this->assertJson($encoded, 'HTML-unsafe characters should produce valid JSON.');

    // Verify HTML-unsafe characters are escaped as hex codes.
    $this->assertStringContainsString('\\u003C', $encoded, '< should be escaped to \\u003C.');
    $this->assertStringContainsString('\\u003E', $encoded, '> should be escaped to \\u003E.');
    $this->assertStringContainsString('\\u0027', $encoded, "' should be escaped to \\u0027.");
    $this->assertStringContainsString('\\u0026', $encoded, '& should be escaped to \\u0026.');
    $this->assertStringContainsString('\\u0022', $encoded, '" should be escaped to \\u0022.');
  }

  /**
   * Simple structured data smoke test.
   *
   * This verifies that the encoder works for nested arrays and that invalid
   * UTF-8 inside the structure is still handled correctly.
   */
  public function testStructuredDataSmokeTest(): void {
    $data = [
      'title' => 'Example',
      'body' => "Content with invalid UTF-8: \x80",
      'metadata' => [
        'tags' => ['one', 'two'],
      ],
    ];

    $encoded = $this->encoder->encode($data, 'json');

    $this->assertJson($encoded, 'Structured data should produce valid JSON.');
    $this->assertStringContainsString('\\ufffd', $encoded, 'Invalid UTF-8 in nested data should be replaced.');

    $decoded = json_decode($encoded, TRUE);
    $this->assertSame(JSON_ERROR_NONE, json_last_error(), 'Structured data should be decodable.');
    $this->assertIsArray($decoded, 'Decoded data should be an array.');
    $this->assertArrayHasKey('title', $decoded, 'Decoded data should have title key.');
    $this->assertArrayHasKey('metadata', $decoded, 'Decoded data should have metadata key.');
  }

}
