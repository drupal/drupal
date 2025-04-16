<?php

declare(strict_types=1);

namespace Drupal\Tests\Component\Utility;

use Drupal\Component\Utility\UrlHelper;
use PHPUnit\Framework\TestCase;

/**
 * @group Utility
 *
 * @coversDefaultClass \Drupal\Component\Utility\UrlHelper
 */
class UrlHelperTest extends TestCase {

  /**
   * Provides test data for testBuildQuery().
   *
   * @return array
   */
  public static function providerTestBuildQuery() {
    return [
      [['a' => ' &#//+%20@۞'], 'a=%20%26%23//%2B%2520%40%DB%9E', 'Value was properly encoded.'],
      [[' &#//+%20@۞' => 'a'], '%20%26%23%2F%2F%2B%2520%40%DB%9E=a', 'Key was properly encoded.'],
      [['a' => '1', 'b' => '2', 'c' => '3'], 'a=1&b=2&c=3', 'Multiple values were properly concatenated.'],
      [['a' => ['b' => '2', 'c' => '3'], 'd' => 'foo'], 'a%5Bb%5D=2&a%5Bc%5D=3&d=foo', 'Nested array was properly encoded.'],
      [['foo' => NULL], 'foo', 'Simple parameters are properly added.'],
    ];
  }

  /**
   * Tests query building.
   *
   * @dataProvider providerTestBuildQuery
   * @covers ::buildQuery
   *
   * @param array $query
   *   The array of query parameters.
   * @param string $expected
   *   The expected query string.
   * @param string $message
   *   The assertion message.
   */
  public function testBuildQuery($query, $expected, $message): void {
    $this->assertEquals(UrlHelper::buildQuery($query), $expected, $message);
  }

  /**
   * Data provider for testValidAbsolute().
   *
   * @return array
   */
  public static function providerTestValidAbsoluteData(): array {
    $urls = [
      'example.com',
      'www.example.com',
      'ex-ample.com',
      // cspell:disable-next-line
      '3xampl3.com',
      'example.com/parenthesis',
      'example.com/index.html#pagetop',
      'example.com:8080',
      'subdomain.example.com',
      'example.com/index.php/node',
      'example.com/index.php/node?param=false',
      'user@www.example.com',
      'user:pass@www.example.com:8080/login.php?do=login&style=%23#pagetop',
      '127.0.0.1',
      'example.org?',
      'john%20doe:secret:foo@example.org/',
      'example.org/~,$\'*;',
      'caf%C3%A9.example.org',
      // cspell:ignore FEDC
      '[FEDC:BA98:7654:3210:FEDC:BA98:7654:3210]:80/index.html',
    ];

    return self::dataEnhanceWithScheme($urls);
  }

  /**
   * Tests valid absolute URLs.
   *
   * @dataProvider providerTestValidAbsoluteData
   * @covers ::isValid
   *
   * @param string $url
   *   The URL to test.
   * @param string $scheme
   *   The scheme to test.
   */
  public function testValidAbsolute(string $url, string $scheme): void {
    $test_url = $scheme . '://' . $url;
    $valid_url = UrlHelper::isValid($test_url, TRUE);
    $this->assertTrue($valid_url, $test_url . ' is a valid URL.');
  }

  /**
   * Provides data for testInvalidAbsolute().
   *
   * @return array
   */
  public static function providerTestInvalidAbsolute(): array {
    $data = [
      '',
      'ex!ample.com',
      'ex%ample.com',
    ];
    return self::dataEnhanceWithScheme($data);
  }

  /**
   * Tests that we get the same thing out that we put in.
   */
  public function testCompressUncompress(): void {
    $data = [];
    while (count($data) < 30) {
      $data[] = 'drupal/drupal' . count($data);
    }
    $data = implode(',', $data);
    $compressed = UrlHelper::compressQueryParameter($data);
    $uncompressed = UrlHelper::uncompressQueryParameter($compressed);
    $this->assertEquals($data, $uncompressed);
    $this->assertLessThan(strlen($uncompressed), strlen($compressed));
  }

  /**
   * Tests passing an invalid string as a compressed query parameter.
   */
  public function testUncompressInvalidString(): void {
    // Pass an invalid string to ::uncompressQueryParameter() and ensure it
    // returns the passed string without resulting in a PHP warning.
    $this->assertSame('llama', UrlHelper::uncompressQueryParameter('llama'));
  }

  /**
   * Tests invalid absolute URLs.
   *
   * @dataProvider providerTestInvalidAbsolute
   * @covers ::isValid
   *
   * @param string $url
   *   The URL to test.
   * @param string $scheme
   *   The scheme to test.
   */
  public function testInvalidAbsolute(string $url, string $scheme): void {
    $test_url = $scheme . '://' . $url;
    $valid_url = UrlHelper::isValid($test_url, TRUE);
    $this->assertFalse($valid_url, $test_url . ' is NOT a valid URL.');
  }

  /**
   * Provides data for testValidRelative().
   *
   * @return array
   */
  public static function providerTestValidRelativeData(): array {
    $data = [
      'paren(the)sis',
      'index.html#pagetop',
      'index.php/node',
      'index.php/node?param=false',
      'login.php?do=login&style=%23#pagetop',
    ];

    return self::dataEnhanceWithPrefix($data);
  }

  /**
   * Tests valid relative URLs.
   *
   * @dataProvider providerTestValidRelativeData
   * @covers ::isValid
   *
   * @param string $url
   *   The URL to test.
   * @param string $prefix
   *   The prefix to test.
   */
  public function testValidRelative(string $url, string $prefix): void {
    $test_url = $prefix . $url;
    $valid_url = UrlHelper::isValid($test_url);
    $this->assertTrue($valid_url, $test_url . ' is a valid URL.');
  }

  /**
   * Provides data for testInvalidRelative().
   *
   * @return array
   */
  public static function providerTestInvalidRelativeData(): array {
    $data = [
      // cspell:disable-next-line
      'ex^mple',
      'example<>',
      'ex%ample',
    ];
    return self::dataEnhanceWithPrefix($data);
  }

  /**
   * Tests invalid relative URLs.
   *
   * @dataProvider providerTestInvalidRelativeData
   * @covers ::isValid
   *
   * @param string $url
   *   The URL to test.
   * @param string $prefix
   *   The prefix to test.
   */
  public function testInvalidRelative(string $url, string $prefix): void {
    $test_url = $prefix . $url;
    $valid_url = UrlHelper::isValid($test_url);
    $this->assertFalse($valid_url, $test_url . ' is NOT a valid URL.');
  }

  /**
   * Tests query filtering.
   *
   * @dataProvider providerTestFilterQueryParameters
   * @covers ::filterQueryParameters
   *
   * @param array $query
   *   The array of query parameters.
   * @param array $exclude
   *   A list of $query array keys to remove. Use "parent[child]" to exclude
   *   nested items.
   * @param array $expected
   *   An array containing query parameters.
   */
  public function testFilterQueryParameters($query, $exclude, $expected): void {
    $filtered = UrlHelper::filterQueryParameters($query, $exclude);
    $this->assertEquals($expected, $filtered, 'The query was not properly filtered.');
  }

  /**
   * Provides data to self::testFilterQueryParameters().
   *
   * @return array
   */
  public static function providerTestFilterQueryParameters() {
    return [
      // Test without an exclude filter.
      [
        'query' => ['a' => ['b' => 'c']],
        'exclude' => [],
        'expected' => ['a' => ['b' => 'c']],
      ],
      // Exclude the 'b' element.
      [
        'query' => ['a' => ['b' => 'c', 'd' => 'e']],
        'exclude' => ['a[b]'],
        'expected' => ['a' => ['d' => 'e']],
      ],
    ];
  }

  /**
   * Tests URL parsing.
   *
   * @dataProvider providerTestParse
   * @covers ::parse
   *
   * @param string $url
   *   URL to test.
   * @param array $expected
   *   Associative array with expected parameters.
   */
  public function testParse($url, $expected): void {
    $parsed = UrlHelper::parse($url);
    $this->assertEquals($expected, $parsed, 'The URL was not properly parsed.');
  }

  /**
   * Provides data for self::testParse().
   *
   * @return array
   */
  public static function providerTestParse() {
    return [
      [
        'http://www.example.com/my/path',
        [
          'path' => 'http://www.example.com/my/path',
          'query' => [],
          'fragment' => '',
        ],
      ],
      [
        'http://www.example.com/my/path?destination=home#footer',
        [
          'path' => 'http://www.example.com/my/path',
          'query' => [
            'destination' => 'home',
          ],
          'fragment' => 'footer',
        ],
      ],
      'absolute fragment, no query' => [
        'http://www.example.com/my/path#footer',
        [
          'path' => 'http://www.example.com/my/path',
          'query' => [],
          'fragment' => 'footer',
        ],
      ],
      [
        'http://',
        [
          'path' => '',
          'query' => [],
          'fragment' => '',
        ],
      ],
      [
        'https://',
        [
          'path' => '',
          'query' => [],
          'fragment' => '',
        ],
      ],
      [
        '/my/path?destination=home#footer',
        [
          'path' => '/my/path',
          'query' => [
            'destination' => 'home',
          ],
          'fragment' => 'footer',
        ],
      ],
      'relative fragment, no query' => [
        '/my/path#footer',
        [
          'path' => '/my/path',
          'query' => [],
          'fragment' => 'footer',
        ],
      ],
      'URL with two question marks, not encoded' => [
        'http://www.example.com/my/path?destination=home&search=http://www.example.com/search?limit=10#footer',
        [
          'path' => 'http://www.example.com/my/path',
          'query' => [
            'destination' => 'home',
            'search' => 'http://www.example.com/search?limit=10',
          ],
          'fragment' => 'footer',
        ],
      ],
      'URL with three question marks, not encoded' => [
        'http://www.example.com/my/path?destination=home&search=http://www.example.com/search?limit=10&referer=http://www.example.com/my/path?destination=home&other#footer',
        [
          'path' => 'http://www.example.com/my/path',
          'query' => [
            'destination' => 'home',
            'search' => 'http://www.example.com/search?limit=10',
            'referer' => 'http://www.example.com/my/path?destination=home',
            'other' => '',
          ],
          'fragment' => 'footer',
        ],
      ],
      'URL with three question marks, encoded' => [
        'http://www.example.com/my/path?destination=home&search=http://www.example.com/search?limit=10&referer=http%3A%2F%2Fwww.example.com%2Fmy%2Fpath%3Fdestination%3Dhome%26other#footer',
        [
          'path' => 'http://www.example.com/my/path',
          'query' => [
            'destination' => 'home',
            'search' => 'http://www.example.com/search?limit=10',
            'referer' => 'http://www.example.com/my/path?destination=home&other',
          ],
          'fragment' => 'footer',
        ],
      ],
      'Malformed URL should not trigger PHP warnings' => [
        "#/://#",
        [
          'path' => NULL,
          'query' => [],
          'fragment' => "/://#",
        ],
      ],
    ];
  }

  /**
   * Tests path encoding.
   *
   * @dataProvider providerTestEncodePath
   * @covers ::encodePath
   *
   * @param string $path
   *   A path to encode.
   * @param string $expected
   *   The expected encoded path.
   */
  public function testEncodePath($path, $expected): void {
    $encoded = UrlHelper::encodePath($path);
    $this->assertEquals($expected, $encoded);
  }

  /**
   * Provides data for self::testEncodePath().
   *
   * @return array
   */
  public static function providerTestEncodePath() {
    return [
      ['unencoded path with spaces', 'unencoded%20path%20with%20spaces'],
      ['slashes/should/be/preserved', 'slashes/should/be/preserved'],
    ];
  }

  /**
   * Tests external versus internal paths.
   *
   * @dataProvider providerTestIsExternal
   * @covers ::isExternal
   *
   * @param string $path
   *   URL or path to test.
   * @param bool $expected
   *   Expected result.
   */
  public function testIsExternal($path, $expected): void {
    $isExternal = UrlHelper::isExternal($path);
    $this->assertEquals($expected, $isExternal);
  }

  /**
   * Provides data for self::testIsExternal().
   *
   * @return array
   */
  public static function providerTestIsExternal() {
    return [
      ['/internal/path', FALSE],
      ['https://example.com/external/path', TRUE],
      ['javascript://fake-external-path', FALSE],
      // External URL without an explicit protocol.
      ['//www.example.com/foo/bar?foo=bar&bar=baz&baz#foo', TRUE],
      // Internal URL starting with a slash.
      ['/www.example.com', FALSE],
      // Simple external URLs.
      ['http://example.com', TRUE],
      ['https://example.com', TRUE],
      ['http://example.com/foo/bar?foo=bar&bar=baz&baz#foo', TRUE],
      ['//example.com', TRUE],
      // Some browsers ignore or strip leading control characters.
      ["\x00//www.example.com", TRUE],
      ["\x08//www.example.com", TRUE],
      ["\x1F//www.example.com", TRUE],
      ["\n//www.example.com", TRUE],
      // JSON supports decoding directly from UTF-8 code points.
      [json_decode('"\u00AD"') . "//www.example.com", TRUE],
      [json_decode('"\u200E"') . "//www.example.com", TRUE],
      [json_decode('"\uE0020"') . "//www.example.com", TRUE],
      [json_decode('"\uE000"') . "//www.example.com", TRUE],
      // Backslashes should be normalized to forward.
      ['\\\\example.com', TRUE],
      // Local URLs.
      ['node', FALSE],
      ['/system/ajax', FALSE],
      ['?q=foo:bar', FALSE],
      ['node/edit:me', FALSE],
      ['/example.com', FALSE],
      ['<front>', FALSE],
    ];
  }

  /**
   * Tests bad protocol filtering and escaping.
   *
   * @dataProvider providerTestFilterBadProtocol
   * @covers ::setAllowedProtocols
   * @covers ::filterBadProtocol
   *
   * @param string $uri
   *   Protocol URI.
   * @param string $expected
   *   Expected escaped value.
   * @param array $protocols
   *   Protocols to allow.
   *
   * @runInSeparateProcess
   */
  public function testFilterBadProtocol($uri, $expected, $protocols): void {
    UrlHelper::setAllowedProtocols($protocols);
    $this->assertEquals($expected, UrlHelper::filterBadProtocol($uri));
    // Multiple calls to UrlHelper::filterBadProtocol() do not cause double
    // escaping.
    $this->assertEquals($expected, UrlHelper::filterBadProtocol(UrlHelper::filterBadProtocol($uri)));
  }

  /**
   * Provides data for self::testTestFilterBadProtocol().
   *
   * @return array
   */
  public static function providerTestFilterBadProtocol() {
    return [
      ['javascript://example.com?foo&bar', '//example.com?foo&amp;bar', ['http', 'https']],
      // Test custom protocols.
      ['http://example.com?foo&bar', '//example.com?foo&amp;bar', ['https']],
      // Valid protocol.
      ['http://example.com?foo&bar', 'http://example.com?foo&amp;bar', ['https', 'http']],
      // Colon not part of the URL scheme.
      ['/test:8888?foo&bar', '/test:8888?foo&amp;bar', ['http']],
    ];
  }

  /**
   * Tests dangerous URL protocol filtering.
   *
   * @dataProvider providerTestStripDangerousProtocols
   * @covers ::setAllowedProtocols
   * @covers ::stripDangerousProtocols
   *
   * @param string $uri
   *   Protocol URI.
   * @param string $expected
   *   Expected escaped value.
   * @param array $protocols
   *   Protocols to allow.
   *
   * @runInSeparateProcess
   */
  public function testStripDangerousProtocols($uri, $expected, $protocols): void {
    UrlHelper::setAllowedProtocols($protocols);
    $stripped = UrlHelper::stripDangerousProtocols($uri);
    $this->assertEquals($expected, $stripped);
  }

  /**
   * Provides data for self::testStripDangerousProtocols().
   *
   * @return array
   */
  public static function providerTestStripDangerousProtocols() {
    return [
      ['javascript://example.com', '//example.com', ['http', 'https']],
      // Test custom protocols.
      ['http://example.com', '//example.com', ['https']],
      // Valid protocol.
      ['http://example.com', 'http://example.com', ['https', 'http']],
      // Colon not part of the URL scheme.
      ['/test:8888', '/test:8888', ['http']],
    ];
  }

  /**
   * Enhances test URLs with schemes.
   *
   * @param array $urls
   *   The list of URLs.
   *
   * @return array
   *   A list of provider data with schemes.
   */
  protected static function dataEnhanceWithScheme(array $urls): array {
    $url_schemes = ['http', 'https', 'ftp'];
    $data = [];
    foreach ($url_schemes as $scheme) {
      foreach ($urls as $url) {
        $data[] = [$url, $scheme];
      }
    }
    return $data;
  }

  /**
   * Enhances test URLs with prefixes.
   *
   * @param array $urls
   *   The list of URLs.
   *
   * @return array
   *   A list of provider data with prefixes.
   */
  protected static function dataEnhanceWithPrefix(array $urls): array {
    $prefixes = ['', '/'];
    $data = [];
    foreach ($prefixes as $prefix) {
      foreach ($urls as $url) {
        $data[] = [$url, $prefix];
      }
    }
    return $data;
  }

  /**
   * Tests detecting external URLs that point to local resources.
   *
   * @param string $url
   *   The external URL to test.
   * @param string $base_url
   *   The base URL.
   * @param bool $expected
   *   TRUE if an external URL points to this installation as determined by the
   *   base URL.
   *
   * @covers ::externalIsLocal
   * @dataProvider providerTestExternalIsLocal
   */
  public function testExternalIsLocal($url, $base_url, $expected): void {
    $this->assertSame($expected, UrlHelper::externalIsLocal($url, $base_url));
  }

  /**
   * Provider for local external URL detection.
   *
   * @see \Drupal\Tests\Component\Utility\UrlHelperTest::testExternalIsLocal()
   */
  public static function providerTestExternalIsLocal() {
    return [
      // Different mixes of trailing slash.
      ['http://example.com', 'http://example.com', TRUE],
      ['http://example.com/', 'http://example.com', TRUE],
      ['http://example.com', 'http://example.com/', TRUE],
      ['http://example.com/', 'http://example.com/', TRUE],
      // Sub directory of site.
      ['http://example.com/foo', 'http://example.com/', TRUE],
      ['http://example.com/foo/bar', 'http://example.com/foo', TRUE],
      ['http://example.com/foo/bar', 'http://example.com/foo/', TRUE],
      // Different sub-domain.
      ['http://example.com', 'http://www.example.com/', FALSE],
      ['http://example.com/', 'http://www.example.com/', FALSE],
      ['http://example.com/foo', 'http://www.example.com/', FALSE],
      // Different TLD.
      ['http://example.com', 'http://example.ca', FALSE],
      ['http://example.com', 'http://example.ca/', FALSE],
      ['http://example.com/', 'http://example.ca/', FALSE],
      ['http://example.com/foo', 'http://example.ca', FALSE],
      ['http://example.com/foo', 'http://example.ca/', FALSE],
      // Different site path.
      ['http://example.com/foo', 'http://example.com/bar', FALSE],
      ['http://example.com', 'http://example.com/bar', FALSE],
      ['http://example.com/bar', 'http://example.com/bar/', FALSE],
      // Ensure \ is normalized to / since some browsers do that.
      ['http://www.example.ca\@example.com', 'http://example.com', FALSE],
      // Some browsers ignore or strip leading control characters.
      ["\x00//www.example.ca", 'http://example.com', FALSE],
    ];
  }

  /**
   * Tests invalid URL arguments.
   *
   * @param string $url
   *   The URL to test.
   * @param string $base_url
   *   The base URL.
   *
   * @covers ::externalIsLocal
   * @dataProvider providerTestExternalIsLocalInvalid
   */
  public function testExternalIsLocalInvalid($url, $base_url): void {
    $this->expectException(\InvalidArgumentException::class);
    UrlHelper::externalIsLocal($url, $base_url);
  }

  /**
   * Provides invalid argument data for local external URL detection.
   *
   * @see \Drupal\Tests\Component\Utility\UrlHelperTest::testExternalIsLocalInvalid()
   */
  public static function providerTestExternalIsLocalInvalid() {
    return [
      ['http://example.com/foo', ''],
      ['http://example.com/foo', 'bar'],
      ['http://example.com/foo', 'http://'],
      // Invalid destination URLs.
      ['', 'http://example.com/foo'],
      ['bar', 'http://example.com/foo'],
      ['/bar', 'http://example.com/foo'],
      ['bar/', 'http://example.com/foo'],
      ['http://', 'http://example.com/foo'],
    ];
  }

}
