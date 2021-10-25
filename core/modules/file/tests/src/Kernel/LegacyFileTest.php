<?php

namespace Drupal\Tests\file\Kernel;

use Drupal\Component\Utility\Environment;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Tests deprecated file functions.
 *
 * @group file
 * @group legacy
 */
class LegacyFileTest extends FileManagedUnitTestBase {

  /**
   * Tests file size upload errors.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function testFileSaveUploadSingleErrorFormSize() {
    $file_name = $this->randomMachineName();
    $file_info = $this->createMock(UploadedFile::class);
    $file_info->expects($this->once())->method('getError')->willReturn(UPLOAD_ERR_FORM_SIZE);
    $file_info->expects($this->once())->method('getClientOriginalName')->willReturn($file_name);
    $this->expectDeprecation('_file_save_upload_single() is deprecated in drupal:9.3.0 and is removed from drupal:10.0.0. Use \Drupal\file\Upload\FileUploadHandler::handleFileUpload() instead. See https://www.drupal.org/node/3239547');
    $this->assertFalse(\_file_save_upload_single($file_info, 'name'));
    $expected_message = new TranslatableMarkup('The file %file could not be saved because it exceeds %maxsize, the maximum allowed size for uploads.', ['%file' => $file_name, '%maxsize' => format_size(Environment::getUploadMaxSize())]);
    $this->assertEquals($expected_message, \Drupal::messenger()->all()['error'][0]);
  }

  /**
   * Tests the deprecation of _views_file_status().
   *
   * @group legacy
   */
  public function testViewsFileStatus() {
    $this->expectDeprecation('_views_file_status() is deprecated in drupal:9.3.0 and is removed from drupal:10.0.0. There is no replacement. See https://www.drupal.org/node/3227228');
    $this->assertIsArray(_views_file_status());
  }

  /**
   * Tests file_save_data deprecation and that it works without a destination.
   */
  public function testSaveData() {
    $this->expectDeprecation('file_save_data is deprecated in drupal:9.3.0 and will be removed in drupal:10.0.0. Use \Drupal\file\FileRepositoryInterface::writeData() instead. See https://www.drupal.org/node/3223520');
    $contents = $this->randomMachineName(8);
    $result = file_save_data($contents);
    $this->assertNotFalse($result, 'Unnamed file saved correctly.');
  }

  /**
   * Tests the file_copy deprecation and legacy behavior.
   */
  public function testCopy() {
    $this->expectDeprecation('file_copy is deprecated in drupal:9.3.0 and will be removed in drupal:10.0.0. Use \Drupal\file\FileRepositoryInterface::copy() instead. See https://www.drupal.org/node/3223520');
    $contents = $this->randomMachineName(10);
    $source = $this->createFile(NULL, $contents);
    $desired_uri = 'public://' . $this->randomMachineName();

    // Clone the object so we don't have to worry about the function changing
    // our reference copy.
    $result = file_copy(clone $source, $desired_uri, FileSystemInterface::EXISTS_ERROR);

    // Check the return status and that the contents have not changed.
    $this->assertNotFalse($result, 'File copied successfully.');
    $this->assertEquals($contents, file_get_contents($result->getFileUri()));

  }

  /**
   * Tests the file_copy deprecation and legacy behavior.
   */
  public function testMove() {
    $this->expectDeprecation('file_move is deprecated in drupal:9.3.0 and will be removed in drupal:10.0.0. Use \Drupal\file\FileRepositoryInterface::move() instead. See https://www.drupal.org/node/3223520');
    $contents = $this->randomMachineName(10);
    $source = $this->createFile(NULL, $contents);
    $desired_uri = 'public://' . $this->randomMachineName();
    // Clone the object so we don't have to worry about the function changing
    // our reference copy.
    $result = file_move(clone $source, $desired_uri, FileSystemInterface::EXISTS_ERROR);

    // Check the return status and that the contents have not changed.
    $this->assertFileNotExists($source->getFileUri());
    $this->assertEquals($contents, file_get_contents($result->getFileUri()));
  }

}
