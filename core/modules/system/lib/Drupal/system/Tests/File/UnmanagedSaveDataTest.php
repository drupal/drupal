<?php

/**
 * @file
 * Definition of Drupal\system\Tests\File\UnmanagedSaveDataTest.
 */

namespace Drupal\system\Tests\File;

/**
 * Tests the file_unmanaged_save_data() function.
 */
class UnmanagedSaveDataTest extends FileTestBase {
  public static function getInfo() {
    return array(
      'name' => 'Unmanaged file save data',
      'description' => 'Tests the unmanaged file save data function.',
      'group' => 'File API',
    );
  }

  /**
   * Test the file_unmanaged_save_data() function.
   */
  function testFileSaveData() {
    $contents = $this->randomName(8);
    $this->settingsSet('file_chmod_file', 0777);

    // No filename.
    $filepath = file_unmanaged_save_data($contents);
    $this->assertTrue($filepath, 'Unnamed file saved correctly.');
    $this->assertEqual(file_uri_scheme($filepath), file_default_scheme(), "File was placed in Drupal's files directory.");
    $this->assertEqual($contents, file_get_contents($filepath), 'Contents of the file are correct.');

    // Provide a filename.
    $filepath = file_unmanaged_save_data($contents, 'public://asdf.txt', FILE_EXISTS_REPLACE);
    $this->assertTrue($filepath, 'Unnamed file saved correctly.');
    $this->assertEqual('asdf.txt', drupal_basename($filepath), 'File was named correctly.');
    $this->assertEqual($contents, file_get_contents($filepath), 'Contents of the file are correct.');
    $this->assertFilePermissions($filepath, 0777);
  }
}
