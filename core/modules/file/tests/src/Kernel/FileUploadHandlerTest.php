<?php

declare(strict_types=1);

namespace Drupal\Tests\file\Kernel;

use Drupal\Core\Lock\LockAcquiringException;
use Drupal\Core\Lock\LockBackendInterface;
use Drupal\file\Upload\FileUploadHandler;
use Drupal\file\Upload\UploadedFileInterface;
use Drupal\KernelTests\KernelTestBase;
use Symfony\Component\Validator\ConstraintViolationList;

/**
 * Tests the file upload handler.
 *
 * @group file
 */
class FileUploadHandlerTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['file', 'file_validator_test'];

  /**
   * The file upload handler under test.
   */
  protected FileUploadHandler $fileUploadHandler;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->fileUploadHandler = $this->container->get('file.upload_handler');
  }

  /**
   * Test the lock acquire exception.
   */
  public function testLockAcquireException(): void {

    $lock = $this->createMock(LockBackendInterface::class);
    $lock->expects($this->once())->method('acquire')->willReturn(FALSE);

    $fileUploadHandler = new FileUploadHandler(
      $this->container->get('file_system'),
      $this->container->get('entity_type.manager'),
      $this->container->get('stream_wrapper_manager'),
      $this->container->get('event_dispatcher'),
      $this->container->get('file.mime_type.guesser'),
      $this->container->get('current_user'),
      $this->container->get('request_stack'),
      $this->container->get('file.repository'),
      $this->container->get('file.validator'),
      $lock,
      $this->container->get('validation.basic_recursive_validator_factory'),
    );

    $file_name = $this->randomMachineName();
    $file_info = $this->createMock(UploadedFileInterface::class);
    $file_info->expects($this->once())->method('getClientOriginalName')->willReturn($file_name);
    $file_info->expects($this->once())->method('validate')->willReturn(new ConstraintViolationList());

    $this->expectException(LockAcquiringException::class);
    $this->expectExceptionMessage(sprintf('File "temporary://%s" is already locked for writing.', $file_name));

    $fileUploadHandler->handleFileUpload(uploadedFile: $file_info);
  }

}
