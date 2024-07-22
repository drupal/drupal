<?php

declare(strict_types=1);

namespace Drupal\Tests\update\Functional;

use Drupal\Core\Url;
use Drupal\Tests\Traits\Core\CronRunTrait;

/**
 * Tests general functionality of the Update module.
 *
 * @group update
 */
class UpdateMiscTest extends UpdateTestBase {

  use CronRunTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['language', 'block'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $setting = [
      '#all' => [
        'version' => '8.0.0',
      ],
    ];
    $this->config('update_test.settings')->set('system_info', $setting)->save();
    $this->drupalPlaceBlock('local_actions_block');
  }

  /**
   * Checks that clearing the disk cache works.
   */
  public function testClearDiskCache(): void {
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
   * Tests the Update Manager module when the update server returns 503 errors.
   */
  public function testServiceUnavailable(): void {
    $admin_user = $this->drupalCreateUser([
      'administer site configuration',
    ]);
    $this->drupalLogin($admin_user);
    $this->refreshUpdateStatus([], '503-error');
    // Ensure that no "Warning: SimpleXMLElement..." parse errors are found.
    $this->assertSession()->pageTextNotContains('SimpleXMLElement');
    $this->assertSession()->pageTextContainsOnce('Failed to get available update data for one project.');
  }

  /**
   * Tests that exactly one fetch task per project is created and not more.
   */
  public function testFetchTasks(): void {
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
   * Checks the messages at admin/modules when the site is up to date.
   */
  public function testModulePageUpToDate(): void {
    $this->drupalLogin($this->drupalCreateUser([
      'administer site configuration',
      'view update notifications',
    ]));
    // Instead of using refreshUpdateStatus(), set these manually.
    $this->config('update.settings')
      ->set('fetch.url', Url::fromRoute('update_test.update_test')
        ->setAbsolute()
        ->toString())
      ->save();
    $this->config('update_test.settings')
      ->set('xml_map', ['drupal' => '8.0.0'])
      ->save();

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
  public function testModulePageRegularUpdate(): void {
    $this->drupalLogin($this->drupalCreateUser([
      'administer site configuration',
      'administer modules',
      'view update notifications',
    ]));
    // Instead of using refreshUpdateStatus(), set these manually.
    $this->config('update.settings')
      ->set('fetch.url', Url::fromRoute('update_test.update_test')->setAbsolute()->toString())
      ->save();
    $this->config('update_test.settings')
      ->set('xml_map', ['drupal' => '8.0.1'])
      ->save();

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
  public function testModulePageSecurityUpdate(): void {
    $this->drupalLogin($this->drupalCreateUser([
      'administer site configuration',
      'administer modules',
      'administer themes',
      'view update notifications',
    ]));
    // Instead of using refreshUpdateStatus(), set these manually.
    $this->config('update.settings')
      ->set('fetch.url', Url::fromRoute('update_test.update_test')->setAbsolute()->toString())
      ->save();
    $this->mockReleaseHistory(['drupal' => 'sec.8.0.2']);

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
   * Checks that running cron updates the list of available updates.
   */
  public function testModulePageRunCron(): void {
    $this->config('update.settings')
      ->set('fetch.url', Url::fromRoute('update_test.update_test')->setAbsolute()->toString())
      ->save();
    $this->mockReleaseHistory(['drupal' => '8.0.0']);

    $this->cronRun();
    $this->drupalGet('admin/modules');
    $this->assertSession()->pageTextNotContains('No update information available.');
  }

  /**
   * Checks language module in core package at admin/reports/updates.
   */
  public function testLanguageModuleUpdate(): void {
    $this->drupalLogin($this->drupalCreateUser([
      'administer site configuration',
    ]));
    // Instead of using refreshUpdateStatus(), set these manually.
    $this->config('update.settings')
      ->set('fetch.url', Url::fromRoute('update_test.update_test')->setAbsolute()->toString())
      ->save();
    $this->mockReleaseHistory(['drupal' => '0.1']);

    $this->drupalGet('admin/reports/updates');
    $this->assertSession()->pageTextContains('Language');
  }

}
