<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Session\SessionConfigurationTest.
 */

namespace Drupal\Tests\Core\Session;

use Drupal\Tests\UnitTestCase;
use Drupal\Core\Session\SessionConfiguration;
use Symfony\Component\HttpFoundation\Request;

/**
 * @coversDefaultClass \Drupal\Core\Session\SessionConfiguration
 * @group Session
 */
class SessionConfigurationTest extends UnitTestCase {

  /**
   * Constructs a partially mocked SUT.
   *
   * @returns \Drupal\Core\Session\SessionConfiguration|\PHPUnit_Framework_MockObject_MockObject
   */
  protected function createSessionConfiguration($options = []) {
    return $this->getMock('Drupal\Core\Session\SessionConfiguration', ['drupalValidTestUa'], [$options]);
  }

  /**
   * Tests whether the session.cookie_domain ini settings is computed correctly.
   *
   * @covers ::getOptions()
   *
   * @dataProvider providerTestGeneratedCookieDomain
   */
  public function testGeneratedCookieDomain($uri, $expected_domain) {
    $config = $this->createSessionConfiguration();

    $request = Request::create($uri);
    $options = $config->getOptions($request);

    $this->assertEquals($expected_domain, $options['cookie_domain']);
  }

  /**
   * Data provider for the cookie domain test.
   *
   * @returns array
   *   Test data
   */
  public function providerTestGeneratedCookieDomain() {
    return [
      ['http://example.com/path/index.php', '.example.com'],
      ['http://www.example.com/path/index.php', '.example.com'],
      ['http://subdomain.example.com/path/index.php', '.subdomain.example.com'],
      ['http://example.com:8080/path/index.php', '.example.com'],
      ['https://example.com/path/index.php', '.example.com'],
      ['http://localhost/path/index.php', ''],
      ['http://127.0.0.1/path/index.php', ''],
      ['http://127.0.0.1:8888/path/index.php', ''],
      ['http://1.1.1.1/path/index.php', ''],
      ['http://[::1]/path/index.php', ''],
      ['http://[::1]:8888/path/index.php', ''],
    ];
  }

  /**
   * Tests the constructor injected session.cookie_domain ini setting.
   *
   * @covers ::__construct()
   * @covers ::getOptions()
   *
   * @dataProvider providerTestEnforcedCookieDomain
   */
  public function testEnforcedCookieDomain($uri, $expected_domain) {
    $config = $this->createSessionConfiguration(['cookie_domain' => '.example.com']);

    $request = Request::create($uri);
    $options = $config->getOptions($request);

    $this->assertEquals($expected_domain, $options['cookie_domain']);
  }

  /**
   * Data provider for the cookie domain test.
   *
   * @returns array
   *   Test data
   */
  public function providerTestEnforcedCookieDomain() {
    return [
      ['http://example.com/path/index.php', '.example.com'],
      ['http://www.example.com/path/index.php', '.example.com'],
      ['http://subdomain.example.com/path/index.php', '.example.com'],
      ['http://example.com:8080/path/index.php', '.example.com'],
      ['https://example.com/path/index.php', '.example.com'],
      ['http://localhost/path/index.php', '.example.com'],
      ['http://127.0.0.1/path/index.php', '.example.com'],
      ['http://127.0.0.1:8888/path/index.php', '.example.com'],
      ['http://1.1.1.1/path/index.php', '.example.com'],
      ['http://[::1]/path/index.php', '.example.com'],
      ['http://[::1]:8888/path/index.php', '.example.com'],
    ];
  }

  /**
   * Tests whether the session.cookie_secure ini settings is computed correctly.
   *
   * @covers ::getOptions()
   *
   * @dataProvider providerTestCookieSecure
   */
  public function testCookieSecure($uri, $expected_secure) {
    $config = $this->createSessionConfiguration();

    $request = Request::create($uri);
    $options = $config->getOptions($request);

    $this->assertEquals($expected_secure, $options['cookie_secure']);
  }

  /**
   * Tests that session.cookie_secure ini settings cannot be overridden.
   *
   * @covers ::__construct()
   * @covers ::getOptions()
   *
   * @dataProvider providerTestCookieSecure
   */
  public function testCookieSecureNotOverridable($uri, $expected_secure) {
    $config = $this->createSessionConfiguration(['cookie_secure' => FALSE]);

    $request = Request::create($uri);
    $options = $config->getOptions($request);

    $this->assertEquals($expected_secure, $options['cookie_secure']);
  }

  /**
   * Data provider for the cookie secure test.
   *
   * @returns array
   *   Test data
   */
  public function providerTestCookieSecure() {
    return [
      ['http://example.com/path/index.php', FALSE],
      ['https://www.example.com/path/index.php', TRUE],
      ['http://127.0.0.1/path/index.php', FALSE],
      ['https://127.0.0.1:8888/path/index.php', TRUE],
      ['http://[::1]/path/index.php', FALSE],
      ['https://[::1]:8888/path/index.php', TRUE],
    ];
  }

  /**
   * Tests whether the session.name ini settings is computed correctly.
   *
   * @covers ::getOptions()
   *
   * @dataProvider providerTestGeneratedSessionName
   */
  public function testGeneratedSessionName($uri, $expected_name) {
    $config = $this->createSessionConfiguration();

    $request = Request::create($uri);
    $options = $config->getOptions($request);

    $this->assertEquals($expected_name, $options['name']);
  }

  /**
   * Data provider for the cookie name test.
   *
   * @returns array
   *   Test data
   */
  public function providerTestGeneratedSessionName() {
    $data = [
      ['http://example.com/path/index.php', 'SESS', 'example.com'],
      ['http://www.example.com/path/index.php', 'SESS', 'www.example.com'],
      ['http://subdomain.example.com/path/index.php', 'SESS', 'subdomain.example.com'],
      ['http://example.com:8080/path/index.php', 'SESS', 'example.com'],
      ['https://example.com/path/index.php', 'SSESS', 'example.com'],
      ['http://example.com/path/core/install.php', 'SESS', 'example.com'],
      ['http://localhost/path/index.php', 'SESS', 'localhost'],
      ['http://127.0.0.1/path/index.php', 'SESS', '127.0.0.1'],
      ['http://127.0.0.1:8888/path/index.php', 'SESS', '127.0.0.1'],
      ['https://127.0.0.1/path/index.php', 'SSESS', '127.0.0.1'],
      ['https://127.0.0.1:8443/path/index.php', 'SSESS', '127.0.0.1'],
      ['http://1.1.1.1/path/index.php', 'SESS', '1.1.1.1'],
      ['https://1.1.1.1/path/index.php', 'SSESS', '1.1.1.1'],
      ['http://[::1]/path/index.php', 'SESS', '[::1]'],
      ['http://[::1]:8888/path/index.php', 'SESS', '[::1]'],
      ['https://[::1]/path/index.php', 'SSESS', '[::1]'],
      ['https://[::1]:8443/path/index.php', 'SSESS', '[::1]'],
    ];

    return array_map(function ($record) {
      return [$record[0], $record[1] . substr(hash('sha256', $record[2]), 0, 32)];
    }, $data);
  }

  /**
   * Tests whether the session.name ini settings is computed correctly.
   *
   * @covers ::getOptions()
   *
   * @dataProvider providerTestEnforcedSessionName
   */
  public function testEnforcedSessionNameViaCookieDomain($uri, $expected_name) {
    $config = $this->createSessionConfiguration(['cookie_domain' => '.example.com']);

    $request = Request::create($uri);
    $options = $config->getOptions($request);

    $this->assertEquals($expected_name, $options['name']);
  }

  /**
   * Data provider for the cookie name test.
   *
   * @returns array
   *   Test data
   */
  public function providerTestEnforcedSessionName() {
    $data = [
      ['http://example.com/path/index.php', 'SESS', '.example.com'],
      ['http://www.example.com/path/index.php', 'SESS', '.example.com'],
      ['http://subdomain.example.com/path/index.php', 'SESS', '.example.com'],
      ['http://example.com:8080/path/index.php', 'SESS', '.example.com'],
      ['https://example.com/path/index.php', 'SSESS', '.example.com'],
      ['http://example.com/path/core/install.php', 'SESS', '.example.com'],
      ['http://localhost/path/index.php', 'SESS', '.example.com'],
      ['http://127.0.0.1/path/index.php', 'SESS', '.example.com'],
      ['http://127.0.0.1:8888/path/index.php', 'SESS', '.example.com'],
      ['https://127.0.0.1/path/index.php', 'SSESS', '.example.com'],
      ['https://127.0.0.1:8443/path/index.php', 'SSESS', '.example.com'],
      ['http://1.1.1.1/path/index.php', 'SESS', '.example.com'],
      ['https://1.1.1.1/path/index.php', 'SSESS', '.example.com'],
      ['http://[::1]/path/index.php', 'SESS', '.example.com'],
      ['http://[::1]:8888/path/index.php', 'SESS', '.example.com'],
      ['https://[::1]/path/index.php', 'SSESS', '.example.com'],
      ['https://[::1]:8443/path/index.php', 'SSESS', '.example.com'],
    ];

    return array_map(function ($record) {
      return [$record[0], $record[1] . substr(hash('sha256', $record[2]), 0, 32)];
    }, $data);
  }

}
