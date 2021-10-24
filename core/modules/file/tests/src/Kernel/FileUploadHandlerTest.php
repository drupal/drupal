<?php

namespace Drupal\Tests\file\Kernel;

use Drupal\Component\Utility\Environment;
use Drupal\file\Upload\UploadedFileInterface;
use Drupal\KernelTests\KernelTestBase;
use Symfony\Component\HttpFoundation\File\Exception\FormSizeFileException;

/**
 * Tests the file upload handler.
 *
 * @group file
 */
class FileUploadHandlerTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['file'];

  /**
   * The file upload handler under test.
   *
   * @var \Drupal\file\Upload\FileUploadHandler
   */
  protected $fileUploadHandler;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->fileUploadHandler = $this->container->get('file.upload_handler');
  }

  /**
   * Tests file size upload errors.
   */
  public function testFileSaveUploadSingleErrorFormSize() {
    $file_name = $this->randomMachineName();
    $file_info = $this->createMock(UploadedFileInterface::class);
    $file_info->expects($this->once())->method('getError')->willReturn(UPLOAD_ERR_FORM_SIZE);
    $file_info->expects($this->once())->method('getClientOriginalName')->willReturn($file_name);
    $file_info->expects($this->once())->method('getErrorMessage')->willReturn(sprintf('The file "%s" could not be saved because it exceeds %s, the maximum allowed size for uploads.', $file_name, format_size(Environment::getUploadMaxSize())));
    $this->expectException(FormSizeFileException::class);
    $this->expectExceptionMessage(sprintf('The file "%s" could not be saved because it exceeds %s, the maximum allowed size for uploads.', $file_name, format_size(Environment::getUploadMaxSize())));
    $this->fileUploadHandler->handleFileUpload($file_info);
  }

}
