<?php

/**
 * @file
 * Definition of Drupal\statistics\Tests\StatisticsBlockVisitorsTest.
 */

namespace Drupal\statistics\Tests;

/**
 * Tests that the visitor blocking functionality works.
 */
class StatisticsBlockVisitorsTest extends StatisticsTestBase {
  public static function getInfo() {
    return array(
      'name' => 'Top visitor blocking',
      'description' => 'Tests blocking of IP addresses via the top visitors report.',
      'group' => 'Statistics'
    );
  }

  /**
   * Blocks an IP address via the top visitors report and then unblocks it.
   */
  function testIPAddressBlocking() {
    // IP address for testing.
    $test_ip_address = '192.168.1.1';

    // Verify the IP address from accesslog appears on the top visitors page
    // and that a 'block IP address' link is displayed.
    $this->drupalLogin($this->blocking_user);
    $this->drupalGet('admin/reports/visitors');
    $this->assertText($test_ip_address, t('IP address found.'));
    $this->assertText(t('block IP address'), t('Block IP link displayed'));

    // Block the IP address.
    $this->clickLink('block IP address');
    $this->assertText(t('IP address blocking'), t('IP blocking page displayed.'));
    $edit = array();
    $edit['ip'] = $test_ip_address;
    $this->drupalPost('admin/config/people/ip-blocking', $edit, t('Add'));
    $ip = db_query("SELECT iid from {blocked_ips} WHERE ip = :ip", array(':ip' => $edit['ip']))->fetchField();
    $this->assertNotEqual($ip, FALSE, t('IP address found in database'));
    $this->assertRaw(t('The IP address %ip has been blocked.', array('%ip' => $edit['ip'])), t('IP address was blocked.'));

    // Verify that the block/unblock link on the top visitors page has been
    // altered.
    $this->drupalGet('admin/reports/visitors');
    $this->assertText(t('unblock IP address'), t('Unblock IP address link displayed'));

    // Unblock the IP address.
    $this->clickLink('unblock IP address');
    $this->assertRaw(t('Are you sure you want to delete %ip?', array('%ip' => $test_ip_address)), t('IP address deletion confirmation found.'));
    $edit = array();
    $this->drupalPost('admin/config/people/ip-blocking/delete/1', NULL, t('Delete'));
    $this->assertRaw(t('The IP address %ip was deleted.', array('%ip' => $test_ip_address)), t('IP address deleted.'));
  }
}
