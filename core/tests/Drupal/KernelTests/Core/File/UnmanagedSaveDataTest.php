<?php

namespace Drupal\KernelTests\Core\File;

/**
 * Tests the file_unmanaged_save_data() function.
 *
 * @group File
 */
class UnmanagedSaveDataTest extends FileTestBase {

  /**
   * Test the file_unmanaged_save_data() function.
   */
  public function testFileSaveData() {
    $contents = $this->randomMachineName(8);
    $this->setSetting('file_chmod_file', 0777);

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
