<?php

namespace Drupal\Tests\file\Kernel;

use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\File\Exception\FileExistsException;
use Drupal\Core\File\Exception\InvalidStreamWrapperException;
use Drupal\Core\File\FileSystemInterface;
use Drupal\file\Entity\File;
use Drupal\file\FileRepository;

/**
 * Tests the file copy function.
 *
 * @coversDefaultClass \Drupal\file\FileRepository
 * @group file
 */
class CopyTest extends FileManagedUnitTestBase {

  /**
   * The file repository service under test.
   *
   * @var \Drupal\file\FileRepository
   */
  protected $fileRepository;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->fileRepository = $this->container->get('file.repository');
  }

  /**
   * Tests file copying in the normal, base case.
   *
   * @covers ::copy
   */
  public function testNormal() {
    $contents = $this->randomMachineName(10);
    $source = $this->createFile(NULL, $contents);
    $desired_uri = 'public://' . $this->randomMachineName();

    // Clone the object so we don't have to worry about the function changing
    // our reference copy.
    $result = $this->fileRepository->copy(clone $source, $desired_uri, FileSystemInterface::EXISTS_ERROR);

    // Check the return status and that the contents changed.
    $this->assertNotFalse($result, 'File copied successfully.');
    $this->assertEquals($contents, file_get_contents($result->getFileUri()), 'Contents of file were copied correctly.');

    // Check that the correct hooks were called.
    $this->assertFileHooksCalled(['copy', 'insert']);

    $this->assertDifferentFile($source, $result);
    $this->assertEquals($result->getFileUri(), $desired_uri, 'The copied file entity has the desired filepath.');
    $this->assertFileExists($source->getFileUri());
    $this->assertFileExists($result->getFileUri());

    // Reload the file from the database and check that the changes were
    // actually saved.
    $this->assertFileUnchanged($result, File::load($result->id()));
  }

  /**
   * Tests renaming when copying over a file that already exists.
   *
   * @covers ::copy
   */
  public function testExistingRename() {
    // Setup a file to overwrite.
    $contents = $this->randomMachineName(10);
    $source = $this->createFile(NULL, $contents);
    $target = $this->createFile();
    $this->assertDifferentFile($source, $target);

    // Clone the object so we don't have to worry about the function changing
    // our reference copy.
    $result = $this->fileRepository->copy(clone $source, $target->getFileUri(), FileSystemInterface::EXISTS_RENAME);

    // Check the return status and that the contents changed.
    $this->assertNotFalse($result, 'File copied successfully.');
    $this->assertEquals($contents, file_get_contents($result->getFileUri()), 'Contents of file were copied correctly.');
    $this->assertNotEquals($source->getFileUri(), $result->getFileUri(), 'Returned file path has changed from the original.');

    // Check that the correct hooks were called.
    $this->assertFileHooksCalled(['copy', 'insert']);

    // Load all the affected files to check the changes that actually made it
    // to the database.
    $loaded_source = File::load($source->id());
    $loaded_target = File::load($target->id());
    $loaded_result = File::load($result->id());

    // Verify that the source file wasn't changed.
    $this->assertFileUnchanged($source, $loaded_source);

    // Verify that what was returned is what's in the database.
    $this->assertFileUnchanged($result, $loaded_result);

    // Make sure we end up with three distinct files afterwards.
    $this->assertDifferentFile($loaded_source, $loaded_target);
    $this->assertDifferentFile($loaded_target, $loaded_result);
    $this->assertDifferentFile($loaded_source, $loaded_result);
  }

  /**
   * Tests replacement when copying over a file that already exists.
   *
   * @covers ::copy
   */
  public function testExistingReplace() {
    // Setup a file to overwrite.
    $contents = $this->randomMachineName(10);
    $source = $this->createFile(NULL, $contents);
    $target = $this->createFile();
    $this->assertDifferentFile($source, $target);

    // Clone the object so we don't have to worry about the function changing
    // our reference copy.
    $result = $this->fileRepository->copy(clone $source, $target->getFileUri(), FileSystemInterface::EXISTS_REPLACE);

    // Check the return status and that the contents changed.
    $this->assertNotFalse($result, 'File copied successfully.');
    $this->assertEquals($contents, file_get_contents($result->getFileUri()), 'Contents of file were overwritten.');
    $this->assertDifferentFile($source, $result);

    // Check that the correct hooks were called.
    $this->assertFileHooksCalled(['load', 'copy', 'update']);

    // Load all the affected files to check the changes that actually made it
    // to the database.
    $loaded_source = File::load($source->id());
    $loaded_target = File::load($target->id());
    $loaded_result = File::load($result->id());

    // Verify that the source file wasn't changed.
    $this->assertFileUnchanged($source, $loaded_source);

    // Verify that what was returned is what's in the database.
    $this->assertFileUnchanged($result, $loaded_result);

    // Target file was reused for the result.
    $this->assertFileUnchanged($loaded_target, $loaded_result);
  }

  /**
   * Tests that copying over an existing file fails when instructed to do so.
   *
   * @covers ::copy
   */
  public function testExistingError() {
    $contents = $this->randomMachineName(10);
    $source = $this->createFile();
    $target = $this->createFile(NULL, $contents);
    $this->assertDifferentFile($source, $target);

    // Clone the object so we don't have to worry about the function changing
    // our reference copy.
    try {
      $result = $this->fileRepository->copy(clone $source, $target->getFileUri(), FileSystemInterface::EXISTS_ERROR);
      $this->fail('expected FileExistsException');
    }
    // FileExistsException is a subclass of FileException.
    catch (FileExistsException $e) {
      // expected exception.
      $this->assertStringContainsString("could not be copied because a file by that name already exists in the destination directory", $e->getMessage());
    }
    // Check the contents were not changed.
    $this->assertEquals($contents, file_get_contents($target->getFileUri()), 'Contents of file were not altered.');

    // Check that the correct hooks were called.
    $this->assertFileHooksCalled([]);

    $this->assertFileUnchanged($source, File::load($source->id()));
    $this->assertFileUnchanged($target, File::load($target->id()));
  }

  /**
   * Tests for an invalid stream wrapper.
   *
   * @covers ::copy
   */
  public function testInvalidStreamWrapper() {
    $this->expectException(InvalidStreamWrapperException::class);
    $this->expectExceptionMessage('Invalid stream wrapper: foo://');
    $source = $this->createFile();
    $this->fileRepository->copy($source, 'foo://');
  }

  /**
   * Tests for entity storage exception.
   *
   * @covers ::copy
   */
  public function testEntityStorageException() {
    /** @var \Drupal\Core\Entity\EntityTypeManager $entityTypeManager */
    $entityTypeManager = $this->prophesize(EntityTypeManager::class);
    $entityTypeManager->getStorage('file')
      ->willThrow(EntityStorageException::class);

    $fileRepository = new FileRepository(
      $this->container->get('file_system'),
      $this->container->get('stream_wrapper_manager'),
      $entityTypeManager->reveal(),
      $this->container->get('module_handler'),
      $this->container->get('file.usage'),
      $this->container->get('current_user')
    );

    $this->expectException(EntityStorageException::class);
    $source = $this->createFile();
    $target = $this->createFile();
    $fileRepository->copy($source, $target->getFileUri(), FileSystemInterface::EXISTS_REPLACE);
  }

}
