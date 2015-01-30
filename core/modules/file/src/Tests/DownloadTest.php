<?php

/**
 * @file
 * Definition of Drupal\file\Tests\DownloadTest.
 */

namespace Drupal\file\Tests;

use Symfony\Component\HttpFoundation\Request;

/**
 * Tests for download/file transfer functions.
 *
 * @group file
 */
class DownloadTest extends FileManagedTestBase {
  protected function setUp() {
    parent::setUp();
    // Clear out any hook calls.
    file_test_reset();
  }

  /**
   * Test the public file transfer system.
   */
  function testPublicFileTransfer() {
    // Test generating an URL to a created file.
    $file = $this->createFile();
    $url = file_create_url($file->getFileUri());
    // URLs can't contain characters outside the ASCII set so $filename has to be
    // encoded.
    $filename = $GLOBALS['base_url'] . '/' . file_stream_wrapper_get_instance_by_scheme('public')->getDirectoryPath() . '/' . rawurlencode($file->getFilename());
    $this->assertEqual($filename, $url, 'Correctly generated a URL for a created file.');
    $this->drupalHead($url);
    $this->assertResponse(200, 'Confirmed that the generated URL is correct by downloading the created file.');

    // Test generating an URL to a shipped file (i.e. a file that is part of
    // Drupal core, a module or a theme, for example a JavaScript file).
    $filepath = 'core/assets/vendor/jquery/jquery.min.js';
    $url = file_create_url($filepath);
    $this->assertEqual($GLOBALS['base_url'] . '/' . $filepath, $url, 'Correctly generated a URL for a shipped file.');
    $this->drupalHead($url);
    $this->assertResponse(200, 'Confirmed that the generated URL is correct by downloading the shipped file.');
  }

  /**
   * Test the private file transfer system.
   */
  public function testPrivateFileTransferWithoutPageCache() {
    $this->doPrivateFileTransferTest();
  }

  /**
   * Test the private file transfer system with page cache.
   */
  public function testPrivateFileTransferWithPageCache() {
    // Turn on page caching and rerun the test.
    $config = $this->config('system.performance');
    $config->set('cache.page.use_internal', 1);
    $config->set('cache.page.max_age', 300);
    $config->save();

    $this->doPrivateFileTransferTest();
  }

  /**
   * Test the private file transfer system.
   */
  protected function doPrivateFileTransferTest() {
    // Set file downloads to private so handler functions get called.

    // Create a file.
    $contents = $this->randomMachineName(8);
    $file = $this->createFile(NULL, $contents, 'private');
    $url  = file_create_url($file->getFileUri());

    // Set file_test access header to allow the download.
    file_test_set_return('download', array('x-foo' => 'Bar'));
    $this->drupalGet($url);
    $this->assertEqual($this->drupalGetHeader('x-foo'), 'Bar', 'Found header set by file_test module on private download.');
    $this->assertFalse($this->drupalGetHeader('x-drupal-cache'), 'Page cache is disabled on private file download.');
    $this->assertResponse(200, 'Correctly allowed access to a file when file_test provides headers.');

    // Test that the file transferred correctly.
    $this->assertEqual($contents, $this->content, 'Contents of the file are correct.');

    // Deny access to all downloads via a -1 header.
    file_test_set_return('download', -1);
    $this->drupalHead($url);
    $this->assertResponse(403, 'Correctly denied access to a file when file_test sets the header to -1.');

    // Try non-existent file.
    $url = file_create_url('private://' . $this->randomMachineName());
    $this->drupalHead($url);
    $this->assertResponse(404, 'Correctly returned 404 response for a non-existent file.');
  }

  /**
   * Test file_create_url().
   */
  function testFileCreateUrl() {

    // Tilde (~) is excluded from this test because it is encoded by
    // rawurlencode() in PHP 5.2 but not in PHP 5.3, as per RFC 3986.
    // @see http://www.php.net/manual/function.rawurlencode.php#86506
    $basename = " -._!$'\"()*@[]?&+%#,;=:\n\x00" . // "Special" ASCII characters.
      "%23%25%26%2B%2F%3F" . // Characters that look like a percent-escaped string.
      "éøïвβ中國書۞"; // Characters from various non-ASCII alphabets.
    $basename_encoded = '%20-._%21%24%27%22%28%29%2A%40%5B%5D%3F%26%2B%25%23%2C%3B%3D%3A__' .
      '%2523%2525%2526%252B%252F%253F' .
      '%C3%A9%C3%B8%C3%AF%D0%B2%CE%B2%E4%B8%AD%E5%9C%8B%E6%9B%B8%DB%9E';

    // Public files should not be served by Drupal, so their URLs should not be
    // routed through Drupal, whereas private files should be served by Drupal,
    // so they need to be. The difference is most apparent when $script_path
    // is not empty (i.e., when not using clean URLs).
    $clean_url_settings = array(
      'clean' => '',
      'unclean' => 'index.php/',
    );
    foreach ($clean_url_settings as $clean_url_setting => $script_path) {
      $clean_urls = $clean_url_setting == 'clean';
      $request = $this->prepareRequestForGenerator($clean_urls);
      $base_path = $request->getSchemeAndHttpHost() . $request->getBasePath();
      $this->checkUrl('public', '', $basename, $base_path . '/' . file_stream_wrapper_get_instance_by_scheme('public')->getDirectoryPath() . '/' . $basename_encoded);
      $this->checkUrl('private', '', $basename, $base_path . '/' . $script_path . 'system/files/' . $basename_encoded);
    }
    $this->assertEqual(file_create_url('data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAUAAAAFCAYAAACNbyblAAAAHElEQVQI12P4//8/w38GIAXDIBKE0DHxgljNBAAO9TXL0Y4OHwAAAABJRU5ErkJggg=='), 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAUAAAAFCAYAAACNbyblAAAAHElEQVQI12P4//8/w38GIAXDIBKE0DHxgljNBAAO9TXL0Y4OHwAAAABJRU5ErkJggg==', t('Generated URL matches expected URL.'));
  }

  /**
   * Download a file from the URL generated by file_create_url().
   *
   * Create a file with the specified scheme, directory and filename; check that
   * the URL generated by file_create_url() for the specified file equals the
   * specified URL; fetch the URL and then compare the contents to the file.
   *
   * @param $scheme
   *   A scheme, e.g. "public"
   * @param $directory
   *   A directory, possibly ""
   * @param $filename
   *   A filename
   * @param $expected_url
   *   The expected URL
   */
  private function checkUrl($scheme, $directory, $filename, $expected_url) {
    // Convert $filename to a valid filename, i.e. strip characters not
    // supported by the filesystem, and create the file in the specified
    // directory.
    $filepath = file_create_filename($filename, $directory);
    $directory_uri = $scheme . '://' . dirname($filepath);
    file_prepare_directory($directory_uri, FILE_CREATE_DIRECTORY);
    $file = $this->createFile($filepath, NULL, $scheme);

    $url = file_create_url($file->getFileUri());
    $this->assertEqual($url, $expected_url);

    if ($scheme == 'private') {
      // Tell the implementation of hook_file_download() in file_test.module
      // that this file may be downloaded.
      file_test_set_return('download', array('x-foo' => 'Bar'));
    }

    $this->drupalGet($url);
    if ($this->assertResponse(200) == 'pass') {
      $this->assertRaw(file_get_contents($file->getFileUri()), 'Contents of the file are correct.');
    }

    $file->delete();
  }
}
