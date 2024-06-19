<?php

declare(strict_types=1);

namespace Drupal\Tests\system\Functional\System;

use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\Traits\Core\CronRunTrait;
use Drupal\Tests\WaitTerminateTestTrait;

/**
 * Tests cron runs.
 *
 * @group system
 */
class CronRunTest extends BrowserTestBase {

  use CronRunTrait;
  use WaitTerminateTestTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'common_test',
    'common_test_cron_helper',
    'automated_cron',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests cron runs.
   */
  public function testCronRun(): void {
    // Run cron anonymously without any cron key.
    $this->drupalGet('cron');
    $this->assertSession()->statusCodeEquals(404);

    // Run cron anonymously with a random cron key.
    $key = $this->randomMachineName(16);
    $this->drupalGet('cron/' . $key);
    $this->assertSession()->statusCodeEquals(403);

    // Run cron anonymously with the valid cron key.
    $key = \Drupal::state()->get('system.cron_key');
    $this->drupalGet('cron/' . $key);
    $this->assertSession()->statusCodeEquals(204);
  }

  /**
   * Ensure that the automated cron run module is working.
   *
   * In these tests we do not use \Drupal::time()->getRequestTime() to track start time, because we
   * need the exact time when cron is triggered.
   */
  public function testAutomatedCron(): void {
    // To prevent race conditions between the admin_user login triggering cron
    // and updating its state, and this test doing the same thing, we use
    // \Drupal\Tests\WaitTerminateTestTrait::setWaitForTerminate.
    $this->setWaitForTerminate();

    // Test with a logged-in user; anonymous users likely don't cause Drupal to
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
    $this->assertSame($cron_last, \Drupal::state()->get('system.cron_last'), 'Cron does not run when the cron interval is not passed.');

    // Test if cron runs when the cron interval was passed.
    $cron_last = time() - 200;
    \Drupal::state()->set('system.cron_last', $cron_last);
    $this->drupalGet('');
    sleep(1);
    // Verify that cron runs when the cron interval has passed.
    $this->assertLessThan(\Drupal::state()->get('system.cron_last'), $cron_last);

    // Disable cron through the interface by setting the interval to zero.
    $this->drupalGet('admin/config/system/cron');
    $this->submitForm(['interval' => 0], 'Save configuration');
    $this->assertSession()->pageTextContains('The configuration options have been saved.');
    $this->drupalLogout();

    // Test if cron does not run when the cron interval is set to zero.
    $cron_last = time() - 200;
    \Drupal::state()->set('system.cron_last', $cron_last);
    $this->drupalGet('');
    $this->assertSame($cron_last, \Drupal::state()->get('system.cron_last'), 'Cron does not run when the cron threshold is disabled.');
  }

  /**
   * Make sure exceptions thrown on hook_cron() don't affect other modules.
   */
  public function testCronExceptions(): void {
    \Drupal::state()->delete('common_test.cron');
    // The common_test module throws an exception. If it isn't caught, the tests
    // won't finish successfully.
    // The common_test_cron_helper module sets the 'common_test_cron' variable.
    $this->cronRun();
    $result = \Drupal::state()->get('common_test.cron');
    $this->assertEquals('success', $result, 'Cron correctly handles exceptions thrown during hook_cron() invocations.');
  }

  /**
   * Make sure the cron UI reads from the state storage.
   */
  public function testCronUI(): void {
    $admin_user = $this->drupalCreateUser(['administer site configuration']);
    $this->drupalLogin($admin_user);
    $this->drupalGet('admin/config/system/cron');
    // Don't use REQUEST to calculate the exact time, because that will
    // fail randomly. Look for the word 'years', because without a timestamp,
    // the time will start at 1 January 1970.
    $this->assertSession()->pageTextNotContains('years');

    $cron_last = time() - 200;
    \Drupal::state()->set('system.cron_last', $cron_last);

    $this->submitForm([], 'Save configuration');
    $this->assertSession()->pageTextContains('The configuration options have been saved.');
    $this->assertSession()->addressEquals('admin/config/system/cron');

    // Check that cron does not run when saving the configuration form.
    $this->assertEquals($cron_last, \Drupal::state()->get('system.cron_last'), 'Cron does not run when saving the configuration form.');

    // Check that cron runs when triggered manually.
    $this->submitForm([], 'Run cron');
    // Verify that cron runs when triggered manually.
    $this->assertLessThan(\Drupal::state()->get('system.cron_last'), $cron_last);
  }

  /**
   * Ensure that the manual cron run is working.
   */
  public function testManualCron(): void {
    $admin_user = $this->drupalCreateUser(['administer site configuration']);
    $this->drupalLogin($admin_user);

    $this->drupalGet('admin/reports/status/run-cron');
    $this->assertSession()->statusCodeEquals(403);

    $this->drupalGet('admin/reports/status');
    $this->clickLink('Run cron');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Cron ran successfully.');
  }

}
