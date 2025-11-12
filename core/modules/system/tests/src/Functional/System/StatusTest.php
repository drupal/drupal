<?php

declare(strict_types=1);

namespace Drupal\Tests\system\Functional\System;

use Drupal\Component\Utility\Bytes;
use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;
use Drupal\Core\StringTranslation\PluralTranslatableMarkup;

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
  public function testStatusPage(): void {
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

    $settings['settings']['sa_core_2023_004_phpinfo_flags'] = (object) [
      'value' => INFO_ALL,
      'required' => TRUE,
    ];
    $this->writeSettings($settings);
    $this->drupalGet('admin/reports/status/php');
    $this->assertSession()->pageTextContains('PHP');
    $this->assertSession()->pageTextContains('$_COOKIE');

    // Check if cron error is displayed in errors section.
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
    $session->pageTextContains('Deprecated modules installed');
    $session->pageTextContains('Deprecated modules found: Deprecated module.');

    // Check that the deprecated module link was rendered correctly.
    $this->assertSession()->elementExists('xpath', "//a[contains(@href, 'http://example.com/deprecated')]");

    // Uninstall a deprecated module and confirm the warning is not displayed.
    $module_installer->uninstall(['deprecated_module']);
    $this->drupalGet('admin/reports/status');
    $session->pageTextNotContains('Deprecated modules installed');
    $session->pageTextNotContains('Deprecated modules found: Deprecated module.');
    $this->assertSession()->elementNotExists('xpath', "//a[contains(@href, 'http://example.com/deprecated')]");

    // Make sure there are no warnings about obsolete modules.
    $session->pageTextNotContains('Obsolete extensions installed');
    $session->pageTextNotContains('Obsolete extensions found: System obsolete status test.');

    // Install an obsolete module. Normally this isn't possible, so write to
    // configuration directly.
    $this->config('core.extension')->set('module.system_status_obsolete_test', 0)->save();
    $this->rebuildAll();
    $this->drupalGet('admin/reports/status');
    $session->pageTextContains('Obsolete extensions installed');
    $session->pageTextContains('Obsolete extensions found: System obsolete status test.');
    $session->pageTextContains('Obsolete extensions are provided only so that they can be uninstalled cleanly. You should immediately uninstall these extensions since they may be removed in a future release.');
    $this->assertSession()->elementExists('xpath', "//a[contains(@href, '/admin/modules/uninstall')]");

    // Make sure the warning is gone after uninstalling the module.
    $module_installer->uninstall(['system_status_obsolete_test']);
    $this->drupalGet('admin/reports/status');
    $session->pageTextNotContains('Obsolete extensions installed');
    $session->pageTextNotContains('Obsolete extensions found: System obsolete status test.');
    $session->pageTextNotContains('Obsolete extensions are provided only so that they can be uninstalled cleanly. You should immediately uninstall these extensions since they may be removed in a future release.');

    // Install deprecated theme and confirm warning message is displayed.
    $theme_installer = \Drupal::service('theme_installer');
    $theme_installer->install(['test_deprecated_theme']);
    $this->drupalGet('admin/reports/status');
    $session->pageTextContains('Deprecated themes installed');
    $session->pageTextContains('Deprecated themes found: Test deprecated theme.');

    // Check that the deprecated theme link was rendered correctly.
    $this->assertSession()->elementExists('xpath', "//a[contains(@href, 'http://example.com/deprecated_theme')]");

    // Uninstall a deprecated theme and confirm the warning is not displayed.
    $theme_installer->uninstall(['test_deprecated_theme']);
    $this->drupalGet('admin/reports/status');
    $session->pageTextNotContains('Deprecated themes installed');
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

    // Test APCu status.
    $this->markTestSkipped('Skipped due to bugs with APUc size warnings. See https://www.drupal.org/project/drupal/issues/3539331');
    $elements = $this->xpath('//details[summary[contains(@class, "system-status-report__status-title") and normalize-space(text()) = "PHP APCu caching"]]/div[@class="system-status-report__entry__value"]/text()');
    // Ensure the status is not a warning if APCu size is greater than or equal
    // to the recommended size.
    if (preg_match('/^Enabled \((.*)\)$/', $elements[0]->getText(), $matches)) {
      if (Bytes::toNumber($matches[1]) >= 1024 * 1024 * 32) {
        $this->assertFalse($elements[0]->find('xpath', '../../summary')->hasClass('system-status-report__status-icon--warning'));
      }
    }
  }

  /**
   * Tests that the Error counter matches the displayed number of errors.
   */
  public function testErrorElementCount(): void {
    // Trigger "cron has not run recently" error:
    $cron_config = \Drupal::config('system.cron');
    $time = \Drupal::time()->getRequestTime();
    \Drupal::state()->set('install_time', $time);
    $threshold_error = $cron_config->get('threshold.requirements_error');
    \Drupal::state()->set('system.cron_last', $time - $threshold_error - 1);

    $this->drupalGet('admin/reports/status');

    $error_elements = $this->cssSelect('.system-status-report__status-icon--error');
    $this->assertNotEquals(count($error_elements), 0, 'Errors are listed on the page.');
    $expected_text = new PluralTranslatableMarkup(count($error_elements), 'Error', 'Errors');
    $expected_text = count($error_elements) . ' ' . $expected_text;
    $this->assertSession()->responseContains((string) $expected_text);
  }

  /**
   * Tests that the Warning counter matches the displayed number of warnings.
   */
  public function testWarningElementCount(): void {
    // Trigger "cron has not run recently" with warning threshold:
    $cron_config = \Drupal::config('system.cron');
    $time = \Drupal::time()->getRequestTime();
    \Drupal::state()->set('install_time', $time);
    $threshold_warning = $cron_config->get('threshold.requirements_warning');
    \Drupal::state()->set('system.cron_last', $time - $threshold_warning - 1);

    $this->drupalGet('admin/reports/status');

    $warning_elements = $this->cssSelect('.system-status-report__status-icon--warning');
    $this->assertNotEquals(count($warning_elements), 0, 'Warnings are listed on the page.');
    $expected_text = new PluralTranslatableMarkup(count($warning_elements), 'Warning', 'Warnings');
    $expected_text = count($warning_elements) . ' ' . $expected_text;
    $this->assertSession()->responseContains((string) $expected_text);
  }

}
