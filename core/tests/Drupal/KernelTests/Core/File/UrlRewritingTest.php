<?php

namespace Drupal\KernelTests\Core\File;

use Symfony\Component\HttpFoundation\Request;

/**
 * Tests for file URL rewriting.
 *
 * @group File
 */
class UrlRewritingTest extends FileTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('file_test');

  /**
   * Tests the rewriting of shipped file URLs by hook_file_url_alter().
   */
  function testShippedFileURL()  {
    // Test generating a URL to a shipped file (i.e. a file that is part of
    // Drupal core, a module or a theme, for example a JavaScript file).

    // Test alteration of file URLs to use a CDN.
    \Drupal::state()->set('file_test.hook_file_url_alter', 'cdn');
    $filepath = 'core/assets/vendor/jquery/jquery.min.js';
    $url = file_create_url($filepath);
    $this->assertEqual(FILE_URL_TEST_CDN_1 . '/' . $filepath, $url, 'Correctly generated a CDN URL for a shipped file.');
    $filepath = 'core/misc/favicon.ico';
    $url = file_create_url($filepath);
    $this->assertEqual(FILE_URL_TEST_CDN_2 . '/' . $filepath, $url, 'Correctly generated a CDN URL for a shipped file.');

    // Test alteration of file URLs to use root-relative URLs.
    \Drupal::state()->set('file_test.hook_file_url_alter', 'root-relative');
    $filepath = 'core/assets/vendor/jquery/jquery.min.js';
    $url = file_create_url($filepath);
    $this->assertEqual(base_path() . '/' . $filepath, $url, 'Correctly generated a root-relative URL for a shipped file.');
    $filepath = 'core/misc/favicon.ico';
    $url = file_create_url($filepath);
    $this->assertEqual(base_path() . '/' . $filepath, $url, 'Correctly generated a root-relative URL for a shipped file.');

    // Test alteration of file URLs to use protocol-relative URLs.
    \Drupal::state()->set('file_test.hook_file_url_alter', 'protocol-relative');
    $filepath = 'core/assets/vendor/jquery/jquery.min.js';
    $url = file_create_url($filepath);
    $this->assertEqual('/' . base_path() . '/' . $filepath, $url, 'Correctly generated a protocol-relative URL for a shipped file.');
    $filepath = 'core/misc/favicon.ico';
    $url = file_create_url($filepath);
    $this->assertEqual('/' . base_path() . '/' . $filepath, $url, 'Correctly generated a protocol-relative URL for a shipped file.');

    // Test alteration of file URLs with query strings and/or fragment.
    \Drupal::state()->delete('file_test.hook_file_url_alter');
    $filepath = 'core/misc/favicon.ico';
    $url = file_create_url($filepath . '?foo');
    $this->assertEqual($GLOBALS['base_url'] . '/' . $filepath . '?foo=', $url, 'Correctly generated URL. The query string is present.');
    $url = file_create_url($filepath . '?foo=bar');
    $this->assertEqual($GLOBALS['base_url'] . '/' . $filepath . '?foo=bar', $url, 'Correctly generated URL. The query string is present.');
    $url = file_create_url($filepath . '#v1.2');
    $this->assertEqual($GLOBALS['base_url'] . '/' . $filepath . '#v1.2', $url, 'Correctly generated URL. The fragment is present.');
    $url = file_create_url($filepath . '?foo=bar#v1.2');
    $this->assertEqual($GLOBALS['base_url'] . '/' . $filepath . '?foo=bar#v1.2', $url, 'Correctly generated URL. The query string amd fragment is present.');
  }

  /**
   * Tests the rewriting of public managed file URLs by hook_file_url_alter().
   */
  function testPublicManagedFileURL() {
    // Test generating a URL to a managed file.

    // Test alteration of file URLs to use a CDN.
    \Drupal::state()->set('file_test.hook_file_url_alter', 'cdn');
    $uri = $this->createUri();
    $url = file_create_url($uri);
    $public_directory_path = \Drupal::service('stream_wrapper_manager')->getViaScheme('public')->getDirectoryPath();
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

  /**
   * Test file_url_transform_relative().
   */
  function testRelativeFileURL() {
    // Disable file_test.module's hook_file_url_alter() implementation.
    \Drupal::state()->set('file_test.hook_file_url_alter', NULL);

    // Create a mock Request for file_url_transform_relative().
    $request = Request::create($GLOBALS['base_url']);
    $this->container->get('request_stack')->push($request);
    \Drupal::setContainer($this->container);

    // Shipped file.
    $filepath = 'core/assets/vendor/jquery/jquery.min.js';
    $url = file_create_url($filepath);
    $this->assertIdentical(base_path() . $filepath, file_url_transform_relative($url));

    // Managed file.
    $uri = $this->createUri();
    $url = file_create_url($uri);
    $public_directory_path = \Drupal::service('stream_wrapper_manager')->getViaScheme('public')->getDirectoryPath();
    $this->assertIdentical(base_path() . $public_directory_path . '/' . rawurlencode(drupal_basename($uri)), file_url_transform_relative($url));
  }

}
