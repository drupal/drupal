<?php

/**
 * @file
 * Contains \Drupal\Tests\Component\Utility\UrlHelperTest.
 */

namespace Drupal\Tests\Component\Utility;


use Drupal\Component\Utility\UrlHelper;
use Drupal\Component\Utility\String;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the http query methods.
 *
 * @see \Drupal\Component\Utility\Url
 */
class UrlHelperTest extends UnitTestCase {

  public static function getInfo() {
    return array(
      'name' => 'UrlHelper Tests',
      'description' => 'Tests the UrlHelper utility class.',
      'group' => 'Path API',
    );
  }

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
   * Tests UrlHelper::buildQuery().
   *
   * @param array $query
   *   The array of query parameters.
   * @param string $expected
   *   The expected query string.
   * @param string $message
   *   The assertion message.
   *
   * @dataProvider providerTestBuildQuery
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
   * @param string $url
   *   The url to test.
   * @param string $scheme
   *   The scheme to test.
   *
   * @dataProvider providerTestValidAbsoluteData
   */
  public function testValidAbsolute($url, $scheme) {
    $test_url = $scheme . '://' . $url;
    $valid_url = UrlHelper::isValid($test_url, TRUE);
    $this->assertTrue($valid_url, String::format('@url is a valid URL.', array('@url' => $test_url)));
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
   * @param string $url
   *   The url to test.
   * @param string $scheme
   *   The scheme to test.
   *
   * @dataProvider providerTestInvalidAbsolute
   */
  public function testInvalidAbsolute($url, $scheme) {
    $test_url = $scheme . '://' . $url;
    $valid_url = UrlHelper::isValid($test_url, TRUE);
    $this->assertFalse($valid_url, String::format('@url is NOT a valid URL.', array('@url' => $test_url)));
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
   * @param string $url
   *   The url to test.
   * @param string $prefix
   *   The prefix to test.
   *
   * @dataProvider providerTestValidRelativeData
   */
  public function testValidRelative($url, $prefix) {
    $test_url = $prefix . $url;
    $valid_url = UrlHelper::isValid($test_url);
    $this->assertTrue($valid_url, String::format('@url is a valid URL.', array('@url' => $test_url)));
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
   * @param string $url
   *   The url to test.
   * @param string $prefix
   *   The prefix to test.
   *
   * @dataProvider providerTestInvalidRelativeData
   */
  public function testInvalidRelative($url, $prefix) {
    $test_url = $prefix . $url;
    $valid_url = UrlHelper::isValid($test_url);
    $this->assertFalse($valid_url, String::format('@url is NOT a valid URL.', array('@url' => $test_url)));
  }

  /**
   * Tests query filtering.
   *
   * @param array $query
   *   The array of query parameters.
   * @param array $exclude
   *   A list of $query array keys to remove. Use "parent[child]" to exclude
   *   nested items.
   * @param array $expected
   *   An array containing query parameters.
   *
   * @dataProvider providerTestFilterQueryParameters
   *
   * @see \Drupal\Component\Utility\Url::filterQueryParameters().
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
   * @param string $url
   *   URL to test.
   * @param array $expected
   *   Associative array with expected parameters.
   *
   * @dataProvider providerTestParse
   *
   * @see \Drupal\Component\Utility\Url::parse()
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
   * @param string $path
   *   A path to encode.
   * @param string $expected
   *   The expected encoded path.
   *
   * @see \Drupal\Component\Utility\Url::encodePath().
   *
   * @dataProvider providerTestEncodePath
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
   * @param string $path
   *   URL or path to test.
   * @param bool $expected
   *   Expected result.
   *
   * @see \Drupal\Component\Utility\Url::isExternal()
   *
   * @dataProvider providerTestIsExternal
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
    );
  }

  /**
   * Tests bad protocol filtering and escaping.
   *
   * @param string $uri
   *    Protocol URI.
   * @param string $expected
   *    Expected escaped value.
   * @param array $protocols
   *    Protocols to allow.
   *
   * @dataProvider providerTestFilterBadProtocol
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
   * @param string $uri
   *    Protocol URI.
   * @param string $expected
   *    Expected escaped value.
   * @param array $protocols
   *    Protocols to allow.
   *
   * @see \Drupal\Component\Utility\Url::stripDangerousProtocols()
   *
   * @dataProvider providerTestStripDangerousProtocols
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

}
