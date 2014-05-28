<?php

/**
 * @file
 * Definition of Drupal\file\Tests\UsageTest.
 */

namespace Drupal\file\Tests;

/**
 * Tests file usage functions.
 */
class UsageTest extends FileManagedUnitTestBase {
  public static function getInfo() {
    return array(
      'name' => 'File usage',
      'description' => 'Tests the file usage functions.',
      'group' => 'File Managed API',
    );
  }

  /**
   * Tests \Drupal\file\FileUsage\DatabaseFileUsageBackend::listUsage().
   */
  function testGetUsage() {
    $file = $this->createFile();
    db_insert('file_usage')
      ->fields(array(
        'fid' => $file->id(),
        'module' => 'testing',
        'type' => 'foo',
        'id' => 1,
        'count' => 1
      ))
      ->execute();
    db_insert('file_usage')
      ->fields(array(
        'fid' => $file->id(),
        'module' => 'testing',
        'type' => 'bar',
        'id' => 2,
        'count' => 2
      ))
      ->execute();

    $usage = $this->container->get('file.usage')->listUsage($file);

    $this->assertEqual(count($usage['testing']), 2, 'Returned the correct number of items.');
    $this->assertTrue(isset($usage['testing']['foo'][1]), 'Returned the correct id.');
    $this->assertTrue(isset($usage['testing']['bar'][2]), 'Returned the correct id.');
    $this->assertEqual($usage['testing']['foo'][1], 1, 'Returned the correct count.');
    $this->assertEqual($usage['testing']['bar'][2], 2, 'Returned the correct count.');
  }

  /**
   * Tests \Drupal\file\FileUsage\DatabaseFileUsageBackend::add().
   */
  function testAddUsage() {
    $file = $this->createFile();
    $file_usage = $this->container->get('file.usage');
    $file_usage->add($file, 'testing', 'foo', 1);
    // Add the file twice to ensure that the count is incremented rather than
    // creating additional records.
    $file_usage->add($file, 'testing', 'bar', 2);
    $file_usage->add($file, 'testing', 'bar', 2);

    $usage = db_select('file_usage', 'f')
      ->fields('f')
      ->condition('f.fid', $file->id())
      ->execute()
      ->fetchAllAssoc('id');
    $this->assertEqual(count($usage), 2, 'Created two records');
    $this->assertEqual($usage[1]->module, 'testing', 'Correct module');
    $this->assertEqual($usage[2]->module, 'testing', 'Correct module');
    $this->assertEqual($usage[1]->type, 'foo', 'Correct type');
    $this->assertEqual($usage[2]->type, 'bar', 'Correct type');
    $this->assertEqual($usage[1]->count, 1, 'Correct count');
    $this->assertEqual($usage[2]->count, 2, 'Correct count');
  }

  /**
   * Tests \Drupal\file\FileUsage\DatabaseFileUsageBackend::delete().
   */
  function testRemoveUsage() {
    $file = $this->createFile();
    $file_usage = $this->container->get('file.usage');
    db_insert('file_usage')
      ->fields(array(
        'fid' => $file->id(),
        'module' => 'testing',
        'type' => 'bar',
        'id' => 2,
        'count' => 3,
      ))
      ->execute();

    // Normal decrement.
    $file_usage->delete($file, 'testing', 'bar', 2);
    $count = db_select('file_usage', 'f')
      ->fields('f', array('count'))
      ->condition('f.fid', $file->id())
      ->execute()
      ->fetchField();
    $this->assertEqual(2, $count, 'The count was decremented correctly.');

    // Multiple decrement and removal.
    $file_usage->delete($file, 'testing', 'bar', 2, 2);
    $count = db_select('file_usage', 'f')
      ->fields('f', array('count'))
      ->condition('f.fid', $file->id())
      ->execute()
      ->fetchField();
    $this->assertIdentical(FALSE, $count, 'The count was removed entirely when empty.');

    // Non-existent decrement.
    $file_usage->delete($file, 'testing', 'bar', 2);
    $count = db_select('file_usage', 'f')
      ->fields('f', array('count'))
      ->condition('f.fid', $file->id())
      ->execute()
      ->fetchField();
    $this->assertIdentical(FALSE, $count, 'Decrementing non-exist record complete.');
  }

  /**
   * Create files for all the possible combinations of age and status.
   *
   * We are using UPDATE statements because using the API would set the
   * timestamp.
   */
  function createTempFiles() {
    // Temporary file that is old.
    $temp_old = file_save_data('');
    db_update('file_managed')
      ->fields(array(
        'status' => 0,
        'changed' => REQUEST_TIME - $this->container->get('config.factory')->get('system.file')->get('temporary_maximum_age') - 1,
      ))
      ->condition('fid', $temp_old->id())
      ->execute();
    $this->assertTrue(file_exists($temp_old->getFileUri()), 'Old temp file was created correctly.');

    // Temporary file that is new.
    $temp_new = file_save_data('');
    db_update('file_managed')
      ->fields(array('status' => 0))
      ->condition('fid', $temp_new->id())
      ->execute();
    $this->assertTrue(file_exists($temp_new->getFileUri()), 'New temp file was created correctly.');

    // Permanent file that is old.
    $perm_old = file_save_data('');
    db_update('file_managed')
      ->fields(array('changed' => REQUEST_TIME - $this->container->get('config.factory')->get('system.file')->get('temporary_maximum_age') - 1))
      ->condition('fid', $temp_old->id())
      ->execute();
    $this->assertTrue(file_exists($perm_old->getFileUri()), 'Old permanent file was created correctly.');

    // Permanent file that is new.
    $perm_new = file_save_data('');
    $this->assertTrue(file_exists($perm_new->getFileUri()), 'New permanent file was created correctly.');
    return array($temp_old, $temp_new, $perm_old, $perm_new);
  }

  /**
   * Ensure that temporary files are removed by default.
   */
  function testTempFileCleanupDefault() {
    list($temp_old, $temp_new, $perm_old, $perm_new) = $this->createTempFiles();

    // Run cron and then ensure that only the old, temp file was deleted.
    $this->container->get('cron')->run();
    $this->assertFalse(file_exists($temp_old->getFileUri()), 'Old temp file was correctly removed.');
    $this->assertTrue(file_exists($temp_new->getFileUri()), 'New temp file was correctly ignored.');
    $this->assertTrue(file_exists($perm_old->getFileUri()), 'Old permanent file was correctly ignored.');
    $this->assertTrue(file_exists($perm_new->getFileUri()), 'New permanent file was correctly ignored.');
  }

  /**
   * Ensure that temporary files are kept as configured.
   */
  function testTempFileNoCleanup() {
    list($temp_old, $temp_new, $perm_old, $perm_new) = $this->createTempFiles();

    // Set the max age to 0, meaning no temporary files will be deleted.
    \Drupal::config('system.file')
      ->set('temporary_maximum_age', 0)
      ->save();

    // Run cron and then ensure that no file was deleted.
    $this->container->get('cron')->run();
    $this->assertTrue(file_exists($temp_old->getFileUri()), 'Old temp file was correctly ignored.');
    $this->assertTrue(file_exists($temp_new->getFileUri()), 'New temp file was correctly ignored.');
    $this->assertTrue(file_exists($perm_old->getFileUri()), 'Old permanent file was correctly ignored.');
    $this->assertTrue(file_exists($perm_new->getFileUri()), 'New permanent file was correctly ignored.');
  }

  /**
   * Ensure that temporary files are kept as configured.
   */
  function testTempFileCustomCleanup() {
    list($temp_old, $temp_new, $perm_old, $perm_new) = $this->createTempFiles();

    // Set the max age to older than default.
    \Drupal::config('system.file')
      ->set('temporary_maximum_age', 21600 + 2)
      ->save();

    // Run cron and then ensure that more files were deleted.
    $this->container->get('cron')->run();
    $this->assertTrue(file_exists($temp_old->getFileUri()), 'Old temp file was correctly ignored.');
    $this->assertTrue(file_exists($temp_new->getFileUri()), 'New temp file was correctly ignored.');
    $this->assertTrue(file_exists($perm_old->getFileUri()), 'Old permanent file was correctly ignored.');
    $this->assertTrue(file_exists($perm_new->getFileUri()), 'New permanent file was correctly ignored.');
  }
}
