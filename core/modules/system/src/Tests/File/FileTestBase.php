<?php

/**
 * @file
 * Definition of Drupal\system\Tests\File\FileTestBase.
 */

namespace Drupal\system\Tests\File;

use Drupal\simpletest\DrupalUnitTestBase;

/**
 * Base class for file tests that adds some additional file specific
 * assertions and helper functions.
 */
abstract class FileTestBase extends DrupalUnitTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('system');

  /**
   * A stream wrapper scheme to register for the test.
   *
   * @var string
   */
  protected $scheme;

  /**
   * A fully-qualified stream wrapper class name to register for the test.
   *
   * @var string
   */
  protected $classname;

  protected function setUp() {
    parent::setUp();
    $this->installConfig(array('system'));
    $this->registerStreamWrapper('private', 'Drupal\Core\StreamWrapper\PrivateStream');

    if (isset($this->scheme)) {
      $this->registerStreamWrapper($this->scheme, $this->classname);
    }
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
      $path = file_default_scheme() . '://' . $this->randomMachineName();
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
      $filepath = 'Файл для тестирования ' . $this->randomMachineName();
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
