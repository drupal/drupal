<?php

namespace Drupal\KernelTests\Core\File;

use Drupal\Core\File\FileSystemInterface;

/**
 * Tests the file_unmanaged_save_data() function.
 *
 * @group File
 */
class FileSaveDataTest extends FileTestBase {

  /**
   * Test the file_unmanaged_save_data() function.
   */
  public function testFileSaveData() {
    $contents = $this->randomMachineName(8);
    $this->setSetting('file_chmod_file', 0777);

    // No filename.
    /** @var \Drupal\Core\File\FileSystemInterface $file_system */
    $file_system = \Drupal::service('file_system');

    // Provide a filename.
    $filepath = $file_system->saveData($contents, 'public://asdf.txt', FileSystemInterface::EXISTS_REPLACE);
    $this->assertNotFalse($filepath, 'Unnamed file saved correctly.');
    $this->assertEqual('asdf.txt', \Drupal::service('file_system')->basename($filepath), 'File was named correctly.');
    $this->assertEqual($contents, file_get_contents($filepath), 'Contents of the file are correct.');
    $this->assertFilePermissions($filepath, 0777);
  }

}
