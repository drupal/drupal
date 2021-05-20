<?php

namespace Drupal\Tests\file\Kernel;

use Drupal\Core\StreamWrapper\StreamWrapperManagerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\file\Entity\File;

/**
 * Tests the file_save_data() function.
 *
 * @group file
 */
class SaveDataTest extends FileManagedUnitTestBase {

  /**
   * Test the file_save_data() function when no filename is provided.
   */
  public function testWithoutFilename() {
    $contents = $this->randomMachineName(8);

    $result = file_save_data($contents);
    $this->assertNotFalse($result, 'Unnamed file saved correctly.');

    $stream_wrapper_manager = \Drupal::service('stream_wrapper_manager');
    assert($stream_wrapper_manager instanceof StreamWrapperManagerInterface);
    $this->assertEquals(\Drupal::config('system.file')->get('default_scheme'), $stream_wrapper_manager::getScheme($result->getFileUri()), "File was placed in Drupal's files directory.");
    $this->assertEquals(\Drupal::service('file_system')->basename($result->getFileUri()), $result->getFilename(), "Filename was set to the file's basename.");
    $this->assertEquals($contents, file_get_contents($result->getFileUri()), 'Contents of the file are correct.');
    $this->assertEquals('application/octet-stream', $result->getMimeType(), 'A MIME type was set.');
    $this->assertTrue($result->isPermanent(), "The file's status was set to permanent.");

    // Check that the correct hooks were called.
    $this->assertFileHooksCalled(['insert']);

    // Verify that what was returned is what's in the database.
    $this->assertFileUnchanged($result, File::load($result->id()));
  }

  /**
   * Test the file_save_data() function when a filename is provided.
   */
  public function testWithFilename() {
    $contents = $this->randomMachineName(8);

    // Using filename with non-latin characters.
    // cSpell:disable-next-line
    $filename = 'Текстовый файл.txt';

    $result = file_save_data($contents, 'public://' . $filename);
    $this->assertNotFalse($result, 'Unnamed file saved correctly.');

    $stream_wrapper_manager = \Drupal::service('stream_wrapper_manager');
    assert($stream_wrapper_manager instanceof StreamWrapperManagerInterface);
    $this->assertEquals('public', $stream_wrapper_manager::getScheme($result->getFileUri()), "File was placed in Drupal's files directory.");
    $this->assertEquals($filename, \Drupal::service('file_system')->basename($result->getFileUri()), 'File was named correctly.');
    $this->assertEquals($contents, file_get_contents($result->getFileUri()), 'Contents of the file are correct.');
    $this->assertEquals('text/plain', $result->getMimeType(), 'A MIME type was set.');
    $this->assertTrue($result->isPermanent(), "The file's status was set to permanent.");

    // Check that the correct hooks were called.
    $this->assertFileHooksCalled(['insert']);

    // Verify that what was returned is what's in the database.
    $this->assertFileUnchanged($result, File::load($result->id()));
  }

  /**
   * Test file_save_data() when renaming around an existing file.
   */
  public function testExistingRename() {
    // Setup a file to overwrite.
    $existing = $this->createFile();
    $contents = $this->randomMachineName(8);

    $result = file_save_data($contents, $existing->getFileUri(), FileSystemInterface::EXISTS_RENAME);
    $this->assertNotFalse($result, 'File saved successfully.');

    $stream_wrapper_manager = \Drupal::service('stream_wrapper_manager');
    assert($stream_wrapper_manager instanceof StreamWrapperManagerInterface);
    $this->assertEquals('public', $stream_wrapper_manager::getScheme($result->getFileUri()), "File was placed in Drupal's files directory.");
    $this->assertEquals($existing->getFilename(), $result->getFilename(), 'Filename was set to the basename of the source, rather than that of the renamed file.');
    $this->assertEquals($contents, file_get_contents($result->getFileUri()), 'Contents of the file are correct.');
    $this->assertEquals('application/octet-stream', $result->getMimeType(), 'A MIME type was set.');
    $this->assertTrue($result->isPermanent(), "The file's status was set to permanent.");

    // Check that the correct hooks were called.
    $this->assertFileHooksCalled(['insert']);

    // Ensure that the existing file wasn't overwritten.
    $this->assertDifferentFile($existing, $result);
    $this->assertFileUnchanged($existing, File::load($existing->id()));

    // Verify that was returned is what's in the database.
    $this->assertFileUnchanged($result, File::load($result->id()));
  }

  /**
   * Test file_save_data() when replacing an existing file.
   */
  public function testExistingReplace() {
    // Setup a file to overwrite.
    $existing = $this->createFile();
    $contents = $this->randomMachineName(8);

    $result = file_save_data($contents, $existing->getFileUri(), FileSystemInterface::EXISTS_REPLACE);
    $this->assertNotFalse($result, 'File saved successfully.');

    $stream_wrapper_manager = \Drupal::service('stream_wrapper_manager');
    assert($stream_wrapper_manager instanceof StreamWrapperManagerInterface);
    $this->assertEquals('public', $stream_wrapper_manager::getScheme($result->getFileUri()), "File was placed in Drupal's files directory.");
    $this->assertEquals($existing->getFilename(), $result->getFilename(), 'Filename was set to the basename of the existing file, rather than preserving the original name.');
    $this->assertEquals($contents, file_get_contents($result->getFileUri()), 'Contents of the file are correct.');
    $this->assertEquals('application/octet-stream', $result->getMimeType(), 'A MIME type was set.');
    $this->assertTrue($result->isPermanent(), "The file's status was set to permanent.");

    // Check that the correct hooks were called.
    $this->assertFileHooksCalled(['load', 'update']);

    // Verify that the existing file was re-used.
    $this->assertSameFile($existing, $result);

    // Verify that what was returned is what's in the database.
    $this->assertFileUnchanged($result, File::load($result->id()));
  }

  /**
   * Test that file_save_data() fails overwriting an existing file.
   */
  public function testExistingError() {
    $contents = $this->randomMachineName(8);
    $existing = $this->createFile(NULL, $contents);

    // Check the overwrite error.
    $result = file_save_data('asdf', $existing->getFileUri(), FileSystemInterface::EXISTS_ERROR);
    $this->assertFalse($result, 'Overwriting a file fails when FileSystemInterface::EXISTS_ERROR is specified.');
    $this->assertEquals($contents, file_get_contents($existing->getFileUri()), 'Contents of existing file were unchanged.');

    // Check that no hooks were called while failing.
    $this->assertFileHooksCalled([]);

    // Ensure that the existing file wasn't overwritten.
    $this->assertFileUnchanged($existing, File::load($existing->id()));
  }

}
