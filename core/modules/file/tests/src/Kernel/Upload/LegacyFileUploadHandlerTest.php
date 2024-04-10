<?php

declare(strict_types=1);

namespace Drupal\Tests\file\Kernel\Upload;

use Drupal\file\Upload\UploadedFileInterface;
use Drupal\KernelTests\KernelTestBase;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

/**
 * Provides tests for legacy file upload handler code.
 *
 * @group file
 * @group legacy
 * @coversDefaultClass \Drupal\file\Upload\FileUploadHandler
 */
class LegacyFileUploadHandlerTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['file'];

  /**
   * @covers ::handleFileUpload
   */
  public function testThrow(): void {
    $fileUploadHandler = $this->container->get('file.upload_handler');

    $uploadedFile = $this->createMock(UploadedFileInterface::class);
    $uploadedFile->expects($this->once())
      ->method('isValid')
      ->willReturn(FALSE);

    $this->expectDeprecation('Calling Drupal\file\Upload\FileUploadHandler::handleFileUpload() with the $throw argument as TRUE is deprecated in drupal:10.3.0 and will be removed in drupal:11.0.0. Use \Drupal\file\Upload\FileUploadResult::getViolations() instead. See https://www.drupal.org/node/3375456');
    $this->expectException(FileException::class);
    $result = $fileUploadHandler->handleFileUpload(uploadedFile: $uploadedFile, throw: TRUE);
  }

}
