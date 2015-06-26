<?php

/**
 * @file
 * Contains \Drupal\simpletest\Tests\BrowserTest.
 */

namespace Drupal\simpletest\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests the internal browser of the testing framework.
 *
 * @group simpletest
 */
class BrowserTest extends WebTestBase {

  /**
   * A flag indicating whether a cookie has been set in a test.
   *
   * @var bool
   */
  protected static $cookieSet = FALSE;

  /**
   * Test \Drupal\simpletest\WebTestBase::getAbsoluteUrl().
   */
  function testGetAbsoluteUrl() {
    $url = 'user/login';

    $this->drupalGet($url);
    $absolute = \Drupal::url('user.login', array(), array('absolute' => TRUE));
    $this->assertEqual($absolute, $this->url, 'Passed and requested URL are equal.');
    $this->assertEqual($this->url, $this->getAbsoluteUrl($this->url), 'Requested and returned absolute URL are equal.');

    $this->drupalPostForm(NULL, array(), t('Log in'));
    $this->assertEqual($absolute, $this->url, 'Passed and requested URL are equal.');
    $this->assertEqual($this->url, $this->getAbsoluteUrl($this->url), 'Requested and returned absolute URL are equal.');

    $this->clickLink('Create new account');
    $absolute = \Drupal::url('user.register', array(), array('absolute' => TRUE));
    $this->assertEqual($absolute, $this->url, 'Passed and requested URL are equal.');
    $this->assertEqual($this->url, $this->getAbsoluteUrl($this->url), 'Requested and returned absolute URL are equal.');
  }

  /**
   * Tests XPath escaping.
   */
  function testXPathEscaping() {
    $testpage = <<< EOF
<html>
<body>
<a href="link1">A "weird" link, just to bother the dumb "XPath 1.0"</a>
<a href="link2">A second "even more weird" link, in memory of George O'Malley</a>
<a href="link3">A \$third$ link, so weird it's worth $1 million</a>
<a href="link4">A fourth link, containing alternative \\1 regex backreferences \\2</a>
</body>
</html>
EOF;
    $this->setRawContent($testpage);

    // Matches the first link.
    $urls = $this->xpath('//a[text()=:text]', array(':text' => 'A "weird" link, just to bother the dumb "XPath 1.0"'));
    $this->assertEqual($urls[0]['href'], 'link1', 'Match with quotes.');

    $urls = $this->xpath('//a[text()=:text]', array(':text' => 'A second "even more weird" link, in memory of George O\'Malley'));
    $this->assertEqual($urls[0]['href'], 'link2', 'Match with mixed single and double quotes.');

    $urls = $this->xpath('//a[text()=:text]', array(':text' => 'A $third$ link, so weird it\'s worth $1 million'));
    $this->assertEqual($urls[0]['href'], 'link3', 'Match with a regular expression back reference symbol (dollar sign).');

    $urls = $this->xpath('//a[text()=:text]', array(':text' => 'A fourth link, containing alternative \\1 regex backreferences \\2'));
    $this->assertEqual($urls[0]['href'], 'link4', 'Match with another regular expression back reference symbol (double backslash).');
  }

  /**
   * Tests that cookies set during a request are available for testing.
   */
  public function testCookies() {
    // Check that the $this->cookies property is populated when a user logs in.
    $user = $this->drupalCreateUser();
    $edit = ['name' => $user->getUsername(), 'pass' => $user->pass_raw];
    $this->drupalPostForm('<front>', $edit, t('Log in'));
    $this->assertEqual(count($this->cookies), 1, 'A cookie is set when the user logs in.');

    // Check that the name and value of the cookie match the request data.
    $cookie_header = $this->drupalGetHeader('set-cookie', TRUE);

    // The name and value are located at the start of the string, separated by
    // an equals sign and ending in a semicolon.
    preg_match('/^([^=]+)=([^;]+)/', $cookie_header, $matches);
    $name = $matches[1];
    $value = $matches[2];

    $this->assertTrue(array_key_exists($name, $this->cookies), 'The cookie name is correct.');
    $this->assertEqual($value, $this->cookies[$name]['value'], 'The cookie value is correct.');

    // Set a flag indicating that a cookie has been set in this test.
    // @see testCookieDoesNotBleed()
    static::$cookieSet = TRUE;
  }

  /**
   * Tests that the cookies from a previous test do not bleed into a new test.
   *
   * @see static::testCookies()
   */
  public function testCookieDoesNotBleed() {
    // In order for this test to be effective it should always run after the
    // testCookies() test.
    $this->assertTrue(static::$cookieSet, 'Tests have been executed in the expected order.');
    $this->assertEqual(count($this->cookies), 0, 'No cookies are present at the start of a new test.');
  }

}
