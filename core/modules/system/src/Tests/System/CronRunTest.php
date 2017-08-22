<?php

namespace Drupal\system\Tests\System;

use Drupal\simpletest\WebTestBase;

/**
 * Tests cron runs.
 *
 * @group system
 */
class CronRunTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['common_test', 'common_test_cron_helper', 'automated_cron'];

  /**
   * Test cron runs.
   */
  public function testCronRun() {
    // Run cron anonymously without any cron key.
    $this->drupalGet('cron');
    $this->assertResponse(404);

    // Run cron anonymously with a random cron key.
    $key = $this->randomMachineName(16);
    $this->drupalGet('cron/' . $key);
    $this->assertResponse(403);

    // Run cron anonymously with the valid cron key.
    $key = \Drupal::state()->get('system.cron_key');
    $this->drupalGet('cron/' . $key);
    $this->assertResponse(204);
  }

  /**
   * Ensure that the automated cron run module is working.
   *
   * In these tests we do not use REQUEST_TIME to track start time, because we
   * need the exact time when cron is triggered.
   */
  public function testAutomatedCron() {
    // Test with a logged in user; anonymous users likely don't cause Drupal to
    // fully bootstrap, because of the internal page cache or an external
    // reverse proxy. Reuse this user for disabling cron later in the test.
    $admin_user = $this->drupalCreateUser(['administer site configuration']);
    $this->drupalLogin($admin_user);

    // Ensure cron does not run when a non-zero cron interval is specified and
    // was not passed.
    $cron_last = time();
    $cron_safe_interval = 100;
    \Drupal::state()->set('system.cron_last', $cron_last);
    $this->config('automated_cron.settings')
      ->set('interval', $cron_safe_interval)
      ->save();
    $this->drupalGet('');
    $this->assertTrue($cron_last == \Drupal::state()->get('system.cron_last'), 'Cron does not run when the cron interval is not passed.');

    // Test if cron runs when the cron interval was passed.
    $cron_last = time() - 200;
    \Drupal::state()->set('system.cron_last', $cron_last);
    $this->drupalGet('');
    sleep(1);
    $this->assertTrue($cron_last < \Drupal::state()->get('system.cron_last'), 'Cron runs when the cron interval is passed.');

    // Disable cron through the interface by setting the interval to zero.
    $this->drupalPostForm('admin/config/system/cron', ['interval' => 0], t('Save configuration'));
    $this->assertText(t('The configuration options have been saved.'));
    $this->drupalLogout();

    // Test if cron does not run when the cron interval is set to zero.
    $cron_last = time() - 200;
    \Drupal::state()->set('system.cron_last', $cron_last);
    $this->drupalGet('');
    $this->assertTrue($cron_last == \Drupal::state()->get('system.cron_last'), 'Cron does not run when the cron threshold is disabled.');
  }

  /**
   * Make sure exceptions thrown on hook_cron() don't affect other modules.
   */
  public function testCronExceptions() {
    \Drupal::state()->delete('common_test.cron');
    // The common_test module throws an exception. If it isn't caught, the tests
    // won't finish successfully.
    // The common_test_cron_helper module sets the 'common_test_cron' variable.
    $this->cronRun();
    $result = \Drupal::state()->get('common_test.cron');
    $this->assertEqual($result, 'success', 'Cron correctly handles exceptions thrown during hook_cron() invocations.');
  }

  /**
   * Make sure the cron UI reads from the state storage.
   */
  public function testCronUI() {
    $admin_user = $this->drupalCreateUser(['administer site configuration']);
    $this->drupalLogin($admin_user);
    $this->drupalGet('admin/config/system/cron');
    // Don't use REQUEST to calculate the exact time, because that will
    // fail randomly. Look for the word 'years', because without a timestamp,
    // the time will start at 1 January 1970.
    $this->assertNoText('years');

    $cron_last = time() - 200;
    \Drupal::state()->set('system.cron_last', $cron_last);

    $this->drupalPostForm(NULL, [], 'Save configuration');
    $this->assertText('The configuration options have been saved.');
    $this->assertUrl('admin/config/system/cron');

    // Check that cron does not run when saving the configuration form.
    $this->assertEqual($cron_last, \Drupal::state()->get('system.cron_last'), 'Cron does not run when saving the configuration form.');

    // Check that cron runs when triggered manually.
    $this->drupalPostForm(NULL, [], 'Run cron');
    $this->assertTrue($cron_last < \Drupal::state()->get('system.cron_last'), 'Cron runs when triggered manually.');
  }

  /**
   * Ensure that the manual cron run is working.
   */
  public function testManualCron() {
    $admin_user = $this->drupalCreateUser(['administer site configuration']);
    $this->drupalLogin($admin_user);

    $this->drupalGet('admin/reports/status/run-cron');
    $this->assertResponse(403);

    $this->drupalGet('admin/reports/status');
    $this->clickLink(t('Run cron'));
    $this->assertResponse(200);
    $this->assertText(t('Cron ran successfully.'));
  }

}
