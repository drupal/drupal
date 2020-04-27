<?php

namespace Drupal\Tests\file\Kernel;

use Drupal\Core\Database\Database;
use Drupal\file\Entity\File;

/**
 * Tests the file delete function.
 *
 * @group file
 */
class DeleteTest extends FileManagedUnitTestBase {

  /**
   * Tries deleting a normal file (as opposed to a directory, symlink, etc).
   */
  public function testUnused() {
    $file = $this->createFile();

    // Check that deletion removes the file and database record.
    $this->assertFileExists($file->getFileUri());
    $file->delete();
    $this->assertFileHooksCalled(['delete']);
    $this->assertFileNotExists($file->getFileUri());
    $this->assertNull(File::load($file->id()), 'File was removed from the database.');
  }

  /**
   * Tries deleting a file that is in use.
   */
  public function testInUse() {
    // This test expects unused managed files to be marked as a temporary file
    // and then deleted up by file_cron().
    $this->config('file.settings')
      ->set('make_unused_managed_files_temporary', TRUE)
      ->save();
    $file = $this->createFile();
    $file_usage = $this->container->get('file.usage');
    $file_usage->add($file, 'testing', 'test', 1);
    $file_usage->add($file, 'testing', 'test', 1);

    $file_usage->delete($file, 'testing', 'test', 1);
    $usage = $file_usage->listUsage($file);
    $this->assertEqual($usage['testing']['test'], [1 => 1], 'Test file is still in use.');
    $this->assertFileExists($file->getFileUri());
    $this->assertNotEmpty(File::load($file->id()), 'File still exists in the database.');

    // Clear out the call to hook_file_load().
    file_test_reset();

    $file_usage->delete($file, 'testing', 'test', 1);
    $usage = $file_usage->listUsage($file);
    $this->assertFileHooksCalled(['load', 'update']);
    $this->assertTrue(empty($usage), 'File usage data was removed.');
    $this->assertFileExists($file->getFileUri());
    $file = File::load($file->id());
    $this->assertNotEmpty($file, 'File still exists in the database.');
    $this->assertTrue($file->isTemporary(), 'File is temporary.');
    file_test_reset();

    // Call file_cron() to clean up the file. Make sure the changed timestamp
    // of the file is older than the system.file.temporary_maximum_age
    // configuration value. We use an UPDATE statement because using the API
    // would set the timestamp.
    Database::getConnection()->update('file_managed')
      ->fields([
        'changed' => REQUEST_TIME - ($this->config('system.file')->get('temporary_maximum_age') + 1),
      ])
      ->condition('fid', $file->id())
      ->execute();
    \Drupal::service('cron')->run();

    // file_cron() loads
    $this->assertFileHooksCalled(['delete']);
    $this->assertFileNotExists($file->getFileUri());
    $this->assertNull(File::load($file->id()), 'File was removed from the database.');
  }

  /**
   * Tries to run cron deletion on file deleted from the file-system.
   */
  public function testCronDeleteNonExistingTemporary() {
    $file = $this->createFile();
    // Delete the file, but leave it in the file_managed table.
    \Drupal::service('file_system')->delete($file->getFileUri());
    $this->assertFileNotExists($file->getFileUri());
    $this->assertInstanceOf(File::class, File::load($file->id()));

    // Call file_cron() to clean up the file. Make sure the changed timestamp
    // of the file is older than the system.file.temporary_maximum_age
    // configuration value.
    \Drupal::database()->update('file_managed')
      ->fields([
        'changed' => REQUEST_TIME - ($this->config('system.file')->get('temporary_maximum_age') + 1),
      ])
      ->condition('fid', $file->id())
      ->execute();
    \Drupal::service('cron')->run();

    $this->assertNull(File::load($file->id()), 'File was removed from the database.');
  }

}
