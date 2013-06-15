<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Common\UrlValidatorTest.
 */

namespace Drupal\Tests\Core\Common;

use Drupal\Component\Utility\String;
use Drupal\Component\Utility\UrlValidator;
use Drupal\Tests\UnitTestCase;

/**
 * Tests URL validation by valid_url().
 */
class UrlValidatorTest extends UnitTestCase {

  public static function getInfo() {
    return array(
      'name' => 'URL validation',
      'description' => 'Tests URL validation by valid_url()',
      'group' => 'Common',
    );
  }

  /**
   * Data provider for absolute URLs.
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
    $valid_url = UrlValidator::isValid($test_url, TRUE);
    $this->assertTrue($valid_url, String::format('@url is a valid URL.', array('@url' => $test_url)));
  }

  /**
   * Provides invalid absolute URLs.
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
    $valid_url = UrlValidator::isValid($test_url, TRUE);
    $this->assertFalse($valid_url, String::format('@url is NOT a valid URL.', array('@url' => $test_url)));
  }

  /**
   * Provides valid relative URLs
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
    $valid_url = Urlvalidator::isValid($test_url);
    $this->assertTrue($valid_url, String::format('@url is a valid URL.', array('@url' => $test_url)));
  }

  /**
   * Provides invalid relative URLs.
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
    $valid_url = UrlValidator::isValid($test_url);
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
