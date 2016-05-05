<?php

namespace Drupal\KernelTests\Component\Utility;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Url;
use Drupal\KernelTests\KernelTestBase;

/**
 * Provides a test covering integration of SafeMarkup with other systems.
 *
 * @group Utility
*/
class SafeMarkupKernelTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['system'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->container->get('router.builder')->rebuild();
  }

  /**
   * Gets arguments for SafeMarkup::format() based on Url::fromUri() parameters.
   *
   * @param string $uri
   *   The URI of the resource.
   * @param array $options
   *   The options to pass to Url::fromUri().
   *
   * @return array
   *   Array containing:
   *   - ':url': A URL string.
   */
  protected static function getSafeMarkupUriArgs($uri, $options = []) {
    $args[':url'] = Url::fromUri($uri, $options)->toString();
    return $args;
  }

  /**
   * Tests URL ":placeholders" in SafeMarkup::format().
   *
   * @dataProvider providerTestSafeMarkupUri
   */
  public function testSafeMarkupUri($string, $uri, $options, $expected) {
    $args = self::getSafeMarkupUriArgs($uri, $options);
    $this->assertEquals($expected, SafeMarkup::format($string, $args));
  }

  /**
   * @return array
   */
  public function providerTestSafeMarkupUri() {
    $data = [];
    $data['routed-url'] = [
      'Hey giraffe <a href=":url">MUUUH</a>',
      'route:system.admin',
      [],
      'Hey giraffe <a href="/admin">MUUUH</a>',
    ];
    $data['routed-with-query'] = [
      'Hey giraffe <a href=":url">MUUUH</a>',
      'route:system.admin',
      ['query' => ['bar' => 'baz#']],
      'Hey giraffe <a href="/admin?bar=baz%23">MUUUH</a>',
    ];
    $data['routed-with-fragment'] = [
      'Hey giraffe <a href=":url">MUUUH</a>',
      'route:system.admin',
      ['fragment' => 'bar&lt;'],
      'Hey giraffe <a href="/admin#bar&amp;lt;">MUUUH</a>',
    ];
    $data['unrouted-url'] = [
      'Hey giraffe <a href=":url">MUUUH</a>',
      'base://foo',
      [],
      'Hey giraffe <a href="/foo">MUUUH</a>',
    ];
    $data['unrouted-with-query'] = [
      'Hey giraffe <a href=":url">MUUUH</a>',
      'base://foo',
      ['query' => ['bar' => 'baz#']],
      'Hey giraffe <a href="/foo?bar=baz%23">MUUUH</a>',
    ];
    $data['unrouted-with-fragment'] = [
      'Hey giraffe <a href=":url">MUUUH</a>',
      'base://foo',
      ['fragment' => 'bar&lt;'],
      'Hey giraffe <a href="/foo#bar&amp;lt;">MUUUH</a>',
    ];
    $data['mailto-protocol'] = [
      'Hey giraffe <a href=":url">MUUUH</a>',
      'mailto:test@example.com',
      [],
      'Hey giraffe <a href="mailto:test@example.com">MUUUH</a>',
    ];

    return $data;
  }

  /**
   * @dataProvider providerTestSafeMarkupUriWithException
   * @expectedException \InvalidArgumentException
   */
  public function testSafeMarkupUriWithExceptionUri($string, $uri) {
    // Should throw an \InvalidArgumentException, due to Uri::toString().
    $args = self::getSafeMarkupUriArgs($uri);

    SafeMarkup::format($string, $args);
  }

  /**
   * @return array
   */
  public function providerTestSafeMarkupUriWithException() {
    $data = [];
    $data['js-protocol'] = [
      'Hey giraffe <a href=":url">MUUUH</a>',
      "javascript:alert('xss')",
    ];
    $data['js-with-fromCharCode'] = [
      'Hey giraffe <a href=":url">MUUUH</a>',
      "javascript:alert(String.fromCharCode(88,83,83))",
    ];
    $data['non-url-with-colon'] = [
      'Hey giraffe <a href=":url">MUUUH</a>',
      "llamas: they are not URLs",
    ];
    $data['non-url-with-html'] = [
      'Hey giraffe <a href=":url">MUUUH</a>',
      '<span>not a url</span>',
    ];

    return $data;
  }

}
