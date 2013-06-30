<?php

/**
 * @file
 * Contains \Drupal\Tests\Component\Utility\UrlTest.
 */

namespace Drupal\Tests\Component\Utility;


use Drupal\Component\Utility\Url;
use Drupal\Component\Utility\String;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the http query methods.
 *
 * @see \Drupal\Component\Utility\Url
 */
class UrlTest extends UnitTestCase {

  public static function getInfo() {
    return array(
      'name' => t('Url Tests'),
      'description' => t('Tests the Url utility class.'),
      'group' => t('Path API'),
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
    );
  }

  /**
   * Tests Url::buildQuery().
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
    $this->assertEquals(Url::buildQuery($query), $expected, $message);
  }

  /**
   * Data provider for testValidAbsolute().
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
    $valid_url = Url::isValid($test_url, TRUE);
    $this->assertTrue($valid_url, String::format('@url is a valid URL.', array('@url' => $test_url)));
  }

  /**
   * Provides data for testInvalidAbsolute().
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
    $valid_url = Url::isValid($test_url, TRUE);
    $this->assertFalse($valid_url, String::format('@url is NOT a valid URL.', array('@url' => $test_url)));
  }

  /**
   * Provides data for testValidRelative().
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
    $valid_url = Url::isValid($test_url);
    $this->assertTrue($valid_url, String::format('@url is a valid URL.', array('@url' => $test_url)));
  }

  /**
   * Provides data for testInvalidRelative().
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
    $valid_url = Url::isValid($test_url);
    $this->assertFalse($valid_url, String::format('@url is NOT a valid URL.', array('@url' => $test_url)));
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
