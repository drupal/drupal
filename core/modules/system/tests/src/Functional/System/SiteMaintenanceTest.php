<?php

declare(strict_types=1);

namespace Drupal\Tests\system\Functional\System;

use Drupal\Core\Test\AssertMailTrait;
use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;
use Drupal\user\Entity\User;

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

  /**
   * User allowed to access site in maintenance mode.
   *
   * @var \Drupal\user\Entity\User
   */
  protected User $user;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Configure 'node' as front page.
    $this->config('system.site')->set('page.front', '/node')->save();
    $this->config('system.performance')
      ->set('js.preprocess', 1)
      ->set('css.preprocess', 1)
      ->save();

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
  public function testSiteMaintenance(): void {

    // Verify that permission message is displayed.
    $this->drupalGet(Url::fromRoute('system.site_maintenance_mode'));
    $this->assertSession()->pageTextContains('Visitors will only see the maintenance mode message. Only users with the "Use the site in maintenance mode" permission will be able to access the site. Authorized users can log in directly via the user login page.');
    $this->assertSession()->linkExists('permission');
    $this->assertSession()->linkByHrefExists(Url::fromRoute('user.admin_permissions')->toString());
    $this->assertSession()->linkExists('user login');
    $this->assertSession()->linkByHrefExists(Url::fromRoute('user.login')->toString());

    $this->drupalGet(Url::fromRoute('user.page'));
    // Aggregation should be enabled, individual assets should not be rendered.
    $this->assertSession()->elementNotExists('xpath', '//script[contains(@src, "/core/misc/drupal.js")]');
    $this->assertSession()->elementNotExists('xpath', '//link[contains(@href, "/core/modules/system/css/components/align.module.css")]');
    // Turn on maintenance mode.
    $edit = [
      'maintenance_mode' => 1,
    ];
    $this->drupalGet('admin/config/development/maintenance');
    $this->submitForm($edit, 'Save configuration');

    $admin_message = 'Operating in maintenance mode. Go online.';
    $user_message = 'Operating in maintenance mode.';
    $offline_message = $this->config('system.site')->get('name') . ' is currently under maintenance. We should be back shortly. Thank you for your patience.';

    $this->drupalGet(Url::fromRoute('user.page'));
    // Aggregation should be disabled, individual assets should be rendered.
    $this->assertSession()->elementExists('xpath', '//script[contains(@src, "/core/misc/drupal.js")]');
    $this->assertSession()->elementExists('xpath', '//link[contains(@href, "/core/modules/system/css/components/align.module.css")]');
    $this->assertSession()->pageTextContains($admin_message);
    $this->assertSession()->linkExists('Go online.');
    $this->assertSession()->linkByHrefExists(Url::fromRoute('system.site_maintenance_mode')->toString());

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
    $this->assertSession()->pageTextNotContains($admin_message);

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

    // Check if title displays in Olivero on maintenance page.
    \Drupal::service('theme_installer')->install(['olivero']);
    $this->config('system.theme')->set('default', 'olivero')->save();

    // Logout and verify that offline message is displayed in Olivero.
    $this->drupalLogout();
    $this->drupalGet('');
    $this->assertEquals('Site under maintenance', $this->cssSelect('main h1')[0]->getText());
  }

  /**
   * Tests responses to non-HTML requests when in maintenance mode.
   */
  public function testNonHtmlRequest(): void {
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
