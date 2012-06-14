<?php

/**
 * @file
 * Definition of Drupal\system\Tests\File\FileHookTestBase.
 */

namespace Drupal\system\Tests\File;

/**
 * Base class for file tests that use the file_test module to test uploads and
 * hooks.
 */
class FileHookTestBase extends FileTestBase {
  function setUp() {
    // Install file_test module
    parent::setUp('file_test');
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
    // Determine which hooks were called.
    $actual = array_keys(array_filter(file_test_get_all_calls()));

    // Determine if there were any expected that were not called.
    $uncalled = array_diff($expected, $actual);
    if (count($uncalled)) {
      $this->assertTrue(FALSE, t('Expected hooks %expected to be called but %uncalled was not called.', array('%expected' => implode(', ', $expected), '%uncalled' => implode(', ', $uncalled))));
    }
    else {
      $this->assertTrue(TRUE, t('All the expected hooks were called: %expected', array('%expected' => empty($expected) ? t('(none)') : implode(', ', $expected))));
    }

    // Determine if there were any unexpected calls.
    $unexpected = array_diff($actual, $expected);
    if (count($unexpected)) {
      $this->assertTrue(FALSE, t('Unexpected hooks were called: %unexpected.', array('%unexpected' => empty($unexpected) ? t('(none)') : implode(', ', $unexpected))));
    }
    else {
      $this->assertTrue(TRUE, t('No unexpected hooks were called.'));
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
        $message = t('hook_file_@name was called correctly.', array('@name' => $hook));
      }
      elseif ($expected_count == 0) {
        $message = format_plural($actual_count, 'hook_file_@name was not expected to be called but was actually called once.', 'hook_file_@name was not expected to be called but was actually called @count times.', array('@name' => $hook, '@count' => $actual_count));
      }
      else {
        $message = t('hook_file_@name was expected to be called %expected times but was called %actual times.', array('@name' => $hook, '%expected' => $expected_count, '%actual' => $actual_count));
      }
    }
    $this->assertEqual($actual_count, $expected_count, $message);
  }
}
