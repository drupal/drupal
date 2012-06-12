<?php

/**
 * @file
 * Definition of Drupal\system\Tests\System\CronRunTest.
 */

namespace Drupal\system\Tests\System;

use Drupal\simpletest\WebTestBase;

class CronRunTest extends WebTestBase {
  public static function getInfo() {
    return array(
      'name' => 'Cron run',
      'description' => 'Test cron run.',
      'group' => 'System',
    );
  }

  function setUp() {
    parent::setUp(array('common_test', 'common_test_cron_helper'));
  }

  /**
   * Test cron runs.
   */
  function testCronRun() {
    global $base_url;

    // Run cron anonymously without any cron key.
    $this->drupalGet('cron');
    $this->assertResponse(404);

    // Run cron anonymously with a random cron key.
    $key = $this->randomName(16);
    $this->drupalGet('cron/' . $key);
    $this->assertResponse(403);

    // Run cron anonymously with the valid cron key.
    $key = config('system.cron')->get('cron_key');
    $this->drupalGet('cron/' . $key);
    $this->assertResponse(204);
  }

  /**
   * Ensure that the automatic cron run feature is working.
   *
   * In these tests we do not use REQUEST_TIME to track start time, because we
   * need the exact time when cron is triggered.
   */
  function testAutomaticCron() {
    // Ensure cron does not run when the cron threshold is enabled and was
    // not passed.
    $cron_last = time();
    $cron_safe_threshold = 100;
    variable_set('cron_last', $cron_last);
    config('system.cron')
      ->set('cron_safe_threshold', $cron_safe_threshold)
      ->save();
    $this->drupalGet('');
    $this->assertTrue($cron_last == variable_get('cron_last', NULL), t('Cron does not run when the cron threshold is not passed.'));

    // Test if cron runs when the cron threshold was passed.
    $cron_last = time() - 200;
    variable_set('cron_last', $cron_last);
    $this->drupalGet('');
    sleep(1);
    $this->assertTrue($cron_last < variable_get('cron_last', NULL), t('Cron runs when the cron threshold is passed.'));

    // Disable the cron threshold through the interface.
    $admin_user = $this->drupalCreateUser(array('administer site configuration'));
    $this->drupalLogin($admin_user);
    $this->drupalPost('admin/config/system/cron', array('cron_safe_threshold' => 0), t('Save configuration'));
    $this->assertText(t('The configuration options have been saved.'));
    $this->drupalLogout();

    // Test if cron does not run when the cron threshold is disabled.
    $cron_last = time() - 200;
    variable_set('cron_last', $cron_last);
    $this->drupalGet('');
    $this->assertTrue($cron_last == variable_get('cron_last', NULL), t('Cron does not run when the cron threshold is disabled.'));
  }

  /**
   * Ensure that temporary files are removed.
   *
   * Create files for all the possible combinations of age and status. We are
   * using UPDATE statements because using the API would set the timestamp.
   */
  function testTempFileCleanup() {
    // Temporary file that is older than DRUPAL_MAXIMUM_TEMP_FILE_AGE.
    $temp_old = file_save_data('');
    db_update('file_managed')
      ->fields(array(
        'status' => 0,
        'timestamp' => 1,
      ))
      ->condition('fid', $temp_old->fid)
      ->execute();
    $this->assertTrue(file_exists($temp_old->uri), t('Old temp file was created correctly.'));

    // Temporary file that is less than DRUPAL_MAXIMUM_TEMP_FILE_AGE.
    $temp_new = file_save_data('');
    db_update('file_managed')
      ->fields(array('status' => 0))
      ->condition('fid', $temp_new->fid)
      ->execute();
    $this->assertTrue(file_exists($temp_new->uri), t('New temp file was created correctly.'));

    // Permanent file that is older than DRUPAL_MAXIMUM_TEMP_FILE_AGE.
    $perm_old = file_save_data('');
    db_update('file_managed')
      ->fields(array('timestamp' => 1))
      ->condition('fid', $temp_old->fid)
      ->execute();
    $this->assertTrue(file_exists($perm_old->uri), t('Old permanent file was created correctly.'));

    // Permanent file that is newer than DRUPAL_MAXIMUM_TEMP_FILE_AGE.
    $perm_new = file_save_data('');
    $this->assertTrue(file_exists($perm_new->uri), t('New permanent file was created correctly.'));

    // Run cron and then ensure that only the old, temp file was deleted.
    $this->cronRun();
    $this->assertFalse(file_exists($temp_old->uri), t('Old temp file was correctly removed.'));
    $this->assertTrue(file_exists($temp_new->uri), t('New temp file was correctly ignored.'));
    $this->assertTrue(file_exists($perm_old->uri), t('Old permanent file was correctly ignored.'));
    $this->assertTrue(file_exists($perm_new->uri), t('New permanent file was correctly ignored.'));
  }

  /**
   * Make sure exceptions thrown on hook_cron() don't affect other modules.
   */
  function testCronExceptions() {
    variable_del('common_test_cron');
    // The common_test module throws an exception. If it isn't caught, the tests
    // won't finish successfully.
    // The common_test_cron_helper module sets the 'common_test_cron' variable.
    $this->cronRun();
    $result = variable_get('common_test_cron');
    $this->assertEqual($result, 'success', t('Cron correctly handles exceptions thrown during hook_cron() invocations.'));
  }
}
