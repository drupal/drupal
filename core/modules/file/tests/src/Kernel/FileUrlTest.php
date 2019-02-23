<?php

namespace Drupal\Tests\file\Kernel;

use Drupal\Core\File\FileSystemInterface;

/**
 * Tests the file url.
 *
 * @group file
 */
class FileUrlTest extends FileManagedUnitTestBase {

  /**
   * Test public files with a different host name from settings.
   */
  public function testFilesUrlWithDifferentHostName() {
    $test_base_url = 'http://www.example.com/cdn';
    $this->setSetting('file_public_base_url', $test_base_url);
    $filepath = \Drupal::service('file_system')->createFilename('test.txt', '');
    $directory_uri = 'public://' . dirname($filepath);
    \Drupal::service('file_system')->prepareDirectory($directory_uri, FileSystemInterface::CREATE_DIRECTORY);
    $file = $this->createFile($filepath, NULL, 'public');
    $url = file_create_url($file->getFileUri());
    $expected_url = $test_base_url . '/' . basename($filepath);
    $this->assertSame($url, $expected_url);
  }

}
