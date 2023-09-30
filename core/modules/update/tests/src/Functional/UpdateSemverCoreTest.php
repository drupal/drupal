<?php

namespace Drupal\Tests\update\Functional;

use Drupal\Core\Url;

/**
 * Tests the semantic version handling in the Update Manager.
 *
 * @group update
 * @group #slow
 */
class UpdateSemverCoreTest extends UpdateSemverTestBase {

  /**
   * {@inheritdoc}
   */
  protected $updateTableLocator = 'table.update';

  /**
   * {@inheritdoc}
   */
  protected $updateProject = 'drupal';

  /**
   * {@inheritdoc}
   */
  protected $projectTitle = 'Drupal';

  /**
   * Sets the version to x.x.x when no project-specific mapping is defined.
   *
   * @param string $version
   *   The version.
   */
  protected function setProjectInstalledVersion($version) {
    $setting = [
      '#all' => [
        'version' => $version,
      ],
    ];
    $this->config('update_test.settings')->set('system_info', $setting)->save();
  }

  /**
   * Tests the security coverage messages for Drupal core versions.
   *
   * @param string $installed_version
   *   The installed Drupal version to test.
   * @param string $fixture
   *   The test fixture that contains the test XML.
   * @param string $requirements_section_heading
   *   The requirements section heading.
   * @param string $message
   *   The expected coverage message.
   * @param string $mock_date
   *   The mock date to use if needed in the format CCYY-MM-DD. If an empty
   *   string is provided, no mock date will be used.
   *
   * @dataProvider securityCoverageMessageProvider
   */
  public function testSecurityCoverageMessage($installed_version, $fixture, $requirements_section_heading, $message, $mock_date) {
    \Drupal::state()->set('update_test.mock_date', $mock_date);
    $this->setProjectInstalledVersion($installed_version);
    $this->refreshUpdateStatus(['drupal' => $fixture]);
    $this->drupalGet('admin/reports/status');

    if (empty($requirements_section_heading)) {
      $this->assertSession()->pageTextNotContains('Drupal core security coverage');
      return;
    }

    $all_requirements_details = $this->getSession()->getPage()->findAll(
      'css',
      'details.system-status-report__entry:contains("Drupal core security coverage")'
    );
    // Ensure we only have 1 security message section.
    $this->assertCount(1, $all_requirements_details);
    $requirements_details = $all_requirements_details[0];
    // Ensure that messages are under the correct heading which could be
    // 'Checked', 'Warnings found', or 'Errors found'.
    $requirements_section_element = $requirements_details->getParent();
    $this->assertCount(1, $requirements_section_element->findAll('css', "h3:contains('$requirements_section_heading')"));
    $actual_message = $requirements_details->find('css', 'div.system-status-report__entry__value')->getText();
    $this->assertNotEmpty($actual_message);
    $this->assertEquals($message, $actual_message);
  }

  /**
   * Data provider for testSecurityCoverageMessage().
   *
   * These test cases rely on the following fixtures containing the following
   * releases:
   * - drupal.sec.2.0_3.0-rc1.xml
   *   - 8.2.0
   *   - 8.3.0-rc1
   * - drupal.sec.2.0.xml
   *   - 8.2.0
   * - drupal.sec.2.0_9.0.0.xml
   *   - 8.2.0
   *   - 9.0.0
   * - drupal.sec.9.5.0.xml
   *   - 9.4.0
   *   - 9.5.0
   * - drupal.sec.10.5.0.xml
   *   - 10.4.0
   *   - 10.5.0
   */
  public function securityCoverageMessageProvider() {
    $release_coverage_message = 'Visit the release cycle overview for more information on supported releases.';
    $coverage_ended_message = 'Coverage has ended';
    $update_asap_message = 'Update to a supported minor as soon as possible to continue receiving security updates.';
    $update_soon_message = 'Update to a supported minor version soon to continue receiving security updates.';
    $test_cases = [
      '8.0.0, unsupported' => [
        'installed_version' => '8.0.0',
        'fixture' => 'sec.2.0_3.0-rc1',
        'requirements_section_heading' => 'Errors found',
        'message' => "$coverage_ended_message $update_asap_message $release_coverage_message",
        'mock_date' => '',
      ],
      '8.1.0, supported with 3rc' => [
        'installed_version' => '8.1.0',
        'fixture' => 'sec.2.0_3.0-rc1',
        'requirements_section_heading' => 'Warnings found',
        'message' => "Covered until 8.3.0 Update to 8.2 or higher soon to continue receiving security updates. $release_coverage_message",
        'mock_date' => '',
      ],
      '8.1.0, supported' => [
        'installed_version' => '8.1.0',
        'fixture' => 'sec.2.0',
        'requirements_section_heading' => 'Warnings found',
        'message' => "Covered until 8.3.0 Update to 8.2 or higher soon to continue receiving security updates. $release_coverage_message",
        'mock_date' => '',
      ],
      '8.2.0, supported with 3rc' => [
        'installed_version' => '8.2.0',
        'fixture' => 'sec.2.0_3.0-rc1',
        'requirements_section_heading' => 'Checked',
        'message' => "Covered until 8.4.0 $release_coverage_message",
        'mock_date' => '',
      ],
      '8.2.0, supported' => [
        'installed_version' => '8.2.0',
        'fixture' => 'sec.2.0',
        'requirements_section_heading' => 'Checked',
        'message' => "Covered until 8.4.0 $release_coverage_message",
        'mock_date' => '',
      ],
      // Ensure we don't show messages for pre-release or dev versions.
      '8.2.0-beta2, no message' => [
        'installed_version' => '8.2.0-beta2',
        'fixture' => 'sec.2.0_3.0-rc1',
        'requirements_section_heading' => '',
        'message' => '',
        'mock_date' => '',
      ],
      '8.1.0-dev, no message' => [
        'installed_version' => '8.1.0-dev',
        'fixture' => 'sec.2.0_3.0-rc1',
        'requirements_section_heading' => '',
        'message' => '',
        'mock_date' => '',
      ],
      // Ensures the message is correct if the next major version has been
      // released and the additional minors indicated by
      // CORE_MINORS_WITH_SECURITY_COVERAGE minors have been released.
      '8.0.0, 9 unsupported' => [
        'installed_version' => '8.0.0',
        'fixture' => 'sec.2.0_9.0.0',
        'requirements_section_heading' => 'Errors found',
        'message' => "$coverage_ended_message $update_asap_message $release_coverage_message",
        'mock_date' => '',
      ],
      // Ensures the message is correct if the next major version has been
      // released and the additional minors indicated by
      // CORE_MINORS_WITH_SECURITY_COVERAGE minors have not been released.
      '8.2.0, 9 warning' => [
        'installed_version' => '8.2.0',
        'fixture' => 'sec.2.0_9.0.0',
        'requirements_section_heading' => 'Warnings found',
        'message' => "Covered until 8.4.0 Update to 8.3 or higher soon to continue receiving security updates. $release_coverage_message",
        'mock_date' => '',
      ],
    ];

    // Drupal 9.4.x test cases.
    $test_cases += [
      // Ensure that a message is displayed during 9.4's active support.
      '9.4.0, supported' => [
        'installed_version' => '9.4.0',
        'fixture' => 'sec.9.5.0',
        'requirements_section_heading' => 'Checked',
        'message' => "Covered until 2023-Jun-21 $release_coverage_message",
        'mock_date' => '2022-12-13',
      ],
      // Ensure a warning is displayed if less than six months remain until the
      // end of 9.4's security coverage.
      '9.4.0, supported, 6 months warn' => [
        'installed_version' => '9.4.0',
        'fixture' => 'sec.9.5.0',
        'requirements_section_heading' => 'Warnings found',
        'message' => "Covered until 2023-Jun-21 $update_soon_message $release_coverage_message",
        'mock_date' => '2022-12-14',
      ],
    ];
    // Ensure that the message does not change, including on the last day of
    // security coverage.
    $test_cases['9.4.0, supported, last day warn'] = $test_cases['9.4.0, supported, 6 months warn'];
    $test_cases['9.4.0, supported, last day warn']['mock_date'] = '2023-06-20';

    // Ensure that if the 9.4 support window is finished a message is
    // displayed.
    $test_cases['9.4.0, support over'] = [
      'installed_version' => '9.4.0',
      'fixture' => 'sec.9.5.0',
      'requirements_section_heading' => 'Errors found',
      'message' => "$coverage_ended_message $update_asap_message $release_coverage_message",
      'mock_date' => '2023-06-22',
    ];

    // Drupal 9.5 test cases.
    $test_cases['9.5.0, supported'] = [
      'installed_version' => '9.5.0',
      'fixture' => 'sec.9.5.0',
      'requirements_section_heading' => 'Checked',
      'message' => "Covered until 2023-Nov $release_coverage_message",
      'mock_date' => '2023-01-01',
    ];
    // Ensure a warning is displayed if less than six months remain until the
    // end of 9.5's security coverage.
    $test_cases['9.5.0, supported, 6 months warn'] = [
      'installed_version' => '9.5.0',
      'fixture' => 'sec.9.5.0',
      'requirements_section_heading' => 'Warnings found',
      'message' => "Covered until 2023-Nov $update_soon_message $release_coverage_message",
      'mock_date' => '2023-05-15',
    ];

    // Ensure that the message does not change, including on the last day of
    // security coverage.
    $test_cases['9.5.0, supported, last day warn'] = $test_cases['9.5.0, supported, 6 months warn'];
    $test_cases['9.5.0, supported, last day warn']['mock_date'] = '2023-10-31';

    // Ensure that if the support window is finished a message is displayed.
    $test_cases['9.5.0, support over'] = [
      'installed_version' => '9.5.0',
      'fixture' => 'sec.9.5.0',
      'requirements_section_heading' => 'Errors found',
      'message' => "$coverage_ended_message $update_asap_message $release_coverage_message",
      'mock_date' => '2023-11-01',
    ];

    // Drupal 9 test cases.
    $test_cases += [
      // Ensure the end dates for 9.4 and 9.5 only apply to major version 9.
      '10.5.0' => [
        'installed_version' => '10.5.0',
        'fixture' => 'sec.10.5.0',
        'requirements_section_heading' => 'Checked',
        'message' => "Covered until 10.7.0 $release_coverage_message",
        'mock_date' => '',
      ],
      '10.4.0' => [
        'installed_version' => '10.4.0',
        'fixture' => 'sec.10.5.0',
        'requirements_section_heading' => 'Warnings found',
        'message' => "Covered until 10.6.0 Update to 10.5 or higher soon to continue receiving security updates. $release_coverage_message",
        'mock_date' => '',
      ],
    ];
    return $test_cases;

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
    $this->config('update_test.settings')
      ->set('xml_map', ['drupal' => '0.0'])
      ->save();

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
    $this->config('update_test.settings')
      ->set('xml_map', ['drupal' => '0.0'])
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
    $this->config('update_test.settings')
      ->set('xml_map', ['drupal' => '0.1'])
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
    $this->config('update_test.settings')
      ->set('xml_map', ['drupal' => 'sec.0.2'])
      ->save();

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
    $this->config('update_test.settings')
      ->set('xml_map', ['drupal' => '0.1'])
      ->save();

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
    $this->config('update_test.settings')
      ->set('xml_map', ['drupal' => 'broken'])
      ->save();

    // This will retrieve broken updates.
    $this->cronRun();
    $this->drupalGet('admin/reports/status');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('There was a problem checking available updates for Drupal.');
    $this->config('update_test.settings')
      ->set('xml_map', ['drupal' => 'sec.0.2'])
      ->save();
    // Simulate the update_available_releases state expiring before cron is run
    // and the state is used by \Drupal\update\UpdateManager::getProjects().
    \Drupal::keyValueExpirable('update_available_releases')->deleteAll();
    // This cron run should retrieve fixed updates.
    $this->cronRun();
    $this->drupalGet('admin/structure');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('There is a security update available for your version of Drupal.');
  }

}
