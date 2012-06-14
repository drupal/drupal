<?php

/**
 * @file
 * Definition of Drupal\system\Tests\File\CopyTest.
 */

namespace Drupal\system\Tests\File;

/**
 * Copy related tests.
 */
class CopyTest extends FileHookTestBase {
  public static function getInfo() {
    return array(
      'name' => 'File copying',
      'description' => 'Tests the file copy function.',
      'group' => 'File API',
    );
  }

  /**
   * Test file copying in the normal, base case.
   */
  function testNormal() {
    $contents = $this->randomName(10);
    $source = $this->createFile(NULL, $contents);
    $desired_uri = 'public://' . $this->randomName();

    // Clone the object so we don't have to worry about the function changing
    // our reference copy.
    $result = file_copy(clone $source, $desired_uri, FILE_EXISTS_ERROR);

    // Check the return status and that the contents changed.
    $this->assertTrue($result, t('File copied successfully.'));
    $this->assertEqual($contents, file_get_contents($result->uri), t('Contents of file were copied correctly.'));

    // Check that the correct hooks were called.
    $this->assertFileHooksCalled(array('copy', 'insert'));

    $this->assertDifferentFile($source, $result);
    $this->assertEqual($result->uri, $desired_uri, t('The copied file entity has the desired filepath.'));
    $this->assertTrue(file_exists($source->uri), t('The original file still exists.'));
    $this->assertTrue(file_exists($result->uri), t('The copied file exists.'));

    // Reload the file from the database and check that the changes were
    // actually saved.
    $this->assertFileUnchanged($result, file_load($result->fid, TRUE));
  }

  /**
   * Test renaming when copying over a file that already exists.
   */
  function testExistingRename() {
    // Setup a file to overwrite.
    $contents = $this->randomName(10);
    $source = $this->createFile(NULL, $contents);
    $target = $this->createFile();
    $this->assertDifferentFile($source, $target);

    // Clone the object so we don't have to worry about the function changing
    // our reference copy.
    $result = file_copy(clone $source, $target->uri, FILE_EXISTS_RENAME);

    // Check the return status and that the contents changed.
    $this->assertTrue($result, t('File copied successfully.'));
    $this->assertEqual($contents, file_get_contents($result->uri), t('Contents of file were copied correctly.'));
    $this->assertNotEqual($result->uri, $source->uri, t('Returned file path has changed from the original.'));

    // Check that the correct hooks were called.
    $this->assertFileHooksCalled(array('copy', 'insert'));

    // Load all the affected files to check the changes that actually made it
    // to the database.
    $loaded_source = file_load($source->fid, TRUE);
    $loaded_target = file_load($target->fid, TRUE);
    $loaded_result = file_load($result->fid, TRUE);

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
   * Test replacement when copying over a file that already exists.
   */
  function testExistingReplace() {
    // Setup a file to overwrite.
    $contents = $this->randomName(10);
    $source = $this->createFile(NULL, $contents);
    $target = $this->createFile();
    $this->assertDifferentFile($source, $target);

    // Clone the object so we don't have to worry about the function changing
    // our reference copy.
    $result = file_copy(clone $source, $target->uri, FILE_EXISTS_REPLACE);

    // Check the return status and that the contents changed.
    $this->assertTrue($result, t('File copied successfully.'));
    $this->assertEqual($contents, file_get_contents($result->uri), t('Contents of file were overwritten.'));
    $this->assertDifferentFile($source, $result);

    // Check that the correct hooks were called.
    $this->assertFileHooksCalled(array('load', 'copy', 'update'));

    // Load all the affected files to check the changes that actually made it
    // to the database.
    $loaded_source = file_load($source->fid, TRUE);
    $loaded_target = file_load($target->fid, TRUE);
    $loaded_result = file_load($result->fid, TRUE);

    // Verify that the source file wasn't changed.
    $this->assertFileUnchanged($source, $loaded_source);

    // Verify that what was returned is what's in the database.
    $this->assertFileUnchanged($result, $loaded_result);

    // Target file was reused for the result.
    $this->assertFileUnchanged($loaded_target, $loaded_result);
  }

  /**
   * Test that copying over an existing file fails when FILE_EXISTS_ERROR is
   * specified.
   */
  function testExistingError() {
    $contents = $this->randomName(10);
    $source = $this->createFile();
    $target = $this->createFile(NULL, $contents);
    $this->assertDifferentFile($source, $target);

    // Clone the object so we don't have to worry about the function changing
    // our reference copy.
    $result = file_copy(clone $source, $target->uri, FILE_EXISTS_ERROR);

    // Check the return status and that the contents were not changed.
    $this->assertFalse($result, t('File copy failed.'));
    $this->assertEqual($contents, file_get_contents($target->uri), t('Contents of file were not altered.'));

    // Check that the correct hooks were called.
    $this->assertFileHooksCalled(array());

    $this->assertFileUnchanged($source, file_load($source->fid, TRUE));
    $this->assertFileUnchanged($target, file_load($target->fid, TRUE));
  }
}
