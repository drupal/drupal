<?php

namespace Drupal\Tests\system\Functional\System;

use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;
use Symfony\Component\CssSelector\CssSelectorConverter;

/**
 * Tests output on the status overview page.
 *
 * @group system
 */
class StatusTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['update_test_postupdate'];

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
    ]);
    $this->drupalLogin($admin_user);
  }

  /**
   * Tests that the status page returns.
   */
  public function testStatusPage() {
    // Go to Administration.
    $this->drupalGet('admin/reports/status');
    $this->assertSession()->statusCodeEquals(200);

    $phpversion = phpversion();
    $this->assertText($phpversion, 'Php version is shown on the page.');

    if (function_exists('phpinfo')) {
      $this->assertLinkByHref(Url::fromRoute('system.php')->toString());
    }
    else {
      $this->assertNoLinkByHref(Url::fromRoute('system.php')->toString());
    }

    // If a module is fully installed no pending updates exists.
    $this->assertNoText(t('Out of date'));

    // The setting config_sync_directory is not properly formed.
    $this->assertRaw(t("Your %file file must define the %setting setting", ['%file' => $this->siteDirectory . '/settings.php', '%setting' => "\$settings['config_sync_directory']"]));

    // Set the schema version of update_test_postupdate to a lower version, so
    // update_test_postupdate_update_8001() needs to be executed.
    drupal_set_installed_schema_version('update_test_postupdate', 8000);
    $this->drupalGet('admin/reports/status');
    $this->assertText(t('Out of date'));

    // Now cleanup the executed post update functions.
    drupal_set_installed_schema_version('update_test_postupdate', 8001);
    /** @var \Drupal\Core\Update\UpdateRegistry $post_update_registry */
    $post_update_registry = \Drupal::service('update.post_update_registry');
    $post_update_registry->filterOutInvokedUpdatesByModule('update_test_postupdate');
    $this->drupalGet('admin/reports/status');
    $this->assertText(t('Out of date'));

    $this->drupalGet('admin/reports/status/php');
    $this->assertSession()->statusCodeEquals(200);

    // Check if cron error is displayed in errors section
    $cron_last_run = \Drupal::state()->get('system.cron_last');
    \Drupal::state()->set('system.cron_last', 0);
    $this->drupalGet('admin/reports/status');
    $css_selector_converter = new CssSelectorConverter();
    $xpath = $css_selector_converter->toXPath('details.system-status-report__entry') . '//div[contains(text(), "Cron has not run recently")]';
    $this->assertNotEmpty($this->xpath($xpath), 'Cron has not run recently error is being displayed.');
    \Drupal::state()->set('system.cron_last', $cron_last_run);
  }

}
