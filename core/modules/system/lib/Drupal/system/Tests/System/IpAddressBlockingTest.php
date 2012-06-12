<?php

/**
 * @file
 * Definition of Drupal\system\Tests\System\IpAddressBlockingTest.
 */

namespace Drupal\system\Tests\System;

use Drupal\simpletest\WebTestBase;

class IpAddressBlockingTest extends WebTestBase {
  protected $blocking_user;

  /**
   * Implement getInfo().
   */
  public static function getInfo() {
    return array(
      'name' => 'IP address blocking',
      'description' => 'Test IP address blocking.',
      'group' => 'System'
    );
  }

  /**
   * Implement setUp().
   */
  function setUp() {
    parent::setUp();

    // Create user.
    $this->blocking_user = $this->drupalCreateUser(array('block IP addresses'));
    $this->drupalLogin($this->blocking_user);
  }

  /**
   * Test a variety of user input to confirm correct validation and saving of data.
   */
  function testIPAddressValidation() {
    $this->drupalGet('admin/config/people/ip-blocking');

    // Block a valid IP address.
    $edit = array();
    $edit['ip'] = '192.168.1.1';
    $this->drupalPost('admin/config/people/ip-blocking', $edit, t('Add'));
    $ip = db_query("SELECT iid from {blocked_ips} WHERE ip = :ip", array(':ip' => $edit['ip']))->fetchField();
    $this->assertTrue($ip, t('IP address found in database.'));
    $this->assertRaw(t('The IP address %ip has been blocked.', array('%ip' => $edit['ip'])), t('IP address was blocked.'));

    // Try to block an IP address that's already blocked.
    $edit = array();
    $edit['ip'] = '192.168.1.1';
    $this->drupalPost('admin/config/people/ip-blocking', $edit, t('Add'));
    $this->assertText(t('This IP address is already blocked.'));

    // Try to block a reserved IP address.
    $edit = array();
    $edit['ip'] = '255.255.255.255';
    $this->drupalPost('admin/config/people/ip-blocking', $edit, t('Add'));
    $this->assertText(t('Enter a valid IP address.'));

    // Try to block a reserved IP address.
    $edit = array();
    $edit['ip'] = 'test.example.com';
    $this->drupalPost('admin/config/people/ip-blocking', $edit, t('Add'));
    $this->assertText(t('Enter a valid IP address.'));

    // Submit an empty form.
    $edit = array();
    $edit['ip'] = '';
    $this->drupalPost('admin/config/people/ip-blocking', $edit, t('Add'));
    $this->assertText(t('Enter a valid IP address.'));

    // Pass an IP address as a URL parameter and submit it.
    $submit_ip = '1.2.3.4';
    $this->drupalPost('admin/config/people/ip-blocking/' . $submit_ip, NULL, t('Add'));
    $ip = db_query("SELECT iid from {blocked_ips} WHERE ip = :ip", array(':ip' => $submit_ip))->fetchField();
    $this->assertTrue($ip, t('IP address found in database'));
    $this->assertRaw(t('The IP address %ip has been blocked.', array('%ip' => $submit_ip)), t('IP address was blocked.'));

    // Submit your own IP address. This fails, although it works when testing manually.
     // TODO: on some systems this test fails due to a bug or inconsistency in cURL.
     // $edit = array();
     // $edit['ip'] = ip_address();
     // $this->drupalPost('admin/config/people/ip-blocking', $edit, t('Save'));
     // $this->assertText(t('You may not block your own IP address.'));
  }
}
