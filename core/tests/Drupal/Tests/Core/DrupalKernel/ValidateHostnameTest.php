<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\DrupalKernel\ValidateHostnameTest.
 */

namespace Drupal\Tests\Core\DrupalKernel;

use Drupal\Core\DrupalKernel;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * @coversDefaultClass \Drupal\Core\DrupalKernel
 * @group DrupalKernel
 */
class ValidateHostnameTest extends UnitTestCase {

  /**
   * @covers ::validateHostname
   * @dataProvider providerTestValidateHostname
   */
  public function testValidateHostname($hostname, $message, $expected = FALSE) {
    $server = ['HTTP_HOST' => $hostname];
    $request = new Request([], [], [], [], [], $server);
    $validated_hostname = DrupalKernel::validateHostname($request);
    $this->assertSame($expected, $validated_hostname, $message);
  }

  /**
   * Provides test data for testValidateHostname().
   */
  public function providerTestValidateHostname() {
    $data = [];

    // Verifies that DrupalKernel::validateHostname() prevents invalid
    // characters per RFC 952/2181.
    $data[] = ['security/.drupal.org:80', 'HTTP_HOST with / is invalid'];
    $data[] = ['security/.drupal.org:80', 'HTTP_HOST with / is invalid'];
    $data[] = ['security\\.drupal.org:80', 'HTTP_HOST with \\ is invalid'];
    $data[] = ['security<.drupal.org:80', 'HTTP_HOST with &lt; is invalid'];
    $data[] = ['security..drupal.org:80', 'HTTP_HOST with .. is invalid'];

    // Verifies hostnames that are too long, or have too many parts are
    // invalid.
    $data[] = [str_repeat('x', 1000) . '.security.drupal.org:80', 'HTTP_HOST with more than 1000 characters is invalid.'];
    $data[] = [str_repeat('x.', 100) . 'security.drupal.org:80', 'HTTP_HOST with more than 100 subdomains is invalid.'];
    $data[] = ['security.drupal.org:80' . str_repeat(':x', 100), 'HTTP_HOST with more than 100 port separators is invalid.'];

    // Verifies that a valid hostname is allowed.
    $data[] = ['security.drupal.org:80', 'Properly formed HTTP_HOST is valid.', TRUE];

    // Verifies that using valid IP address for the hostname is allowed.
    $data[] = ['72.21.91.99:80', 'Properly formed HTTP_HOST with IPv4 address valid.', TRUE];
    $data[] = ['2607:f8b0:4004:803::1002:80', 'Properly formed HTTP_HOST with IPv6 address valid.', TRUE];

    // Verfies that the IPv6 loopback address is valid.
    $data[] = ['[::1]:80', 'HTTP_HOST containing IPv6 loopback is valid.', TRUE];

    return $data;
  }

}
