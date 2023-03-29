<?php

namespace Drupal\Tests\system\Functional\Datetime;

use Drupal\Tests\BrowserTestBase;

// cspell:ignore ABCDEFGHIJK

/**
 * Tests converting JavaScript time zone abbreviations to time zone identifiers.
 *
 * @group Datetime
 */
class TimeZoneAbbreviationRouteTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Test that the AJAX Timezone Callback can deal with various formats.
   */
  public function testSystemTimezone() {
    $options = [
      'query' => [
        'date' => 'Tue+Sep+17+2013+21%3A35%3A31+GMT%2B0100+(BST)#',
      ],
    ];
    // Query the AJAX Timezone Callback with a long-format date.
    $response = $this->drupalGet('system/timezone/BST/3600/1', $options);
    $this->assertEquals($response, '"Europe\/London"');
  }

  /**
   * Test the AJAX Timezone Callback with invalid inputs.
   *
   * @param string $path
   *   Path to call.
   * @param string|null $expectedResponse
   *   Expected response, or NULL if expecting error.
   * @param bool $expectInvalidRequest
   *   Whether to expect the request is invalid.
   *
   * @dataProvider providerAbbreviationConversion
   */
  public function testAbbreviationConversion($path, $expectedResponse = NULL, $expectInvalidRequest = FALSE) {
    $response = $this->drupalGet('system/timezone/' . $path);
    if (isset($expectedResponse)) {
      $this->assertEquals($response, $expectedResponse);
    }
    $this->assertSession()->statusCodeEquals($expectInvalidRequest ? 404 : 200);
  }

  /**
   * Provides test data for testGet().
   *
   * @return array
   *   Test scenarios.
   */
  public function providerAbbreviationConversion() {
    return [
      'valid, default offset' => [
        'CST/0/0',
        '"America\/Chicago"',
      ],
      // This should be the same TZID as default value.
      'valid, default, explicit' => [
        'CST/-1/0',
        '"America\/Chicago"',
      ],
      // Same abbreviation but different offset.
      'valid, default, alternative offset' => [
        'CST/28800/0',
        '"Asia\/Chongqing"',
      ],
      // Using '0' as offset will get the best matching time zone for an offset.
      'valid, no abbreviation, offset, no DST' => [
        '0/3600/0',
        '"Europe\/Paris"',
      ],
      'valid, no abbreviation, offset, with DST' => [
        '0/3600/1',
        '"Europe\/London"',
      ],
      'invalid, unknown abbreviation' => [
        'foo/0/0',
        NULL,
        FALSE,
      ],
      'invalid abbreviation, out of range (short)' => [
        'A',
        NULL,
        TRUE,
      ],
      'invalid abbreviation, out of range (long)' => [
        'ABCDEFGHIJK',
        NULL,
        TRUE,
      ],
      'invalid offset, non integer' => [
        'CST/foo',
        NULL,
        TRUE,
      ],
      'invalid offset, out of range (lower)' => [
        'CST/-100000',
        'false',
      ],
      'invalid offset, out of range (higher)' => [
        'CST/100000',
        'false',
      ],
      'invalid DST value' => [
        'CST/3600/blah',
        NULL,
        TRUE,
      ],
      'invalid DST value, out of range (lower)' => [
        'CST/3600/-2',
        NULL,
        TRUE,
      ],
      'invalid DST value, out of range (higher)' => [
        'CST/3600/2',
        NULL,
        TRUE,
      ],
    ];
  }

}
