<?php

declare(strict_types=1);

namespace Drupal\Tests\update\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests the update_delete_file_if_stale() function.
 *
 * @group update
 * @group legacy
 */
class UpdateDeleteFileIfStaleTest extends KernelTestBase {

  /**
   * Disable strict config schema checking.
   *
   * This test requires saving invalid configuration. This allows for the
   * simulation of a temporary file becoming stale.
   *
   * @var bool
   */
  protected $strictConfigSchema = FALSE;

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
  public function testUpdateDeleteFileIfStale(): void {
    $file_system = $this->container->get('file_system');

    $file_name = $file_system->saveData($this->randomMachineName(), 'public://');
    $this->assertNotNull($file_name);
    $file_path = $file_system->realpath($file_name);

    // During testing, the file change and the stale checking occurs in the same
    // request, so the beginning of request will be before the file changes and
    // \Drupal::time()->getRequestTime() - $filectime is negative or zero.
    // Set the maximum age to a number even smaller than that.
    $this->config('system.file')
      ->set('temporary_maximum_age', 100000)
      ->save();

    // First test that the file is not stale and thus not deleted.
    $deleted = update_delete_file_if_stale($file_path);
    $this->assertFalse($deleted);
    $this->assertFileExists($file_path);

    // Set the maximum age to a number smaller than
    // \Drupal::time()->getRequestTime() - $filectime.
    $this->config('system.file')
      ->set('temporary_maximum_age', -100000)
      ->save();

    // Now attempt to delete the file; as it should be considered stale, this
    // attempt should succeed.
    $deleted = update_delete_file_if_stale($file_path);
    $this->assertTrue($deleted);
    $this->assertFileDoesNotExist($file_path);
  }

}
