<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Component\Render;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Url;
use Drupal\KernelTests\KernelTestBase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Provides a test covering integration of FormattableMarkup with other systems.
 */
#[Group('Render')]
#[RunTestsInSeparateProcesses]
class FormattableMarkupKernelTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system'];

  /**
   * Gets arguments for FormattableMarkup based on Url::fromUri() parameters.
   *
   * @param string $uri
   *   The URI of the resource.
   * @param array $options
   *   The options to pass to Url::fromUri().
   *
   * @return array
   *   Array containing:
   *   - ':url': A URL string.
   *
   * @see \Drupal\Component\Render\FormattableMarkup
   */
  protected static function getFormattableMarkupUriArgs($uri, $options = []) {
    $args[':url'] = Url::fromUri($uri, $options)->toString();
    return $args;
  }

  /**
   * Tests URL ":placeholders" in \Drupal\Component\Render\FormattableMarkup.
   */
  #[DataProvider('providerTestFormattableMarkupUri')]
  public function testFormattableMarkupUri($string, $uri, $options, $expected): void {
    $args = self::getFormattableMarkupUriArgs($uri, $options);
    $this->assertSame($expected, (string) new FormattableMarkup($string, $args));
  }

  /**
   * Provides data for testFormattableMarkupUri().
   *
   * @return array
   *   Data provider for testFormattableMarkupUri().
   */
  public static function providerTestFormattableMarkupUri(): array {
    $data = [];
    $data['routed-url'] = [
      'Hey giraffe <a href=":url">example</a>',
      'route:system.admin',
      [],
      'Hey giraffe <a href="/admin">example</a>',
    ];
    $data['routed-with-query'] = [
      'Hey giraffe <a href=":url">example</a>',
      'route:system.admin',
      ['query' => ['bar' => 'baz#']],
      'Hey giraffe <a href="/admin?bar=baz%23">example</a>',
    ];
    $data['routed-with-fragment'] = [
      'Hey giraffe <a href=":url">example</a>',
      'route:system.admin',
      ['fragment' => 'bar&lt;'],
      'Hey giraffe <a href="/admin#bar&amp;lt;">example</a>',
    ];
    $data['unrouted-url'] = [
      'Hey giraffe <a href=":url">example</a>',
      'base://foo',
      [],
      'Hey giraffe <a href="/foo">example</a>',
    ];
    $data['unrouted-with-query'] = [
      'Hey giraffe <a href=":url">example</a>',
      'base://foo',
      ['query' => ['bar' => 'baz#']],
      'Hey giraffe <a href="/foo?bar=baz%23">example</a>',
    ];
    $data['unrouted-with-fragment'] = [
      'Hey giraffe <a href=":url">example</a>',
      'base://foo',
      ['fragment' => 'bar&lt;'],
      'Hey giraffe <a href="/foo#bar&amp;lt;">example</a>',
    ];
    $data['mailto-protocol'] = [
      'Hey giraffe <a href=":url">example</a>',
      'mailto:test@example.com',
      [],
      'Hey giraffe <a href="mailto:test@example.com">example</a>',
    ];

    return $data;
  }

  /**
 * Tests formattable markup uri with exception uri.
 */
  #[DataProvider('providerTestFormattableMarkupUriWithException')]
  public function testFormattableMarkupUriWithExceptionUri($string, $uri): void {
    // Should throw an \InvalidArgumentException, due to Uri::toString().
    $this->expectException(\InvalidArgumentException::class);
    $args = self::getFormattableMarkupUriArgs($uri);

    new FormattableMarkup($string, $args);
  }

  /**
   * Provides data for testFormattableMarkupUriWithExceptionUri().
   *
   * @return array
   *   Data provider for testFormattableMarkupUriWithExceptionUri().
   */
  public static function providerTestFormattableMarkupUriWithException(): array {
    $data = [];
    $data['js-protocol'] = [
      'Hey giraffe <a href=":url">example</a>',
      "javascript:alert('xss')",
    ];
    $data['js-with-fromCharCode'] = [
      'Hey giraffe <a href=":url">example</a>',
      "javascript:alert(String.fromCharCode(88,83,83))",
    ];
    $data['non-url-with-colon'] = [
      'Hey giraffe <a href=":url">example</a>',
      "llamas: they are not URLs",
    ];
    $data['non-url-with-html'] = [
      'Hey giraffe <a href=":url">example</a>',
      '<span>not a url</span>',
    ];

    return $data;
  }

}
