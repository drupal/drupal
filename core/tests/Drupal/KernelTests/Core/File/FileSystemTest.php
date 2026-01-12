<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\File;

use Drupal\Core\File\Exception\DirectoryNotReadyException;
use Drupal\Core\File\Exception\FileException;
use Drupal\Core\File\Exception\FileExistsException;
use Drupal\Core\File\Exception\FileNotExistsException;
use Drupal\Core\File\FileExists;
use Drupal\Core\File\FileSystem;
use Drupal\KernelTests\KernelTestBase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests Drupal\Core\File\FileSystem.
 */
#[CoversClass(FileSystem::class)]
#[Group('File')]
#[RunTestsInSeparateProcesses]
class FileSystemTest extends KernelTestBase {

  /**
   * The file handler under test.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->fileSystem = $this->container->get('file_system');
  }

  /**
   * Tests ensure file exists before copy.
   *
   * @legacy-covers ::copy
   */
  public function testEnsureFileExistsBeforeCopy(): void {
    // We need to compute the exception message here because it will include
    // the 'real' path to the file, which varies with $this->siteDirectory.
    $this->expectException(FileNotExistsException::class);
    $this->expectExceptionMessage("File 'public://test.txt' ('{$this->siteDirectory}/files/test.txt') could not be copied because it does not exist");

    $this->fileSystem->copy('public://test.txt', 'public://test-copy.txt');
  }

  /**
   * Tests destination directory failure on copy.
   *
   * @legacy-covers ::copy
   */
  public function testDestinationDirectoryFailureOnCopy(): void {
    $this->expectException(DirectoryNotReadyException::class);
    $this->expectExceptionMessage("The specified file 'public://test.txt' could not be copied because the destination directory 'public://subdirectory' is not properly configured. This may be caused by a problem with file or directory permissions.");
    touch('public://test.txt');
    // public://subdirectory has not been created, so
    // \Drupal::service('file_system')->prepareDirectory() will fail, causing
    // copy() to throw DirectoryNotReadyException.
    $this->fileSystem->copy('public://test.txt', 'public://subdirectory/test.txt');
  }

  /**
   * Tests copy failure if file already exists.
   */
  public function testCopyFailureIfFileAlreadyExists(): void {
    $this->expectException(FileExistsException::class);
    $this->expectExceptionMessage("File 'public://test.txt' could not be copied because a file by that name already exists in the destination directory ('')");
    $uri = 'public://test.txt';
    touch($uri);
    $this->fileSystem->copy($uri, $uri, FileExists::Error);
  }

  /**
   * Tests copy failure if self overwrite.
   */
  public function testCopyFailureIfSelfOverwrite(): void {
    $this->expectException(FileException::class);
    $this->expectExceptionMessage("'public://test.txt' could not be copied because it would overwrite itself");
    $uri = 'public://test.txt';
    touch($uri);
    $this->fileSystem->copy($uri, $uri, FileExists::Replace);
  }

  /**
   * Tests copy self rename.
   */
  public function testCopySelfRename(): void {
    $uri = 'public://test.txt';
    touch($uri);
    $this->fileSystem->copy($uri, $uri);
    $this->assertFileExists('public://test_0.txt');
  }

  /**
   * Tests successful copy.
   *
   * @legacy-covers ::copy
   */
  public function testSuccessfulCopy(): void {
    touch('public://test.txt');
    $this->fileSystem->copy('public://test.txt', 'public://test-copy.txt');
    $this->assertFileExists('public://test-copy.txt');
  }

}
