<?php

/**
 * @file
 * Definition of Drupal\system\Tests\File\UrlRewritingTest.
 */

namespace Drupal\system\Tests\File;

/**
 * Tests for file URL rewriting.
 */
class UrlRewritingTest extends FileTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('file_test');

  public static function getInfo() {
    return array(
      'name' => 'File URL rewriting',
      'description' => 'Tests for file URL rewriting.',
      'group' => 'File',
    );
  }

  /**
   * Test the generating of rewritten shipped file URLs.
   */
  function testShippedFileURL()  {
    // Test generating an URL to a shipped file (i.e. a file that is part of
    // Drupal core, a module or a theme, for example a JavaScript file).

    // Test alteration of file URLs to use a CDN.
    \Drupal::state()->set('file_test.hook_file_url_alter', 'cdn');
    $filepath = 'core/misc/jquery.js';
    $url = file_create_url($filepath);
    $this->assertEqual(FILE_URL_TEST_CDN_1 . '/' . $filepath, $url, 'Correctly generated a CDN URL for a shipped file.');
    $filepath = 'core/misc/favicon.ico';
    $url = file_create_url($filepath);
    $this->assertEqual(FILE_URL_TEST_CDN_2 . '/' . $filepath, $url, 'Correctly generated a CDN URL for a shipped file.');

    // Test alteration of file URLs to use root-relative URLs.
    \Drupal::state()->set('file_test.hook_file_url_alter', 'root-relative');
    $filepath = 'core/misc/jquery.js';
    $url = file_create_url($filepath);
    $this->assertEqual(base_path() . '/' . $filepath, $url, 'Correctly generated a root-relative URL for a shipped file.');
    $filepath = 'core/misc/favicon.ico';
    $url = file_create_url($filepath);
    $this->assertEqual(base_path() . '/' . $filepath, $url, 'Correctly generated a root-relative URL for a shipped file.');

    // Test alteration of file URLs to use protocol-relative URLs.
    \Drupal::state()->set('file_test.hook_file_url_alter', 'protocol-relative');
    $filepath = 'core/misc/jquery.js';
    $url = file_create_url($filepath);
    $this->assertEqual('/' . base_path() . '/' . $filepath, $url, 'Correctly generated a protocol-relative URL for a shipped file.');
    $filepath = 'core/misc/favicon.ico';
    $url = file_create_url($filepath);
    $this->assertEqual('/' . base_path() . '/' . $filepath, $url, 'Correctly generated a protocol-relative URL for a shipped file.');
  }

  /**
   * Test the generating of rewritten public created file URLs.
   */
  function testPublicCreatedFileURL() {
    // Test generating an URL to a created file.

    // Test alteration of file URLs to use a CDN.
    \Drupal::state()->set('file_test.hook_file_url_alter', 'cdn');
    $uri = $this->createUri();
    $url = file_create_url($uri);
    $public_directory_path = file_stream_wrapper_get_instance_by_scheme('public')->getDirectoryPath();
    $this->assertEqual(FILE_URL_TEST_CDN_2 . '/' . $public_directory_path . '/' . drupal_basename($uri), $url, 'Correctly generated a CDN URL for a created file.');

    // Test alteration of file URLs to use root-relative URLs.
    \Drupal::state()->set('file_test.hook_file_url_alter', 'root-relative');
    $uri = $this->createUri();
    $url = file_create_url($uri);
    $this->assertEqual(base_path() . '/' . $public_directory_path . '/' . drupal_basename($uri), $url, 'Correctly generated a root-relative URL for a created file.');

    // Test alteration of file URLs to use a protocol-relative URLs.
    \Drupal::state()->set('file_test.hook_file_url_alter', 'protocol-relative');
    $uri = $this->createUri();
    $url = file_create_url($uri);
    $this->assertEqual('/' . base_path() . '/' . $public_directory_path . '/' . drupal_basename($uri), $url, 'Correctly generated a protocol-relative URL for a created file.');
  }
}
