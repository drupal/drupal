<?php

declare(strict_types=1);

namespace Drupal\Tests\jsonapi\Unit\Encoder;

use Drupal\jsonapi\Encoder\JsonEncoder;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests the JSON:API encoder.
 *
 * @internal
 */
#[CoversClass(JsonEncoder::class)]
#[Group('jsonapi')]
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
    $this->assertTrue($this->encoder->supportsEncoding('api_json'));
    $this->assertFalse($this->encoder->supportsEncoding('json'));
    $this->assertFalse($this->encoder->supportsEncoding('xml'));
  }

  /**
   * Tests JSON:API encoder inherits control character handling.
   *
   * This verifies that the JSON:API encoder correctly inherits the
   * JSON_INVALID_UTF8_SUBSTITUTE flag from the parent serialization encoder.
   * The comprehensive tests for this functionality are in the serialization
   * module's JsonEncoderTest.
   *
   * @see \Drupal\Tests\serialization\Unit\Encoder\JsonEncoderTest
   * @see https://www.drupal.org/project/drupal/issues/3549107
   */
  public function testInheritsControlCharacterHandling(): void {
    // Test that invalid UTF-8 is handled (would fail without the flag).
    $input = "Test\x80Data";
    $encoded = $this->encoder->encode($input, 'api_json');

    // Verify it's valid JSON.
    $this->assertJson($encoded, 'Encoded output should be valid JSON even with invalid UTF-8.');

    // Verify the replacement character is present.
    $this->assertStringContainsString('\ufffd', $encoded, 'Invalid UTF-8 should be replaced with U+FFFD.');

    // Verify it can be decoded.
    json_decode($encoded);
    $this->assertSame(JSON_ERROR_NONE, json_last_error(), 'Encoded JSON should be decodable without errors.');
  }

}
