<?php

/**
 * @file
 * Definition of Drupal\system\Tests\File\MoveTest.
 */

namespace Drupal\system\Tests\File;

/**
 * Move related tests
 */
class MoveTest extends FileHookTestBase {
  public static function getInfo() {
    return array(
      'name' => 'File moving',
      'description' => 'Tests the file move function.',
      'group' => 'File API',
    );
  }

  /**
   * Move a normal file.
   */
  function testNormal() {
    $contents = $this->randomName(10);
    $source = $this->createFile(NULL, $contents);
    $desired_filepath = 'public://' . $this->randomName();

    // Clone the object so we don't have to worry about the function changing
    // our reference copy.
    $result = file_move(clone $source, $desired_filepath, FILE_EXISTS_ERROR);

    // Check the return status and that the contents changed.
    $this->assertTrue($result, t('File moved successfully.'));
    $this->assertFalse(file_exists($source->uri));
    $this->assertEqual($contents, file_get_contents($result->uri), t('Contents of file correctly written.'));

    // Check that the correct hooks were called.
    $this->assertFileHooksCalled(array('move', 'load', 'update'));

    // Make sure we got the same file back.
    $this->assertEqual($source->fid, $result->fid, t("Source file id's' %fid is unchanged after move.", array('%fid' => $source->fid)));

    // Reload the file from the database and check that the changes were
    // actually saved.
    $loaded_file = file_load($result->fid, TRUE);
    $this->assertTrue($loaded_file, t('File can be loaded from the database.'));
    $this->assertFileUnchanged($result, $loaded_file);
  }

  /**
   * Test renaming when moving onto a file that already exists.
   */
  function testExistingRename() {
    // Setup a file to overwrite.
    $contents = $this->randomName(10);
    $source = $this->createFile(NULL, $contents);
    $target = $this->createFile();
    $this->assertDifferentFile($source, $target);

    // Clone the object so we don't have to worry about the function changing
    // our reference copy.
    $result = file_move(clone $source, $target->uri, FILE_EXISTS_RENAME);

    // Check the return status and that the contents changed.
    $this->assertTrue($result, t('File moved successfully.'));
    $this->assertFalse(file_exists($source->uri));
    $this->assertEqual($contents, file_get_contents($result->uri), t('Contents of file correctly written.'));

    // Check that the correct hooks were called.
    $this->assertFileHooksCalled(array('move', 'load', 'update'));

    // Compare the returned value to what made it into the database.
    $this->assertFileUnchanged($result, file_load($result->fid, TRUE));
    // The target file should not have been altered.
    $this->assertFileUnchanged($target, file_load($target->fid, TRUE));
    // Make sure we end up with two distinct files afterwards.
    $this->assertDifferentFile($target, $result);

    // Compare the source and results.
    $loaded_source = file_load($source->fid, TRUE);
    $this->assertEqual($loaded_source->fid, $result->fid, t("Returned file's id matches the source."));
    $this->assertNotEqual($loaded_source->uri, $source->uri, t("Returned file path has changed from the original."));
  }

  /**
   * Test replacement when moving onto a file that already exists.
   */
  function testExistingReplace() {
    // Setup a file to overwrite.
    $contents = $this->randomName(10);
    $source = $this->createFile(NULL, $contents);
    $target = $this->createFile();
    $this->assertDifferentFile($source, $target);

    // Clone the object so we don't have to worry about the function changing
    // our reference copy.
    $result = file_move(clone $source, $target->uri, FILE_EXISTS_REPLACE);

    // Look at the results.
    $this->assertEqual($contents, file_get_contents($result->uri), t('Contents of file were overwritten.'));
    $this->assertFalse(file_exists($source->uri));
    $this->assertTrue($result, t('File moved successfully.'));

    // Check that the correct hooks were called.
    $this->assertFileHooksCalled(array('move', 'update', 'delete', 'load'));

    // Reload the file from the database and check that the changes were
    // actually saved.
    $loaded_result = file_load($result->fid, TRUE);
    $this->assertFileUnchanged($result, $loaded_result);
    // Check that target was re-used.
    $this->assertSameFile($target, $loaded_result);
    // Source and result should be totally different.
    $this->assertDifferentFile($source, $loaded_result);
  }

  /**
   * Test replacement when moving onto itself.
   */
  function testExistingReplaceSelf() {
    // Setup a file to overwrite.
    $contents = $this->randomName(10);
    $source = $this->createFile(NULL, $contents);

    // Copy the file over itself. Clone the object so we don't have to worry
    // about the function changing our reference copy.
    $result = file_move(clone $source, $source->uri, FILE_EXISTS_REPLACE);
    $this->assertFalse($result, t('File move failed.'));
    $this->assertEqual($contents, file_get_contents($source->uri), t('Contents of file were not altered.'));

    // Check that no hooks were called while failing.
    $this->assertFileHooksCalled(array());

    // Load the file from the database and make sure it is identical to what
    // was returned.
    $this->assertFileUnchanged($source, file_load($source->fid, TRUE));
  }

  /**
   * Test that moving onto an existing file fails when FILE_EXISTS_ERROR is
   * specified.
   */
  function testExistingError() {
    $contents = $this->randomName(10);
    $source = $this->createFile();
    $target = $this->createFile(NULL, $contents);
    $this->assertDifferentFile($source, $target);

    // Clone the object so we don't have to worry about the function changing
    // our reference copy.
    $result = file_move(clone $source, $target->uri, FILE_EXISTS_ERROR);

    // Check the return status and that the contents did not change.
    $this->assertFalse($result, t('File move failed.'));
    $this->assertTrue(file_exists($source->uri));
    $this->assertEqual($contents, file_get_contents($target->uri), t('Contents of file were not altered.'));

    // Check that no hooks were called while failing.
    $this->assertFileHooksCalled(array());

    // Load the file from the database and make sure it is identical to what
    // was returned.
    $this->assertFileUnchanged($source, file_load($source->fid, TRUE));
    $this->assertFileUnchanged($target, file_load($target->fid, TRUE));
  }
}
