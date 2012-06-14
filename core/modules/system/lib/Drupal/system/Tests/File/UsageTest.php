<?php

/**
 * @file
 * Definition of Drupal\system\Tests\File\UsageTest.
 */

namespace Drupal\system\Tests\File;

/**
 * Tests file usage functions.
 */
class UsageTest extends FileTestBase {
  public static function getInfo() {
    return array(
      'name' => 'File usage',
      'description' => 'Tests the file usage functions.',
      'group' => 'File',
    );
  }

  /**
   * Tests file_usage_list().
   */
  function testGetUsage() {
    $file = $this->createFile();
    db_insert('file_usage')
      ->fields(array(
        'fid' => $file->fid,
        'module' => 'testing',
        'type' => 'foo',
        'id' => 1,
        'count' => 1
      ))
      ->execute();
    db_insert('file_usage')
      ->fields(array(
        'fid' => $file->fid,
        'module' => 'testing',
        'type' => 'bar',
        'id' => 2,
        'count' => 2
      ))
      ->execute();

    $usage = file_usage_list($file);

    $this->assertEqual(count($usage['testing']), 2, t('Returned the correct number of items.'));
    $this->assertTrue(isset($usage['testing']['foo'][1]), t('Returned the correct id.'));
    $this->assertTrue(isset($usage['testing']['bar'][2]), t('Returned the correct id.'));
    $this->assertEqual($usage['testing']['foo'][1], 1, t('Returned the correct count.'));
    $this->assertEqual($usage['testing']['bar'][2], 2, t('Returned the correct count.'));
  }

  /**
   * Tests file_usage_add().
   */
  function testAddUsage() {
    $file = $this->createFile();
    file_usage_add($file, 'testing', 'foo', 1);
    // Add the file twice to ensure that the count is incremented rather than
    // creating additional records.
    file_usage_add($file, 'testing', 'bar', 2);
    file_usage_add($file, 'testing', 'bar', 2);

    $usage = db_select('file_usage', 'f')
      ->fields('f')
      ->condition('f.fid', $file->fid)
      ->execute()
      ->fetchAllAssoc('id');
    $this->assertEqual(count($usage), 2, t('Created two records'));
    $this->assertEqual($usage[1]->module, 'testing', t('Correct module'));
    $this->assertEqual($usage[2]->module, 'testing', t('Correct module'));
    $this->assertEqual($usage[1]->type, 'foo', t('Correct type'));
    $this->assertEqual($usage[2]->type, 'bar', t('Correct type'));
    $this->assertEqual($usage[1]->count, 1, t('Correct count'));
    $this->assertEqual($usage[2]->count, 2, t('Correct count'));
  }

  /**
   * Tests file_usage_delete().
   */
  function testRemoveUsage() {
    $file = $this->createFile();
    db_insert('file_usage')
      ->fields(array(
        'fid' => $file->fid,
        'module' => 'testing',
        'type' => 'bar',
        'id' => 2,
        'count' => 3,
      ))
      ->execute();

    // Normal decrement.
    file_usage_delete($file, 'testing', 'bar', 2);
    $count = db_select('file_usage', 'f')
      ->fields('f', array('count'))
      ->condition('f.fid', $file->fid)
      ->execute()
      ->fetchField();
    $this->assertEqual(2, $count, t('The count was decremented correctly.'));

    // Multiple decrement and removal.
    file_usage_delete($file, 'testing', 'bar', 2, 2);
    $count = db_select('file_usage', 'f')
      ->fields('f', array('count'))
      ->condition('f.fid', $file->fid)
      ->execute()
      ->fetchField();
    $this->assertIdentical(FALSE, $count, t('The count was removed entirely when empty.'));

    // Non-existent decrement.
    file_usage_delete($file, 'testing', 'bar', 2);
    $count = db_select('file_usage', 'f')
      ->fields('f', array('count'))
      ->condition('f.fid', $file->fid)
      ->execute()
      ->fetchField();
    $this->assertIdentical(FALSE, $count, t('Decrementing non-exist record complete.'));
  }
}
