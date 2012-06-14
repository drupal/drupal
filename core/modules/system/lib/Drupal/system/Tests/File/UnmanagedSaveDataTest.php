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

    // No filename.
    $filepath = file_unmanaged_save_data($contents);
    $this->assertTrue($filepath, t('Unnamed file saved correctly.'));
    $this->assertEqual(file_uri_scheme($filepath), file_default_scheme(), t("File was placed in Drupal's files directory."));
    $this->assertEqual($contents, file_get_contents($filepath), t('Contents of the file are correct.'));

    // Provide a filename.
    $filepath = file_unmanaged_save_data($contents, 'public://asdf.txt', FILE_EXISTS_REPLACE);
    $this->assertTrue($filepath, t('Unnamed file saved correctly.'));
    $this->assertEqual('asdf.txt', drupal_basename($filepath), t('File was named correctly.'));
    $this->assertEqual($contents, file_get_contents($filepath), t('Contents of the file are correct.'));
    $this->assertFilePermissions($filepath, variable_get('file_chmod_file', 0664));
  }
}
