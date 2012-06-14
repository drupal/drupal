<?php

/**
 * @file
 * Definition of Drupal\system\Tests\File\LoadTest.
 */

namespace Drupal\system\Tests\File;

/**
 * Tests the file_load() function.
 */
class LoadTest extends FileHookTestBase {
  public static function getInfo() {
    return array(
      'name' => 'File loading',
      'description' => 'Tests the file_load() function.',
      'group' => 'File API',
    );
  }

  /**
   * Try to load a non-existent file by fid.
   */
  function testLoadMissingFid() {
    $this->assertFalse(file_load(-1), t("Try to load an invalid fid fails."));
    $this->assertFileHooksCalled(array());
  }

  /**
   * Try to load a non-existent file by URI.
   */
  function testLoadMissingFilepath() {
    $files = file_load_multiple(array(), array('uri' => 'foobar://misc/druplicon.png'));
    $this->assertFalse(reset($files), t("Try to load a file that doesn't exist in the database fails."));
    $this->assertFileHooksCalled(array());
  }

  /**
   * Try to load a non-existent file by status.
   */
  function testLoadInvalidStatus() {
    $files = file_load_multiple(array(), array('status' => -99));
    $this->assertFalse(reset($files), t("Trying to load a file with an invalid status fails."));
    $this->assertFileHooksCalled(array());
  }

  /**
   * Load a single file and ensure that the correct values are returned.
   */
  function testSingleValues() {
    // Create a new file entity from scratch so we know the values.
    $file = $this->createFile('druplicon.txt', NULL, 'public');

    $by_fid_file = file_load($file->fid);
    $this->assertFileHookCalled('load');
    $this->assertTrue(is_object($by_fid_file), t('file_load() returned an object.'));
    $this->assertEqual($by_fid_file->fid, $file->fid, t("Loading by fid got the same fid."), 'File');
    $this->assertEqual($by_fid_file->uri, $file->uri, t("Loading by fid got the correct filepath."), 'File');
    $this->assertEqual($by_fid_file->filename, $file->filename, t("Loading by fid got the correct filename."), 'File');
    $this->assertEqual($by_fid_file->filemime, $file->filemime, t("Loading by fid got the correct MIME type."), 'File');
    $this->assertEqual($by_fid_file->status, $file->status, t("Loading by fid got the correct status."), 'File');
    $this->assertTrue($by_fid_file->file_test['loaded'], t('file_test_file_load() was able to modify the file during load.'));
  }

  /**
   * This will test loading file data from the database.
   */
  function testMultiple() {
    // Create a new file entity.
    $file = $this->createFile('druplicon.txt', NULL, 'public');

    // Load by path.
    file_test_reset();
    $by_path_files = file_load_multiple(array(), array('uri' => $file->uri));
    $this->assertFileHookCalled('load');
    $this->assertEqual(1, count($by_path_files), t('file_load_multiple() returned an array of the correct size.'));
    $by_path_file = reset($by_path_files);
    $this->assertTrue($by_path_file->file_test['loaded'], t('file_test_file_load() was able to modify the file during load.'));
    $this->assertEqual($by_path_file->fid, $file->fid, t("Loading by filepath got the correct fid."), 'File');

    // Load by fid.
    file_test_reset();
    $by_fid_files = file_load_multiple(array($file->fid), array());
    $this->assertFileHookCalled('load');
    $this->assertEqual(1, count($by_fid_files), t('file_load_multiple() returned an array of the correct size.'));
    $by_fid_file = reset($by_fid_files);
    $this->assertTrue($by_fid_file->file_test['loaded'], t('file_test_file_load() was able to modify the file during load.'));
    $this->assertEqual($by_fid_file->uri, $file->uri, t("Loading by fid got the correct filepath."), 'File');
  }
}
