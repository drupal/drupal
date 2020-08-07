<?php

namespace Drupal\Tests\update\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests the update_delete_file_if_stale() function.
 *
 * @group update
 */
class UpdateDeleteFileIfStaleTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'update',
  ];

  /**
   * Tests the deletion of stale files.
   */
  public function testUpdateDeleteFileIfStale() {
    $file_system = $this->container->get('file_system');

    $file_name = $file_system->saveData($this->randomMachineName(), 'public://');
    $this->assertNotNull($file_name);
    $file_path = $file_system->realpath($file_name);

    // During testing, the file change and the stale checking occurs in the same
    // request, so the beginning of request will be before the file changes and
    // REQUEST_TIME - $filectime is negative or zero. Set the maximum age to a
    // number greater than that.
    $this->config('system.file')
      ->set('temporary_maximum_age', 100000)
      ->save();

    // First test that the file is not stale and thus not deleted.
    $deleted = update_delete_file_if_stale($file_path);
    $this->assertFalse($deleted);
    $this->assertFileExists($file_path);

    // Set the maximum age to a number smaller than REQUEST_TIME - $filectime.
    $this->config('system.file')
      ->set('temporary_maximum_age', -100000)
      ->save();

    // Now attempt to delete the file; as it should be considered stale, this
    // attempt should succeed.
    $deleted = update_delete_file_if_stale($file_path);
    $this->assertTrue($deleted);
    $this->assertFileNotExists($file_path);
  }

}
