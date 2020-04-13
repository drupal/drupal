<?php

namespace Drupal\KernelTests\Core\File;

use Drupal\Core\File\Exception\DirectoryNotReadyException;
use Drupal\Core\File\Exception\FileException;
use Drupal\Core\File\Exception\FileExistsException;
use Drupal\Core\File\Exception\FileNotExistsException;
use Drupal\Core\File\FileSystemInterface;
use Drupal\KernelTests\KernelTestBase;

/**
 * @coversDefaultClass \Drupal\Core\File\FileSystem
 * @group File
 */
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
  protected static $modules = ['system'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->fileSystem = $this->container->get('file_system');
  }

  /**
   * @covers ::copy
   */
  public function testEnsureFileExistsBeforeCopy() {
    // We need to compute the exception message here because it will include
    // the 'real' path to the file, which varies with $this->siteDirectory.
    $this->expectException(FileNotExistsException::class);
    $this->expectExceptionMessage("File 'public://test.txt' ('{$this->siteDirectory}/files/test.txt') could not be copied because it does not exist");

    $this->fileSystem->copy('public://test.txt', 'public://test-copy.txt');
  }

  /**
   * @covers ::copy
   */
  public function testDestinationDirectoryFailureOnCopy() {
    $this->expectException(DirectoryNotReadyException::class);
    $this->expectExceptionMessage("The specified file 'public://test.txt' could not be copied because the destination directory is not properly configured. This may be caused by a problem with file or directory permissions");
    touch('public://test.txt');
    // public://subdirectory has not been created, so \Drupal::service('file_system')->prepareDirectory()
    // will fail, causing copy() to throw DirectoryNotReadyException.
    $this->fileSystem->copy('public://test.txt', 'public://subdirectory/test.txt');
  }

  /**
   * @covers ::copy
   */
  public function testCopyFailureIfFileAlreadyExists() {
    $this->expectException(FileExistsException::class);
    $this->expectExceptionMessage("File 'public://test.txt' could not be copied because a file by that name already exists in the destination directory ('')");
    $uri = 'public://test.txt';
    touch($uri);
    $this->fileSystem->copy($uri, $uri, FileSystemInterface::EXISTS_ERROR);
  }

  /**
   * @covers ::copy
   */
  public function testCopyFailureIfSelfOverwrite() {
    $this->expectException(FileException::class);
    $this->expectExceptionMessage("'public://test.txt' could not be copied because it would overwrite itself");
    $uri = 'public://test.txt';
    touch($uri);
    $this->fileSystem->copy($uri, $uri, FileSystemInterface::EXISTS_REPLACE);
  }

  /**
   * @covers ::copy
   */
  public function testCopySelfRename() {
    $uri = 'public://test.txt';
    touch($uri);
    $this->fileSystem->copy($uri, $uri);
    $this->assertFileExists('public://test_0.txt');
  }

  /**
   * @covers ::copy
   */
  public function testSuccessfulCopy() {
    touch('public://test.txt');
    $this->fileSystem->copy('public://test.txt', 'public://test-copy.txt');
    $this->assertFileExists('public://test-copy.txt');
  }

}
