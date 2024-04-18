<?php

namespace Drupal\Tests\update\Functional;

use Drupal\Core\Url;

/**
 * Tests edge cases of the Available Updates report UI.
 *
 * For example, manually checking for updates, recovering from problems
 * connecting to the release history server, clearing the disk cache, and more.
 *
 * @group update
 * @group #slow
 */
class UpdateSemverCoreTest extends UpdateSemverCoreTestBase {

  /**
   * Ensures proper results where there are date mismatches among modules.
   */
  public function testDatestampMismatch() {
    $this->mockInstalledExtensionsInfo([
      'block' => [
        // This is 2001-09-09 01:46:40 GMT, so test for "2001-Sep-".
        'datestamp' => '1000000000',
      ],
    ]);
    // We need to think we're running a -dev snapshot to see dates.
    $this->mockDefaultExtensionsInfo([
      'version' => '8.1.0-dev',
      'datestamp' => time(),
    ]);
    $this->refreshUpdateStatus(['drupal' => 'dev']);
    $this->assertSession()->pageTextNotContains('2001-Sep-');
    $this->assertSession()->pageTextContains('Up to date');
    $this->assertSession()->pageTextNotContains('Update available');
    $this->assertSession()->pageTextNotContains('Security update required!');
  }

  /**
   * Checks that running cron updates the list of available updates.
   */
  public function testModulePageRunCron() {
    $this->setProjectInstalledVersion('8.0.0');
    $this->config('update.settings')
      ->set('fetch.url', Url::fromRoute('update_test.update_test')->setAbsolute()->toString())
      ->save();
    $this->mockReleaseHistory(['drupal' => '0.0']);

    $this->cronRun();
    $this->drupalGet('admin/modules');
    $this->assertSession()->pageTextNotContains('No update information available.');
  }

  /**
   * Checks that clearing the disk cache works.
   */
  public function testClearDiskCache() {
    $directories = [
      _update_manager_cache_directory(FALSE),
      _update_manager_extract_directory(FALSE),
    ];
    // Check that update directories does not exists.
    foreach ($directories as $directory) {
      $this->assertDirectoryDoesNotExist($directory);
    }

    // Method must not fail if update directories do not exists.
    update_clear_update_disk_cache();
  }

  /**
   * Checks the messages at admin/modules when the site is up to date.
   */
  public function testModulePageUpToDate() {
    $this->setProjectInstalledVersion('8.0.0');
    // Instead of using refreshUpdateStatus(), set these manually.
    $this->config('update.settings')
      ->set('fetch.url', Url::fromRoute('update_test.update_test')->setAbsolute()->toString())
      ->save();
    $this->mockReleaseHistory(['drupal' => '0.0']);

    $this->drupalGet('admin/reports/updates');
    $this->clickLink('Check manually');
    $this->checkForMetaRefresh();
    $this->assertSession()->pageTextContains('Checked available update data for one project.');
    $this->drupalGet('admin/modules');
    $this->assertSession()->pageTextNotContains('There are updates available for your version of Drupal.');
    $this->assertSession()->pageTextNotContains('There is a security update available for your version of Drupal.');
  }

  /**
   * Checks the messages at admin/modules when an update is missing.
   */
  public function testModulePageRegularUpdate() {
    $this->drupalLogin($this->drupalCreateUser([
      'administer site configuration',
      'administer modules',
      'view update notifications',
    ]));
    $this->setProjectInstalledVersion('8.0.0');
    // Instead of using refreshUpdateStatus(), set these manually.
    $this->config('update.settings')
      ->set('fetch.url', Url::fromRoute('update_test.update_test')->setAbsolute()->toString())
      ->save();
    $this->mockReleaseHistory(['drupal' => '0.1']);

    $this->drupalGet('admin/reports/updates');
    $this->clickLink('Check manually');
    $this->checkForMetaRefresh();
    $this->assertSession()->pageTextContains('Checked available update data for one project.');
    $this->drupalGet('admin/modules');
    $this->assertSession()->pageTextContains('There are updates available for your version of Drupal.');
    $this->assertSession()->pageTextNotContains('There is a security update available for your version of Drupal.');

    // A user without the "view update notifications" permission shouldn't be
    // notified about available updates.
    $this->drupalLogin($this->drupalCreateUser([
      'administer site configuration',
      'administer modules',
    ]));
    $this->drupalGet('admin/modules');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextNotContains('There are updates available for your version of Drupal.');
  }

  /**
   * Checks the messages at admin/modules when a security update is missing.
   */
  public function testModulePageSecurityUpdate() {
    $this->drupalLogin($this->drupalCreateUser([
      'administer site configuration',
      'administer modules',
      'administer themes',
      'view update notifications',
    ]));
    $this->setProjectInstalledVersion('8.0.0');
    // Instead of using refreshUpdateStatus(), set these manually.
    $this->config('update.settings')
      ->set('fetch.url', Url::fromRoute('update_test.update_test')->setAbsolute()->toString())
      ->save();
    $this->mockReleaseHistory(['drupal' => 'sec.0.2']);

    $this->drupalGet('admin/reports/updates');
    $this->clickLink('Check manually');
    $this->checkForMetaRefresh();
    $this->assertSession()->pageTextContains('Checked available update data for one project.');
    $this->drupalGet('admin/modules');
    $this->assertSession()->pageTextNotContains('There are updates available for your version of Drupal.');
    $this->assertSession()->pageTextContains('There is a security update available for your version of Drupal.');

    // Make sure admin/appearance warns you you're missing a security update.
    $this->drupalGet('admin/appearance');
    $this->assertSession()->pageTextNotContains('There are updates available for your version of Drupal.');
    $this->assertSession()->pageTextContains('There is a security update available for your version of Drupal.');

    // Make sure duplicate messages don't appear on Update status pages.
    $this->drupalGet('admin/reports/status');
    $this->assertSession()->pageTextContainsOnce('There is a security update available for your version of Drupal.');

    $this->drupalGet('admin/reports/updates');
    $this->assertSession()->pageTextNotContains('There is a security update available for your version of Drupal.');

    $this->drupalGet('admin/reports/updates/settings');
    $this->assertSession()->pageTextNotContains('There is a security update available for your version of Drupal.');
  }

  /**
   * Tests the Update Manager module when the update server returns 503 errors.
   */
  public function testServiceUnavailable() {
    $this->refreshUpdateStatus([], '503-error');
    // Ensure that no "Warning: SimpleXMLElement..." parse errors are found.
    $this->assertSession()->pageTextNotContains('SimpleXMLElement');
    $this->assertSession()->pageTextContainsOnce('Failed to get available update data for one project.');
  }

  /**
   * Tests that exactly one fetch task per project is created and not more.
   */
  public function testFetchTasks() {
    $project_a = [
      'name' => 'aaa_update_test',
    ];
    $project_b = [
      'name' => 'bbb_update_test',
    ];
    $queue = \Drupal::queue('update_fetch_tasks');
    $this->assertEquals(0, $queue->numberOfItems(), 'Queue is empty');
    update_create_fetch_task($project_a);
    $this->assertEquals(1, $queue->numberOfItems(), 'Queue contains one item');
    update_create_fetch_task($project_b);
    $this->assertEquals(2, $queue->numberOfItems(), 'Queue contains two items');
    // Try to add a project again.
    update_create_fetch_task($project_a);
    $this->assertEquals(2, $queue->numberOfItems(), 'Queue still contains two items');

    // Clear storage and try again.
    update_storage_clear();
    update_create_fetch_task($project_a);
    $this->assertEquals(2, $queue->numberOfItems(), 'Queue contains two items');
  }

  /**
   * Checks language module in core package at admin/reports/updates.
   */
  public function testLanguageModuleUpdate() {
    $this->setProjectInstalledVersion('8.0.0');
    // Instead of using refreshUpdateStatus(), set these manually.
    $this->config('update.settings')
      ->set('fetch.url', Url::fromRoute('update_test.update_test')->setAbsolute()->toString())
      ->save();
    $this->mockReleaseHistory(['drupal' => '0.1']);

    $this->drupalGet('admin/reports/updates');
    $this->assertSession()->pageTextContains('Language');
  }

  /**
   * Ensures that the local actions appear.
   */
  public function testLocalActions() {
    $admin_user = $this->drupalCreateUser([
      'administer site configuration',
      'administer modules',
      'administer software updates',
      'administer themes',
    ]);
    $this->drupalLogin($admin_user);

    $this->drupalGet('admin/modules');
    $this->clickLink('Add new module');
    $this->assertSession()->addressEquals('admin/modules/install');

    $this->drupalGet('admin/appearance');
    $this->clickLink('Add new theme');
    $this->assertSession()->addressEquals('admin/theme/install');

    $this->drupalGet('admin/reports/updates');
    $this->clickLink('Add new module or theme');
    $this->assertSession()->addressEquals('admin/reports/updates/install');
  }

  /**
   * Checks that Drupal recovers after problems connecting to update server.
   *
   * This test uses the following XML fixtures.
   *  - drupal.broken.xml
   *  - drupal.sec.8.0.2.xml
   *     'supported_branches' is '8.0.,8.1.'.
   */
  public function testBrokenThenFixedUpdates() {
    $this->drupalLogin($this->drupalCreateUser([
      'administer site configuration',
      'view update notifications',
      'access administration pages',
    ]));
    $this->setProjectInstalledVersion('8.0.0');
    // Instead of using refreshUpdateStatus(), set these manually.
    $this->config('update.settings')
      ->set('fetch.url', Url::fromRoute('update_test.update_test')->setAbsolute()->toString())
      ->save();
    // Use update XML that has no information to simulate a broken response from
    // the update server.
    $this->mockReleaseHistory(['drupal' => 'broken']);

    // This will retrieve broken updates.
    $this->cronRun();
    $this->drupalGet('admin/reports/status');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('There was a problem checking available updates for Drupal.');
    $this->mockReleaseHistory(['drupal' => 'sec.0.2']);
    // Simulate the update_available_releases state expiring before cron is run
    // and the state is used by \Drupal\update\UpdateManager::getProjects().
    \Drupal::keyValueExpirable('update_available_releases')->deleteAll();
    // This cron run should retrieve fixed updates.
    $this->cronRun();
    $this->drupalGet('admin/config');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('There is a security update available for your version of Drupal.');
  }

  /**
   * Tests when a dev release does not have a date.
   */
  public function testDevNoReleaseDate() {
    $this->setProjectInstalledVersion('8.0.x-dev');
    $this->refreshUpdateStatus([$this->updateProject => 'dev-no-date']);
  }

}
