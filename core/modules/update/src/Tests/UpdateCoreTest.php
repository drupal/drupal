<?php

/**
 * @file
 * Definition of Drupal\update\Tests\UpdateCoreTest.
 */

namespace Drupal\update\Tests;

/**
 * Tests behavior related to discovering and listing updates to Drupal core.
 */
class UpdateCoreTest extends UpdateTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('update_test', 'update', 'language');

  public static function getInfo() {
    return array(
      'name' => 'Update core functionality',
      'description' => 'Tests the Update Manager module through a series of functional tests using mock XML data.',
      'group' => 'Update',
    );
  }

  function setUp() {
    parent::setUp();
    $admin_user = $this->drupalCreateUser(array('administer site configuration', 'administer modules', 'administer themes'));
    $this->drupalLogin($admin_user);
  }


  /**
   * Tests the Update Manager module when no updates are available.
   */
  function testNoUpdatesAvailable() {
    $this->setSystemInfo7_0();
    $this->refreshUpdateStatus(array('drupal' => '0'));
    $this->standardTests();
    $this->assertText(t('Up to date'));
    $this->assertNoText(t('Update available'));
    $this->assertNoText(t('Security update required!'));
  }

  /**
   * Tests the Update Manager module when one normal update is available.
   */
  function testNormalUpdateAvailable() {
    $this->setSystemInfo7_0();
    $this->refreshUpdateStatus(array('drupal' => '1'));
    $this->standardTests();
    $this->assertNoText(t('Up to date'));
    $this->assertText(t('Update available'));
    $this->assertNoText(t('Security update required!'));
    $this->assertRaw(l('7.1', 'http://example.com/drupal-7-1-release'), 'Link to release appears.');
    $this->assertRaw(l(t('Download'), 'http://example.com/drupal-7-1.tar.gz'), 'Link to download appears.');
    $this->assertRaw(l(t('Release notes'), 'http://example.com/drupal-7-1-release'), 'Link to release notes appears.');
  }

  /**
   * Tests the Update Manager module when a security update is available.
   */
  function testSecurityUpdateAvailable() {
    $this->setSystemInfo7_0();
    $this->refreshUpdateStatus(array('drupal' => '2-sec'));
    $this->standardTests();
    $this->assertNoText(t('Up to date'));
    $this->assertNoText(t('Update available'));
    $this->assertText(t('Security update required!'));
    $this->assertRaw(l('7.2', 'http://example.com/drupal-7-2-release'), 'Link to release appears.');
    $this->assertRaw(l(t('Download'), 'http://example.com/drupal-7-2.tar.gz'), 'Link to download appears.');
    $this->assertRaw(l(t('Release notes'), 'http://example.com/drupal-7-2-release'), 'Link to release notes appears.');
  }

  /**
   * Ensures proper results where there are date mismatches among modules.
   */
  function testDatestampMismatch() {
    $system_info = array(
      '#all' => array(
        // We need to think we're running a -dev snapshot to see dates.
        'version' => '7.0-dev',
        'datestamp' => time(),
      ),
      'block' => array(
        // This is 2001-09-09 01:46:40 GMT, so test for "2001-Sep-".
        'datestamp' => '1000000000',
      ),
    );
    \Drupal::config('update_test.settings')->set('system_info', $system_info)->save();
    $this->refreshUpdateStatus(array('drupal' => 'dev'));
    $this->assertNoText(t('2001-Sep-'));
    $this->assertText(t('Up to date'));
    $this->assertNoText(t('Update available'));
    $this->assertNoText(t('Security update required!'));
  }

  /**
   * Checks that running cron updates the list of available updates.
   */
  function testModulePageRunCron() {
    $this->setSystemInfo7_0();
    \Drupal::config('update.settings')->set('fetch.url', url('update-test', array('absolute' => TRUE)))->save();
    \Drupal::config('update_test.settings')->set('xml_map', array('drupal' => '0'))->save();

    $this->cronRun();
    $this->drupalGet('admin/modules');
    $this->assertNoText(t('No update information available.'));
  }

  /**
   * Checks the messages at admin/modules when the site is up to date.
   */
  function testModulePageUpToDate() {
    $this->setSystemInfo7_0();
    // Instead of using refreshUpdateStatus(), set these manually.
    \Drupal::config('update.settings')->set('fetch.url', url('update-test', array('absolute' => TRUE)))->save();
    \Drupal::config('update_test.settings')->set('xml_map', array('drupal' => '0'))->save();

    $this->drupalGet('admin/reports/updates');
    $this->clickLink(t('Check manually'));
    $this->assertText(t('Checked available update data for one project.'));
    $this->drupalGet('admin/modules');
    $this->assertNoText(t('There are updates available for your version of Drupal.'));
    $this->assertNoText(t('There is a security update available for your version of Drupal.'));
  }

  /**
   * Checks the messages at admin/modules when an update is missing.
   */
  function testModulePageRegularUpdate() {
    $this->setSystemInfo7_0();
    // Instead of using refreshUpdateStatus(), set these manually.
    \Drupal::config('update.settings')->set('fetch.url', url('update-test', array('absolute' => TRUE)))->save();
    \Drupal::config('update_test.settings')->set('xml_map', array('drupal' => '1'))->save();

    $this->drupalGet('admin/reports/updates');
    $this->clickLink(t('Check manually'));
    $this->assertText(t('Checked available update data for one project.'));
    $this->drupalGet('admin/modules');
    $this->assertText(t('There are updates available for your version of Drupal.'));
    $this->assertNoText(t('There is a security update available for your version of Drupal.'));
  }

  /**
   * Checks the messages at admin/modules when a security update is missing.
   */
  function testModulePageSecurityUpdate() {
    $this->setSystemInfo7_0();
    // Instead of using refreshUpdateStatus(), set these manually.
    \Drupal::config('update.settings')->set('fetch.url', url('update-test', array('absolute' => TRUE)))->save();
    \Drupal::config('update_test.settings')->set('xml_map', array('drupal' => '2-sec'))->save();

    $this->drupalGet('admin/reports/updates');
    $this->clickLink(t('Check manually'));
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
  function testServiceUnavailable() {
    $this->refreshUpdateStatus(array(), '503-error');
    // Ensure that no "Warning: SimpleXMLElement..." parse errors are found.
    $this->assertNoText('SimpleXMLElement');
    $this->assertUniqueText(t('Failed to get available update data for one project.'));
  }

  /**
   * Tests that exactly one fetch task per project is created and not more.
   */
  function testFetchTasks() {
    $projecta = array(
      'name' => 'aaa_update_test',
    );
    $projectb = array(
      'name' => 'bbb_update_test',
    );
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
    drupal_static_reset('_update_create_fetch_task');
    update_create_fetch_task($projecta);
    $this->assertEqual($queue->numberOfItems(), 2, 'Queue contains two items');
  }

  /**
   * Checks language module in core package at admin/reports/updates.
   */
  function testLanguageModuleUpdate() {
    $this->setSystemInfo7_0();
    // Instead of using refreshUpdateStatus(), set these manually.
    \Drupal::config('update.settings')->set('fetch.url', url('update-test', array('absolute' => TRUE)))->save();
    \Drupal::config('update_test.settings')->set('xml_map', array('drupal' => '1'))->save();

    $this->drupalGet('admin/reports/updates');
    $this->assertText(t('Language'));
  }

  /**
   * Sets the version to 7.0 when no project-specific mapping is defined.
   */
  protected function setSystemInfo7_0() {
    $setting = array(
      '#all' => array(
        'version' => '7.0',
      ),
    );
    \Drupal::config('update_test.settings')->set('system_info', $setting)->save();
  }

  /**
   * Ensures that the local actions appear.
   */
  public function testLocalActions() {
    $admin_user = $this->drupalCreateUser(array('administer site configuration', 'administer modules', 'administer software updates', 'administer themes'));
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
