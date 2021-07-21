<?php

namespace Drupal\Tests\update\Functional;

use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests the Update Manager settings.
 *
 * @group update
 */
class UpdateSettingsTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['update_test', 'update', 'language', 'block'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $admin_user = $this->drupalCreateUser([
      'administer site configuration',
      'administer modules',
      'administer themes',
    ]);
    $this->drupalLogin($admin_user);
  }

  /**
   * Ensures that the notifications enabled by the default.
   */
  public function testNotificationsSetting() {
    $this->drupalGet(Url::fromRoute('update.settings'));
    // Checkbox should be checked so expected message on the page should be:
    $this->assertSession()->pageTextContains('Uncheck to hide system notifications for available updates. Hiding it may have security implications.');

    $this->submitForm(
      ['update_system_notifications' => 0],
      'Save configuration'
    );

    // Checkbox should be unchecked so expected message on the page should be:
    $this->assertSession()->pageTextContains('Check to show system notifications for available updates. Hiding it may have security implications.');
  }

}
