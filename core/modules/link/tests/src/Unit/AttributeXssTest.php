<?php

declare(strict_types=1);

namespace Drupal\Tests\link\Unit;

use Drupal\link\AttributeXss;
use Drupal\Tests\UnitTestCase;

/**
 * Tests AttributeXss.
 *
 * @group link
 * @covers \Drupal\link\AttributeXss
 */
final class AttributeXssTest extends UnitTestCase {

  /**
   * Covers ::sanitizeAttributes.
   *
   * @dataProvider providerSanitizeAttributes
   */
  public function testSanitizeAttributes(array $attributes, array $expected): void {
    self::assertSame($expected, AttributeXss::sanitizeAttributes($attributes));
  }

  /**
   * Data provider for ::testSanitizeAttributes.
   *
   * @return \Generator
   *   Test cases.
   */
  public static function providerSanitizeAttributes(): \Generator {
    yield 'safe' => [
      ['class' => ['foo', 'bar'], 'data-biscuit' => TRUE],
      ['class' => ['foo', 'bar'], 'data-biscuit' => TRUE],
    ];

    yield 'valueless' => [
      ['class' => ['foo', 'bar'], 'selected' => ''],
      ['class' => ['foo', 'bar'], 'selected' => ''],
    ];

    yield 'empty names' => [
      ['class' => ['foo', 'bar'], '' => 'live', '  ' => TRUE],
      ['class' => ['foo', 'bar']],
    ];

    yield 'only empty names' => [
      ['' => 'live', '  ' => TRUE],
      [],
    ];

    yield 'valueless, mangled with a space' => [
      ['class' => ['foo', 'bar'], 'selected href' => 'http://example.com'],
      ['class' => ['foo', 'bar'], 'selected' => 'selected', 'href' => 'http://example.com'],
    ];

    yield 'valueless, mangled with a space, blocked' => [
      ['class' => ['foo', 'bar'], 'selected onclick href' => 'http://example.com'],
      ['class' => ['foo', 'bar'], 'selected' => 'selected', 'href' => 'http://example.com'],
    ];

    yield 'with encoding' => [
      ['class' => ['foo', 'bar'], 'data-how-good' => "It's the bee's knees"],
      ['class' => ['foo', 'bar'], 'data-how-good' => "It's the bee's knees"],
    ];

    yield 'valueless, mangled with multiple spaces, blocked' => [
      ['class' => ['foo', 'bar'], 'selected  onclick href' => 'http://example.com'],
      ['class' => ['foo', 'bar'], 'selected' => 'selected', 'href' => 'http://example.com'],
    ];

    yield 'valueless, mangled with multiple spaces, blocked, mangled first' => [
      ['selected  onclick href' => 'http://example.com', 'class' => ['foo', 'bar']],
      ['selected' => 'selected', 'href' => 'http://example.com', 'class' => ['foo', 'bar']],
    ];

    yield 'valueless but with value' => [
      ['class' => ['foo', 'bar'], 'selected' => 'selected', 'href' => 'http://example.com'],
      ['class' => ['foo', 'bar'], 'selected' => 'selected', 'href' => 'http://example.com'],
    ];

    yield 'valueless but with value, bad protocol' => [
      ['class' => ['foo', 'bar'], 'selected' => 'selected', 'href' => 'javascript:alert()'],
      ['class' => ['foo', 'bar'], 'selected' => 'selected', 'href' => 'alert()'],
    ];

    yield 'valueless, mangled with a space and bad protocol' => [
      ['class' => ['foo', 'bar'], 'selected href' => 'javascript:alert()'],
      ['class' => ['foo', 'bar'], 'selected' => 'selected', 'href' => 'alert()'],
    ];

    yield 'valueless, mangled with a space and bad protocol, repeated' => [
      ['class' => ['foo', 'bar'], 'selected href' => 'javascript:alert()', 'href' => 'http://example.com'],
      ['class' => ['foo', 'bar'], 'selected' => 'selected', 'href' => 'alert()'],
    ];

    yield 'with a space' => [
      ['class' => ['foo', 'bar'], 'href' => \urlencode('some file.pdf')],
      ['class' => ['foo', 'bar'], 'href' => 'some+file.pdf'],
    ];

    yield 'with an unencoded space' => [
      ['class' => ['foo', 'bar'], 'href' => 'some file.pdf'],
      ['class' => ['foo', 'bar'], 'href' => 'some file.pdf'],
    ];

    yield 'xss onclick' => [
      ['class' => ['foo', 'bar'], 'onclick' => 'alert("whoop");'],
      ['class' => ['foo', 'bar']],
    ];

    yield 'xss onclick, valueless, mangled with a space' => [
      ['class' => ['foo', 'bar'], 'selected onclick href' => 'http://example.com'],
      ['class' => ['foo', 'bar'], 'selected' => 'selected', 'href' => 'http://example.com'],
    ];

    yield 'xss protocol' => [
      ['class' => ['foo', 'bar'], 'src' => 'javascript:alert("whoop");'],
      ['class' => ['foo', 'bar'], 'src' => 'alert("whoop");'],
    ];

  }

}
