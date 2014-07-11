<?php

/**
 * @file
 * Definition of Drupal\system\Tests\File\ScanDirectoryTest.
 */

namespace Drupal\system\Tests\File;

/**
 * Tests the file_scan_directory() function.
 *
 * @group File
 */
class ScanDirectoryTest extends FileTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('file_test');

  function setUp() {
    parent::setUp();
    $this->path = drupal_get_path('module', 'simpletest') . '/files';
  }

  /**
   * Check the format of the returned values.
   */
  function testReturn() {
    // Grab a listing of all the JavaSscript files and check that they're
    // passed to the callback.
    $all_files = file_scan_directory($this->path, '/^javascript-/');
    ksort($all_files);
    $this->assertEqual(2, count($all_files), 'Found two, expected javascript files.');

    // Check the first file.
    $file = reset($all_files);
    $this->assertEqual(key($all_files), $file->uri, 'Correct array key was used for the first returned file.');
    $this->assertEqual($file->uri, $this->path . '/javascript-1.txt', 'First file name was set correctly.');
    $this->assertEqual($file->filename, 'javascript-1.txt', 'First basename was set correctly');
    $this->assertEqual($file->name, 'javascript-1', 'First name was set correctly.');

    // Check the second file.
    $file = next($all_files);
    $this->assertEqual(key($all_files), $file->uri, 'Correct array key was used for the second returned file.');
    $this->assertEqual($file->uri, $this->path . '/javascript-2.script', 'Second file name was set correctly.');
    $this->assertEqual($file->filename, 'javascript-2.script', 'Second basename was set correctly');
    $this->assertEqual($file->name, 'javascript-2', 'Second name was set correctly.');
  }

  /**
   * Check that the callback function is called correctly.
   */
  function testOptionCallback() {

    // When nothing is matched nothing should be passed to the callback.
    $all_files = file_scan_directory($this->path, '/^NONEXISTINGFILENAME/', array('callback' => 'file_test_file_scan_callback'));
    $this->assertEqual(0, count($all_files), 'No files were found.');
    $results = file_test_file_scan_callback();
    file_test_file_scan_callback_reset();
    $this->assertEqual(0, count($results), 'No files were passed to the callback.');

    // Grab a listing of all the JavaSscript files and check that they're
    // passed to the callback.
    $all_files = file_scan_directory($this->path, '/^javascript-/', array('callback' => 'file_test_file_scan_callback'));
    $this->assertEqual(2, count($all_files), 'Found two, expected javascript files.');
    $results = file_test_file_scan_callback();
    file_test_file_scan_callback_reset();
    $this->assertEqual(2, count($results), 'Files were passed to the callback.');
  }

  /**
   * Check that the no-mask parameter is honored.
   */
  function testOptionNoMask() {
    // Grab a listing of all the JavaSscript files.
    $all_files = file_scan_directory($this->path, '/^javascript-/');
    $this->assertEqual(2, count($all_files), 'Found two, expected javascript files.');

    // Now use the nomast parameter to filter out the .script file.
    $filtered_files = file_scan_directory($this->path, '/^javascript-/', array('nomask' => '/.script$/'));
    $this->assertEqual(1, count($filtered_files), 'Filtered correctly.');
  }

  /**
   * Check that key parameter sets the return value's key.
   */
  function testOptionKey() {
    // "filename", for the path starting with $dir.
    $expected = array($this->path . '/javascript-1.txt', $this->path . '/javascript-2.script');
    $actual = array_keys(file_scan_directory($this->path, '/^javascript-/', array('key' => 'filepath')));
    sort($actual);
    $this->assertEqual($expected, $actual, 'Returned the correct values for the filename key.');

    // "basename", for the basename of the file.
    $expected = array('javascript-1.txt', 'javascript-2.script');
    $actual = array_keys(file_scan_directory($this->path, '/^javascript-/', array('key' => 'filename')));
    sort($actual);
    $this->assertEqual($expected, $actual, 'Returned the correct values for the basename key.');

    // "name" for the name of the file without an extension.
    $expected = array('javascript-1', 'javascript-2');
    $actual = array_keys(file_scan_directory($this->path, '/^javascript-/', array('key' => 'name')));
    sort($actual);
    $this->assertEqual($expected, $actual, 'Returned the correct values for the name key.');

    // Invalid option that should default back to "filename".
    $expected = array($this->path . '/javascript-1.txt', $this->path . '/javascript-2.script');
    $actual = array_keys(file_scan_directory($this->path, '/^javascript-/', array('key' => 'INVALID')));
    sort($actual);
    $this->assertEqual($expected, $actual, 'An invalid key defaulted back to the default.');
  }

  /**
   * Check that the recurse option decends into subdirectories.
   */
  function testOptionRecurse() {
    $files = file_scan_directory(drupal_get_path('module', 'simpletest'), '/^javascript-/', array('recurse' => FALSE));
    $this->assertTrue(empty($files), "Without recursion couldn't find javascript files.");

    $files = file_scan_directory(drupal_get_path('module', 'simpletest'), '/^javascript-/', array('recurse' => TRUE));
    $this->assertEqual(2, count($files), 'With recursion we found the expected javascript files.');
  }


  /**
   * Check that the min_depth options lets us ignore files in the starting
   * directory.
   */
  function testOptionMinDepth() {
    $files = file_scan_directory($this->path, '/^javascript-/', array('min_depth' => 0));
    $this->assertEqual(2, count($files), 'No minimum-depth gets files in current directory.');

    $files = file_scan_directory($this->path, '/^javascript-/', array('min_depth' => 1));
    $this->assertTrue(empty($files), 'Minimum-depth of 1 successfully excludes files from current directory.');
  }
}
