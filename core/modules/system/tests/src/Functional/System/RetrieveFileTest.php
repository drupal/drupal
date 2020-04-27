<?php

namespace Drupal\Tests\system\Functional\System;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests HTTP file fetching and error handling.
 *
 * @group system
 */
class RetrieveFileTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Invokes system_retrieve_file() in several scenarios.
   */
  public function testFileRetrieving() {
    // Test 404 handling by trying to fetch a randomly named file.
    /** @var \Drupal\Core\File\FileSystemInterface $file_system */
    $file_system = \Drupal::service('file_system');
    $file_system->mkdir($sourcedir = 'public://' . $this->randomMachineName());
    $filename = 'Файл для тестирования ' . $this->randomMachineName();
    $url = file_create_url($sourcedir . '/' . $filename);
    $retrieved_file = system_retrieve_file($url);
    $this->assertFalse($retrieved_file, 'Non-existent file not fetched.');

    // Actually create that file, download it via HTTP and test the returned path.
    file_put_contents($sourcedir . '/' . $filename, 'testing');
    $retrieved_file = system_retrieve_file($url);

    // URLs could not contains characters outside the ASCII set so $filename
    // has to be encoded.
    $encoded_filename = rawurlencode($filename);

    $this->assertEqual($retrieved_file, 'public://' . $encoded_filename, 'Sane path for downloaded file returned (public:// scheme).');
    $this->assertFileExists($retrieved_file);
    $this->assertEqual(filesize($retrieved_file), 7, 'File size of downloaded file is correct (public:// scheme).');
    /** @var \Drupal\Core\File\FileSystemInterface $file_system */
    $file_system = \Drupal::service('file_system');
    $file_system->delete($retrieved_file);

    // Test downloading file to a different location.
    $file_system->mkdir($targetdir = 'temporary://' . $this->randomMachineName());
    $retrieved_file = system_retrieve_file($url, $targetdir);
    $this->assertEqual($retrieved_file, "$targetdir/$encoded_filename", 'Sane path for downloaded file returned (temporary:// scheme).');
    $this->assertFileExists($retrieved_file);
    $this->assertEqual(filesize($retrieved_file), 7, 'File size of downloaded file is correct (temporary:// scheme).');
    $file_system->delete($retrieved_file);

    $file_system->deleteRecursive($sourcedir);
    $file_system->deleteRecursive($targetdir);
  }

}
