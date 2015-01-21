<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\DrupalKernel\DrupalKernelTrustedHostsTest.
 */

namespace Drupal\Tests\Core\DrupalKernel;

use Drupal\Core\DrupalKernel;
use Drupal\Core\Site\Settings;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * @coversDefaultClass \Drupal\Core\DrupalKernel
 * @group DrupalKernel
 */
class DrupalKernelTrustedHostsTest extends UnitTestCase {

  /**
   * Tests hostname validation with settings.
   *
   * @covers ::setupTrustedHosts()
   *
   * @dataProvider providerTestTrustedHosts
   */
  public function testTrustedHosts($host, $server_name, $message, $expected = FALSE) {
    $request = new Request();

    $settings = new Settings(array(
      'trusted_host_patterns' => array(
        '^example\.com$',
        '^.+\.example\.com$',
        '^example\.org',
        '^.+\.example\.org',
      )
    ));

    if (!empty($host)) {
      $request->headers->set('HOST', $host);
    }

    $request->server->set('SERVER_NAME', $server_name);

    $method = new \ReflectionMethod('Drupal\Core\DrupalKernel', 'setupTrustedHosts');
    $method->setAccessible(TRUE);
    $valid_host = $method->invoke(null, $request, $settings->get('trusted_host_patterns', array()));

    $this->assertSame($expected, $valid_host, $message);
  }

  /**
   * Provides test data for testTrustedHosts().
   */
  public function providerTestTrustedHosts() {
    $data = [];

    // Tests canonical URL.
    $data[] = ['www.example.com', 'www.example.com', 'canonical URL is trusted', TRUE];

    // Tests missing hostname for HTTP/1.0 compatability where the Host
    // header is optional.
    $data[] = [NULL, 'www.example.com', 'empty Host is valid', TRUE];

    // Tests the additional paterns from the settings.
    $data[] = ['example.com', 'www.example.com', 'host from settings is trusted', TRUE];
    $data[] = ['subdomain.example.com', 'www.example.com', 'host from settings is trusted', TRUE];
    $data[] = ['www.example.org', 'www.example.com', 'host from settings is trusted', TRUE];
    $data[] = ['example.org', 'www.example.com', 'host from settings is trusted', TRUE];

    // Tests mismatch.
    $data[] = ['www.blackhat.com', 'www.example.com', 'unspecified host is untrusted', FALSE];

    return $data;
  }

}
