<?php

declare(strict_types=1);

namespace Drupal\Tests\jsonapi\Unit;

use Drupal\jsonapi\JsonApiSpec;
use Drupal\Tests\UnitTestCase;

// cspell:ignore kitt

/**
 * @coversDefaultClass \Drupal\jsonapi\JsonApiSpec
 * @group jsonapi
 *
 * @internal
 */
class JsonApiSpecTest extends UnitTestCase {

  /**
   * Ensures that member names are properly validated.
   *
   * @dataProvider providerTestIsValidMemberName
   * @covers ::isValidMemberName
   */
  public function testIsValidMemberName($member_name, $expected): void {
    $this->assertSame($expected, JsonApiSpec::isValidMemberName($member_name));
  }

  /**
   * Data provider for testIsValidMemberName.
   */
  public static function providerTestIsValidMemberName() {
    // Copied from http://jsonapi.org/format/upcoming/#document-member-names.
    $data = [];
    $data['alphanumeric-lowercase'] = ['12kittens', TRUE];
    $data['alphanumeric-uppercase'] = ['12KITTENS', TRUE];
    $data['alphanumeric-mixed'] = ['12KiTtEnS', TRUE];
    $data['unicode-above-u+0080'] = ['12🐱🐱', TRUE];
    $data['hyphen-start'] = ['-kittens', FALSE];
    $data['hyphen-middle'] = ['kitt-ens', TRUE];
    $data['hyphen-end'] = ['kittens-', FALSE];
    $data['low-line-start'] = ['_kittens', FALSE];
    $data['low-line-middle'] = ['kitt_ens', TRUE];
    $data['low-line-end'] = ['kittens_', FALSE];
    $data['space-start'] = [' kittens', FALSE];
    $data['space-middle'] = ['kitt ens', TRUE];
    $data['space-end'] = ['kittens ', FALSE];

    // Additional test cases.
    // @todo When D8 requires PHP >= 7, convert to \u{10FFFF}.
    $data['unicode-above-u+0080-highest-allowed'] = ["12􏿿", TRUE];
    $data['single-character'] = ['a', TRUE];

    $unsafe_chars = [
      '+',
      ',',
      '.',
      '[',
      ']',
      '!',
      '"',
      '#',
      '$',
      '%',
      '&',
      '\'',
      '(',
      ')',
      '*',
      '/',
      ':',
      ';',
      '<',
      '=',
      '>',
      '?',
      '@',
      '\\',
      '^',
      '`',
      '{',
      '|',
      '}',
      '~',
    ];
    foreach ($unsafe_chars as $unsafe_char) {
      $data['unsafe-' . $unsafe_char] = ['kitt' . $unsafe_char . 'ens', FALSE];
    }

    // The ASCII control characters are in the range 0x00 to 0x1F plus 0x7F.
    for ($ascii = 0; $ascii <= 0x1F; $ascii++) {
      $data['unsafe-ascii-control-' . $ascii] = ['kitt' . chr($ascii) . 'ens', FALSE];
    }
    $data['unsafe-ascii-control-' . 0x7F] = ['kitt' . chr(0x7F) . 'ens', FALSE];

    return $data;
  }

  /**
   * Provides test cases.
   *
   * @dataProvider providerTestIsValidCustomQueryParameter
   * @covers ::isValidCustomQueryParameter
   * @covers ::isValidMemberName
   */
  public function testIsValidCustomQueryParameter($custom_query_parameter, $expected): void {
    $this->assertSame($expected, JsonApiSpec::isValidCustomQueryParameter($custom_query_parameter));
  }

  /**
   * Data provider for testIsValidCustomQueryParameter.
   */
  public static function providerTestIsValidCustomQueryParameter() {
    $data = static::providerTestIsValidMemberName();

    // All valid member names are also valid custom query parameters, except for
    // single-character ones.
    $data['single-character'][1] = FALSE;

    // Custom query parameter test cases.
    $data['custom-query-parameter-lowercase'] = ['foobar', FALSE];
    $data['custom-query-parameter-dash'] = ['foo-bar', TRUE];
    $data['custom-query-parameter-underscore'] = ['foo_bar', TRUE];
    $data['custom-query-parameter-camel-case'] = ['fooBar', TRUE];

    return $data;
  }

}
