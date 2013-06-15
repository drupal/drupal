<?php

/**
 * @file
 * Definition of Drupal\system\Tests\File\FileTestBase.
 */

namespace Drupal\system\Tests\File;

use Drupal\file\FileInterface;
use Drupal\simpletest\WebTestBase;

/**
 * Base class for file tests that adds some additional file specific
 * assertions and helper functions.
 */
abstract class FileTestBase extends WebTestBase {

  function setUp() {
    parent::setUp();
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
   * @param \Drupal\file\FileInterface $before
   *   File object to compare.
   * @param \Drupal\file\FileInterface $after
   *   File object to compare.
   */
  function assertFileUnchanged(FileInterface $before, FileInterface $after) {
    $this->assertEqual($before->id(), $after->id(), t('File id is the same: %file1 == %file2.', array('%file1' => $before->id(), '%file2' => $after->id())), 'File unchanged');
    $this->assertEqual($before->getOwner()->id(), $after->getOwner()->id(), t('File owner is the same: %file1 == %file2.', array('%file1' => $before->getOwner()->id(), '%file2' => $after->getOwner()->id())), 'File unchanged');
    $this->assertEqual($before->getFilename(), $after->getFilename(), t('File name is the same: %file1 == %file2.', array('%file1' => $before->getFilename(), '%file2' => $after->getFilename())), 'File unchanged');
    $this->assertEqual($before->getFileUri(), $after->getFileUri(), t('File path is the same: %file1 == %file2.', array('%file1' => $before->getFileUri(), '%file2' => $after->getFileUri())), 'File unchanged');
    $this->assertEqual($before->getMimeType(), $after->getMimeType(), t('File MIME type is the same: %file1 == %file2.', array('%file1' => $before->getMimeType(), '%file2' => $after->getMimeType())), 'File unchanged');
    $this->assertEqual($before->getSize(), $after->getSize(), t('File size is the same: %file1 == %file2.', array('%file1' => $before->getSize(), '%file2' => $after->getSize())), 'File unchanged');
    $this->assertEqual($before->isPermanent(), $after->isPermanent(), t('File status is the same: %file1 == %file2.', array('%file1' => $before->isPermanent(), '%file2' => $after->isPermanent())), 'File unchanged');
  }

  /**
   * Check that two files are not the same by comparing the fid and filepath.
   *
   * @param \Drupal\file\FileInterface $file1
   *   File object to compare.
   * @param \Drupal\file\FileInterface $file2
   *   File object to compare.
   */
  function assertDifferentFile(FileInterface $file1, FileInterface $file2) {
    $this->assertNotEqual($file1->id(), $file2->id(), t('Files have different ids: %file1 != %file2.', array('%file1' => $file1->id(), '%file2' => $file2->id())), 'Different file');
    $this->assertNotEqual($file1->getFileUri(), $file2->getFileUri(), t('Files have different paths: %file1 != %file2.', array('%file1' => $file1->getFileUri(), '%file2' => $file2->getFileUri())), 'Different file');
  }

  /**
   * Check that two files are the same by comparing the fid and filepath.
   *
   * @param \Drupal\file\FileInterface $file1
   *   File object to compare.
   * @param \Drupal\file\FileInterface $file2
   *   File object to compare.
   */
  function assertSameFile(FileInterface $file1, FileInterface $file2) {
    $this->assertEqual($file1->id(), $file2->id(), t('Files have the same ids: %file1 == %file2.', array('%file1' => $file1->id(), '%file2-fid' => $file2->id())), 'Same file');
    $this->assertEqual($file1->getFileUri(), $file2->getFileUri(), t('Files have the same path: %file1 == %file2.', array('%file1' => $file1->getFileUri(), '%file2' => $file2->getFileUri())), 'Same file');
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
    // Configuration system stores default modes as strings.
    if (is_string($expected_mode)) {
      // Convert string to octal.
      $expected_mode = octdec($expected_mode);
    }
    // Clear out PHP's file stat cache to be sure we see the current value.
    clearstatcache(TRUE, $filepath);

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
    // Configuration system stores default modes as strings.
    if (is_string($expected_mode)) {
      // Convert string to octal.
      $expected_mode = octdec($expected_mode);
    }
    // Clear out PHP's file stat cache to be sure we see the current value.
    clearstatcache(TRUE, $directory);

    // Mask out all but the last three octets.
    $actual_mode = fileperms($directory) & 0777;
    $expected_mode = $expected_mode & 0777;

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
    $this->assertTrue(drupal_mkdir($path) && is_dir($path), 'Directory was created successfully.');
    return $path;
  }

    /**
   * Create a file and return the URI of it.
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
   *   File URI.
   */
  function createUri($filepath = NULL, $contents = NULL, $scheme = NULL) {
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
    return $filepath;
  }
}
