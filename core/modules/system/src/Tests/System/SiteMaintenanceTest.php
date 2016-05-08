<?php

namespace Drupal\system\Tests\System;

use Drupal\Core\Url;
use Drupal\simpletest\WebTestBase;

/**
 * Tests access to site while in maintenance mode.
 *
 * @group system
 */
class SiteMaintenanceTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('node');

  protected $adminUser;

  protected function setUp() {
    parent::setUp();

    // Configure 'node' as front page.
    $this->config('system.site')->set('page.front', '/node')->save();
    $this->config('system.performance')->set('js.preprocess', 1)->save();

    // Create a user allowed to access site in maintenance mode.
    $this->user = $this->drupalCreateUser(array('access site in maintenance mode'));
    // Create an administrative user.
    $this->adminUser = $this->drupalCreateUser(array('administer site configuration', 'access site in maintenance mode'));
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Verify site maintenance mode functionality.
   */
  protected function testSiteMaintenance() {
    $this->drupalGet(Url::fromRoute('user.page'));
    // JS should be aggregated, so drupal.js is not in the page source.
    $links = $this->xpath('//script[contains(@src, :href)]', array(':href' => '/core/misc/drupal.js'));
    $this->assertFalse(isset($links[0]), 'script /core/misc/drupal.js not in page');
    // Turn on maintenance mode.
    $edit = array(
      'maintenance_mode' => 1,
    );
    $this->drupalPostForm('admin/config/development/maintenance', $edit, t('Save configuration'));

    $admin_message = t('Operating in maintenance mode. <a href=":url">Go online.</a>', array(':url' => \Drupal::url('system.site_maintenance_mode')));
    $user_message = t('Operating in maintenance mode.');
    $offline_message = t('@site is currently under maintenance. We should be back shortly. Thank you for your patience.', array('@site' => $this->config('system.site')->get('name')));

    $this->drupalGet(Url::fromRoute('user.page'));
    // JS should not be aggregated, so drupal.js is expected in the page source.
    $links = $this->xpath('//script[contains(@src, :href)]', array(':href' => '/core/misc/drupal.js'));
    $this->assertTrue(isset($links[0]), 'script /core/misc/drupal.js in page');
    $this->assertRaw($admin_message, 'Found the site maintenance mode message.');

    // Logout and verify that offline message is displayed.
    $this->drupalLogout();
    $this->drupalGet('');
    $this->assertEqual('Site under maintenance', $this->cssSelect('main h1')[0]);
    $this->assertText($offline_message);
    $this->drupalGet('node');
    $this->assertEqual('Site under maintenance', $this->cssSelect('main h1')[0]);
    $this->assertText($offline_message);
    $this->drupalGet('user/register');
    $this->assertEqual('Site under maintenance', $this->cssSelect('main h1')[0]);
    $this->assertText($offline_message);

    // Verify that user is able to log in.
    $this->drupalGet('user');
    $this->assertNoText($offline_message);
    $this->drupalGet('user/login');
    $this->assertNoText($offline_message);

    // Log in user and verify that maintenance mode message is displayed
    // directly after login.
    $edit = array(
      'name' => $this->user->getUsername(),
      'pass' => $this->user->pass_raw,
    );
    $this->drupalPostForm(NULL, $edit, t('Log in'));
    $this->assertText($user_message);

    // Log in administrative user and configure a custom site offline message.
    $this->drupalLogout();
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('admin/config/development/maintenance');
    $this->assertNoRaw($admin_message, 'Site maintenance mode message not displayed.');

    $offline_message = 'Sorry, not online.';
    $edit = array(
      'maintenance_mode_message' => $offline_message,
    );
    $this->drupalPostForm(NULL, $edit, t('Save configuration'));

    // Logout and verify that custom site offline message is displayed.
    $this->drupalLogout();
    $this->drupalGet('');
    $this->assertEqual('Site under maintenance', $this->cssSelect('main h1')[0]);
    $this->assertRaw($offline_message, 'Found the site offline message.');

    // Verify that custom site offline message is not displayed on user/password.
    $this->drupalGet('user/password');
    $this->assertText(t('Username or email address'), 'Anonymous users can access user/password');

    // Submit password reset form.
    $edit = array(
      'name' => $this->user->getUsername(),
    );
    $this->drupalPostForm('user/password', $edit, t('Submit'));
    $mails = $this->drupalGetMails();
    $start = strpos($mails[0]['body'], 'user/reset/' . $this->user->id());
    $path = substr($mails[0]['body'], $start, 66 + strlen($this->user->id()));

    // Log in with temporary login link.
    $this->drupalPostForm($path, array(), t('Log in'));
    $this->assertText($user_message);

    // Regression test to check if title displays in Bartik on maintenance page.
    \Drupal::service('theme_handler')->install(array('bartik'));
    \Drupal::service('theme_handler')->setDefault('bartik');

    // Logout and verify that offline message is displayed in Bartik.
    $this->drupalLogout();
    $this->drupalGet('');
    $this->assertEqual('Site under maintenance', $this->cssSelect('main h1')[0]);
  }

}
