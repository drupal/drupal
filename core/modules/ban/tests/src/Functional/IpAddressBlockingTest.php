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
  protected static $modules = ['ban'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests various user input to confirm correct validation and saving of data.
   */
  public function testIPAddressValidation() {
    // Create user.
    $admin_user = $this->drupalCreateUser(['ban IP addresses']);
    $this->drupalLogin($admin_user);
    $this->drupalGet('admin/config/people/ban');
    $connection = Database::getConnection();

    // Ban a valid IP address.
    $edit = [];
    $edit['ip'] = '1.2.3.3';
    $this->drupalGet('admin/config/people/ban');
    $this->submitForm($edit, 'Add');
    $ip = $connection->select('ban_ip', 'bi')->fields('bi', ['iid'])->condition('ip', $edit['ip'])->execute()->fetchField();
    $this->assertNotEmpty($ip, 'IP address found in database.');
    $this->assertSession()->pageTextContains('The IP address 1.2.3.3 has been banned.');

    // Try to block an IP address that's already blocked.
    $edit = [];
    $edit['ip'] = '1.2.3.3';
    $this->drupalGet('admin/config/people/ban');
    $this->submitForm($edit, 'Add');
    $this->assertSession()->pageTextContains('This IP address is already banned.');

    // Try to block a reserved IP address.
    $edit = [];
    $edit['ip'] = '255.255.255.255';
    $this->drupalGet('admin/config/people/ban');
    $this->submitForm($edit, 'Add');
    $this->assertSession()->pageTextContains('Enter a valid IP address.');

    // Try to block a reserved IP address.
    $edit = [];
    $edit['ip'] = 'test.example.com';
    $this->drupalGet('admin/config/people/ban');
    $this->submitForm($edit, 'Add');
    $this->assertSession()->pageTextContains('Enter a valid IP address.');

    // Submit an empty form.
    $edit = [];
    $edit['ip'] = '';
    $this->drupalGet('admin/config/people/ban');
    $this->submitForm($edit, 'Add');
    $this->assertSession()->pageTextContains('Enter a valid IP address.');

    // Pass an IP address as a URL parameter and submit it.
    $submit_ip = '1.2.3.4';
    $this->drupalGet('admin/config/people/ban/' . $submit_ip);
    $this->submitForm([], 'Add');
    $ip = $connection->select('ban_ip', 'bi')->fields('bi', ['iid'])->condition('ip', $submit_ip)->execute()->fetchField();
    $this->assertNotEmpty($ip, 'IP address found in database');
    $this->assertSession()->pageTextContains("The IP address $submit_ip has been banned.");

    // Submit your own IP address. This fails, although it works when testing
    // manually.
    // TODO: On some systems this test fails due to a bug/inconsistency in cURL.
    // $edit = array();
    // $edit['ip'] = \Drupal::request()->getClientIP();
    // $this->drupalGet('admin/config/people/ban');
    // $this->submitForm($edit, 'Save');
    // $this->assertSession()->pageTextContains('You may not ban your own IP address.');

    // Test duplicate ip address are not present in the 'blocked_ips' table.
    // when they are entered programmatically.
    $banIp = new BanIpManager($connection);
    $ip = '1.0.0.0';
    $banIp->banIp($ip);
    $banIp->banIp($ip);
    $banIp->banIp($ip);
    $query = $connection->select('ban_ip', 'bip');
    $query->fields('bip', ['iid']);
    $query->condition('bip.ip', $ip);
    $ip_count = $query->execute()->fetchAll();
    $this->assertCount(1, $ip_count);
    $ip = '';
    $banIp->banIp($ip);
    $banIp->banIp($ip);
    $query = $connection->select('ban_ip', 'bip');
    $query->fields('bip', ['iid']);
    $query->condition('bip.ip', $ip);
    $ip_count = $query->execute()->fetchAll();
    $this->assertCount(1, $ip_count);
  }

}
