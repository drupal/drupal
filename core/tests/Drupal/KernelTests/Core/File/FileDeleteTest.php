<?php

declare(strict_types=1);

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
  public function testNormal(): void {
    // Create a file for testing
    $uri = $this->createUri();

    // Delete a regular file
    $this->assertTrue(\Drupal::service('file_system')->delete($uri), 'Deleted worked.');
    $this->assertFileDoesNotExist($uri);
  }

  /**
   * Try deleting a missing file.
   */
  public function testMissing(): void {
    // Try to delete a non-existing file
    $this->assertTrue(\Drupal::service('file_system')->delete('public://' . $this->randomMachineName()), 'Returns true when deleting a non-existent file.');
  }

  /**
   * Try deleting a directory.
   */
  public function testDirectory(): void {
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
    $this->assertDirectoryExists($directory);
  }

  /**
   * Tests deleting a symlink to a directory.
   */
  public function testSymlinkDirectory(): void {
    // A directory to operate on.
    $directory = \Drupal::service('file_system')->realpath($this->createDirectory());
    $link = dirname($directory) . '/' . $this->randomMachineName();
    symlink($directory, $link);
    $this->assertDirectoryExists($link);

    \Drupal::service('file_system')->delete($link);
    $this->assertDirectoryExists($directory);
    $this->assertDirectoryDoesNotExist($link);
  }

  /**
   * Tests deleting using a symlinked directory using stream wrappers.
   *
   * Note that this does not work because the path will be resolved to the real
   * path in the stream wrapper and not the link.
   */
  public function testSymlinkDirectoryStreamWrappers(): void {
    // A directory to operate on.
    $directory = $this->createDirectory();
    $link = 'public://' . $this->randomMachineName();
    symlink(\Drupal::service('file_system')->realpath($directory), \Drupal::service('file_system')->realpath($link));
    $this->assertDirectoryExists($link);

    $this->expectExceptionMessage("Cannot delete '$link' because it is a directory. Use deleteRecursive() instead.");
    $this->expectException(NotRegularFileException::class);
    \Drupal::service('file_system')->delete($link);
  }

}
