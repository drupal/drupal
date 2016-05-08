<?php

namespace Drupal\system\Tests\File;

/**
 * Tests the read-only stream wrapper write functions.
 *
 * @group File
 */
class ReadOnlyStreamWrapperTest extends FileTestBase {

  /**
   * A stream wrapper scheme to register for the test.
   *
   * @var string
   */
  protected $scheme = 'dummy-readonly';

  /**
   * A fully-qualified stream wrapper class name to register for the test.
   *
   * @var string
   */
  protected $classname = 'Drupal\file_test\StreamWrapper\DummyReadOnlyStreamWrapper';

  /**
   * Test read-only specific behavior.
   */
  function testReadOnlyBehavior() {
    // Generate a test file
    $filename = $this->randomMachineName();
    $site_path = $this->container->get('site.path');
    $filepath = $site_path . '/files/' . $filename;
    file_put_contents($filepath, $filename);

    // Generate a read-only stream wrapper instance
    $uri = $this->scheme . '://' . $filename;
    \Drupal::service('stream_wrapper_manager')->getViaScheme($this->scheme);

    // Attempt to open a file in read/write mode
    $handle = @fopen($uri, 'r+');
    $this->assertFalse($handle, 'Unable to open a file for reading and writing with the read-only stream wrapper.');
    // Attempt to open a file in binary read mode
    $handle = fopen($uri, 'rb');
    $this->assertTrue($handle, 'Able to open a file for reading in binary mode with the read-only stream wrapper.');
    $this->assertTrue(fclose($handle), 'Able to close file opened in binary mode using the read_only stream wrapper.');
    // Attempt to open a file in text read mode
    $handle = fopen($uri, 'rt');
    $this->assertTrue($handle, 'Able to open a file for reading in text mode with the read-only stream wrapper.');
    $this->assertTrue(fclose($handle), 'Able to close file opened in text mode using the read_only stream wrapper.');
    // Attempt to open a file in read mode
    $handle = fopen($uri, 'r');
    $this->assertTrue($handle, 'Able to open a file for reading with the read-only stream wrapper.');
    // Attempt to change file permissions
    $this->assertFalse(@chmod($uri, 0777), 'Unable to change file permissions when using read-only stream wrapper.');
    // Attempt to acquire an exclusive lock for writing
    $this->assertFalse(@flock($handle, LOCK_EX | LOCK_NB), 'Unable to acquire an exclusive lock using the read-only stream wrapper.');
    // Attempt to obtain a shared lock
    $this->assertTrue(flock($handle, LOCK_SH | LOCK_NB), 'Able to acquire a shared lock using the read-only stream wrapper.');
    // Attempt to release a shared lock
    $this->assertTrue(flock($handle, LOCK_UN | LOCK_NB), 'Able to release a shared lock using the read-only stream wrapper.');
    // Attempt to truncate the file
    $this->assertFalse(@ftruncate($handle, 0), 'Unable to truncate using the read-only stream wrapper.');
    // Attempt to write to the file
    $this->assertFalse(@fwrite($handle, $this->randomMachineName()), 'Unable to write to file using the read-only stream wrapper.');
    // Attempt to flush output to the file
    $this->assertFalse(@fflush($handle), 'Unable to flush output to file using the read-only stream wrapper.');
    // Attempt to close the stream.  (Suppress errors, as fclose triggers fflush.)
    $this->assertTrue(fclose($handle), 'Able to close file using the read_only stream wrapper.');
    // Test the rename() function
    $this->assertFalse(@rename($uri, $this->scheme . '://newname.txt'), 'Unable to rename files using the read-only stream wrapper.');
    // Test the unlink() function
    $this->assertTrue(@drupal_unlink($uri), 'Able to unlink file using read-only stream wrapper.');
    $this->assertTrue(file_exists($filepath), 'Unlink File was not actually deleted.');

    // Test the mkdir() function by attempting to create a directory.
    $dirname = $this->randomMachineName();
    $dir = $site_path . '/files/' . $dirname;
    $readonlydir = $this->scheme . '://' . $dirname;
    $this->assertFalse(@drupal_mkdir($readonlydir, 0775, 0), 'Unable to create directory with read-only stream wrapper.');
    // Create a temporary directory for testing purposes
    $this->assertTrue(drupal_mkdir($dir), 'Test directory created.');
    // Test the rmdir() function by attempting to remove the directory.
    $this->assertFalse(@drupal_rmdir($readonlydir), 'Unable to delete directory with read-only stream wrapper.');
    // Remove the temporary directory.
    drupal_rmdir($dir);
  }

}
