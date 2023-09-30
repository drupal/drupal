<?php

namespace Drupal\Tests\system\Functional\System;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests HTTP file fetching and error handling.
 *
 * @group system
 * @group legacy
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
    $file_system->mkdir($source_dir = 'public://' . $this->randomMachineName());
    // cSpell:disable-next-line
    $filename = 'Файл для тестирования ' . $this->randomMachineName();
    $url = \Drupal::service('file_url_generator')->generateAbsoluteString($source_dir . '/' . $filename);
    $retrieved_file = system_retrieve_file($url);
    $this->assertFalse($retrieved_file, 'Non-existent file not fetched.');

    // Actually create that file, download it via HTTP and test the returned path.
    file_put_contents($source_dir . '/' . $filename, 'testing');
    $retrieved_file = system_retrieve_file($url);

    // URLs could not contains characters outside the ASCII set so $filename
    // has to be encoded.
    $encoded_filename = rawurlencode($filename);

    $this->assertEquals('public://' . $encoded_filename, $retrieved_file, 'Sane path for downloaded file returned (public:// scheme).');
    $this->assertFileExists($retrieved_file);
    $this->assertEquals(7, filesize($retrieved_file), 'File size of downloaded file is correct (public:// scheme).');
    /** @var \Drupal\Core\File\FileSystemInterface $file_system */
    $file_system = \Drupal::service('file_system');
    $file_system->delete($retrieved_file);

    // Test downloading file to a different location.
    $file_system->mkdir($target_dir = 'temporary://' . $this->randomMachineName());
    $retrieved_file = system_retrieve_file($url, $target_dir);
    $this->assertEquals("{$target_dir}/{$encoded_filename}", $retrieved_file, 'Sane path for downloaded file returned (temporary:// scheme).');
    $this->assertFileExists($retrieved_file);
    $this->assertEquals(7, filesize($retrieved_file), 'File size of downloaded file is correct (temporary:// scheme).');
    $file_system->delete($retrieved_file);

    $file_system->deleteRecursive($source_dir);
    $file_system->deleteRecursive($target_dir);
  }

}
