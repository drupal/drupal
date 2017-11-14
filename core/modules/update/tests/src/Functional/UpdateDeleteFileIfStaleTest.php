<?php

namespace Drupal\Tests\update\Functional;

/**
 * Tests the update_delete_file_if_stale() function.
 *
 * @group update
 */
class UpdateDeleteFileIfStaleTest extends UpdateTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['update'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
  }

  /**
   * Tests the deletion of stale files.
   */
  public function testUpdateDeleteFileIfStale() {
    $file_name = file_unmanaged_save_data($this->randomMachineName());
    $this->assertNotNull($file_name);

    // During testing the file change and the stale checking occurs in the same
    // request, so the beginning of request will be before the file changes and
    // REQUEST_TIME - $filectime is negative. Set the maximum age to a number
    // even smaller than that.
    $this->config('system.file')
      ->set('temporary_maximum_age', -100000)
      ->save();

    $file_path = \Drupal::service('file_system')->realpath($file_name);
    update_delete_file_if_stale($file_path);

    $this->assertFalse(is_file($file_path));
  }

}
