<?php

namespace Drupal\Tests\file\Functional;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Url;
use GuzzleHttp\Cookie\CookieJar;

/**
 * Tests the file_save_upload() function.
 *
 * @group file
 */
class SaveUploadTest extends FileManagedTestBase {

  /**
   * Tests that filenames containing invalid UTF-8 are rejected.
   */
  public function testInvalidUtf8FilenameUpload() {
    $account = $this->drupalCreateUser(['access site reports']);
    $this->drupalLogin($account);
    $this->drupalGet('file-test/upload');

    // Filename containing invalid UTF-8.
    $filename = "x\xc0xx.gif";

    $page = $this->getSession()->getPage();
    $data = [
      'multipart' => [
        [
          'name'     => 'file_test_replace',
          'contents' => FILE_EXISTS_RENAME,
        ],
        [
          'name' => 'form_id',
          'contents' => '_file_test_form',
        ],
        [
          'name' => 'form_build_id',
          'contents' => $page->find('hidden_field_selector', ['hidden_field', 'form_build_id'])->getAttribute('value'),
        ],
        [
          'name' => 'form_token',
          'contents' => $page->find('hidden_field_selector', ['hidden_field', 'form_token'])->getAttribute('value'),
        ],
        [
          'name' => 'op',
          'contents' => 'Submit',
        ],
        [
          'name'     => 'files[file_test_upload]',
          'contents' => 'test content',
          'filename' => $filename,
        ],
      ],
      'http_errors' => FALSE,
    ];

    $domain = parse_url($this->getUrl(), PHP_URL_HOST);
    $session_id = $this->getSession()->getCookie($this->getSessionName());
    $data['cookies'] = CookieJar::fromArray([$this->getSessionName() => $session_id], $domain);

    $this->assertFileNotExists('temporary://' . $filename);
    // Use Guzzle's HTTP client directly so we can POST files without having to
    // write them to disk. Not all filesystem support writing files with invalid
    // UTF-8 filenames.
    $response = $this->getHttpClient()->request('POST', Url::fromUri('base:file-test/upload')->setAbsolute()->toString(), $data);

    $content = (string) $response->getBody();
    $this->htmlOutput($content);
    $error_text = new FormattableMarkup('The file %filename could not be uploaded because the name is invalid.', ['%filename' => $filename]);
    $this->assertContains((string) $error_text, $content);
    $this->assertContains('Epic upload FAIL!', $content);
    $this->assertFileNotExists('temporary://' . $filename);
  }

}
