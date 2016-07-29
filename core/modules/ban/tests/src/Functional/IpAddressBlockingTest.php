<?php

namespace Drupal\Tests\ban\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Core\Database\Database;
use Drupal\ban\BanIpManager;

/**
 * Tests IP address banning.
 *
 * @group ban
 */
class IpAddressBlockingTest extends BrowserTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = array('ban');

  /**
   * Tests various user input to confirm correct validation and saving of data.
   */
  function testIPAddressValidation() {
    // Create user.
    $admin_user = $this->drupalCreateUser(array('ban IP addresses'));
    $this->drupalLogin($admin_user);
    $this->drupalGet('admin/config/people/ban');

    // Ban a valid IP address.
    $edit = array();
    $edit['ip'] = '1.2.3.3';
    $this->drupalPostForm('admin/config/people/ban', $edit, t('Add'));
    $ip = db_query("SELECT iid from {ban_ip} WHERE ip = :ip", array(':ip' => $edit['ip']))->fetchField();
    $this->assertTrue($ip, 'IP address found in database.');
    $this->assertRaw(t('The IP address %ip has been banned.', array('%ip' => $edit['ip'])), 'IP address was banned.');

    // Try to block an IP address that's already blocked.
    $edit = array();
    $edit['ip'] = '1.2.3.3';
    $this->drupalPostForm('admin/config/people/ban', $edit, t('Add'));
    $this->assertText(t('This IP address is already banned.'));

    // Try to block a reserved IP address.
    $edit = array();
    $edit['ip'] = '255.255.255.255';
    $this->drupalPostForm('admin/config/people/ban', $edit, t('Add'));
    $this->assertText(t('Enter a valid IP address.'));

    // Try to block a reserved IP address.
    $edit = array();
    $edit['ip'] = 'test.example.com';
    $this->drupalPostForm('admin/config/people/ban', $edit, t('Add'));
    $this->assertText(t('Enter a valid IP address.'));

    // Submit an empty form.
    $edit = array();
    $edit['ip'] = '';
    $this->drupalPostForm('admin/config/people/ban', $edit, t('Add'));
    $this->assertText(t('Enter a valid IP address.'));

    // Pass an IP address as a URL parameter and submit it.
    $submit_ip = '1.2.3.4';
    $this->drupalPostForm('admin/config/people/ban/' . $submit_ip, array(), t('Add'));
    $ip = db_query("SELECT iid from {ban_ip} WHERE ip = :ip", array(':ip' => $submit_ip))->fetchField();
    $this->assertTrue($ip, 'IP address found in database');
    $this->assertRaw(t('The IP address %ip has been banned.', array('%ip' => $submit_ip)), 'IP address was banned.');

    // Submit your own IP address. This fails, although it works when testing
    // manually.
    // TODO: On some systems this test fails due to a bug/inconsistency in cURL.
    // $edit = array();
    // $edit['ip'] = \Drupal::request()->getClientIP();
    // $this->drupalPostForm('admin/config/people/ban', $edit, t('Save'));
    // $this->assertText(t('You may not ban your own IP address.'));

    // Test duplicate ip address are not present in the 'blocked_ips' table.
    // when they are entered programmatically.
    $connection = Database::getConnection();
    $banIp = new BanIpManager($connection);
    $ip = '1.0.0.0';
    $banIp->banIp($ip);
    $banIp->banIp($ip);
    $banIp->banIp($ip);
    $query = db_select('ban_ip', 'bip');
    $query->fields('bip', array('iid'));
    $query->condition('bip.ip', $ip);
    $ip_count = $query->execute()->fetchAll();
    $this->assertEqual(1, count($ip_count));
    $ip = '';
    $banIp->banIp($ip);
    $banIp->banIp($ip);
    $query = db_select('ban_ip', 'bip');
    $query->fields('bip', array('iid'));
    $query->condition('bip.ip', $ip);
    $ip_count = $query->execute()->fetchAll();
    $this->assertEqual(1, count($ip_count));
  }

}
