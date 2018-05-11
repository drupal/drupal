<?php

namespace Drupal\KernelTests\Core\File;

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
  public static $modules = ['file_test'];

  /**
   * @var string
   */
  protected $path;

  protected function setUp() {
    parent::setUp();
    // Hardcode the location of the simpletest files as it is already known
    // and shouldn't change, and we don't yet have a way to retrieve their
    // location from drupal_get_filename() in a cached way.
    // @todo Remove as part of https://www.drupal.org/node/2186491
    $this->path = 'core/modules/simpletest/files';
  }

  /**
   * Check the format of the returned values.
   */
  public function testReturn() {
    // Grab a listing of all the JavaScript files and check that they're
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
  public function testOptionCallback() {

    // When nothing is matched nothing should be passed to the callback.
    $all_files = file_scan_directory($this->path, '/^NONEXISTINGFILENAME/', ['callback' => 'file_test_file_scan_callback']);
    $this->assertEqual(0, count($all_files), 'No files were found.');
    $results = file_test_file_scan_callback();
    file_test_file_scan_callback_reset();
    $this->assertEqual(0, count($results), 'No files were passed to the callback.');

    // Grab a listing of all the JavaScript files and check that they're
    // passed to the callback.
    $all_files = file_scan_directory($this->path, '/^javascript-/', ['callback' => 'file_test_file_scan_callback']);
    $this->assertEqual(2, count($all_files), 'Found two, expected javascript files.');
    $results = file_test_file_scan_callback();
    file_test_file_scan_callback_reset();
    $this->assertEqual(2, count($results), 'Files were passed to the callback.');
  }

  /**
   * Check that the no-mask parameter is honored.
   */
  public function testOptionNoMask() {
    // Grab a listing of all the JavaScript files.
    $all_files = file_scan_directory($this->path, '/^javascript-/');
    $this->assertEqual(2, count($all_files), 'Found two, expected javascript files.');

    // Now use the nomask parameter to filter out the .script file.
    $filtered_files = file_scan_directory($this->path, '/^javascript-/', ['nomask' => '/.script$/']);
    $this->assertEqual(1, count($filtered_files), 'Filtered correctly.');
  }

  /**
   * Check that key parameter sets the return value's key.
   */
  public function testOptionKey() {
    // "filename", for the path starting with $dir.
    $expected = [$this->path . '/javascript-1.txt', $this->path . '/javascript-2.script'];
    $actual = array_keys(file_scan_directory($this->path, '/^javascript-/', ['key' => 'filepath']));
    sort($actual);
    $this->assertEqual($expected, $actual, 'Returned the correct values for the filename key.');

    // "basename", for the basename of the file.
    $expected = ['javascript-1.txt', 'javascript-2.script'];
    $actual = array_keys(file_scan_directory($this->path, '/^javascript-/', ['key' => 'filename']));
    sort($actual);
    $this->assertEqual($expected, $actual, 'Returned the correct values for the basename key.');

    // "name" for the name of the file without an extension.
    $expected = ['javascript-1', 'javascript-2'];
    $actual = array_keys(file_scan_directory($this->path, '/^javascript-/', ['key' => 'name']));
    sort($actual);
    $this->assertEqual($expected, $actual, 'Returned the correct values for the name key.');

    // Invalid option that should default back to "filename".
    $expected = [$this->path . '/javascript-1.txt', $this->path . '/javascript-2.script'];
    $actual = array_keys(file_scan_directory($this->path, '/^javascript-/', ['key' => 'INVALID']));
    sort($actual);
    $this->assertEqual($expected, $actual, 'An invalid key defaulted back to the default.');
  }

  /**
   * Check that the recurse option descends into subdirectories.
   */
  public function testOptionRecurse() {
    $files = file_scan_directory($this->path . '/..', '/^javascript-/', ['recurse' => FALSE]);
    $this->assertTrue(empty($files), "Without recursion couldn't find javascript files.");

    $files = file_scan_directory($this->path . '/..', '/^javascript-/', ['recurse' => TRUE]);
    $this->assertEqual(2, count($files), 'With recursion we found the expected javascript files.');
  }

  /**
   * Check that the min_depth options lets us ignore files in the starting
   * directory.
   */
  public function testOptionMinDepth() {
    $files = file_scan_directory($this->path, '/^javascript-/', ['min_depth' => 0]);
    $this->assertEqual(2, count($files), 'No minimum-depth gets files in current directory.');

    $files = file_scan_directory($this->path, '/^javascript-/', ['min_depth' => 1]);
    $this->assertTrue(empty($files), 'Minimum-depth of 1 successfully excludes files from current directory.');
  }

  /**
   * Tests file_scan_directory() obeys 'file_scan_ignore_directories' setting.
   */
  public function testIgnoreDirectories() {
    $files = file_scan_directory('core/modules/system/tests/fixtures/IgnoreDirectories', '/\.txt$/');
    $this->assertCount(2, $files, '2 text files found when not ignoring directories.');

    $this->setSetting('file_scan_ignore_directories', ['frontend_framework']);
    $files = file_scan_directory('core/modules/system/tests/fixtures/IgnoreDirectories', '/\.txt$/');
    $this->assertCount(1, $files, '1 text files found when ignoring directories called "frontend_framework".');

    // Ensure that the directories in file_scan_ignore_directories are escaped
    // using preg_quote.
    $this->setSetting('file_scan_ignore_directories', ['frontend.*']);
    $files = file_scan_directory('core/modules/system/tests/fixtures/IgnoreDirectories', '/\.txt$/');
    $this->assertCount(2, $files, '2 text files found when ignoring a directory that is not there.');

    $files = file_scan_directory('core/modules/system/tests/fixtures/IgnoreDirectories', '/\.txt$/', ['nomask' => '/^something_thing_else$/']);
    $this->assertCount(2, $files, '2 text files found when an "nomask" option is passed in.');
  }

}
