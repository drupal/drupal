<?php

namespace Drupal\Tests\file\Kernel;

use Drupal\Core\File\FileSystemInterface;
use Drupal\file\Entity\File;

/**
 * Tests the file copy function.
 *
 * @group file
 */
class CopyTest extends FileManagedUnitTestBase {

  /**
   * Tests file copying in the normal, base case.
   */
  public function testNormal() {
    $contents = $this->randomMachineName(10);
    $source = $this->createFile(NULL, $contents);
    $desired_uri = 'public://' . $this->randomMachineName();

    // Clone the object so we don't have to worry about the function changing
    // our reference copy.
    $result = file_copy(clone $source, $desired_uri, FileSystemInterface::EXISTS_ERROR);

    // Check the return status and that the contents changed.
    $this->assertNotFalse($result, 'File copied successfully.');
    $this->assertEquals($contents, file_get_contents($result->getFileUri()), 'Contents of file were copied correctly.');

    // Check that the correct hooks were called.
    $this->assertFileHooksCalled(['copy', 'insert']);

    $this->assertDifferentFile($source, $result);
    $this->assertEquals($result->getFileUri(), $desired_uri, 'The copied file entity has the desired filepath.');
    $this->assertFileExists($source->getFileUri());
    $this->assertFileExists($result->getFileUri());

    // Reload the file from the database and check that the changes were
    // actually saved.
    $this->assertFileUnchanged($result, File::load($result->id()));
  }

  /**
   * Tests renaming when copying over a file that already exists.
   */
  public function testExistingRename() {
    // Setup a file to overwrite.
    $contents = $this->randomMachineName(10);
    $source = $this->createFile(NULL, $contents);
    $target = $this->createFile();
    $this->assertDifferentFile($source, $target);

    // Clone the object so we don't have to worry about the function changing
    // our reference copy.
    $result = file_copy(clone $source, $target->getFileUri(), FileSystemInterface::EXISTS_RENAME);

    // Check the return status and that the contents changed.
    $this->assertNotFalse($result, 'File copied successfully.');
    $this->assertEquals($contents, file_get_contents($result->getFileUri()), 'Contents of file were copied correctly.');
    $this->assertNotEquals($source->getFileUri(), $result->getFileUri(), 'Returned file path has changed from the original.');

    // Check that the correct hooks were called.
    $this->assertFileHooksCalled(['copy', 'insert']);

    // Load all the affected files to check the changes that actually made it
    // to the database.
    $loaded_source = File::load($source->id());
    $loaded_target = File::load($target->id());
    $loaded_result = File::load($result->id());

    // Verify that the source file wasn't changed.
    $this->assertFileUnchanged($source, $loaded_source);

    // Verify that what was returned is what's in the database.
    $this->assertFileUnchanged($result, $loaded_result);

    // Make sure we end up with three distinct files afterwards.
    $this->assertDifferentFile($loaded_source, $loaded_target);
    $this->assertDifferentFile($loaded_target, $loaded_result);
    $this->assertDifferentFile($loaded_source, $loaded_result);
  }

  /**
   * Tests replacement when copying over a file that already exists.
   */
  public function testExistingReplace() {
    // Setup a file to overwrite.
    $contents = $this->randomMachineName(10);
    $source = $this->createFile(NULL, $contents);
    $target = $this->createFile();
    $this->assertDifferentFile($source, $target);

    // Clone the object so we don't have to worry about the function changing
    // our reference copy.
    $result = file_copy(clone $source, $target->getFileUri(), FileSystemInterface::EXISTS_REPLACE);

    // Check the return status and that the contents changed.
    $this->assertNotFalse($result, 'File copied successfully.');
    $this->assertEquals($contents, file_get_contents($result->getFileUri()), 'Contents of file were overwritten.');
    $this->assertDifferentFile($source, $result);

    // Check that the correct hooks were called.
    $this->assertFileHooksCalled(['load', 'copy', 'update']);

    // Load all the affected files to check the changes that actually made it
    // to the database.
    $loaded_source = File::load($source->id());
    $loaded_target = File::load($target->id());
    $loaded_result = File::load($result->id());

    // Verify that the source file wasn't changed.
    $this->assertFileUnchanged($source, $loaded_source);

    // Verify that what was returned is what's in the database.
    $this->assertFileUnchanged($result, $loaded_result);

    // Target file was reused for the result.
    $this->assertFileUnchanged($loaded_target, $loaded_result);
  }

  /**
   * Tests that copying over an existing file fails when instructed to do so.
   */
  public function testExistingError() {
    $contents = $this->randomMachineName(10);
    $source = $this->createFile();
    $target = $this->createFile(NULL, $contents);
    $this->assertDifferentFile($source, $target);

    // Clone the object so we don't have to worry about the function changing
    // our reference copy.
    $result = file_copy(clone $source, $target->getFileUri(), FileSystemInterface::EXISTS_ERROR);

    // Check the return status and that the contents were not changed.
    $this->assertFalse($result, 'File copy failed.');
    $this->assertEquals($contents, file_get_contents($target->getFileUri()), 'Contents of file were not altered.');

    // Check that the correct hooks were called.
    $this->assertFileHooksCalled([]);

    $this->assertFileUnchanged($source, File::load($source->id()));
    $this->assertFileUnchanged($target, File::load($target->id()));
  }

}
