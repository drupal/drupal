<?php

namespace Drupal\KernelTests\Core\File;

use Drupal\KernelTests\KernelTestBase;
use org\bovigo\vfs\vfsStream;

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
    $source = file_directory_temp() . '/example.txt';
    file_put_contents($source, 'example');
    $filename = file_unmanaged_copy($source);
    $this->assertEquals('public://example.txt', $filename);
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
    $source = file_directory_temp() . '/example.txt';
    file_put_contents($source, 'example');
    $filename = file_unmanaged_move($source);
    $this->assertEquals('public://example.txt', $filename);
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
    $filename = file_unmanaged_save_data('example');
    $this->assertStringMatchesFormat('public://file%s', $filename);
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

  /**
   * @expectedDeprecation file_upload_max_size() is deprecated in Drupal 8.7.0 and will be removed before Drupal 9.0.0. Use \Drupal\Component\Utility\Environment::getUploadMaxSize() instead. See https://www.drupal.org/node/3000058.
   */
  public function testDeprecatedFileUploadMaxSize() {
    $this->assertNotNull(file_upload_max_size());
  }

  /**
   * @expectedDeprecation drupal_chmod() is deprecated in Drupal 8.0.0 and will be removed before Drupal 9.0.0. Use \Drupal\Core\File\FileSystemInterface::chmod(). See https://www.drupal.org/node/2418133.
   */
  public function testDeprecatedDrupalChmod() {
    $this->assertNotNull(drupal_chmod(''));
  }

  /**
   * @expectedDeprecation drupal_dirname() is deprecated in Drupal 8.0.0 and will be removed before Drupal 9.0.0. Use \Drupal\Core\File\FileSystemInterface::dirname(). See https://www.drupal.org/node/2418133.
   */
  public function testDeprecatedDrupalDirname() {
    $this->assertNotNull(drupal_dirname(''));
  }

  /**
   * @expectedDeprecation drupal_basename() is deprecated in Drupal 8.0.0 and will be removed before Drupal 9.0.0. Use \Drupal\Core\File\FileSystemInterface::basename(). See https://www.drupal.org/node/2418133.
   */
  public function testDeprecatedDrupalBasename() {
    $this->assertNotNull(drupal_basename(''));
  }

  /**
   * @expectedDeprecation drupal_mkdir() is deprecated in Drupal 8.0.0 and will be removed before Drupal 9.0.0. Use \Drupal\Core\File\FileSystemInterface::mkdir(). See https://www.drupal.org/node/2418133.
   */
  public function testDeprecatedDrupalMkdir() {
    $this->assertNotNull(drupal_mkdir('public://test.txt'));
  }

  /**
   * @expectedDeprecation drupal_rmdir() is deprecated in Drupal 8.0.0 and will be removed before Drupal 9.0.0. Use \Drupal\Core\File\FileSystemInterface::rmdir(). See https://www.drupal.org/node/2418133.
   */
  public function testDeprecatedDrupalRmdir() {
    $this->assertNotNull(drupal_rmdir('public://test.txt'));
  }

  /**
   * @expectedDeprecation tempnam() is deprecated in Drupal 8.0.0 and will be removed before Drupal 9.0.0. Use \Drupal\Core\File\FileSystemInterface::tempnam(). See https://www.drupal.org/node/2418133.
   */
  public function testDeprecatedDrupalTempnam() {
    $this->assertNotNull(drupal_tempnam('temporary://', 'file'));
  }

  /**
   * Tests deprecation of the drupal_unlink() function.
   *
   * @expectedDeprecation drupal_unlink() is deprecated in Drupal 8.0.0, will be removed before Drupal 9.0.0. Use \Drupal\Core\File\FileSystemInterface::unlink(). See https://www.drupal.org/node/2418133.
   */
  public function testUnlink() {
    vfsStream::setup('dir');
    vfsStream::create(['test.txt' => 'asdf']);
    $uri = 'vfs://dir/test.txt';

    $this->assertFileExists($uri);
    drupal_unlink($uri);
    $this->assertFileNotExists($uri);
  }

  /**
   * @expectedDeprecation file_default_scheme() is deprecated in drupal:8.8.0. It will be removed from drupal:9.0.0. Use \Drupal::config('system.file')->get('default_scheme') instead. See https://www.drupal.org/project/paragraphs/issues/3049030
   */
  public function testDeprecatedDefaultScheme() {
    $this->assertNotNull(file_default_scheme());
  }

  /**
   * @expectedDeprecation file_directory_os_temp() is deprecated in drupal:8.3.0 and is removed from drupal:9.0.0. Use \Drupal\Component\FileSystem\FileSystem::getOsTemporaryDirectory() instead. See https://www.drupal.org/node/2418133
   */
  public function testDeprecatedDirectoryOsTemp() {
    $this->assertNotNull(file_directory_os_temp());
  }

}
