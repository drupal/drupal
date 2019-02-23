<?php

namespace Drupal\KernelTests\Core\File;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests deprecations in file.inc.
 *
 * @group File
 * @group legacy
 */
class FileSystemDeprecationTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['system'];

  /**
   * @expectedDeprecation drupal_move_uploaded_file() is deprecated in Drupal 8.0.x-dev and will be removed before Drupal 9.0.0. Use \Drupal\Core\File\FileSystemInterface::moveUploadedFile(). See https://www.drupal.org/node/2418133.
   */
  public function testDeprecatedFileMoveUploadedFile() {
    $this->assertNotNull(drupal_move_uploaded_file('', ''));
  }

  /**
   * @expectedDeprecation file_unmanaged_copy() is deprecated in Drupal 8.7.0 and will be removed before Drupal 9.0.0. Use \Drupal\Core\File\FileSystemInterface::copy(). See https://www.drupal.org/node/3006851.
   */
  public function testDeprecatedUnmanagedFileCopy() {
    $this->assertNotNull(file_unmanaged_copy(NULL));
  }

  /**
   * @expectedDeprecation file_unmanaged_delete() is deprecated in Drupal 8.7.0 and will be removed before Drupal 9.0.0. Use \Drupal\Core\File\FileSystemInterface::delete(). See https://www.drupal.org/node/3006851.
   */
  public function testDeprecatedUnmanagedFileDelete() {
    $this->assertNotNull(file_unmanaged_delete(NULL));
  }

  /**
   * @expectedDeprecation file_unmanaged_delete_recursive() is deprecated in Drupal 8.7.0 and will be removed before Drupal 9.0.0. Use \Drupal\Core\File\FileSystemInterface::deleteRecursive(). See https://www.drupal.org/node/3006851.
   */
  public function testDeprecatedUnmanagedFileDeleteRecursive() {
    $this->assertNotNull(file_unmanaged_delete_recursive(NULL));
  }

  /**
   * @expectedDeprecation file_unmanaged_move() is deprecated in Drupal 8.7.0 and will be removed before Drupal 9.0.0. Use \Drupal\Core\File\FileSystemInterface::move(). See https://www.drupal.org/node/3006851.
   */
  public function testDeprecatedUnmanagedFileMove() {
    $this->assertNotNull(file_unmanaged_move(NULL));
  }

  /**
   * @expectedDeprecation file_unmanaged_prepare() is deprecated in Drupal 8.7.0 and will be removed before Drupal 9.0.0. Use \Drupal\Core\File\FileSystemInterface::getDestinationFilename() instead. See https://www.drupal.org/node/3006851.
   */
  public function testDeprecatedUnmanagedPrepare() {
    $this->assertNotNull(file_unmanaged_prepare(NULL));
  }

  /**
   * @expectedDeprecation file_unmanaged_save_data() is deprecated in Drupal 8.7.0 and will be removed before Drupal 9.0.0. Use \Drupal\Core\File\FileSystemInterface::saveData(). See https://www.drupal.org/node/3006851.
   */
  public function testDeprecatedUnmanagedSaveData() {
    $this->assertNotNull(file_unmanaged_save_data(NULL));
  }

  /**
   * @expectedDeprecation file_prepare_directory() is deprecated in Drupal 8.7.0 and will be removed before Drupal 9.0.0. Use \Drupal\Core\File\FileSystemInterface::prepareDirectory(). See https://www.drupal.org/node/3006851.
   */
  public function testDeprecatedFilePrepareDirectory() {
    $dir = NULL;
    $this->assertNotNull(file_prepare_directory($dir));
  }

  /**
   * @expectedDeprecation file_destination() is deprecated in Drupal 8.7.0 and will be removed before Drupal 9.0.0. Use \Drupal\Core\File\FileSystemInterface::getDestinationFilename(). See https://www.drupal.org/node/3006851.
   */
  public function testDeprecatedFileDestination() {
    $this->assertNotNull(file_destination('', ''));
  }

  /**
   * @expectedDeprecation file_create_filename() is deprecated in Drupal 8.7.0 and will be removed before Drupal 9.0.0. Use \Drupal\Core\File\FileSystemInterface::createFilename(). See https://www.drupal.org/node/3006851.
   */
  public function testDeprecatedFileCreate() {
    $this->assertNotNull(file_create_filename('', ''));
  }

}
