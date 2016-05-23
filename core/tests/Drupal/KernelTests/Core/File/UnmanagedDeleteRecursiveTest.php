<?php

namespace Drupal\KernelTests\Core\File;

/**
 * Tests the unmanaged file delete recursive function.
 *
 * @group File
 */
class UnmanagedDeleteRecursiveTest extends FileTestBase {
  /**
   * Delete a normal file.
   */
  function testSingleFile() {
    // Create a file for testing
    $filepath = file_default_scheme() . '://' . $this->randomMachineName();
    file_put_contents($filepath, '');

    // Delete the file.
    $this->assertTrue(file_unmanaged_delete_recursive($filepath), 'Function reported success.');
    $this->assertFalse(file_exists($filepath), 'Test file has been deleted.');
  }

  /**
   * Try deleting an empty directory.
   */
  function testEmptyDirectory() {
    // A directory to operate on.
    $directory = $this->createDirectory();

    // Delete the directory.
    $this->assertTrue(file_unmanaged_delete_recursive($directory), 'Function reported success.');
    $this->assertFalse(file_exists($directory), 'Directory has been deleted.');
  }

  /**
   * Try deleting a directory with some files.
   */
  function testDirectory() {
    // A directory to operate on.
    $directory = $this->createDirectory();
    $filepathA = $directory . '/A';
    $filepathB = $directory . '/B';
    file_put_contents($filepathA, '');
    file_put_contents($filepathB, '');

    // Delete the directory.
    $this->assertTrue(file_unmanaged_delete_recursive($directory), 'Function reported success.');
    $this->assertFalse(file_exists($filepathA), 'Test file A has been deleted.');
    $this->assertFalse(file_exists($filepathB), 'Test file B has been deleted.');
    $this->assertFalse(file_exists($directory), 'Directory has been deleted.');
  }

  /**
   * Try deleting subdirectories with some files.
   */
  function testSubDirectory() {
    // A directory to operate on.
    $directory = $this->createDirectory();
    $subdirectory = $this->createDirectory($directory . '/sub');
    $filepathA = $directory . '/A';
    $filepathB = $subdirectory . '/B';
    file_put_contents($filepathA, '');
    file_put_contents($filepathB, '');

    // Delete the directory.
    $this->assertTrue(file_unmanaged_delete_recursive($directory), 'Function reported success.');
    $this->assertFalse(file_exists($filepathA), 'Test file A has been deleted.');
    $this->assertFalse(file_exists($filepathB), 'Test file B has been deleted.');
    $this->assertFalse(file_exists($subdirectory), 'Subdirectory has been deleted.');
    $this->assertFalse(file_exists($directory), 'Directory has been deleted.');
  }

}
