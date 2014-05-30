<?php

/**
 * @file
 * Contains \Drupal\update\Tests\UpdateDeleteFileIfStaleTest.
 */

namespace Drupal\update\Tests;

/**
 * Provides tests for update_delete_file_if_stale().
 */
class UpdateDeleteFileIfStaleTest extends UpdateTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('update');

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'name' => 'Deleting obsolete files tests.',
      'description' => 'Tests the update_delete_file_if_stale() function.',
      'group' => 'Update',
    );
  }

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
  }

  /**
   * Tests the deletion of stale files.
   */
  function testUpdateDeleteFileIfStale() {
    $file_name = file_unmanaged_save_data($this->randomName());
    $this->assertNotNull($file_name);

    // During testing the file change and the stale checking occurs in the same
    // request, so the beginning of request will be before the file changes and
    // REQUEST_TIME - $filectime is negative. Set the maximum age to a number
    // even smaller than that.
    $this->container->get('config.factory')
      ->get('system.file')
      ->set('temporary_maximum_age', -100000)
      ->save();

    $file_path = drupal_realpath($file_name);
    update_delete_file_if_stale($file_path);

    $this->assertFalse(is_file($file_path));
  }

}
