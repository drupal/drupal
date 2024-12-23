<?php

declare(strict_types=1);

namespace Drupal\Tests\file\Kernel;

use Drupal\file\Entity\File;
use Drupal\file\FileInterface;
use Drupal\file_test\FileTestHelper;

/**
 * Tests \Drupal\file\Entity\File::load().
 *
 * @group file
 */
class LoadTest extends FileManagedUnitTestBase {

  /**
   * Try to load a non-existent file by fid.
   */
  public function testLoadMissingFid(): void {
    $this->assertNull(File::load(-1), 'Try to load an invalid fid fails.');
    $this->assertFileHooksCalled([]);
  }

  /**
   * Try to load a non-existent file by URI.
   */
  public function testLoadMissingFilepath(): void {
    $files = \Drupal::entityTypeManager()->getStorage('file')->loadByProperties(['uri' => 'foobar://misc/druplicon.png']);
    $this->assertFalse(reset($files), "Try to load a file that doesn't exist in the database fails.");
    $this->assertFileHooksCalled([]);
  }

  /**
   * Try to load a non-existent file by status.
   */
  public function testLoadInvalidStatus(): void {
    $files = \Drupal::entityTypeManager()->getStorage('file')->loadByProperties(['status' => -99]);
    $this->assertFalse(reset($files), 'Trying to load a file with an invalid status fails.');
    $this->assertFileHooksCalled([]);
  }

  /**
   * Load a single file and ensure that the correct values are returned.
   */
  public function testSingleValues(): void {
    // Create a new file entity from scratch so we know the values.
    $file = $this->createFile('druplicon.txt', NULL, 'public');
    $by_fid_file = File::load($file->id());
    $this->assertFileHookCalled('load');
    $this->assertIsObject($by_fid_file);
    $this->assertEquals($file->id(), $by_fid_file->id(), 'Loading by fid got the same fid.');
    $this->assertEquals($file->getFileUri(), $by_fid_file->getFileUri(), 'Loading by fid got the correct filepath.');
    $this->assertEquals($file->getFilename(), $by_fid_file->getFilename(), 'Loading by fid got the correct filename.');
    $this->assertEquals($file->getMimeType(), $by_fid_file->getMimeType(), 'Loading by fid got the correct MIME type.');
    $this->assertEquals($file->isPermanent(), $by_fid_file->isPermanent(), 'Loading by fid got the correct status.');
    $this->assertTrue($by_fid_file->file_test['loaded'], 'file_test_file_load() was able to modify the file during load.');
  }

  /**
   * This will test loading file data from the database.
   */
  public function testMultiple(): void {
    // Create a new file entity.
    $file = $this->createFile('druplicon.txt', NULL, 'public');

    // Load by path.
    FileTestHelper::reset();
    $by_path_files = \Drupal::entityTypeManager()->getStorage('file')->loadByProperties(['uri' => $file->getFileUri()]);
    $this->assertFileHookCalled('load');
    $this->assertCount(1, $by_path_files, '\Drupal::entityTypeManager()->getStorage(\'file\')->loadByProperties() returned an array of the correct size.');
    $by_path_file = reset($by_path_files);
    $this->assertTrue($by_path_file->file_test['loaded'], 'file_test_file_load() was able to modify the file during load.');
    $this->assertEquals($file->id(), $by_path_file->id(), 'Loading by filepath got the correct fid.');

    // Load by fid.
    FileTestHelper::reset();
    $by_fid_files = File::loadMultiple([$file->id()]);
    $this->assertFileHooksCalled([]);
    $this->assertCount(1, $by_fid_files, '\Drupal\file\Entity\File::loadMultiple() returned an array of the correct size.');
    $by_fid_file = reset($by_fid_files);
    $this->assertTrue($by_fid_file->file_test['loaded'], 'file_test_file_load() was able to modify the file during load.');
    $this->assertEquals($file->getFileUri(), $by_fid_file->getFileUri(), 'Loading by fid got the correct filepath.');
  }

  /**
   * Loads a single file and ensure that the correct values are returned.
   */
  public function testUuidValues(): void {
    // Create a new file entity from scratch so we know the values.
    $file = $this->createFile('druplicon.txt', NULL, 'public');
    $file->save();
    FileTestHelper::reset();

    $by_uuid_file = \Drupal::service('entity.repository')->loadEntityByUuid('file', $file->uuid());
    $this->assertFileHookCalled('load');
    $this->assertInstanceOf(FileInterface::class, $by_uuid_file);
    $this->assertEquals($file->id(), $by_uuid_file->id(), 'Loading by UUID got the same fid.');
    $this->assertTrue($by_uuid_file->file_test['loaded'], 'file_test_file_load() was able to modify the file during load.');
  }

}
