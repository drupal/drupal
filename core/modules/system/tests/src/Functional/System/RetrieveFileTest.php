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
   * Invokes system_retrieve_file() in several scenarios.
   */
  public function testFileRetrieving() {
    // Test 404 handling by trying to fetch a randomly named file.
    drupal_mkdir($sourcedir = 'public://' . $this->randomMachineName());
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
    $this->assertTrue(is_file($retrieved_file), 'Downloaded file does exist (public:// scheme).');
    $this->assertEqual(filesize($retrieved_file), 7, 'File size of downloaded file is correct (public:// scheme).');
    file_unmanaged_delete($retrieved_file);

    // Test downloading file to a different location.
    drupal_mkdir($targetdir = 'temporary://' . $this->randomMachineName());
    $retrieved_file = system_retrieve_file($url, $targetdir);
    $this->assertEqual($retrieved_file, "$targetdir/$encoded_filename", 'Sane path for downloaded file returned (temporary:// scheme).');
    $this->assertTrue(is_file($retrieved_file), 'Downloaded file does exist (temporary:// scheme).');
    $this->assertEqual(filesize($retrieved_file), 7, 'File size of downloaded file is correct (temporary:// scheme).');
    file_unmanaged_delete($retrieved_file);

    file_unmanaged_delete_recursive($sourcedir);
    file_unmanaged_delete_recursive($targetdir);
  }

}
