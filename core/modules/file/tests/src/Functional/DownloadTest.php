<?php

namespace Drupal\Tests\file\Functional;

use Drupal\Core\Database\Database;
use Drupal\Core\File\FileSystemInterface;

/**
 * Tests for download/file transfer functions.
 *
 * @group file
 */
class DownloadTest extends FileManagedTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * The file URL generator.
   *
   * @var \Drupal\Core\File\FileUrlGeneratorInterface
   */
  protected $fileUrlGenerator;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // This test currently frequently causes the SQLite database to lock, so
    // skip the test on SQLite until the issue can be resolved.
    // @todo Fix root cause and re-enable in
    //   https://www.drupal.org/project/drupal/issues/3311587
    if (Database::getConnection()->driver() === 'sqlite') {
      $this->markTestSkipped('Test frequently causes a locked database on SQLite');
    }

    $this->fileUrlGenerator = $this->container->get('file_url_generator');
    // Clear out any hook calls.
    file_test_reset();
  }

  /**
   * Tests the public file transfer system.
   */
  public function testPublicFileTransfer() {
    // Test generating a URL to a created file.
    $file = $this->createFile();
    $url = $this->fileUrlGenerator->generateAbsoluteString($file->getFileUri());
    // URLs can't contain characters outside the ASCII set so $filename has to be
    // encoded.
    $filename = $GLOBALS['base_url'] . '/' . \Drupal::service('stream_wrapper_manager')->getViaScheme('public')->getDirectoryPath() . '/' . rawurlencode($file->getFilename());
    $this->assertEquals($filename, $url, 'Correctly generated a URL for a created file.');
    $http_client = $this->getHttpClient();
    $response = $http_client->head($url);
    $this->assertEquals(200, $response->getStatusCode(), 'Confirmed that the generated URL is correct by downloading the created file.');

    // Test generating a URL to a shipped file (i.e. a file that is part of
    // Drupal core, a module or a theme, for example a JavaScript file).
    $filepath = 'core/assets/vendor/jquery/jquery.min.js';
    $url = $this->fileUrlGenerator->generateAbsoluteString($filepath);
    $this->assertEquals($GLOBALS['base_url'] . '/' . $filepath, $url, 'Correctly generated a URL for a shipped file.');
    $response = $http_client->head($url);
    $this->assertEquals(200, $response->getStatusCode(), 'Confirmed that the generated URL is correct by downloading the shipped file.');
  }

  /**
   * Tests the private file transfer system.
   */
  public function testPrivateFileTransferWithoutPageCache() {
    $this->doPrivateFileTransferTest();
  }

  /**
   * Tests the private file transfer system.
   */
  protected function doPrivateFileTransferTest() {
    // Set file downloads to private so handler functions get called.

    // Create a file.
    $contents = $this->randomMachineName(8);
    $file = $this->createFile($contents . '.txt', $contents, 'private');
    // Created private files without usage are by default not accessible
    // for a user different from the owner, but createFile always uses uid 1
    // as the owner of the files. Therefore make it permanent to allow access
    // if a module allows it.
    $file->setPermanent();
    $file->save();

    $url = $this->fileUrlGenerator->generateAbsoluteString($file->getFileUri());

    // Set file_test access header to allow the download.
    file_test_reset();
    file_test_set_return('download', ['x-foo' => 'Bar']);
    $this->drupalGet($url);
    // Verify that header is set by file_test module on private download.
    $this->assertSession()->responseHeaderEquals('x-foo', 'Bar');
    // Verify that page cache is disabled on private file download.
    $this->assertSession()->responseHeaderDoesNotExist('x-drupal-cache');
    $this->assertSession()->statusCodeEquals(200);
    // Ensure hook_file_download is fired correctly.
    $this->assertEquals($file->getFileUri(), \Drupal::state()->get('file_test.results')['download'][0][0]);

    // Test that the file transferred correctly.
    $this->assertSame($contents, $this->getSession()->getPage()->getContent(), 'Contents of the file are correct.');
    $http_client = $this->getHttpClient();

    // Try non-existent file.
    file_test_reset();
    $not_found_url = $this->fileUrlGenerator->generateAbsoluteString('private://' . $this->randomMachineName() . '.txt');
    $response = $http_client->head($not_found_url, ['http_errors' => FALSE]);
    $this->assertSame(404, $response->getStatusCode(), 'Correctly returned 404 response for a non-existent file.');
    // Assert that hook_file_download is not called.
    $this->assertEquals([], \Drupal::state()->get('file_test.results')['download']);

    // Having tried a non-existent file, try the original file again to ensure
    // it's returned instead of a 404 response.
    // Set file_test access header to allow the download.
    file_test_reset();
    file_test_set_return('download', ['x-foo' => 'Bar']);
    $this->drupalGet($url);
    // Verify that header is set by file_test module on private download.
    $this->assertSession()->responseHeaderEquals('x-foo', 'Bar');
    // Verify that page cache is disabled on private file download.
    $this->assertSession()->responseHeaderDoesNotExist('x-drupal-cache');
    $this->assertSession()->statusCodeEquals(200);
    // Test that the file transferred correctly.
    $this->assertSame($contents, $this->getSession()->getPage()->getContent(), 'Contents of the file are correct.');

    // Deny access to all downloads via a -1 header.
    file_test_set_return('download', -1);
    $response = $http_client->head($url, ['http_errors' => FALSE]);
    $this->assertSame(403, $response->getStatusCode(), 'Correctly denied access to a file when file_test sets the header to -1.');

    // Try requesting the private file URL without a file specified.
    file_test_reset();
    $this->drupalGet('/system/files');
    $this->assertSession()->statusCodeEquals(404);
    // Assert that hook_file_download is not called.
    $this->assertEquals([], \Drupal::state()->get('file_test.results')['download']);
  }

  /**
   * Test FileUrlGeneratorInterface::generateString()
   */
  public function testFileCreateUrl() {
    // "Special" ASCII characters.
    $basename = " -._~!$'\"()*@[]?&+%#,;=:\n\x00" .
      // Characters that look like a percent-escaped string.
      "%23%25%26%2B%2F%3F" .
      // Characters from various non-ASCII alphabets.
      // cSpell:disable-next-line
      "éøïвβ中國書۞";
    $basename_encoded = '%20-._~%21%24%27%22%28%29%2A%40%5B%5D%3F%26%2B%25%23%2C%3B%3D%3A__' .
      '%2523%2525%2526%252B%252F%253F' .
      '%C3%A9%C3%B8%C3%AF%D0%B2%CE%B2%E4%B8%AD%E5%9C%8B%E6%9B%B8%DB%9E';

    // Public files should not be served by Drupal, so their URLs should not be
    // routed through Drupal, whereas private files should be served by Drupal,
    // so they need to be. The difference is most apparent when $script_path
    // is not empty (i.e., when not using clean URLs).
    $clean_url_settings = [
      'clean' => '',
      'unclean' => 'index.php/',
    ];
    $public_directory_path = \Drupal::service('stream_wrapper_manager')->getViaScheme('public')->getDirectoryPath();
    foreach ($clean_url_settings as $clean_url_setting => $script_path) {
      $clean_urls = $clean_url_setting == 'clean';
      $request = $this->prepareRequestForGenerator($clean_urls);
      $base_path = $request->getSchemeAndHttpHost() . $request->getBasePath();
      $this->checkUrl('public', '', $basename, $base_path . '/' . $public_directory_path . '/' . $basename_encoded);
      $this->checkUrl('private', '', $basename, $base_path . '/' . $script_path . 'system/files/' . $basename_encoded);
    }
    $this->assertEquals('data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAUAAAAFCAYAAACNbyblAAAAHElEQVQI12P4//8/w38GIAXDIBKE0DHxgljNBAAO9TXL0Y4OHwAAAABJRU5ErkJggg==', $this->fileUrlGenerator->generateString('data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAUAAAAFCAYAAACNbyblAAAAHElEQVQI12P4//8/w38GIAXDIBKE0DHxgljNBAAO9TXL0Y4OHwAAAABJRU5ErkJggg==', FALSE));
  }

  /**
   * Download a file from the URL generated by generateString().
   *
   * Create a file with the specified scheme, directory and filename; check that
   * the URL generated by FileUrlGeneratorInterface::generateString() for the
   * specified file equals the specified URL; fetch the URL and then compare the
   * contents to the file.
   *
   * @param string $scheme
   *   A scheme, e.g. "public".
   * @param string $directory
   *   A directory, possibly "".
   * @param string $filename
   *   A filename.
   * @param string $expected_url
   *   The expected URL.
   */
  private function checkUrl($scheme, $directory, $filename, $expected_url) {
    // Convert $filename to a valid filename, i.e. strip characters not
    // supported by the filesystem, and create the file in the specified
    // directory.
    $filepath = \Drupal::service('file_system')->createFilename($filename, $directory);
    $directory_uri = $scheme . '://' . dirname($filepath);
    \Drupal::service('file_system')->prepareDirectory($directory_uri, FileSystemInterface::CREATE_DIRECTORY);
    $file = $this->createFile($filepath, NULL, $scheme);

    $url = $this->fileUrlGenerator->generateAbsoluteString($file->getFileUri());
    $this->assertEquals($expected_url, $url);

    if ($scheme == 'private') {
      // Tell the implementation of hook_file_download() in file_test.module
      // that this file may be downloaded.
      file_test_set_return('download', ['x-foo' => 'Bar']);
    }

    $this->drupalGet($url);
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->responseContains(file_get_contents($file->getFileUri()));

    $file->delete();
  }

}
