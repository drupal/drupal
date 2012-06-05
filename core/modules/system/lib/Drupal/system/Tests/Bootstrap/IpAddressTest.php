<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Bootstrap\IpAddressTest.
 */

namespace Drupal\system\Tests\Bootstrap;

use Drupal\simpletest\WebTestBase;

/**
 * Tests getting IP addresses and hostname validation.
 */
class IpAddressTest extends WebTestBase {

  public static function getInfo() {
    return array(
      'name' => 'IP address and HTTP_HOST test',
      'description' => 'Get the IP address from the current visitor from the server variables, check hostname validation.',
      'group' => 'Bootstrap'
    );
  }

  function setUp() {
    $this->oldserver = $_SERVER;

    $this->remote_ip = '127.0.0.1';
    $this->proxy_ip = '127.0.0.2';
    $this->proxy2_ip = '127.0.0.3';
    $this->forwarded_ip = '127.0.0.4';
    $this->cluster_ip = '127.0.0.5';
    $this->untrusted_ip = '0.0.0.0';

    drupal_static_reset('ip_address');

    $_SERVER['REMOTE_ADDR'] = $this->remote_ip;
    unset($_SERVER['HTTP_X_FORWARDED_FOR']);
    unset($_SERVER['HTTP_X_CLUSTER_CLIENT_IP']);

    parent::setUp();
  }

  function tearDown() {
    $_SERVER = $this->oldserver;
    drupal_static_reset('ip_address');
    parent::tearDown();
  }

  /**
   * test IP Address and hostname
   */
  function testIPAddressHost() {
    // Test the normal IP address.
    $this->assertTrue(
      ip_address() == $this->remote_ip,
      t('Got remote IP address.')
    );

    // Proxy forwarding on but no proxy addresses defined.
    variable_set('reverse_proxy', 1);
    $this->assertTrue(
      ip_address() == $this->remote_ip,
      t('Proxy forwarding without trusted proxies got remote IP address.')
    );

    // Proxy forwarding on and proxy address not trusted.
    variable_set('reverse_proxy_addresses', array($this->proxy_ip, $this->proxy2_ip));
    drupal_static_reset('ip_address');
    $_SERVER['REMOTE_ADDR'] = $this->untrusted_ip;
    $this->assertTrue(
      ip_address() == $this->untrusted_ip,
      t('Proxy forwarding with untrusted proxy got remote IP address.')
    );

    // Proxy forwarding on and proxy address trusted.
    $_SERVER['REMOTE_ADDR'] = $this->proxy_ip;
    $_SERVER['HTTP_X_FORWARDED_FOR'] = $this->forwarded_ip;
    drupal_static_reset('ip_address');
    $this->assertTrue(
      ip_address() == $this->forwarded_ip,
      t('Proxy forwarding with trusted proxy got forwarded IP address.')
    );

    // Multi-tier architecture with comma separated values in header.
    $_SERVER['REMOTE_ADDR'] = $this->proxy_ip;
    $_SERVER['HTTP_X_FORWARDED_FOR'] = implode(', ', array($this->untrusted_ip, $this->forwarded_ip, $this->proxy2_ip));
    drupal_static_reset('ip_address');
    $this->assertTrue(
      ip_address() == $this->forwarded_ip,
      t('Proxy forwarding with trusted 2-tier proxy got forwarded IP address.')
    );

    // Custom client-IP header.
    variable_set('reverse_proxy_header', 'HTTP_X_CLUSTER_CLIENT_IP');
    $_SERVER['HTTP_X_CLUSTER_CLIENT_IP'] = $this->cluster_ip;
    drupal_static_reset('ip_address');
    $this->assertTrue(
      ip_address() == $this->cluster_ip,
      t('Cluster environment got cluster client IP.')
    );

    // Verifies that drupal_valid_http_host() prevents invalid characters.
    $this->assertFalse(drupal_valid_http_host('security/.drupal.org:80'), t('HTTP_HOST with / is invalid'));
    $this->assertFalse(drupal_valid_http_host('security\\.drupal.org:80'), t('HTTP_HOST with \\ is invalid'));
    $this->assertFalse(drupal_valid_http_host('security<.drupal.org:80'), t('HTTP_HOST with &lt; is invalid'));
    $this->assertFalse(drupal_valid_http_host('security..drupal.org:80'), t('HTTP_HOST with .. is invalid'));
    // IPv6 loopback address
    $this->assertTrue(drupal_valid_http_host('[::1]:80'), t('HTTP_HOST containing IPv6 loopback is valid'));
  }
}
