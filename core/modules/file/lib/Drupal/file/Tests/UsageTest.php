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
        'changed' => 1,
      ))
      ->condition('fid', $temp_old->id())
      ->execute();
    $this->assertTrue(file_exists($temp_old->getFileUri()), 'Old temp file was created correctly.');

    // Temporary file that is less than DRUPAL_MAXIMUM_TEMP_FILE_AGE.
    $temp_new = file_save_data('');
    db_update('file_managed')
      ->fields(array('status' => 0))
      ->condition('fid', $temp_new->id())
      ->execute();
    $this->assertTrue(file_exists($temp_new->getFileUri()), 'New temp file was created correctly.');

    // Permanent file that is older than DRUPAL_MAXIMUM_TEMP_FILE_AGE.
    $perm_old = file_save_data('');
    db_update('file_managed')
      ->fields(array('changed' => 1))
      ->condition('fid', $temp_old->id())
      ->execute();
    $this->assertTrue(file_exists($perm_old->getFileUri()), 'Old permanent file was created correctly.');

    // Permanent file that is newer than DRUPAL_MAXIMUM_TEMP_FILE_AGE.
    $perm_new = file_save_data('');
    $this->assertTrue(file_exists($perm_new->getFileUri()), 'New permanent file was created correctly.');

    // Run cron and then ensure that only the old, temp file was deleted.
    $this->container->get('cron')->run();
    $this->assertFalse(file_exists($temp_old->getFileUri()), 'Old temp file was correctly removed.');
    $this->assertTrue(file_exists($temp_new->getFileUri()), 'New temp file was correctly ignored.');
    $this->assertTrue(file_exists($perm_old->getFileUri()), 'Old permanent file was correctly ignored.');
    $this->assertTrue(file_exists($perm_new->getFileUri()), 'New permanent file was correctly ignored.');
  }
}
