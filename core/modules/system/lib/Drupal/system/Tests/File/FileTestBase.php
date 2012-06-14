<?php

/**
 * @file
 * Definition of Drupal\system\Tests\File\FileTestBase.
 */

namespace Drupal\system\Tests\File;

use Drupal\simpletest\WebTestBase;
use stdClass;

/**
 * Base class for file tests that adds some additional file specific
 * assertions and helper functions.
 */
class FileTestBase extends WebTestBase {

  function setUp() {
    $modules = func_get_args();
    $modules = (isset($modules[0]) && is_array($modules[0]) ? $modules[0] : $modules);
    parent::setUp($modules);

    // Make sure that custom stream wrappers are registered.
    // @todo This has the potential to be a major bug deeply buried in File API;
    //   file_unmanaged_*() API functions and test functions are invoking native
    //   PHP functions directly, whereas Drupal's custom stream wrappers are not
    //   registered yet.
    file_get_stream_wrappers();
  }

  /**
   * Check that two files have the same values for all fields other than the
   * timestamp.
   *
   * @param $before
   *   File object to compare.
   * @param $after
   *   File object to compare.
   */
  function assertFileUnchanged($before, $after) {
    $this->assertEqual($before->fid, $after->fid, t('File id is the same: %file1 == %file2.', array('%file1' => $before->fid, '%file2' => $after->fid)), 'File unchanged');
    $this->assertEqual($before->uid, $after->uid, t('File owner is the same: %file1 == %file2.', array('%file1' => $before->uid, '%file2' => $after->uid)), 'File unchanged');
    $this->assertEqual($before->filename, $after->filename, t('File name is the same: %file1 == %file2.', array('%file1' => $before->filename, '%file2' => $after->filename)), 'File unchanged');
    $this->assertEqual($before->uri, $after->uri, t('File path is the same: %file1 == %file2.', array('%file1' => $before->uri, '%file2' => $after->uri)), 'File unchanged');
    $this->assertEqual($before->filemime, $after->filemime, t('File MIME type is the same: %file1 == %file2.', array('%file1' => $before->filemime, '%file2' => $after->filemime)), 'File unchanged');
    $this->assertEqual($before->filesize, $after->filesize, t('File size is the same: %file1 == %file2.', array('%file1' => $before->filesize, '%file2' => $after->filesize)), 'File unchanged');
    $this->assertEqual($before->status, $after->status, t('File status is the same: %file1 == %file2.', array('%file1' => $before->status, '%file2' => $after->status)), 'File unchanged');
  }

  /**
   * Check that two files are not the same by comparing the fid and filepath.
   *
   * @param $file1
   *   File object to compare.
   * @param $file2
   *   File object to compare.
   */
  function assertDifferentFile($file1, $file2) {
    $this->assertNotEqual($file1->fid, $file2->fid, t('Files have different ids: %file1 != %file2.', array('%file1' => $file1->fid, '%file2' => $file2->fid)), 'Different file');
    $this->assertNotEqual($file1->uri, $file2->uri, t('Files have different paths: %file1 != %file2.', array('%file1' => $file1->uri, '%file2' => $file2->uri)), 'Different file');
  }

  /**
   * Check that two files are the same by comparing the fid and filepath.
   *
   * @param $file1
   *   File object to compare.
   * @param $file2
   *   File object to compare.
   */
  function assertSameFile($file1, $file2) {
    $this->assertEqual($file1->fid, $file2->fid, t('Files have the same ids: %file1 == %file2.', array('%file1' => $file1->fid, '%file2-fid' => $file2->fid)), 'Same file');
    $this->assertEqual($file1->uri, $file2->uri, t('Files have the same path: %file1 == %file2.', array('%file1' => $file1->uri, '%file2' => $file2->uri)), 'Same file');
  }

  /**
   * Helper function to test the permissions of a file.
   *
   * @param $filepath
   *   String file path.
   * @param $expected_mode
   *   Octal integer like 0664 or 0777.
   * @param $message
   *   Optional message.
   */
  function assertFilePermissions($filepath, $expected_mode, $message = NULL) {
    // Clear out PHP's file stat cache to be sure we see the current value.
    clearstatcache();

    // Mask out all but the last three octets.
    $actual_mode = fileperms($filepath) & 0777;

    // PHP on Windows has limited support for file permissions. Usually each of
    // "user", "group" and "other" use one octal digit (3 bits) to represent the
    // read/write/execute bits. On Windows, chmod() ignores the "group" and
    // "other" bits, and fileperms() returns the "user" bits in all three
    // positions. $expected_mode is updated to reflect this.
    if (substr(PHP_OS, 0, 3) == 'WIN') {
      // Reset the "group" and "other" bits.
      $expected_mode = $expected_mode & 0700;
      // Shift the "user" bits to the "group" and "other" positions also.
      $expected_mode = $expected_mode | $expected_mode >> 3 | $expected_mode >> 6;
    }

    if (!isset($message)) {
      $message = t('Expected file permission to be %expected, actually were %actual.', array('%actual' => decoct($actual_mode), '%expected' => decoct($expected_mode)));
    }
    $this->assertEqual($actual_mode, $expected_mode, $message);
  }

  /**
   * Helper function to test the permissions of a directory.
   *
   * @param $directory
   *   String directory path.
   * @param $expected_mode
   *   Octal integer like 0664 or 0777.
   * @param $message
   *   Optional message.
   */
  function assertDirectoryPermissions($directory, $expected_mode, $message = NULL) {
    // Clear out PHP's file stat cache to be sure we see the current value.
    clearstatcache();

    // Mask out all but the last three octets.
    $actual_mode = fileperms($directory) & 0777;

    // PHP on Windows has limited support for file permissions. Usually each of
    // "user", "group" and "other" use one octal digit (3 bits) to represent the
    // read/write/execute bits. On Windows, chmod() ignores the "group" and
    // "other" bits, and fileperms() returns the "user" bits in all three
    // positions. $expected_mode is updated to reflect this.
    if (substr(PHP_OS, 0, 3) == 'WIN') {
      // Reset the "group" and "other" bits.
      $expected_mode = $expected_mode & 0700;
      // Shift the "user" bits to the "group" and "other" positions also.
      $expected_mode = $expected_mode | $expected_mode >> 3 | $expected_mode >> 6;
    }

    if (!isset($message)) {
      $message = t('Expected directory permission to be %expected, actually were %actual.', array('%actual' => decoct($actual_mode), '%expected' => decoct($expected_mode)));
    }
    $this->assertEqual($actual_mode, $expected_mode, $message);
  }

  /**
   * Create a directory and assert it exists.
   *
   * @param $path
   *   Optional string with a directory path. If none is provided, a random
   *   name in the site's files directory will be used.
   * @return
   *   The path to the directory.
   */
  function createDirectory($path = NULL) {
    // A directory to operate on.
    if (!isset($path)) {
      $path = file_default_scheme() . '://' . $this->randomName();
    }
    $this->assertTrue(drupal_mkdir($path) && is_dir($path), t('Directory was created successfully.'));
    return $path;
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
   * @return
   *   File object.
   */
  function createFile($filepath = NULL, $contents = NULL, $scheme = NULL) {
    if (!isset($filepath)) {
      // Prefix with non-latin characters to ensure that all file-related
      // tests work with international filenames.
      $filepath = 'Файл для тестирования ' . $this->randomName();
    }
    if (!isset($scheme)) {
      $scheme = file_default_scheme();
    }
    $filepath = $scheme . '://' . $filepath;

    if (!isset($contents)) {
      $contents = "file_put_contents() doesn't seem to appreciate empty strings so let's put in some data.";
    }

    file_put_contents($filepath, $contents);
    $this->assertTrue(is_file($filepath), t('The test file exists on the disk.'), 'Create test file');

    $file = new stdClass();
    $file->uri = $filepath;
    $file->filename = drupal_basename($file->uri);
    $file->filemime = 'text/plain';
    $file->uid = 1;
    $file->timestamp = REQUEST_TIME;
    $file->filesize = filesize($file->uri);
    $file->status = 0;
    // Write the record directly rather than using the API so we don't invoke
    // the hooks.
    $this->assertNotIdentical(drupal_write_record('file_managed', $file), FALSE, t('The file was added to the database.'), 'Create test file');

    return entity_create('file', (array) $file);
  }
}
