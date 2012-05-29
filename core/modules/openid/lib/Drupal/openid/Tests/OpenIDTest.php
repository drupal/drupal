<?php

/**
 * @file
 * Definition of Drupal\openid\Tests\OpenIDTest.
 */

namespace Drupal\openid\Tests;

use Drupal\simpletest\WebTestBase;
use stdClass;

/**
 * Test internal helper functions.
 */
class OpenIDTest extends WebTestBase {
  public static function getInfo() {
    return array(
      'name' => 'OpenID helper functions',
      'description' => 'Test OpenID helper functions.',
      'group' => 'OpenID'
    );
  }

  function setUp() {
    parent::setUp('openid');
    module_load_include('inc', 'openid');
  }

  /**
   * Test _openid_dh_XXX_to_XXX() functions.
   */
  function testConversion() {
    $this->assertEqual(_openid_dh_long_to_base64('12345678901234567890123456789012345678901234567890'), 'CHJ/Y2mq+DyhUCZ0evjH8ZbOPwrS', t('_openid_dh_long_to_base64() returned expected result.'));
    $this->assertEqual(_openid_dh_base64_to_long('BsH/g8Nrpn2dtBSdu/sr1y8hxwyx'), '09876543210987654321098765432109876543210987654321', t('_openid_dh_base64_to_long() returned expected result.'));

    $this->assertEqual(_openid_dh_long_to_binary('12345678901234567890123456789012345678901234567890'), "\x08r\x7fci\xaa\xf8<\xa1P&tz\xf8\xc7\xf1\x96\xce?\x0a\xd2", t('_openid_dh_long_to_binary() returned expected result.'));
    $this->assertEqual(_openid_dh_binary_to_long("\x06\xc1\xff\x83\xc3k\xa6}\x9d\xb4\x14\x9d\xbb\xfb+\xd7/!\xc7\x0c\xb1"), '09876543210987654321098765432109876543210987654321', t('_openid_dh_binary_to_long() returned expected result.'));
  }

  /**
   * Test _openid_dh_xorsecret().
   */
  function testOpenidDhXorsecret() {
    $this->assertEqual(_openid_dh_xorsecret('123456790123456790123456790', "abc123ABC\x00\xFF"), "\xa4'\x06\xbe\xf1.\x00y\xff\xc2\xc1", t('_openid_dh_xorsecret() returned expected result.'));
  }

  /**
   * Test _openid_get_bytes().
   */
  function testOpenidGetBytes() {
    $this->assertEqual(strlen(_openid_get_bytes(20)), 20, t('_openid_get_bytes() returned expected result.'));
  }

  /**
   * Test _openid_signature().
   */
  function testOpenidSignature() {
    // Test that signature is calculated according to OpenID Authentication 2.0,
    // section 6.1. In the following array, only the two first entries should be
    // included in the calculation, because the substring following the period
    // is mentioned in the third argument for _openid_signature(). The last
    // entry should not be included, because it does not start with "openid.".
    $response = array(
      'openid.foo' => 'abc1',
      'openid.bar' => 'abc2',
      'openid.baz' => 'abc3',
      'foobar.foo' => 'abc4',
    );
    $association = new stdClass();
    $association->mac_key = "1234567890abcdefghij\x00\x01\x02\x03\x04\x05\x06\x07\x08\x09\xF0\xF1\xF2\xF3\xF4\xF5\xF6\xF7\xF8\xF9";
    $this->assertEqual(_openid_signature($association, $response, array('foo', 'bar')), 'QnKZQzSFstT+GNiJDFOptdcZjrc=', t('Expected signature calculated.'));
  }

  /**
   * Test _openid_is_xri().
   */
  function testOpenidXRITest() {
    // Test that the XRI test is according to OpenID Authentication 2.0,
    // section 7.2. If the user-supplied string starts with xri:// it should be
    // stripped and the resulting string should be treated as an XRI when it
    // starts with "=", "@", "+", "$", "!" or "(".
    $this->assertTrue(_openid_is_xri('xri://=foo'), t('_openid_is_xri() returned expected result for an xri identifier with xri scheme.'));
    $this->assertTrue(_openid_is_xri('xri://@foo'), t('_openid_is_xri() returned expected result for an xri identifier with xri scheme.'));
    $this->assertTrue(_openid_is_xri('xri://+foo'), t('_openid_is_xri() returned expected result for an xri identifier with xri scheme.'));
    $this->assertTrue(_openid_is_xri('xri://$foo'), t('_openid_is_xri() returned expected result for an xri identifier with xri scheme.'));
    $this->assertTrue(_openid_is_xri('xri://!foo'), t('_openid_is_xri() returned expected result for an xri identifier with xri scheme..'));
    $this->assertTrue(_openid_is_xri('xri://(foo'), t('_openid_is_xri() returned expected result for an xri identifier with xri scheme..'));

    $this->assertTrue(_openid_is_xri('=foo'), t('_openid_is_xri() returned expected result for an xri identifier.'));
    $this->assertTrue(_openid_is_xri('@foo'), t('_openid_is_xri() returned expected result for an xri identifier.'));
    $this->assertTrue(_openid_is_xri('+foo'), t('_openid_is_xri() returned expected result for an xri identifier.'));
    $this->assertTrue(_openid_is_xri('$foo'), t('_openid_is_xri() returned expected result for an xri identifier.'));
    $this->assertTrue(_openid_is_xri('!foo'), t('_openid_is_xri() returned expected result for an xri identifier.'));
    $this->assertTrue(_openid_is_xri('(foo'), t('_openid_is_xri() returned expected result for an xri identifier.'));

    $this->assertFalse(_openid_is_xri('foo'), t('_openid_is_xri() returned expected result for an http URL.'));
    $this->assertFalse(_openid_is_xri('xri://foo'), t('_openid_is_xri() returned expected result for an http URL.'));
    $this->assertFalse(_openid_is_xri('http://foo/'), t('_openid_is_xri() returned expected result for an http URL.'));
    $this->assertFalse(_openid_is_xri('http://example.com/'), t('_openid_is_xri() returned expected result for an http URL.'));
    $this->assertFalse(_openid_is_xri('user@example.com/'), t('_openid_is_xri() returned expected result for an http URL.'));
    $this->assertFalse(_openid_is_xri('http://user@example.com/'), t('_openid_is_xri() returned expected result for an http URL.'));
  }

  /**
   * Test openid_normalize().
   */
  function testOpenidNormalize() {
    // Test that the normalization is according to OpenID Authentication 2.0,
    // section 7.2 and 11.5.2.

    $this->assertEqual(openid_normalize('$foo'), '$foo', t('openid_normalize() correctly normalized an XRI.'));
    $this->assertEqual(openid_normalize('xri://$foo'), '$foo', t('openid_normalize() correctly normalized an XRI with an xri:// scheme.'));

    $this->assertEqual(openid_normalize('example.com/'), 'http://example.com/', t('openid_normalize() correctly normalized a URL with a missing scheme.'));
    $this->assertEqual(openid_normalize('example.com'), 'http://example.com/', t('openid_normalize() correctly normalized a URL with a missing scheme and empty path.'));
    $this->assertEqual(openid_normalize('http://example.com'), 'http://example.com/', t('openid_normalize() correctly normalized a URL with an empty path.'));

    $this->assertEqual(openid_normalize('http://example.com/path'), 'http://example.com/path', t('openid_normalize() correctly normalized a URL with a path.'));

    $this->assertEqual(openid_normalize('http://example.com/path#fragment'), 'http://example.com/path', t('openid_normalize() correctly normalized a URL with a fragment.'));
  }

  /**
   * Test openid_extract_namespace().
   */
  function testOpenidExtractNamespace() {
    $response = array(
      'openid.sreg.nickname' => 'john',
      'openid.ns.ext1' => OPENID_NS_SREG,
      'openid.ext1.nickname' => 'george',
      'openid.ext1.email' => 'george@example.com',
      'openid.ns.ext2' => 'http://example.com/ns/ext2',
      'openid.ext2.foo' => '123',
      'openid.ext2.bar' => '456',
      'openid.signed' => 'sreg.nickname,ns.ext1,ext1.email,ext2.foo',
    );

    $values = openid_extract_namespace($response, 'http://example.com/ns/dummy', NULL, FALSE);
    $this->assertEqual($values, array(), t('Nothing found for unused namespace.'));

    $values = openid_extract_namespace($response, 'http://example.com/ns/dummy', 'sreg', FALSE);
    $this->assertEqual($values, array('nickname' => 'john'), t('Value found for fallback prefix.'));

    $values = openid_extract_namespace($response, OPENID_NS_SREG, 'sreg', FALSE);
    $this->assertEqual($values, array('nickname' => 'george', 'email' => 'george@example.com'), t('Namespace takes precedence over fallback prefix.'));

    // ext1.email is signed, but ext1.nickname is not.
    $values = openid_extract_namespace($response, OPENID_NS_SREG, 'sreg', TRUE);
    $this->assertEqual($values, array('email' => 'george@example.com'), t('Unsigned namespaced fields ignored.'));

    $values = openid_extract_namespace($response, 'http://example.com/ns/ext2', 'sreg', FALSE);
    $this->assertEqual($values, array('foo' => '123', 'bar' => '456'), t('Unsigned fields found.'));

    // ext2.foo and ext2.bar are ignored, because ns.ext2 is not signed. The
    // fallback prefix is not used, because the namespace is specified.
    $values = openid_extract_namespace($response, 'http://example.com/ns/ext2', 'sreg', TRUE);
    $this->assertEqual($values, array(), t('Unsigned fields ignored.'));
  }
}
