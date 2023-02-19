<?php

namespace Drupal\Tests\file\Kernel;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\File\Exception\FileExistsException;
use Drupal\Core\File\Exception\InvalidStreamWrapperException;
use Drupal\Core\File\FileSystemInterface;
use Drupal\file\Entity\File;
use Drupal\file\FileRepository;

/**
 * Tests the file move function.
 *
 * @coversDefaultClass \Drupal\file\FileRepository
 * @group file
 */
class MoveTest extends FileManagedUnitTestBase {

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
   * Move a normal file.
   *
   * @covers ::move
   */
  public function testNormal() {
    $contents = $this->randomMachineName(10);
    $source = $this->createFile(NULL, $contents);
    $desired_filepath = 'public://' . $this->randomMachineName();

    // Clone the object so we don't have to worry about the function changing
    // our reference copy.
    $result = $this->fileRepository->move(clone $source, $desired_filepath, FileSystemInterface::EXISTS_ERROR);

    // Check the return status and that the contents changed.
    $this->assertNotFalse($result, 'File moved successfully.');
    $this->assertFileDoesNotExist($source->getFileUri());
    $this->assertEquals($contents, file_get_contents($result->getFileUri()), 'Contents of file correctly written.');

    // Check that the correct hooks were called.
    $this->assertFileHooksCalled(['move', 'load', 'update']);

    // Make sure we got the same file back.
    $this->assertEquals($source->id(), $result->id(), new FormattableMarkup("Source file id's' %fid is unchanged after move.", ['%fid' => $source->id()]));

    // Reload the file from the database and check that the changes were
    // actually saved.
    $loaded_file = File::load($result->id());
    $this->assertNotEmpty($loaded_file, 'File can be loaded from the database.');
    $this->assertFileUnchanged($result, $loaded_file);
  }

  /**
   * Tests renaming when moving onto a file that already exists.
   *
   * @covers ::move
   */
  public function testExistingRename() {
    // Setup a file to overwrite.
    $contents = $this->randomMachineName(10);
    $source = $this->createFile(NULL, $contents);
    $target = $this->createFile();
    $this->assertDifferentFile($source, $target);

    // Clone the object so we don't have to worry about the function changing
    // our reference copy.
    $result = $this->fileRepository->move(clone $source, $target->getFileUri());

    // Check the return status and that the contents changed.
    $this->assertNotFalse($result, 'File moved successfully.');
    $this->assertFileDoesNotExist($source->getFileUri());
    $this->assertEquals($contents, file_get_contents($result->getFileUri()), 'Contents of file correctly written.');

    // Check that the correct hooks were called.
    $this->assertFileHooksCalled(['move', 'load', 'update']);

    // Compare the returned value to what made it into the database.
    $this->assertFileUnchanged($result, File::load($result->id()));
    // The target file should not have been altered.
    $this->assertFileUnchanged($target, File::load($target->id()));
    // Make sure we end up with two distinct files afterwards.
    $this->assertDifferentFile($target, $result);

    // Compare the source and results.
    $loaded_source = File::load($source->id());
    $this->assertEquals($result->id(), $loaded_source->id(), "Returned file's id matches the source.");
    $this->assertNotEquals($source->getFileUri(), $loaded_source->getFileUri(), 'Returned file path has changed from the original.');
  }

  /**
   * Tests replacement when moving onto a file that already exists.
   *
   * @covers ::move
   */
  public function testExistingReplace() {
    // Setup a file to overwrite.
    $contents = $this->randomMachineName(10);
    $source = $this->createFile(NULL, $contents);
    $target = $this->createFile();
    $this->assertDifferentFile($source, $target);

    // Clone the object so we don't have to worry about the function changing
    // our reference copy.
    $result = $this->fileRepository->move(clone $source, $target->getFileUri(), FileSystemInterface::EXISTS_REPLACE);

    // Look at the results.
    $this->assertEquals($contents, file_get_contents($result->getFileUri()), 'Contents of file were overwritten.');
    $this->assertFileDoesNotExist($source->getFileUri());
    $this->assertNotEmpty($result, 'File moved successfully.');

    // Check that the correct hooks were called.
    $this->assertFileHooksCalled(['move', 'update', 'delete', 'load']);

    // Reload the file from the database and check that the changes were
    // actually saved.
    $loaded_result = File::load($result->id());
    $this->assertFileUnchanged($result, $loaded_result);
    // Check that target was re-used.
    $this->assertSameFile($target, $loaded_result);
    // Source and result should be totally different.
    $this->assertDifferentFile($source, $loaded_result);
  }

  /**
   * Tests replacement when moving onto itself.
   *
   * @covers ::move
   */
  public function testExistingReplaceSelf() {
    // Setup a file to overwrite.
    $contents = $this->randomMachineName(10);
    $source = $this->createFile(NULL, $contents);

    // Copy the file over itself. Clone the object so we don't have to worry
    // about the function changing our reference copy.
    try {
      $this->fileRepository->move(clone $source, $source->getFileUri(), FileSystemInterface::EXISTS_ERROR);
      $this->fail('expected FileExistsException');
    }
    catch (FileExistsException $e) {
      // expected exception.
      $this->assertStringContainsString("could not be copied because a file by that name already exists in the destination directory", $e->getMessage());
    }
    $this->assertEquals($contents, file_get_contents($source->getFileUri()), 'Contents of file were not altered.');

    // Check that no hooks were called while failing.
    $this->assertFileHooksCalled([]);

    // Load the file from the database and make sure it is identical to what
    // was returned.
    $this->assertFileUnchanged($source, File::load($source->id()));
  }

  /**
   * Tests that moving onto an existing file fails when instructed to do so.
   *
   * @covers ::move
   */
  public function testExistingError() {
    $contents = $this->randomMachineName(10);
    $source = $this->createFile();
    $target = $this->createFile(NULL, $contents);
    $this->assertDifferentFile($source, $target);

    // Clone the object so we don't have to worry about the function changing
    // our reference copy.
    try {
      $this->fileRepository->move(clone $source, $target->getFileUri(), FileSystemInterface::EXISTS_ERROR);
      $this->fail('expected FileExistsException');
    }
    // FileExistsException is a subclass of FileException.
    catch (FileExistsException $e) {
      // expected exception.
      $this->assertStringContainsString("could not be copied because a file by that name already exists in the destination directory", $e->getMessage());
    }
    // Check the return status and that the contents did not change.
    $this->assertFileExists($source->getFileUri());
    $this->assertEquals($contents, file_get_contents($target->getFileUri()), 'Contents of file were not altered.');

    // Check that no hooks were called while failing.
    $this->assertFileHooksCalled([]);

    // Load the file from the database and make sure it is identical to what
    // was returned.
    $this->assertFileUnchanged($source, File::load($source->id()));
    $this->assertFileUnchanged($target, File::load($target->id()));
  }

  /**
   * Tests for an invalid stream wrapper.
   *
   * @covers ::move
   */
  public function testInvalidStreamWrapper() {
    $this->expectException(InvalidStreamWrapperException::class);
    $this->expectExceptionMessage('Invalid stream wrapper: foo://');
    $source = $this->createFile();
    $this->fileRepository->move($source, 'foo://');
  }

  /**
   * Tests for entity storage exception.
   *
   * @covers ::move
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
    $fileRepository->move($source, $target->getFileUri(), FileSystemInterface::EXISTS_REPLACE);

  }

}
