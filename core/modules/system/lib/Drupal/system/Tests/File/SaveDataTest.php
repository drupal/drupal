<?php

/**
 * @file
 * Definition of Drupal\system\Tests\File\SaveDataTest.
 */

namespace Drupal\system\Tests\File;

/**
 * Tests the file_save_data() function.
 */
class SaveDataTest extends FileHookTestBase {
  public static function getInfo() {
    return array(
      'name' => 'File save data',
      'description' => 'Tests the file save data function.',
      'group' => 'File API',
    );
  }

  /**
   * Test the file_save_data() function when no filename is provided.
   */
  function testWithoutFilename() {
    $contents = $this->randomName(8);

    $result = file_save_data($contents);
    $this->assertTrue($result, t('Unnamed file saved correctly.'));

    $this->assertEqual(file_default_scheme(), file_uri_scheme($result->uri), t("File was placed in Drupal's files directory."));
    $this->assertEqual($result->filename, drupal_basename($result->uri), t("Filename was set to the file's basename."));
    $this->assertEqual($contents, file_get_contents($result->uri), t('Contents of the file are correct.'));
    $this->assertEqual($result->filemime, 'application/octet-stream', t('A MIME type was set.'));
    $this->assertEqual($result->status, FILE_STATUS_PERMANENT, t("The file's status was set to permanent."));

    // Check that the correct hooks were called.
    $this->assertFileHooksCalled(array('insert'));

    // Verify that what was returned is what's in the database.
    $this->assertFileUnchanged($result, file_load($result->fid, TRUE));
  }

  /**
   * Test the file_save_data() function when a filename is provided.
   */
  function testWithFilename() {
    $contents = $this->randomName(8);

    // Using filename with non-latin characters.
    $filename = 'Текстовый файл.txt';

    $result = file_save_data($contents, 'public://' . $filename);
    $this->assertTrue($result, t('Unnamed file saved correctly.'));

    $this->assertEqual('public', file_uri_scheme($result->uri), t("File was placed in Drupal's files directory."));
    $this->assertEqual($filename, drupal_basename($result->uri), t('File was named correctly.'));
    $this->assertEqual($contents, file_get_contents($result->uri), t('Contents of the file are correct.'));
    $this->assertEqual($result->filemime, 'text/plain', t('A MIME type was set.'));
    $this->assertEqual($result->status, FILE_STATUS_PERMANENT, t("The file's status was set to permanent."));

    // Check that the correct hooks were called.
    $this->assertFileHooksCalled(array('insert'));

    // Verify that what was returned is what's in the database.
    $this->assertFileUnchanged($result, file_load($result->fid, TRUE));
  }

  /**
   * Test file_save_data() when renaming around an existing file.
   */
  function testExistingRename() {
    // Setup a file to overwrite.
    $existing = $this->createFile();
    $contents = $this->randomName(8);

    $result = file_save_data($contents, $existing->uri, FILE_EXISTS_RENAME);
    $this->assertTrue($result, t("File saved successfully."));

    $this->assertEqual('public', file_uri_scheme($result->uri), t("File was placed in Drupal's files directory."));
    $this->assertEqual($result->filename, $existing->filename, t("Filename was set to the basename of the source, rather than that of the renamed file."));
    $this->assertEqual($contents, file_get_contents($result->uri), t("Contents of the file are correct."));
    $this->assertEqual($result->filemime, 'application/octet-stream', t("A MIME type was set."));
    $this->assertEqual($result->status, FILE_STATUS_PERMANENT, t("The file's status was set to permanent."));

    // Check that the correct hooks were called.
    $this->assertFileHooksCalled(array('insert'));

    // Ensure that the existing file wasn't overwritten.
    $this->assertDifferentFile($existing, $result);
    $this->assertFileUnchanged($existing, file_load($existing->fid, TRUE));

    // Verify that was returned is what's in the database.
    $this->assertFileUnchanged($result, file_load($result->fid, TRUE));
  }

  /**
   * Test file_save_data() when replacing an existing file.
   */
  function testExistingReplace() {
    // Setup a file to overwrite.
    $existing = $this->createFile();
    $contents = $this->randomName(8);

    $result = file_save_data($contents, $existing->uri, FILE_EXISTS_REPLACE);
    $this->assertTrue($result, t('File saved successfully.'));

    $this->assertEqual('public', file_uri_scheme($result->uri), t("File was placed in Drupal's files directory."));
    $this->assertEqual($result->filename, $existing->filename, t('Filename was set to the basename of the existing file, rather than preserving the original name.'));
    $this->assertEqual($contents, file_get_contents($result->uri), t('Contents of the file are correct.'));
    $this->assertEqual($result->filemime, 'application/octet-stream', t('A MIME type was set.'));
    $this->assertEqual($result->status, FILE_STATUS_PERMANENT, t("The file's status was set to permanent."));

    // Check that the correct hooks were called.
    $this->assertFileHooksCalled(array('load', 'update'));

    // Verify that the existing file was re-used.
    $this->assertSameFile($existing, $result);

    // Verify that what was returned is what's in the database.
    $this->assertFileUnchanged($result, file_load($result->fid, TRUE));
  }

  /**
   * Test that file_save_data() fails overwriting an existing file.
   */
  function testExistingError() {
    $contents = $this->randomName(8);
    $existing = $this->createFile(NULL, $contents);

    // Check the overwrite error.
    $result = file_save_data('asdf', $existing->uri, FILE_EXISTS_ERROR);
    $this->assertFalse($result, t('Overwriting a file fails when FILE_EXISTS_ERROR is specified.'));
    $this->assertEqual($contents, file_get_contents($existing->uri), t('Contents of existing file were unchanged.'));

    // Check that no hooks were called while failing.
    $this->assertFileHooksCalled(array());

    // Ensure that the existing file wasn't overwritten.
    $this->assertFileUnchanged($existing, file_load($existing->fid, TRUE));
  }
}
