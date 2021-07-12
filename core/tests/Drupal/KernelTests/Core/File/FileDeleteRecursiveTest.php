<?php

namespace Drupal\KernelTests\Core\File;

/**
 * Tests the unmanaged file delete recursive function.
 *
 * @group File
 */
class FileDeleteRecursiveTest extends FileTestBase {

  /**
   * Delete a normal file.
   */
  public function testSingleFile() {
    // Create a file for testing
    $filepath = 'public://' . $this->randomMachineName();
    file_put_contents($filepath, '');

    // Delete the file.
    $this->assertTrue(\Drupal::service('file_system')->deleteRecursive($filepath), 'Function reported success.');
    $this->assertFileDoesNotExist($filepath);
  }

  /**
   * Try deleting an empty directory.
   */
  public function testEmptyDirectory() {
    // A directory to operate on.
    $directory = $this->createDirectory();

    // Delete the directory.
    $this->assertTrue(\Drupal::service('file_system')->deleteRecursive($directory), 'Function reported success.');
    $this->assertDirectoryDoesNotExist($directory);
  }

  /**
   * Try deleting a directory with some files.
   */
  public function testDirectory() {
    // A directory to operate on.
    $directory = $this->createDirectory();
    $filepathA = $directory . '/A';
    $filepathB = $directory . '/B';
    file_put_contents($filepathA, '');
    file_put_contents($filepathB, '');

    // Delete the directory.
    $this->assertTrue(\Drupal::service('file_system')->deleteRecursive($directory), 'Function reported success.');
    $this->assertFileDoesNotExist($filepathA);
    $this->assertFileDoesNotExist($filepathB);
    $this->assertDirectoryDoesNotExist($directory);
  }

  /**
   * Try deleting subdirectories with some files.
   */
  public function testSubDirectory() {
    // A directory to operate on.
    $directory = $this->createDirectory();
    $subdirectory = $this->createDirectory($directory . '/sub');
    $filepathA = $directory . '/A';
    $filepathB = $subdirectory . '/B';
    file_put_contents($filepathA, '');
    file_put_contents($filepathB, '');

    // Delete the directory.
    $this->assertTrue(\Drupal::service('file_system')->deleteRecursive($directory), 'Function reported success.');
    $this->assertFileDoesNotExist($filepathA);
    $this->assertFileDoesNotExist($filepathB);
    $this->assertDirectoryDoesNotExist($subdirectory);
    $this->assertDirectoryDoesNotExist($directory);
  }

}
