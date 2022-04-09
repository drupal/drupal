<?php

namespace Drupal\Tests\system\Functional\System;

use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests output on the status overview page.
 *
 * @group system
 */
class StatusTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['update_test_postupdate', 'update'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Unset the sync directory in settings.php to trigger the error.
    $settings['settings']['config_sync_directory'] = (object) [
      'value' => '',
      'required' => TRUE,
    ];
    $this->writeSettings($settings);

    $admin_user = $this->drupalCreateUser([
      'administer site configuration',
      'access site reports',
    ]);
    $this->drupalLogin($admin_user);
  }

  /**
   * Tests that the status page returns.
   *
   * @group legacy
   */
  public function testStatusPage() {
    // Verify if the 'Status report' is the first item link.
    $this->drupalGet('admin/reports');
    $this->assertEquals('Status report', $this->cssSelect('.list-group :first-child')[0]->getText());

    // Go to Administration.
    $this->drupalGet('admin/reports/status');
    $this->assertSession()->statusCodeEquals(200);

    // Verify that the PHP version is shown on the page.
    $this->assertSession()->pageTextContains(phpversion());

    if (function_exists('phpinfo')) {
      $this->assertSession()->linkByHrefExists(Url::fromRoute('system.php')->toString());
    }
    else {
      $this->assertSession()->linkByHrefNotExists(Url::fromRoute('system.php')->toString());
    }

    // If a module is fully installed no pending updates exists.
    $this->assertSession()->pageTextNotContains('Out of date');

    // The setting config_sync_directory is not properly formed.
    $this->assertSession()->pageTextContains("Your {$this->siteDirectory}/settings.php file must define the \$settings['config_sync_directory'] setting");

    /** @var \Drupal\Core\Update\UpdateHookRegistry $update_registry */
    $update_registry = \Drupal::service('update.update_hook_registry');

    // Set the schema version of update_test_postupdate to a lower version, so
    // update_test_postupdate_update_8001() needs to be executed.
    $update_registry->setInstalledVersion('update_test_postupdate', 8000);
    $this->drupalGet('admin/reports/status');
    $this->assertSession()->pageTextContains('Out of date');

    // Now cleanup the executed post update functions.
    $update_registry->setInstalledVersion('update_test_postupdate', 8001);
    /** @var \Drupal\Core\Update\UpdateRegistry $post_update_registry */
    $post_update_registry = \Drupal::service('update.post_update_registry');
    $post_update_registry->filterOutInvokedUpdatesByExtension('update_test_postupdate');
    $this->drupalGet('admin/reports/status');
    $this->assertSession()->pageTextContains('Out of date');

    $this->drupalGet('admin/reports/status/php');
    $this->assertSession()->statusCodeEquals(200);

    // Check if cron error is displayed in errors section
    $cron_last_run = \Drupal::state()->get('system.cron_last');
    \Drupal::state()->set('system.cron_last', 0);
    $this->drupalGet('admin/reports/status');
    $this->assertSession()->elementExists('xpath', '//details[contains(@class, "system-status-report__entry")]//div[contains(text(), "Cron has not run recently")]');
    \Drupal::state()->set('system.cron_last', $cron_last_run);

    // Check if JSON database support is enabled.
    $this->assertSession()->pageTextContains('Database support for JSON');
    $elements = $this->xpath('//details[@class="system-status-report__entry"]//div[contains(text(), :text)]', [
      ':text' => 'Is required in Drupal 10.0.',
    ]);
    $this->assertCount(1, $elements);
    $this->assertStringStartsWith('Available', $elements[0]->getParent()->getText());

    // Test the page with deprecated extensions.
    $module_installer = \Drupal::service('module_installer');
    $session = $this->assertSession();

    // Install a deprecated module.
    $module_installer->install(['deprecated_module']);
    $this->drupalGet('admin/reports/status');

    // Confirm warning messages are displayed for the deprecated module.
    $session->pageTextContains('Deprecated modules enabled');
    $session->pageTextContains('Deprecated modules found: Deprecated module.');

    // Check that the deprecated module link was rendered correctly.
    $this->assertSession()->elementExists('xpath', "//a[contains(@href, 'http://example.com/deprecated')]");

    // Uninstall a deprecated module and confirm the warning is not displayed.
    $module_installer->uninstall(['deprecated_module']);
    $this->drupalGet('admin/reports/status');
    $session->pageTextNotContains('Deprecated modules enabled');
    $session->pageTextNotContains('Deprecated modules found: Deprecated module.');
    $this->assertSession()->elementNotExists('xpath', "//a[contains(@href, 'http://example.com/deprecated')]");

    // Make sure there are no warnings about obsolete modules.
    $session->pageTextNotContains('Obsolete extensions enabled');
    $session->pageTextNotContains('Obsolete extensions found: System obsolete status test.');

    // Install an obsolete module. Normally this isn't possible, so write to
    // configuration directly.
    $this->config('core.extension')->set('module.system_status_obsolete_test', 0)->save();
    $this->rebuildAll();
    $this->drupalGet('admin/reports/status');
    $session->pageTextContains('Obsolete extensions enabled');
    $session->pageTextContains('Obsolete extensions found: System obsolete status test.');

    // Make sure the warning is gone after uninstalling the module.
    $module_installer->uninstall(['system_status_obsolete_test']);
    $this->drupalGet('admin/reports/status');
    $session->pageTextNotContains('Obsolete extensions enabled');
    $session->pageTextNotContains('Obsolete extensions found: System obsolete status test.');

    // Install deprecated theme and confirm warning message is displayed.
    $theme_installer = \Drupal::service('theme_installer');
    $theme_installer->install(['test_deprecated_theme']);
    $this->drupalGet('admin/reports/status');
    $session->pageTextContains('Deprecated themes enabled');
    $session->pageTextContains('Deprecated themes found: Test deprecated theme.');

    // Check that the deprecated theme link was rendered correctly.
    $this->assertSession()->elementExists('xpath', "//a[contains(@href, 'http://example.com/deprecated_theme')]");

    // Uninstall a deprecated theme and confirm the warning is not displayed.
    $theme_installer->uninstall(['test_deprecated_theme']);
    $this->drupalGet('admin/reports/status');
    $session->pageTextNotContains('Deprecated themes enabled');
    $session->pageTextNotContains('Deprecated themes found: Test deprecated theme.');
    $this->assertSession()->elementNotExists('xpath', "//a[contains(@href, 'http://example.com/deprecated_theme')]");

    // Check if pg_trgm extension is enabled on postgres.
    if ($this->getDatabaseConnection()->databaseType() == 'pgsql') {
      $this->assertSession()->pageTextContains('PostgreSQL pg_trgm extension');
      $elements = $this->xpath('//details[@class="system-status-report__entry"]//div[contains(text(), :text)]', [
        ':text' => 'The pg_trgm PostgreSQL extension is present.',
      ]);
      $this->assertCount(1, $elements);
      $this->assertStringStartsWith('Available', $elements[0]->getParent()->getText());
    }
  }

}
