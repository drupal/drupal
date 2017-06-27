<?php

namespace Drupal\Tests\update\Functional;

use Drupal\Core\Url;
use Drupal\Tests\Traits\Core\CronRunTrait;

/**
 * Tests the Update Manager module through a series of functional tests using
 * mock XML data.
 *
 * @group update
 */
class UpdateCoreTest extends UpdateTestBase {

  use CronRunTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['update_test', 'update', 'language', 'block'];

  protected function setUp() {
    parent::setUp();
    $admin_user = $this->drupalCreateUser(['administer site configuration', 'administer modules', 'administer themes']);
    $this->drupalLogin($admin_user);
    $this->drupalPlaceBlock('local_actions_block');
  }

  /**
   * Sets the version to x.x.x when no project-specific mapping is defined.
   *
   * @param string $version
   *   The version.
   */
  protected function setSystemInfo($version) {
    $setting = [
      '#all' => [
        'version' => $version,
      ],
    ];
    $this->config('update_test.settings')->set('system_info', $setting)->save();
  }

  /**
   * Tests the Update Manager module when no updates are available.
   */
  public function testNoUpdatesAvailable() {
    foreach ([0, 1] as $minor_version) {
      foreach ([0, 1] as $patch_version) {
        foreach (['-alpha1', '-beta1', ''] as $extra_version) {
          $this->setSystemInfo("8.$minor_version.$patch_version" . $extra_version);
          $this->refreshUpdateStatus(['drupal' => "$minor_version.$patch_version" . $extra_version]);
          $this->standardTests();
          $this->assertText(t('Up to date'));
          $this->assertNoText(t('Update available'));
          $this->assertNoText(t('Security update required!'));
          $this->assertRaw('check.svg', 'Check icon was found.');
        }
      }
    }
  }

  /**
   * Tests the Update Manager module when one normal update is available.
   */
  public function testNormalUpdateAvailable() {
    $this->setSystemInfo('8.0.0');

    // Ensure that the update check requires a token.
    $this->drupalGet('admin/reports/updates/check');
    $this->assertResponse(403, 'Accessing admin/reports/updates/check without a CSRF token results in access denied.');

    foreach ([0, 1] as $minor_version) {
      foreach (['-alpha1', '-beta1', ''] as $extra_version) {
        $this->refreshUpdateStatus(['drupal' => "$minor_version.1" . $extra_version]);
        $this->standardTests();
        $this->drupalGet('admin/reports/updates');
        $this->clickLink(t('Check manually'));
        $this->checkForMetaRefresh();
        $this->assertNoText(t('Security update required!'));
        $this->assertRaw(\Drupal::l("8.$minor_version.1" . $extra_version, Url::fromUri("http://example.com/drupal-8-$minor_version-1$extra_version-release")), 'Link to release appears.');
        $this->assertRaw(\Drupal::l(t('Download'), Url::fromUri("http://example.com/drupal-8-$minor_version-1$extra_version.tar.gz")), 'Link to download appears.');
        $this->assertRaw(\Drupal::l(t('Release notes'), Url::fromUri("http://example.com/drupal-8-$minor_version-1$extra_version-release")), 'Link to release notes appears.');

        switch ($minor_version) {
          case 0:
            // Both stable and unstable releases are available.
            // A stable release is the latest.
            if ($extra_version == '') {
              $this->assertNoText(t('Up to date'));
              $this->assertText(t('Update available'));
              $this->assertText(t('Recommended version:'));
              $this->assertNoText(t('Latest version:'));
              $this->assertRaw('warning.svg', 'Warning icon was found.');
            }
            // Only unstable releases are available.
            // An unstable release is the latest.
            else {
              $this->assertText(t('Up to date'));
              $this->assertNoText(t('Update available'));
              $this->assertNoText(t('Recommended version:'));
              $this->assertText(t('Latest version:'));
              $this->assertRaw('check.svg', 'Check icon was found.');
            }
            break;
          case 1:
            // Both stable and unstable releases are available.
            // A stable release is the latest.
            if ($extra_version == '') {
              $this->assertNoText(t('Up to date'));
              $this->assertText(t('Update available'));
              $this->assertText(t('Recommended version:'));
              $this->assertNoText(t('Latest version:'));
              $this->assertRaw('warning.svg', 'Warning icon was found.');
            }
            // Both stable and unstable releases are available.
            // An unstable release is the latest.
            else {
              $this->assertNoText(t('Up to date'));
              $this->assertText(t('Update available'));
              $this->assertText(t('Recommended version:'));
              $this->assertText(t('Latest version:'));
              $this->assertRaw('warning.svg', 'Warning icon was found.');
            }
            break;
        }
      }
    }
  }

  /**
   * Tests the Update Manager module when a major update is available.
   */
  public function testMajorUpdateAvailable() {
    foreach ([0, 1] as $minor_version) {
      foreach ([0, 1] as $patch_version) {
        foreach (['-alpha1', '-beta1', ''] as $extra_version) {
          $this->setSystemInfo("8.$minor_version.$patch_version" . $extra_version);
          $this->refreshUpdateStatus(['drupal' => '9']);
          $this->standardTests();
          $this->drupalGet('admin/reports/updates');
          $this->clickLink(t('Check manually'));
          $this->checkForMetaRefresh();
          $this->assertNoText(t('Security update required!'));
          $this->assertRaw(\Drupal::l('9.0.0', Url::fromUri("http://example.com/drupal-9-0-0-release")), 'Link to release appears.');
          $this->assertRaw(\Drupal::l(t('Download'), Url::fromUri("http://example.com/drupal-9-0-0.tar.gz")), 'Link to download appears.');
          $this->assertRaw(\Drupal::l(t('Release notes'), Url::fromUri("http://example.com/drupal-9-0-0-release")), 'Link to release notes appears.');
          $this->assertNoText(t('Up to date'));
          $this->assertText(t('Not supported!'));
          $this->assertText(t('Recommended version:'));
          $this->assertNoText(t('Latest version:'));
          $this->assertRaw('error.svg', 'Error icon was found.');
        }
      }
    }
  }

  /**
   * Tests the Update Manager module when a security update is available.
   */
  public function testSecurityUpdateAvailable() {
    foreach ([0, 1] as $minor_version) {
      $this->setSystemInfo("8.$minor_version.0");
      $this->refreshUpdateStatus(['drupal' => "$minor_version.2-sec"]);
      $this->standardTests();
      $this->assertNoText(t('Up to date'));
      $this->assertNoText(t('Update available'));
      $this->assertText(t('Security update required!'));
      $this->assertRaw(\Drupal::l("8.$minor_version.2", Url::fromUri("http://example.com/drupal-8-$minor_version-2-release")), 'Link to release appears.');
      $this->assertRaw(\Drupal::l(t('Download'), Url::fromUri("http://example.com/drupal-8-$minor_version-2.tar.gz")), 'Link to download appears.');
      $this->assertRaw(\Drupal::l(t('Release notes'), Url::fromUri("http://example.com/drupal-8-$minor_version-2-release")), 'Link to release notes appears.');
      $this->assertRaw('error.svg', 'Error icon was found.');
    }
  }

  /**
   * Ensures proper results where there are date mismatches among modules.
   */
  public function testDatestampMismatch() {
    $system_info = [
      '#all' => [
        // We need to think we're running a -dev snapshot to see dates.
        'version' => '8.1.0-dev',
        'datestamp' => time(),
      ],
      'block' => [
        // This is 2001-09-09 01:46:40 GMT, so test for "2001-Sep-".
        'datestamp' => '1000000000',
      ],
    ];
    $this->config('update_test.settings')->set('system_info', $system_info)->save();
    $this->refreshUpdateStatus(['drupal' => 'dev']);
    $this->assertNoText(t('2001-Sep-'));
    $this->assertText(t('Up to date'));
    $this->assertNoText(t('Update available'));
    $this->assertNoText(t('Security update required!'));
  }

  /**
   * Checks that running cron updates the list of available updates.
   */
  public function testModulePageRunCron() {
    $this->setSystemInfo('8.0.0');
    $this->config('update.settings')
      ->set('fetch.url', Url::fromRoute('update_test.update_test')->setAbsolute()->toString())
      ->save();
    $this->config('update_test.settings')
      ->set('xml_map', ['drupal' => '0.0'])
      ->save();

    $this->cronRun();
    $this->drupalGet('admin/modules');
    $this->assertNoText(t('No update information available.'));
  }

  /**
   * Checks the messages at admin/modules when the site is up to date.
   */
  public function testModulePageUpToDate() {
    $this->setSystemInfo('8.0.0');
    // Instead of using refreshUpdateStatus(), set these manually.
    $this->config('update.settings')
      ->set('fetch.url', Url::fromRoute('update_test.update_test')->setAbsolute()->toString())
      ->save();
    $this->config('update_test.settings')
      ->set('xml_map', ['drupal' => '0.0'])
      ->save();

    $this->drupalGet('admin/reports/updates');
    $this->clickLink(t('Check manually'));
    $this->checkForMetaRefresh();
    $this->assertText(t('Checked available update data for one project.'));
    $this->drupalGet('admin/modules');
    $this->assertNoText(t('There are updates available for your version of Drupal.'));
    $this->assertNoText(t('There is a security update available for your version of Drupal.'));
  }

  /**
   * Checks the messages at admin/modules when an update is missing.
   */
  public function testModulePageRegularUpdate() {
    $this->setSystemInfo('8.0.0');
    // Instead of using refreshUpdateStatus(), set these manually.
    $this->config('update.settings')
      ->set('fetch.url', Url::fromRoute('update_test.update_test')->setAbsolute()->toString())
      ->save();
    $this->config('update_test.settings')
      ->set('xml_map', ['drupal' => '0.1'])
      ->save();

    $this->drupalGet('admin/reports/updates');
    $this->clickLink(t('Check manually'));
    $this->checkForMetaRefresh();
    $this->assertText(t('Checked available update data for one project.'));
    $this->drupalGet('admin/modules');
    $this->assertText(t('There are updates available for your version of Drupal.'));
    $this->assertNoText(t('There is a security update available for your version of Drupal.'));
  }

  /**
   * Checks the messages at admin/modules when a security update is missing.
   */
  public function testModulePageSecurityUpdate() {
    $this->setSystemInfo('8.0.0');
    // Instead of using refreshUpdateStatus(), set these manually.
    $this->config('update.settings')
      ->set('fetch.url', Url::fromRoute('update_test.update_test')->setAbsolute()->toString())
      ->save();
    $this->config('update_test.settings')
      ->set('xml_map', ['drupal' => '0.2-sec'])
      ->save();

    $this->drupalGet('admin/reports/updates');
    $this->clickLink(t('Check manually'));
    $this->checkForMetaRefresh();
    $this->assertText(t('Checked available update data for one project.'));
    $this->drupalGet('admin/modules');
    $this->assertNoText(t('There are updates available for your version of Drupal.'));
    $this->assertText(t('There is a security update available for your version of Drupal.'));

    // Make sure admin/appearance warns you you're missing a security update.
    $this->drupalGet('admin/appearance');
    $this->assertNoText(t('There are updates available for your version of Drupal.'));
    $this->assertText(t('There is a security update available for your version of Drupal.'));

    // Make sure duplicate messages don't appear on Update status pages.
    $this->drupalGet('admin/reports/status');
    // We're expecting "There is a security update..." inside the status report
    // itself, but the drupal_set_message() appears as an li so we can prefix
    // with that and search for the raw HTML.
    $this->assertNoRaw('<li>' . t('There is a security update available for your version of Drupal.'));

    $this->drupalGet('admin/reports/updates');
    $this->assertNoText(t('There is a security update available for your version of Drupal.'));

    $this->drupalGet('admin/reports/updates/settings');
    $this->assertNoText(t('There is a security update available for your version of Drupal.'));
  }

  /**
   * Tests the Update Manager module when the update server returns 503 errors.
   */
  public function testServiceUnavailable() {
    $this->refreshUpdateStatus([], '503-error');
    // Ensure that no "Warning: SimpleXMLElement..." parse errors are found.
    $this->assertNoText('SimpleXMLElement');
    $this->assertUniqueText(t('Failed to get available update data for one project.'));
  }

  /**
   * Tests that exactly one fetch task per project is created and not more.
   */
  public function testFetchTasks() {
    $projecta = [
      'name' => 'aaa_update_test',
    ];
    $projectb = [
      'name' => 'bbb_update_test',
    ];
    $queue = \Drupal::queue('update_fetch_tasks');
    $this->assertEqual($queue->numberOfItems(), 0, 'Queue is empty');
    update_create_fetch_task($projecta);
    $this->assertEqual($queue->numberOfItems(), 1, 'Queue contains one item');
    update_create_fetch_task($projectb);
    $this->assertEqual($queue->numberOfItems(), 2, 'Queue contains two items');
    // Try to add project a again.
    update_create_fetch_task($projecta);
    $this->assertEqual($queue->numberOfItems(), 2, 'Queue still contains two items');

    // Clear storage and try again.
    update_storage_clear();
    update_create_fetch_task($projecta);
    $this->assertEqual($queue->numberOfItems(), 2, 'Queue contains two items');
  }

  /**
   * Checks language module in core package at admin/reports/updates.
   */
  public function testLanguageModuleUpdate() {
    $this->setSystemInfo('8.0.0');
    // Instead of using refreshUpdateStatus(), set these manually.
    $this->config('update.settings')
      ->set('fetch.url', Url::fromRoute('update_test.update_test')->setAbsolute()->toString())
      ->save();
    $this->config('update_test.settings')
      ->set('xml_map', ['drupal' => '0.1'])
      ->save();

    $this->drupalGet('admin/reports/updates');
    $this->assertText(t('Language'));
  }

  /**
   * Ensures that the local actions appear.
   */
  public function testLocalActions() {
    $admin_user = $this->drupalCreateUser(['administer site configuration', 'administer modules', 'administer software updates', 'administer themes']);
    $this->drupalLogin($admin_user);

    $this->drupalGet('admin/modules');
    $this->clickLink(t('Install new module'));
    $this->assertUrl('admin/modules/install');

    $this->drupalGet('admin/appearance');
    $this->clickLink(t('Install new theme'));
    $this->assertUrl('admin/theme/install');

    $this->drupalGet('admin/reports/updates');
    $this->clickLink(t('Install new module or theme'));
    $this->assertUrl('admin/reports/updates/install');
  }

}
