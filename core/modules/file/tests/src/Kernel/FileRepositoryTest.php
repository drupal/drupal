<?php

declare(strict_types=1);

namespace Drupal\Tests\file\Kernel;

use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\File\Exception\FileExistsException;
use Drupal\Core\File\Exception\InvalidStreamWrapperException;
use Drupal\Core\File\FileExists;
use Drupal\Core\StreamWrapper\StreamWrapperManagerInterface;
use Drupal\file\Entity\File;
use Drupal\file\FileRepository;

/**
 * Tests the FileRepository.
 *
 * @coversDefaultClass \Drupal\file\FileRepository
 * @group file
 */
class FileRepositoryTest extends FileManagedUnitTestBase {

  /**
   * The file repository service under test.
   *
   * @var \Drupal\file\FileRepository
   */
  protected $fileRepository;

  /**
   * The file system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->fileRepository = $this->container->get('file.repository');
    $this->fileSystem = $this->container->get('file_system');
  }

  /**
   * Tests the writeData() method.
   *
   * @covers ::writeData
   */
  public function testWithFilename(): void {
    $contents = $this->randomMachineName();

    // Using filename with non-latin characters.
    // cSpell:disable-next-line
    $filename = 'Текстовый файл.txt';

    $result = $this->fileRepository->writeData($contents, 'public://' . $filename);
    $this->assertNotFalse($result, 'Unnamed file saved correctly.');

    $stream_wrapper_manager = \Drupal::service('stream_wrapper_manager');
    assert($stream_wrapper_manager instanceof StreamWrapperManagerInterface);
    $this->assertEquals('public', $stream_wrapper_manager::getScheme($result->getFileUri()), "File was placed in Drupal's files directory.");
    $this->assertEquals($filename, \Drupal::service('file_system')->basename($result->getFileUri()), 'File was named correctly.');
    $this->assertEquals($contents, file_get_contents($result->getFileUri()), 'Contents of the file are correct.');
    $this->assertEquals('text/plain', $result->getMimeType(), 'A MIME type was set.');
    $this->assertTrue($result->isPermanent(), "The file's status was set to permanent.");

    // Check that the correct hooks were called.
    $this->assertFileHooksCalled(['insert']);

    // Verify that what was returned is what's in the database.
    $this->assertFileUnchanged($result, File::load($result->id()));
  }

  /**
   * Tests writeData() when renaming around an existing file.
   *
   * @covers ::writeData
   */
  public function testExistingRename(): void {
    // Setup a file to overwrite.
    $existing = $this->createFile();
    $contents = $this->randomMachineName();

    $result = $this->fileRepository->writeData($contents, $existing->getFileUri());
    $this->assertNotFalse($result, 'File saved successfully.');

    $stream_wrapper_manager = \Drupal::service('stream_wrapper_manager');
    assert($stream_wrapper_manager instanceof StreamWrapperManagerInterface);
    $this->assertEquals('public', $stream_wrapper_manager::getScheme($result->getFileUri()), "File was placed in Drupal's files directory.");
    $this->assertEquals($existing->getFilename(), $result->getFilename(), 'Filename was set to the basename of the source, rather than that of the renamed file.');
    $this->assertEquals($contents, file_get_contents($result->getFileUri()), 'Contents of the file are correct.');
    $this->assertEquals('application/octet-stream', $result->getMimeType(), 'A MIME type was set.');
    $this->assertTrue($result->isPermanent(), "The file's status was set to permanent.");

    // Check that the correct hooks were called.
    $this->assertFileHooksCalled(['insert']);

    // Ensure that the existing file wasn't overwritten.
    $this->assertDifferentFile($existing, $result);
    $this->assertFileUnchanged($existing, File::load($existing->id()));

    // Verify that was returned is what's in the database.
    $this->assertFileUnchanged($result, File::load($result->id()));
  }

  /**
   * Tests writeData() when replacing an existing file.
   *
   * @covers ::writeData
   */
  public function testExistingReplace(): void {
    // Setup a file to overwrite.
    $existing = $this->createFile();
    $contents = $this->randomMachineName();

    $result = $this->fileRepository->writeData($contents, $existing->getFileUri(), FileExists::Replace);
    $this->assertNotFalse($result, 'File saved successfully.');

    $stream_wrapper_manager = \Drupal::service('stream_wrapper_manager');
    assert($stream_wrapper_manager instanceof StreamWrapperManagerInterface);
    $this->assertEquals('public', $stream_wrapper_manager::getScheme($result->getFileUri()), "File was placed in Drupal's files directory.");
    $this->assertEquals($existing->getFilename(), $result->getFilename(), 'Filename was set to the basename of the existing file, rather than preserving the original name.');
    $this->assertEquals($contents, file_get_contents($result->getFileUri()), 'Contents of the file are correct.');
    $this->assertEquals('application/octet-stream', $result->getMimeType(), 'A MIME type was set.');
    $this->assertTrue($result->isPermanent(), "The file's status was set to permanent.");

    // Check that the correct hooks were called.
    $this->assertFileHooksCalled(['load', 'update']);

    // Verify that the existing file was re-used.
    $this->assertSameFile($existing, $result);

    // Verify that what was returned is what's in the database.
    $this->assertFileUnchanged($result, File::load($result->id()));
  }

  /**
   * Tests that writeData() fails overwriting an existing file.
   *
   * @covers ::writeData
   */
  public function testExistingError(): void {
    $contents = $this->randomMachineName();
    $existing = $this->createFile(NULL, $contents);

    // Check the overwrite error.
    try {
      $this->fileRepository->writeData('asdf', $existing->getFileUri(), FileExists::Error);
      $this->fail('expected FileExistsException');
    }
    // FileExistsException is a subclass of FileException.
    catch (FileExistsException $e) {
      $this->assertStringContainsString("could not be copied because a file by that name already exists in the destination directory", $e->getMessage());
    }
    $this->assertEquals($contents, file_get_contents($existing->getFileUri()), 'Contents of existing file were unchanged.');

    // Check that no hooks were called while failing.
    $this->assertFileHooksCalled([]);

    // Ensure that the existing file wasn't overwritten.
    $this->assertFileUnchanged($existing, File::load($existing->id()));
  }

  /**
   * Tests for an invalid stream wrapper.
   *
   * @covers ::writeData
   */
  public function testInvalidStreamWrapper(): void {
    $this->expectException(InvalidStreamWrapperException::class);
    $this->expectExceptionMessage('Invalid stream wrapper: foo://');
    $this->fileRepository->writeData('asdf', 'foo://');
  }

  /**
   * Tests for entity storage exception.
   *
   * @covers ::writeData
   */
  public function testEntityStorageException(): void {
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
    $target = $this->createFile();
    $fileRepository->writeData('asdf', $target->getFileUri(), FileExists::Replace);
  }

  /**
   * Tests loading a file by URI.
   *
   * @covers ::loadByUri
   */
  public function testLoadByUri(): void {
    $source = $this->createFile();
    $result = $this->fileRepository->loadByUri($source->getFileUri());
    $this->assertSameFile($source, $result);
  }

  /**
   * Tests loading a file by case-sensitive URI.
   *
   * @covers ::loadByUri
   */
  public function testLoadByUriCaseSensitive(): void {
    $source = $this->createFile('FooBar.txt');
    $result = $this->fileRepository->loadByUri('public://FooBar.txt');
    $this->assertSameFile($source, $result);
    $result = $this->fileRepository->loadByUri('public://foobar.txt');
    $this->assertNull($result);
  }

}
