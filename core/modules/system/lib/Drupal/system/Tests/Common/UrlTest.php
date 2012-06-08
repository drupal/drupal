<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Common\UrlTest.
 */

namespace Drupal\system\Tests\Common;

use Drupal\simpletest\WebTestBase;

/**
 * Tests for URL generation functions.
 *
 * url() calls module_implements(), which may issue a db query, which requires
 * inheriting from a web test case rather than a unit test case.
 */
class UrlTest extends WebTestBase {
  public static function getInfo() {
    return array(
      'name' => 'URL generation tests',
      'description' => 'Confirm that url(), drupal_get_query_parameters(), drupal_http_build_query(), and l() work correctly with various input.',
      'group' => 'Common',
    );
  }

  /**
   * Confirm that invalid text given as $path is filtered.
   */
  function testLXSS() {
    $text = $this->randomName();
    $path = "<SCRIPT>alert('XSS')</SCRIPT>";
    $link = l($text, $path);
    $sanitized_path = check_url(url($path));
    $this->assertTrue(strpos($link, $sanitized_path) !== FALSE, t('XSS attack @path was filtered', array('@path' => $path)));
  }

  /*
   * Tests for active class in l() function.
   */
  function testLActiveClass() {
    $link = l($this->randomName(), current_path());
    $this->assertTrue($this->hasClass($link, 'active'), t('Class @class is present on link to the current page', array('@class' => 'active')));
  }

  /**
   * Tests for custom class in l() function.
   */
  function testLCustomClass() {
    $class = $this->randomName();
    $link = l($this->randomName(), current_path(), array('attributes' => array('class' => array($class))));
    $this->assertTrue($this->hasClass($link, $class), t('Custom class @class is present on link when requested', array('@class' => $class)));
    $this->assertTrue($this->hasClass($link, 'active'), t('Class @class is present on link to the current page', array('@class' => 'active')));
  }

  private function hasClass($link, $class) {
    return preg_match('|class="([^\"\s]+\s+)*' . $class . '|', $link);
  }

  /**
   * Test drupal_get_query_parameters().
   */
  function testDrupalGetQueryParameters() {
    $original = array(
      'a' => 1,
      'b' => array(
        'd' => 4,
        'e' => array(
          'f' => 5,
        ),
      ),
      'c' => 3,
    );

    // First-level exclusion.
    $result = $original;
    unset($result['b']);
    $this->assertEqual(drupal_get_query_parameters($original, array('b')), $result, t("'b' was removed."));

    // Second-level exclusion.
    $result = $original;
    unset($result['b']['d']);
    $this->assertEqual(drupal_get_query_parameters($original, array('b[d]')), $result, t("'b[d]' was removed."));

    // Third-level exclusion.
    $result = $original;
    unset($result['b']['e']['f']);
    $this->assertEqual(drupal_get_query_parameters($original, array('b[e][f]')), $result, t("'b[e][f]' was removed."));

    // Multiple exclusions.
    $result = $original;
    unset($result['a'], $result['b']['e'], $result['c']);
    $this->assertEqual(drupal_get_query_parameters($original, array('a', 'b[e]', 'c')), $result, t("'a', 'b[e]', 'c' were removed."));
  }

  /**
   * Test drupal_http_build_query().
   */
  function testDrupalHttpBuildQuery() {
    $this->assertEqual(drupal_http_build_query(array('a' => ' &#//+%20@۞')), 'a=%20%26%23//%2B%2520%40%DB%9E', t('Value was properly encoded.'));
    $this->assertEqual(drupal_http_build_query(array(' &#//+%20@۞' => 'a')), '%20%26%23%2F%2F%2B%2520%40%DB%9E=a', t('Key was properly encoded.'));
    $this->assertEqual(drupal_http_build_query(array('a' => '1', 'b' => '2', 'c' => '3')), 'a=1&b=2&c=3', t('Multiple values were properly concatenated.'));
    $this->assertEqual(drupal_http_build_query(array('a' => array('b' => '2', 'c' => '3'), 'd' => 'foo')), 'a[b]=2&a[c]=3&d=foo', t('Nested array was properly encoded.'));
  }

  /**
   * Test drupal_parse_url().
   */
  function testDrupalParseUrl() {
    // Relative, absolute, and external URLs, without/with explicit script path,
    // without/with Drupal path.
    foreach (array('', '/', 'http://drupal.org/') as $absolute) {
      foreach (array('', 'index.php/') as $script) {
        foreach (array('', 'foo/bar') as $path) {
          $url = $absolute . $script . $path . '?foo=bar&bar=baz&baz#foo';
          $expected = array(
            'path' => $absolute . $script . $path,
            'query' => array('foo' => 'bar', 'bar' => 'baz', 'baz' => ''),
            'fragment' => 'foo',
          );
          $this->assertEqual(drupal_parse_url($url), $expected, t('URL parsed correctly.'));
        }
      }
    }

    // Relative URL that is known to confuse parse_url().
    $url = 'foo/bar:1';
    $result = array(
      'path' => 'foo/bar:1',
      'query' => array(),
      'fragment' => '',
    );
    $this->assertEqual(drupal_parse_url($url), $result, t('Relative URL parsed correctly.'));

    // Test that drupal can recognize an absolute URL. Used to prevent attack vectors.
    $url = 'http://drupal.org/foo/bar?foo=bar&bar=baz&baz#foo';
    $this->assertTrue(url_is_external($url), t('Correctly identified an external URL.'));

    // Test that drupal_parse_url() does not allow spoofing a URL to force a malicious redirect.
    $parts = drupal_parse_url('forged:http://cwe.mitre.org/data/definitions/601.html');
    $this->assertFalse(valid_url($parts['path'], TRUE), t('drupal_parse_url() correctly parsed a forged URL.'));
  }

  /**
   * Test url() with/without query, with/without fragment, absolute on/off and
   * assert all that works when clean URLs are on and off.
   */
  function testUrl() {
    global $base_url, $script_path;

    $script_path_original = $script_path;
    foreach (array('', 'index.php/') as $script_path) {
      foreach (array(FALSE, TRUE) as $absolute) {
        // Get the expected start of the path string.
        $base = ($absolute ? $base_url . '/' : base_path()) . $script_path;
        $absolute_string = $absolute ? 'absolute' : NULL;

        $url = $base . 'node/123';
        $result = url('node/123', array('absolute' => $absolute));
        $this->assertEqual($url, $result, "$url == $result");

        $url = $base . 'node/123#foo';
        $result = url('node/123', array('fragment' => 'foo', 'absolute' => $absolute));
        $this->assertEqual($url, $result, "$url == $result");

        $url = $base . 'node/123?foo';
        $result = url('node/123', array('query' => array('foo' => NULL), 'absolute' => $absolute));
        $this->assertEqual($url, $result, "$url == $result");

        $url = $base . 'node/123?foo=bar&bar=baz';
        $result = url('node/123', array('query' => array('foo' => 'bar', 'bar' => 'baz'), 'absolute' => $absolute));
        $this->assertEqual($url, $result, "$url == $result");

        $url = $base . 'node/123?foo#bar';
        $result = url('node/123', array('query' => array('foo' => NULL), 'fragment' => 'bar', 'absolute' => $absolute));
        $this->assertEqual($url, $result, "$url == $result");

        $url = $base;
        $result = url('<front>', array('absolute' => $absolute));
        $this->assertEqual($url, $result, "$url == $result");
      }
    }
    $script_path = $script_path_original;
  }

  /**
   * Test external URL handling.
   */
  function testExternalUrls() {
    $test_url = 'http://drupal.org/';

    // Verify external URL can contain a fragment.
    $url = $test_url . '#drupal';
    $result = url($url);
    $this->assertEqual($url, $result, t('External URL with fragment works without a fragment in $options.'));

    // Verify fragment can be overidden in an external URL.
    $url = $test_url . '#drupal';
    $fragment = $this->randomName(10);
    $result = url($url, array('fragment' => $fragment));
    $this->assertEqual($test_url . '#' . $fragment, $result, t('External URL fragment is overidden with a custom fragment in $options.'));

    // Verify external URL can contain a query string.
    $url = $test_url . '?drupal=awesome';
    $result = url($url);
    $this->assertEqual($url, $result, t('External URL with query string works without a query string in $options.'));

    // Verify external URL can be extended with a query string.
    $url = $test_url;
    $query = array($this->randomName(5) => $this->randomName(5));
    $result = url($url, array('query' => $query));
    $this->assertEqual($url . '?' . http_build_query($query, '', '&'), $result, t('External URL can be extended with a query string in $options.'));

    // Verify query string can be extended in an external URL.
    $url = $test_url . '?drupal=awesome';
    $query = array($this->randomName(5) => $this->randomName(5));
    $result = url($url, array('query' => $query));
    $this->assertEqual($url . '&' . http_build_query($query, '', '&'), $result, t('External URL query string can be extended with a custom query string in $options.'));
  }
}
