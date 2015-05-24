<?php

/**
 * @file
 * Contains \Drupal\Tests\Component\Utility\UrlHelperTest.
 */

namespace Drupal\Tests\Component\Utility;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Component\Utility\SafeMarkup;
use Drupal\Tests\UnitTestCase;

/**
 * @group Utility
 *
 * @coversDefaultClass \Drupal\Component\Utility\UrlHelper
 */
class UrlHelperTest extends UnitTestCase {

  /**
   * Provides test data for testBuildQuery().
   *
   * @return array
   */
  public function providerTestBuildQuery() {
    return array(
      array(array('a' => ' &#//+%20@۞'), 'a=%20%26%23//%2B%2520%40%DB%9E', 'Value was properly encoded.'),
      array(array(' &#//+%20@۞' => 'a'), '%20%26%23%2F%2F%2B%2520%40%DB%9E=a', 'Key was properly encoded.'),
      array(array('a' => '1', 'b' => '2', 'c' => '3'), 'a=1&b=2&c=3', 'Multiple values were properly concatenated.'),
      array(array('a' => array('b' => '2', 'c' => '3'), 'd' => 'foo'), 'a[b]=2&a[c]=3&d=foo', 'Nested array was properly encoded.'),
      array(array('foo' => NULL), 'foo', 'Simple parameters are properly added.'),
    );
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
  public function testBuildQuery($query, $expected, $message) {
    $this->assertEquals(UrlHelper::buildQuery($query), $expected, $message);
  }

  /**
   * Data provider for testValidAbsolute().
   *
   * @return array
   */
  public function providerTestValidAbsoluteData() {
    $urls = array(
      'example.com',
      'www.example.com',
      'ex-ample.com',
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
      '[FEDC:BA98:7654:3210:FEDC:BA98:7654:3210]:80/index.html',
    );

    return $this->dataEnhanceWithScheme($urls);
  }

  /**
   * Tests valid absolute URLs.
   *
   * @dataProvider providerTestValidAbsoluteData
   * @covers ::isValid
   *
   * @param string $url
   *   The url to test.
   * @param string $scheme
   *   The scheme to test.
   */
  public function testValidAbsolute($url, $scheme) {
    $test_url = $scheme . '://' . $url;
    $valid_url = UrlHelper::isValid($test_url, TRUE);
    $this->assertTrue($valid_url, SafeMarkup::format('@url is a valid URL.', array('@url' => $test_url)));
  }

  /**
   * Provides data for testInvalidAbsolute().
   *
   * @return array
   */
  public function providerTestInvalidAbsolute() {
    $data = array(
      '',
      'ex!ample.com',
      'ex%ample.com',
    );
    return $this->dataEnhanceWithScheme($data);
  }

  /**
   * Tests invalid absolute URLs.
   *
   * @dataProvider providerTestInvalidAbsolute
   * @covers ::isValid
   *
   * @param string $url
   *   The url to test.
   * @param string $scheme
   *   The scheme to test.
   */
  public function testInvalidAbsolute($url, $scheme) {
    $test_url = $scheme . '://' . $url;
    $valid_url = UrlHelper::isValid($test_url, TRUE);
    $this->assertFalse($valid_url, SafeMarkup::format('@url is NOT a valid URL.', array('@url' => $test_url)));
  }

  /**
   * Provides data for testValidRelative().
   *
   * @return array
   */
  public function providerTestValidRelativeData() {
    $data = array(
      'paren(the)sis',
      'index.html#pagetop',
      'index.php/node',
      'index.php/node?param=false',
      'login.php?do=login&style=%23#pagetop',
    );

    return $this->dataEnhanceWithPrefix($data);
  }

  /**
   * Tests valid relative URLs.
   *
   * @dataProvider providerTestValidRelativeData
   * @covers ::isValid
   *
   * @param string $url
   *   The url to test.
   * @param string $prefix
   *   The prefix to test.
   */
  public function testValidRelative($url, $prefix) {
    $test_url = $prefix . $url;
    $valid_url = UrlHelper::isValid($test_url);
    $this->assertTrue($valid_url, SafeMarkup::format('@url is a valid URL.', array('@url' => $test_url)));
  }

  /**
   * Provides data for testInvalidRelative().
   *
   * @return array
   */
  public function providerTestInvalidRelativeData() {
    $data = array(
      'ex^mple',
      'example<>',
      'ex%ample',
    );
    return $this->dataEnhanceWithPrefix($data);
  }

  /**
   * Tests invalid relative URLs.
   *
   * @dataProvider providerTestInvalidRelativeData
   * @covers ::isValid
   *
   * @param string $url
   *   The url to test.
   * @param string $prefix
   *   The prefix to test.
   */
  public function testInvalidRelative($url, $prefix) {
    $test_url = $prefix . $url;
    $valid_url = UrlHelper::isValid($test_url);
    $this->assertFalse($valid_url, SafeMarkup::format('@url is NOT a valid URL.', array('@url' => $test_url)));
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
  public function testFilterQueryParameters($query, $exclude, $expected) {
    $filtered = UrlHelper::filterQueryParameters($query, $exclude);
    $this->assertEquals($expected, $filtered, 'The query was not properly filtered.');
  }

  /**
   * Provides data to self::testFilterQueryParameters().
   *
   * @return array
   */
  public static function providerTestFilterQueryParameters() {
    return array(
      // Test without an exclude filter.
      array(
        'query' => array('a' => array('b' => 'c')),
        'exclude' => array(),
        'expected' => array('a' => array('b' => 'c')),
      ),
      // Exclude the 'b' element.
      array(
        'query' => array('a' => array('b' => 'c', 'd' => 'e')),
        'exclude' => array('a[b]'),
        'expected' => array('a' => array('d' => 'e')),
      ),
    );
  }

  /**
   * Tests url parsing.
   *
   * @dataProvider providerTestParse
   * @covers ::parse
   *
   * @param string $url
   *   URL to test.
   * @param array $expected
   *   Associative array with expected parameters.
   */
  public function testParse($url, $expected) {
    $parsed = UrlHelper::parse($url);
    $this->assertEquals($expected, $parsed, 'The url was not properly parsed.');
  }

  /**
   * Provides data for self::testParse().
   *
   * @return array
   */
  public static function providerTestParse() {
    return array(
      array(
        'http://www.example.com/my/path',
        array(
          'path' => 'http://www.example.com/my/path',
          'query' => array(),
          'fragment' => '',
        ),
      ),
      array(
        'http://www.example.com/my/path?destination=home#footer',
        array(
          'path' => 'http://www.example.com/my/path',
          'query' => array(
            'destination' => 'home',
          ),
          'fragment' => 'footer',
        ),
      ),
      array(
        'http://',
        array(
          'path' => '',
          'query' => array(),
          'fragment' => '',
        ),
      ),
      array(
        'https://',
        array(
          'path' => '',
          'query' => array(),
          'fragment' => '',
        ),
      ),
      array(
        '/my/path?destination=home#footer',
        array(
          'path' => '/my/path',
          'query' => array(
            'destination' => 'home',
          ),
          'fragment' => 'footer',
        ),
      ),
    );
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
  public function testEncodePath($path, $expected) {
    $encoded = UrlHelper::encodePath($path);
    $this->assertEquals($expected, $encoded);
  }

  /**
   * Provides data for self::testEncodePath().
   *
   * @return array
   */
  public static function providerTestEncodePath() {
    return array(
      array('unencoded path with spaces', 'unencoded%20path%20with%20spaces'),
      array('slashes/should/be/preserved', 'slashes/should/be/preserved'),
    );
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
  public function testIsExternal($path, $expected) {
    $isExternal = UrlHelper::isExternal($path);
    $this->assertEquals($expected, $isExternal);
  }

  /**
   * Provides data for self::testIsExternal().
   *
   * @return array
   */
  public static function providerTestIsExternal() {
    return array(
      array('/internal/path', FALSE),
      array('https://example.com/external/path', TRUE),
      array('javascript://fake-external-path', FALSE),
      // External URL without an explicit protocol.
      array('//www.drupal.org/foo/bar?foo=bar&bar=baz&baz#foo', TRUE),
      // Internal URL starting with a slash.
      array('/www.drupal.org', FALSE),
    );
  }

  /**
   * Tests bad protocol filtering and escaping.
   *
   * @dataProvider providerTestFilterBadProtocol
   * @covers ::setAllowedProtocols
   * @covers ::filterBadProtocol
   *
   * @param string $uri
   *    Protocol URI.
   * @param string $expected
   *    Expected escaped value.
   * @param array $protocols
   *    Protocols to allow.
   */
  public function testFilterBadProtocol($uri, $expected, $protocols) {
    UrlHelper::setAllowedProtocols($protocols);
    $filtered = UrlHelper::filterBadProtocol($uri);
    $this->assertEquals($expected, $filtered);
  }

  /**
   * Provides data for self::testTestFilterBadProtocol().
   *
   * @return array
   */
  public static function providerTestFilterBadProtocol() {
    return array(
      array('javascript://example.com?foo&bar', '//example.com?foo&amp;bar', array('http', 'https')),
      // Test custom protocols.
      array('http://example.com?foo&bar', '//example.com?foo&amp;bar', array('https')),
      // Valid protocol.
      array('http://example.com?foo&bar', 'http://example.com?foo&amp;bar', array('https', 'http')),
      // Colon not part of the URL scheme.
      array('/test:8888?foo&bar', '/test:8888?foo&amp;bar', array('http')),
    );
  }

  /**
   * Tests dangerous url protocol filtering.
   *
   * @dataProvider providerTestStripDangerousProtocols
   * @covers ::setAllowedProtocols
   * @covers ::stripDangerousProtocols
   *
   * @param string $uri
   *    Protocol URI.
   * @param string $expected
   *    Expected escaped value.
   * @param array $protocols
   *    Protocols to allow.
   */
  public function testStripDangerousProtocols($uri, $expected, $protocols) {
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
    return array(
      array('javascript://example.com', '//example.com', array('http', 'https')),
      // Test custom protocols.
      array('http://example.com', '//example.com', array('https')),
      // Valid protocol.
      array('http://example.com', 'http://example.com', array('https', 'http')),
      // Colon not part of the URL scheme.
      array('/test:8888', '/test:8888', array('http')),
    );
  }

  /**
   * Enhances test urls with schemes
   *
   * @param array $urls
   *   The list of urls.
   *
   * @return array
   *   A list of provider data with schemes.
   */
  protected function dataEnhanceWithScheme(array $urls) {
    $url_schemes = array('http', 'https', 'ftp');
    $data = array();
    foreach ($url_schemes as $scheme) {
      foreach ($urls as $url) {
        $data[] = array($url, $scheme);
      }
    }
    return $data;
  }

  /**
   * Enhances test urls with prefixes.
   *
   * @param array $urls
   *   The list of urls.
   *
   * @return array
   *   A list of provider data with prefixes.
   */
  protected function dataEnhanceWithPrefix(array $urls) {
    $prefixes = array('', '/');
    $data = array();
    foreach ($prefixes as $prefix) {
      foreach ($urls as $url) {
        $data[] = array($url, $prefix);
      }
    }
    return $data;
  }

  /**
   * Test detecting external urls that point to local resources.
   *
   * @param string $url
   *   The external url to test.
   * @param string $base_url
   *   The base url.
   * @param bool $expected
   *   TRUE if an external URL points to this installation as determined by the
   *   base url.
   *
   * @covers ::externalIsLocal
   * @dataProvider providerTestExternalIsLocal
   */
  public function testExternalIsLocal($url, $base_url, $expected) {
    $this->assertSame($expected, UrlHelper::externalIsLocal($url, $base_url));
  }

  /**
   * Provider for local external url detection.
   *
   * @see \Drupal\Tests\Component\Utility\UrlHelperTest::testExternalIsLocal()
   */
  public function providerTestExternalIsLocal() {
    return array(
      // Different mixes of trailing slash.
      array('http://example.com', 'http://example.com', TRUE),
      array('http://example.com/', 'http://example.com', TRUE),
      array('http://example.com', 'http://example.com/', TRUE),
      array('http://example.com/', 'http://example.com/', TRUE),
      // Sub directory of site.
      array('http://example.com/foo', 'http://example.com/', TRUE),
      array('http://example.com/foo/bar', 'http://example.com/foo', TRUE),
      array('http://example.com/foo/bar', 'http://example.com/foo/', TRUE),
      // Different sub-domain.
      array('http://example.com', 'http://www.example.com/', FALSE),
      array('http://example.com/', 'http://www.example.com/', FALSE),
      array('http://example.com/foo', 'http://www.example.com/', FALSE),
      // Different TLD.
      array('http://example.com', 'http://example.ca', FALSE),
      array('http://example.com', 'http://example.ca/', FALSE),
      array('http://example.com/', 'http://example.ca/', FALSE),
      array('http://example.com/foo', 'http://example.ca', FALSE),
      array('http://example.com/foo', 'http://example.ca/', FALSE),
      // Different site path.
      array('http://example.com/foo', 'http://example.com/bar', FALSE),
      array('http://example.com', 'http://example.com/bar', FALSE),
      array('http://example.com/bar', 'http://example.com/bar/', FALSE),
    );
  }

  /**
   * Test invalid url arguments.
   *
   * @param string $url
   *   The url to test.
   * @param string $base_url
   *   The base url.
   *
   * @covers ::externalIsLocal
   * @dataProvider providerTestExternalIsLocalInvalid
   * @expectedException \InvalidArgumentException
   */
  public function testExternalIsLocalInvalid($url, $base_url) {
    UrlHelper::externalIsLocal($url, $base_url);
  }

  /**
   * Provides invalid argument data for local external url detection.
   *
   * @see \Drupal\Tests\Component\Utility\UrlHelperTest::testExternalIsLocalInvalid()
   */
  public function providerTestExternalIsLocalInvalid() {
    return array(
      array('http://example.com/foo', ''),
      array('http://example.com/foo', 'bar'),
      array('http://example.com/foo', 'http://'),
      // Invalid destination urls.
      array('', 'http://example.com/foo'),
      array('bar', 'http://example.com/foo'),
      array('/bar', 'http://example.com/foo'),
      array('bar/', 'http://example.com/foo'),
      array('http://', 'http://example.com/foo'),
    );
  }
}
