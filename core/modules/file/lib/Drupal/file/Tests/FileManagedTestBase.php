<?php

/**
 * @file
 * Definition of Drupal\file\Tests\FileManagedTestBase.
 */

namespace Drupal\file\Tests;

use Drupal\system\Tests\File\FileTestBase;
use \stdClass;

/**
 * Base class for file tests that use the file_test module to test uploads and
 * hooks.
 */
abstract class FileManagedTestBase extends FileTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('file_test', 'file');

  function setUp() {
    parent::setUp();
    // Clear out any hook calls.
    file_test_reset();
  }

  /**
   * Assert that all of the specified hook_file_* hooks were called once, other
   * values result in failure.
   *
   * @param $expected
   *   Array with string containing with the hook name, e.g. 'load', 'save',
   *   'insert', etc.
   */
  function assertFileHooksCalled($expected) {
    \Drupal::state()->resetCache();

    // Determine which hooks were called.
    $actual = array_keys(array_filter(file_test_get_all_calls()));

    // Determine if there were any expected that were not called.
    $uncalled = array_diff($expected, $actual);
    if (count($uncalled)) {
      $this->assertTrue(FALSE, format_string('Expected hooks %expected to be called but %uncalled was not called.', array('%expected' => implode(', ', $expected), '%uncalled' => implode(', ', $uncalled))));
    }
    else {
      $this->assertTrue(TRUE, format_string('All the expected hooks were called: %expected', array('%expected' => empty($expected) ? '(none)' : implode(', ', $expected))));
    }

    // Determine if there were any unexpected calls.
    $unexpected = array_diff($actual, $expected);
    if (count($unexpected)) {
      $this->assertTrue(FALSE, format_string('Unexpected hooks were called: %unexpected.', array('%unexpected' => empty($unexpected) ? '(none)' : implode(', ', $unexpected))));
    }
    else {
      $this->assertTrue(TRUE, 'No unexpected hooks were called.');
    }
  }

  /**
   * Assert that a hook_file_* hook was called a certain number of times.
   *
   * @param $hook
   *   String with the hook name, e.g. 'load', 'save', 'insert', etc.
   * @param $expected_count
   *   Optional integer count.
   * @param $message
   *   Optional translated string message.
   */
  function assertFileHookCalled($hook, $expected_count = 1, $message = NULL) {
    $actual_count = count(file_test_get_calls($hook));

    if (!isset($message)) {
      if ($actual_count == $expected_count) {
        $message = format_string('hook_file_@name was called correctly.', array('@name' => $hook));
      }
      elseif ($expected_count == 0) {
        $message = format_plural($actual_count, 'hook_file_@name was not expected to be called but was actually called once.', 'hook_file_@name was not expected to be called but was actually called @count times.', array('@name' => $hook, '@count' => $actual_count));
      }
      else {
        $message = format_string('hook_file_@name was expected to be called %expected times but was called %actual times.', array('@name' => $hook, '%expected' => $expected_count, '%actual' => $actual_count));
      }
    }
    $this->assertEqual($actual_count, $expected_count, $message);
  }

  /**
   * Create a file and save it to the files table and assert that it occurs
   * correctly.
   *
   * @param $filepath
   *   Optional string specifying the file path. If none is provided then a
   *   randomly named file will be created in the site's files directory.
   * @param $contents
   *   Optional contents to save into the file. If a NULL value is provided an
   *   arbitrary string will be used.
   * @param $scheme
   *   Optional string indicating the stream scheme to use. Drupal core includes
   *   public, private, and temporary. The public wrapper is the default.
   * @return \Drupal\file\FileInterface
   *   File entity.
   */
  function createFile($filepath = NULL, $contents = NULL, $scheme = NULL) {
    $file = new stdClass();
    $file->uri = $this->createUri($filepath, $contents, $scheme);
    $file->filename = drupal_basename($file->uri);
    $file->filemime = 'text/plain';
    $file->uid = 1;
    $file->created = REQUEST_TIME;
    $file->changed = REQUEST_TIME;
    $file->filesize = filesize($file->uri);
    $file->status = 0;
    // Write the record directly rather than using the API so we don't invoke
    // the hooks.
    $this->assertNotIdentical(drupal_write_record('file_managed', $file), FALSE, 'The file was added to the database.', 'Create test file');

    return entity_create('file', (array) $file);
  }
}
