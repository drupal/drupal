<?php

declare(strict_types=1);

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
  public function testDatestampMismatch(): void {
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
   * Tests the Update Manager module when the update server returns 503 errors.
   */
  public function testServiceUnavailable(): void {
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
   * Checks that Drupal recovers after problems connecting to update server.
   *
   * This test uses the following XML fixtures.
   *  - drupal.broken.xml
   *  - drupal.sec.8.0.2.xml
   *     'supported_branches' is '8.0.,8.1.'.
   */
  public function testBrokenThenFixedUpdates(): void {
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
    $this->mockReleaseHistory(['drupal' => 'sec.8.0.2']);
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
  public function testDevNoReleaseDate(): void {
    $this->setProjectInstalledVersion('8.0.x-dev');
    $this->refreshUpdateStatus([$this->updateProject => 'dev-no-date']);
  }

}
