<?php

namespace Drupal\Tests\file\Kernel;

use Drupal\Component\Utility\Environment;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\KernelTests\KernelTestBase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Tests file.module methods.
 *
 * @group file
 */
class FileModuleTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['file'];

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
    $this->assertFalse(\_file_save_upload_single($file_info, 'name'));
    $expected_message = new TranslatableMarkup('The file %file could not be saved because it exceeds %maxsize, the maximum allowed size for uploads.', ['%file' => $file_name, '%maxsize' => format_size(Environment::getUploadMaxSize())]);
    $this->assertEquals($expected_message, \Drupal::messenger()->all()['error'][0]);
  }

}
