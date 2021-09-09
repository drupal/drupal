<?php

namespace Drupal\Tests\system\Functional\System;

use Drupal\Core\Test\AssertMailTrait;
use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests access to site while in maintenance mode.
 *
 * @group system
 */
class SiteMaintenanceTest extends BrowserTestBase {

  use AssertMailTrait {
    getMails as drupalGetMails;
  }

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['node'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  protected $adminUser;

  protected function setUp(): void {
    parent::setUp();

    // Configure 'node' as front page.
    $this->config('system.site')->set('page.front', '/node')->save();
    $this->config('system.performance')->set('js.preprocess', 1)->save();

    // Create a user allowed to access site in maintenance mode.
    $this->user = $this->drupalCreateUser(['access site in maintenance mode']);
    // Create an administrative user.
    $this->adminUser = $this->drupalCreateUser([
      'administer site configuration',
      'access site in maintenance mode',
    ]);
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Verifies site maintenance mode functionality.
   */
  public function testSiteMaintenance() {

    // Verify that permission message is displayed.
    $permission_handler = $this->container->get('user.permissions');
    $permissions = $permission_handler->getPermissions();
    $permission_label = $permissions['access site in maintenance mode']['title'];
    $permission_message = t('Visitors will only see the maintenance mode message. Only users with the "@permission-label" <a href=":permissions-url">permission</a> will be able to access the site. Authorized users can log in directly via the <a href=":user-login">user login</a> page.', ['@permission-label' => $permission_label, ':permissions-url' => Url::fromRoute('user.admin_permissions')->toString(), ':user-login' => Url::fromRoute('user.login')->toString()]);
    $this->drupalGet(Url::fromRoute('system.site_maintenance_mode'));
    $this->assertSession()->responseContains($permission_message);

    $this->drupalGet(Url::fromRoute('user.page'));
    // JS should be aggregated, so drupal.js is not in the page source.
    $links = $this->xpath('//script[contains(@src, :href)]', [':href' => '/core/misc/drupal.js']);
    $this->assertFalse(isset($links[0]), 'script /core/misc/drupal.js not in page');
    // Turn on maintenance mode.
    $edit = [
      'maintenance_mode' => 1,
    ];
    $this->drupalGet('admin/config/development/maintenance');
    $this->submitForm($edit, 'Save configuration');

    $admin_message = t('Operating in maintenance mode. <a href=":url">Go online.</a>', [':url' => Url::fromRoute('system.site_maintenance_mode')->toString()]);
    $user_message = 'Operating in maintenance mode.';
    $offline_message = $this->config('system.site')->get('name') . ' is currently under maintenance. We should be back shortly. Thank you for your patience.';

    $this->drupalGet(Url::fromRoute('user.page'));
    // JS should not be aggregated, so drupal.js is expected in the page source.
    $links = $this->xpath('//script[contains(@src, :href)]', [':href' => '/core/misc/drupal.js']);
    $this->assertTrue(isset($links[0]), 'script /core/misc/drupal.js in page');
    $this->assertSession()->responseContains($admin_message);

    // Logout and verify that offline message is displayed.
    $this->drupalLogout();
    $this->drupalGet('');
    $this->assertEquals('Site under maintenance', $this->cssSelect('main h1')[0]->getText());
    $this->assertSession()->pageTextContains($offline_message);
    $this->drupalGet('node');
    $this->assertEquals('Site under maintenance', $this->cssSelect('main h1')[0]->getText());
    $this->assertSession()->pageTextContains($offline_message);
    $this->drupalGet('user/register');
    $this->assertEquals('Site under maintenance', $this->cssSelect('main h1')[0]->getText());
    $this->assertSession()->pageTextContains($offline_message);

    // Verify that user is able to log in.
    $this->drupalGet('user');
    $this->assertSession()->pageTextNotContains($offline_message);
    $this->drupalGet('user/login');
    $this->assertSession()->pageTextNotContains($offline_message);

    // Log in user and verify that maintenance mode message is displayed
    // directly after login.
    $edit = [
      'name' => $this->user->getAccountName(),
      'pass' => $this->user->pass_raw,
    ];
    $this->submitForm($edit, 'Log in');
    $this->assertSession()->pageTextContains($user_message);

    // Log in administrative user and configure a custom site offline message.
    $this->drupalLogout();
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('admin/config/development/maintenance');
    $this->assertSession()->responseNotContains($admin_message);

    $offline_message = 'Sorry, not online.';
    $edit = [
      'maintenance_mode_message' => $offline_message,
    ];
    $this->submitForm($edit, 'Save configuration');

    // Logout and verify that custom site offline message is displayed.
    $this->drupalLogout();
    $this->drupalGet('');
    $this->assertEquals('Site under maintenance', $this->cssSelect('main h1')[0]->getText());
    $this->assertSession()->pageTextContains($offline_message);

    // Verify that custom site offline message is not displayed on user/password.
    $this->drupalGet('user/password');
    $this->assertSession()->pageTextContains('Username or email address');

    // Submit password reset form.
    $edit = [
      'name' => $this->user->getAccountName(),
    ];
    $this->drupalGet('user/password');
    $this->submitForm($edit, 'Submit');
    $mails = $this->drupalGetMails();
    $start = strpos($mails[0]['body'], 'user/reset/' . $this->user->id());
    $path = substr($mails[0]['body'], $start, 66 + strlen($this->user->id()));

    // Log in with temporary login link.
    $this->drupalGet($path);
    $this->submitForm([], 'Log in');
    $this->assertSession()->pageTextContains($user_message);

    // Regression test to check if title displays in Bartik on maintenance page.
    \Drupal::service('theme_installer')->install(['bartik']);
    $this->config('system.theme')->set('default', 'bartik')->save();

    // Logout and verify that offline message is displayed in Bartik.
    $this->drupalLogout();
    $this->drupalGet('');
    $this->assertEquals('Site under maintenance', $this->cssSelect('main h1')[0]->getText());
  }

  /**
   * Tests responses to non-HTML requests when in maintenance mode.
   */
  public function testNonHtmlRequest() {
    $this->drupalLogout();
    \Drupal::state()->set('system.maintenance_mode', TRUE);
    $formats = ['json', 'xml', 'non-existing'];
    foreach ($formats as $format) {
      $this->drupalGet('<front>', ['query' => ['_format' => $format]]);
      $this->assertSession()->statusCodeEquals(503);
      $this->assertSession()->pageTextContains('Drupal is currently under maintenance. We should be back shortly. Thank you for your patience.');
      $this->assertSession()->responseHeaderEquals('Content-Type', 'text/plain; charset=UTF-8');
    }
  }

}
