<?php

namespace Drupal\KernelTests\Core\File;

use Drupal\Core\File\Exception\NotRegularFileException;

/**
 * Tests the unmanaged file delete function.
 *
 * @group File
 */
class FileDeleteTest extends FileTestBase {

  /**
   * Delete a normal file.
   */
  public function testNormal() {
    // Create a file for testing
    $uri = $this->createUri();

    // Delete a regular file
    $this->assertTrue(\Drupal::service('file_system')->delete($uri), 'Deleted worked.');
    $this->assertFalse(file_exists($uri), 'Test file has actually been deleted.');
  }

  /**
   * Try deleting a missing file.
   */
  public function testMissing() {
    // Try to delete a non-existing file
    $this->assertTrue(\Drupal::service('file_system')->delete('public://' . $this->randomMachineName()), 'Returns true when deleting a non-existent file.');
  }

  /**
   * Try deleting a directory.
   */
  public function testDirectory() {
    // A directory to operate on.
    $directory = $this->createDirectory();

    // Try to delete a directory.
    try {
      \Drupal::service('file_system')->delete($directory);
      $this->fail('Expected NotRegularFileException');
    }
    catch (NotRegularFileException $e) {
      // Ignore.
    }
    $this->assertTrue(file_exists($directory), 'Directory has not been deleted.');
  }

}
