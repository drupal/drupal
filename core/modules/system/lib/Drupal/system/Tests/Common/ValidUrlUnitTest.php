<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Common\ValidUrlUnitTest.
 */

namespace Drupal\system\Tests\Common;

use Drupal\simpletest\UnitTestBase;

/**
 * Tests URL validation by valid_url().
 */
class ValidUrlUnitTest extends UnitTestBase {
  public static function getInfo() {
    return array(
      'name' => 'URL validation',
      'description' => 'Tests URL validation by valid_url()',
      'group' => 'Common',
    );
  }

  /**
   * Test valid absolute urls.
   */
  function testValidAbsolute() {
    $url_schemes = array('http', 'https', 'ftp');
    $valid_absolute_urls = array(
      'example.com',
      'www.example.com',
      'ex-ample.com',
      '3xampl3.com',
      'example.com/paren(the)sis',
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

    foreach ($url_schemes as $scheme) {
      foreach ($valid_absolute_urls as $url) {
        $test_url = $scheme . '://' . $url;
        $valid_url = valid_url($test_url, TRUE);
        $this->assertTrue($valid_url, t('@url is a valid url.', array('@url' => $test_url)));
      }
    }
  }

  /**
   * Test invalid absolute urls.
   */
  function testInvalidAbsolute() {
    $url_schemes = array('http', 'https', 'ftp');
    $invalid_ablosule_urls = array(
      '',
      'ex!ample.com',
      'ex%ample.com',
    );

    foreach ($url_schemes as $scheme) {
      foreach ($invalid_ablosule_urls as $url) {
        $test_url = $scheme . '://' . $url;
        $valid_url = valid_url($test_url, TRUE);
        $this->assertFalse($valid_url, t('@url is NOT a valid url.', array('@url' => $test_url)));
      }
    }
  }

  /**
   * Test valid relative urls.
   */
  function testValidRelative() {
    $valid_relative_urls = array(
      'paren(the)sis',
      'index.html#pagetop',
      'index.php/node',
      'index.php/node?param=false',
      'login.php?do=login&style=%23#pagetop',
    );

    foreach (array('', '/') as $front) {
      foreach ($valid_relative_urls as $url) {
        $test_url = $front . $url;
        $valid_url = valid_url($test_url);
        $this->assertTrue($valid_url, t('@url is a valid url.', array('@url' => $test_url)));
      }
    }
  }

  /**
   * Test invalid relative urls.
   */
  function testInvalidRelative() {
    $invalid_relative_urls = array(
      'ex^mple',
      'example<>',
      'ex%ample',
    );

    foreach (array('', '/') as $front) {
      foreach ($invalid_relative_urls as $url) {
        $test_url = $front . $url;
        $valid_url = valid_url($test_url);
        $this->assertFALSE($valid_url, t('@url is NOT a valid url.', array('@url' => $test_url)));
      }
    }
  }
}
