<?php

namespace Drupal\Tests\file\Kernel;

use Drupal\file\Entity\File;

/**
 * Tests \Drupal\file\Entity\File::load().
 *
 * @group file
 */
class LoadTest extends FileManagedUnitTestBase {

  /**
   * Try to load a non-existent file by fid.
   */
  public function testLoadMissingFid() {
    $this->assertFalse(File::load(-1), 'Try to load an invalid fid fails.');
    $this->assertFileHooksCalled([]);
  }

  /**
   * Try to load a non-existent file by URI.
   */
  public function testLoadMissingFilepath() {
    $files = entity_load_multiple_by_properties('file', ['uri' => 'foobar://misc/druplicon.png']);
    $this->assertFalse(reset($files), "Try to load a file that doesn't exist in the database fails.");
    $this->assertFileHooksCalled([]);
  }

  /**
   * Try to load a non-existent file by status.
   */
  public function testLoadInvalidStatus() {
    $files = entity_load_multiple_by_properties('file', ['status' => -99]);
    $this->assertFalse(reset($files), 'Trying to load a file with an invalid status fails.');
    $this->assertFileHooksCalled([]);
  }

  /**
   * Load a single file and ensure that the correct values are returned.
   */
  public function testSingleValues() {
    // Create a new file entity from scratch so we know the values.
    $file = $this->createFile('druplicon.txt', NULL, 'public');
    $by_fid_file = File::load($file->id());
    $this->assertFileHookCalled('load');
    $this->assertTrue(is_object($by_fid_file), '\Drupal\file\Entity\File::load() returned an object.');
    $this->assertEqual($by_fid_file->id(), $file->id(), 'Loading by fid got the same fid.', 'File');
    $this->assertEqual($by_fid_file->getFileUri(), $file->getFileUri(), 'Loading by fid got the correct filepath.', 'File');
    $this->assertEqual($by_fid_file->getFilename(), $file->getFilename(), 'Loading by fid got the correct filename.', 'File');
    $this->assertEqual($by_fid_file->getMimeType(), $file->getMimeType(), 'Loading by fid got the correct MIME type.', 'File');
    $this->assertEqual($by_fid_file->isPermanent(), $file->isPermanent(), 'Loading by fid got the correct status.', 'File');
    $this->assertTrue($by_fid_file->file_test['loaded'], 'file_test_file_load() was able to modify the file during load.');
  }

  /**
   * This will test loading file data from the database.
   */
  public function testMultiple() {
    // Create a new file entity.
    $file = $this->createFile('druplicon.txt', NULL, 'public');

    // Load by path.
    file_test_reset();
    $by_path_files = entity_load_multiple_by_properties('file', ['uri' => $file->getFileUri()]);
    $this->assertFileHookCalled('load');
    $this->assertEqual(1, count($by_path_files), 'entity_load_multiple_by_properties() returned an array of the correct size.');
    $by_path_file = reset($by_path_files);
    $this->assertTrue($by_path_file->file_test['loaded'], 'file_test_file_load() was able to modify the file during load.');
    $this->assertEqual($by_path_file->id(), $file->id(), 'Loading by filepath got the correct fid.', 'File');

    // Load by fid.
    file_test_reset();
    $by_fid_files = File::loadMultiple([$file->id()]);
    $this->assertFileHooksCalled([]);
    $this->assertEqual(1, count($by_fid_files), '\Drupal\file\Entity\File::loadMultiple() returned an array of the correct size.');
    $by_fid_file = reset($by_fid_files);
    $this->assertTrue($by_fid_file->file_test['loaded'], 'file_test_file_load() was able to modify the file during load.');
    $this->assertEqual($by_fid_file->getFileUri(), $file->getFileUri(), 'Loading by fid got the correct filepath.', 'File');
  }

  /**
   * Loads a single file and ensure that the correct values are returned.
   */
  public function testUuidValues() {
    // Create a new file entity from scratch so we know the values.
    $file = $this->createFile('druplicon.txt', NULL, 'public');
    $file->save();
    file_test_reset();

    $by_uuid_file = \Drupal::entityManager()->loadEntityByUuid('file', $file->uuid());
    $this->assertFileHookCalled('load');
    $this->assertTrue(is_object($by_uuid_file), '\Drupal::entityManager()->loadEntityByUuid() returned a file object.');
    if (is_object($by_uuid_file)) {
      $this->assertEqual($by_uuid_file->id(), $file->id(), 'Loading by UUID got the same fid.', 'File');
      $this->assertTrue($by_uuid_file->file_test['loaded'], 'file_test_file_load() was able to modify the file during load.');
    }
  }

}
