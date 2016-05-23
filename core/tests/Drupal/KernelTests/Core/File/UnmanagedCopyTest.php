<?php

namespace Drupal\KernelTests\Core\File;

use Drupal\Core\Site\Settings;
use Drupal\Core\File\FileSystem;

/**
 * Tests the unmanaged file copy function.
 *
 * @group File
 */
class UnmanagedCopyTest extends FileTestBase {
  /**
   * Copy a normal file.
   */
  function testNormal() {
    // Create a file for testing
    $uri = $this->createUri();

    // Copying to a new name.
    $desired_filepath = 'public://' . $this->randomMachineName();
    $new_filepath = file_unmanaged_copy($uri, $desired_filepath, FILE_EXISTS_ERROR);
    $this->assertTrue($new_filepath, 'Copy was successful.');
    $this->assertEqual($new_filepath, $desired_filepath, 'Returned expected filepath.');
    $this->assertTrue(file_exists($uri), 'Original file remains.');
    $this->assertTrue(file_exists($new_filepath), 'New file exists.');
    $this->assertFilePermissions($new_filepath, Settings::get('file_chmod_file', FileSystem::CHMOD_FILE));

    // Copying with rename.
    $desired_filepath = 'public://' . $this->randomMachineName();
    $this->assertTrue(file_put_contents($desired_filepath, ' '), 'Created a file so a rename will have to happen.');
    $newer_filepath = file_unmanaged_copy($uri, $desired_filepath, FILE_EXISTS_RENAME);
    $this->assertTrue($newer_filepath, 'Copy was successful.');
    $this->assertNotEqual($newer_filepath, $desired_filepath, 'Returned expected filepath.');
    $this->assertTrue(file_exists($uri), 'Original file remains.');
    $this->assertTrue(file_exists($newer_filepath), 'New file exists.');
    $this->assertFilePermissions($newer_filepath, Settings::get('file_chmod_file', FileSystem::CHMOD_FILE));

    // TODO: test copying to a directory (rather than full directory/file path)
    // TODO: test copying normal files using normal paths (rather than only streams)
  }

  /**
   * Copy a non-existent file.
   */
  function testNonExistent() {
    // Copy non-existent file
    $desired_filepath = $this->randomMachineName();
    $this->assertFalse(file_exists($desired_filepath), "Randomly named file doesn't exists.");
    $new_filepath = file_unmanaged_copy($desired_filepath, $this->randomMachineName());
    $this->assertFalse($new_filepath, 'Copying a missing file fails.');
  }

  /**
   * Copy a file onto itself.
   */
  function testOverwriteSelf() {
    // Create a file for testing
    $uri = $this->createUri();

    // Copy the file onto itself with renaming works.
    $new_filepath = file_unmanaged_copy($uri, $uri, FILE_EXISTS_RENAME);
    $this->assertTrue($new_filepath, 'Copying onto itself with renaming works.');
    $this->assertNotEqual($new_filepath, $uri, 'Copied file has a new name.');
    $this->assertTrue(file_exists($uri), 'Original file exists after copying onto itself.');
    $this->assertTrue(file_exists($new_filepath), 'Copied file exists after copying onto itself.');
    $this->assertFilePermissions($new_filepath, Settings::get('file_chmod_file', FileSystem::CHMOD_FILE));

    // Copy the file onto itself without renaming fails.
    $new_filepath = file_unmanaged_copy($uri, $uri, FILE_EXISTS_ERROR);
    $this->assertFalse($new_filepath, 'Copying onto itself without renaming fails.');
    $this->assertTrue(file_exists($uri), 'File exists after copying onto itself.');

    // Copy the file into same directory without renaming fails.
    $new_filepath = file_unmanaged_copy($uri, drupal_dirname($uri), FILE_EXISTS_ERROR);
    $this->assertFalse($new_filepath, 'Copying onto itself fails.');
    $this->assertTrue(file_exists($uri), 'File exists after copying onto itself.');

    // Copy the file into same directory with renaming works.
    $new_filepath = file_unmanaged_copy($uri, drupal_dirname($uri), FILE_EXISTS_RENAME);
    $this->assertTrue($new_filepath, 'Copying into same directory works.');
    $this->assertNotEqual($new_filepath, $uri, 'Copied file has a new name.');
    $this->assertTrue(file_exists($uri), 'Original file exists after copying onto itself.');
    $this->assertTrue(file_exists($new_filepath), 'Copied file exists after copying onto itself.');
    $this->assertFilePermissions($new_filepath, Settings::get('file_chmod_file', FileSystem::CHMOD_FILE));
  }

}
