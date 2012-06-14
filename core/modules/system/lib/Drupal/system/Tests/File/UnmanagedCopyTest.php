<?php

/**
 * @file
 * Definition of Drupal\system\Tests\File\UnmanagedCopyTest.
 */

namespace Drupal\system\Tests\File;

/**
 * Unmanaged copy related tests.
 */
class UnmanagedCopyTest extends FileTestBase {
  public static function getInfo() {
    return array(
      'name' => 'Unmanaged file copying',
      'description' => 'Tests the unmanaged file copy function.',
      'group' => 'File API',
    );
  }

  /**
   * Copy a normal file.
   */
  function testNormal() {
    // Create a file for testing
    $file = $this->createFile();

    // Copying to a new name.
    $desired_filepath = 'public://' . $this->randomName();
    $new_filepath = file_unmanaged_copy($file->uri, $desired_filepath, FILE_EXISTS_ERROR);
    $this->assertTrue($new_filepath, t('Copy was successful.'));
    $this->assertEqual($new_filepath, $desired_filepath, t('Returned expected filepath.'));
    $this->assertTrue(file_exists($file->uri), t('Original file remains.'));
    $this->assertTrue(file_exists($new_filepath), t('New file exists.'));
    $this->assertFilePermissions($new_filepath, variable_get('file_chmod_file', 0664));

    // Copying with rename.
    $desired_filepath = 'public://' . $this->randomName();
    $this->assertTrue(file_put_contents($desired_filepath, ' '), t('Created a file so a rename will have to happen.'));
    $newer_filepath = file_unmanaged_copy($file->uri, $desired_filepath, FILE_EXISTS_RENAME);
    $this->assertTrue($newer_filepath, t('Copy was successful.'));
    $this->assertNotEqual($newer_filepath, $desired_filepath, t('Returned expected filepath.'));
    $this->assertTrue(file_exists($file->uri), t('Original file remains.'));
    $this->assertTrue(file_exists($newer_filepath), t('New file exists.'));
    $this->assertFilePermissions($newer_filepath, variable_get('file_chmod_file', 0664));

    // TODO: test copying to a directory (rather than full directory/file path)
    // TODO: test copying normal files using normal paths (rather than only streams)
  }

  /**
   * Copy a non-existent file.
   */
  function testNonExistent() {
    // Copy non-existent file
    $desired_filepath = $this->randomName();
    $this->assertFalse(file_exists($desired_filepath), t("Randomly named file doesn't exists."));
    $new_filepath = file_unmanaged_copy($desired_filepath, $this->randomName());
    $this->assertFalse($new_filepath, t('Copying a missing file fails.'));
  }

  /**
   * Copy a file onto itself.
   */
  function testOverwriteSelf() {
    // Create a file for testing
    $file = $this->createFile();

    // Copy the file onto itself with renaming works.
    $new_filepath = file_unmanaged_copy($file->uri, $file->uri, FILE_EXISTS_RENAME);
    $this->assertTrue($new_filepath, t('Copying onto itself with renaming works.'));
    $this->assertNotEqual($new_filepath, $file->uri, t('Copied file has a new name.'));
    $this->assertTrue(file_exists($file->uri), t('Original file exists after copying onto itself.'));
    $this->assertTrue(file_exists($new_filepath), t('Copied file exists after copying onto itself.'));
    $this->assertFilePermissions($new_filepath, variable_get('file_chmod_file', 0664));

    // Copy the file onto itself without renaming fails.
    $new_filepath = file_unmanaged_copy($file->uri, $file->uri, FILE_EXISTS_ERROR);
    $this->assertFalse($new_filepath, t('Copying onto itself without renaming fails.'));
    $this->assertTrue(file_exists($file->uri), t('File exists after copying onto itself.'));

    // Copy the file into same directory without renaming fails.
    $new_filepath = file_unmanaged_copy($file->uri, drupal_dirname($file->uri), FILE_EXISTS_ERROR);
    $this->assertFalse($new_filepath, t('Copying onto itself fails.'));
    $this->assertTrue(file_exists($file->uri), t('File exists after copying onto itself.'));

    // Copy the file into same directory with renaming works.
    $new_filepath = file_unmanaged_copy($file->uri, drupal_dirname($file->uri), FILE_EXISTS_RENAME);
    $this->assertTrue($new_filepath, t('Copying into same directory works.'));
    $this->assertNotEqual($new_filepath, $file->uri, t('Copied file has a new name.'));
    $this->assertTrue(file_exists($file->uri), t('Original file exists after copying onto itself.'));
    $this->assertTrue(file_exists($new_filepath), t('Copied file exists after copying onto itself.'));
    $this->assertFilePermissions($new_filepath, variable_get('file_chmod_file', 0664));
  }
}
