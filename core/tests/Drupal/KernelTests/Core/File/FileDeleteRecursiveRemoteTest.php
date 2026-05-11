<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\File;

use Drupal\file_test\StreamWrapper\DummyRemoteStreamWrapper;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests deleteRecursive() with remote stream wrappers where realpath() is FALSE.
 *
 * @internal
 */
#[Group('File')]
#[RunTestsInSeparateProcesses]
class FileDeleteRecursiveRemoteTest extends FileTestBase {

  /**
   * A stream wrapper scheme to register for the test.
   *
   * @var string
   */
  protected $scheme = 'dummy-remote';

  /**
   * A fully-qualified stream wrapper class name to register for the test.
   *
   * @var string
   */
  protected $classname = DummyRemoteStreamWrapper::class;

  /**
   * Verifies the dummy-remote stream wrapper returns FALSE from realpath().
   */
  public function testRealpathReturnsFalse(): void {
    $uri = 'dummy-remote://test.txt';
    file_put_contents($uri, 'test');
    $this->assertFalse($this->container->get('file_system')->realpath($uri));
    unlink($uri);
  }

  /**
   * Tests deleting a single file via a remote stream wrapper.
   */
  public function testSingleFile(): void {
    $filepath = 'dummy-remote://' . $this->randomMachineName();
    file_put_contents($filepath, '');

    $this->assertTrue(\Drupal::service('file_system')->deleteRecursive($filepath));
    $this->assertFileDoesNotExist($filepath);
  }

  /**
   * Tests deleting an empty directory via a remote stream wrapper.
   */
  public function testEmptyDirectory(): void {
    $directory = $this->createDirectory('dummy-remote://' . $this->randomMachineName());

    $this->assertTrue(\Drupal::service('file_system')->deleteRecursive($directory));
    $this->assertDirectoryDoesNotExist($directory);
  }

  /**
   * Tests deleting a directory with files via a remote stream wrapper.
   */
  public function testDirectory(): void {
    $directory = $this->createDirectory('dummy-remote://' . $this->randomMachineName());
    $filepathA = $directory . '/A';
    $filepathB = $directory . '/B';
    file_put_contents($filepathA, '');
    file_put_contents($filepathB, '');

    $this->assertTrue(\Drupal::service('file_system')->deleteRecursive($directory));
    $this->assertFileDoesNotExist($filepathA);
    $this->assertFileDoesNotExist($filepathB);
    $this->assertDirectoryDoesNotExist($directory);
  }

  /**
   * Tests deleting subdirectories with files via a remote stream wrapper.
   */
  public function testSubDirectory(): void {
    $directory = $this->createDirectory('dummy-remote://' . $this->randomMachineName());
    $subdirectory = $this->createDirectory($directory . '/sub');
    $filepathA = $directory . '/A';
    $filepathB = $subdirectory . '/B';
    file_put_contents($filepathA, '');
    file_put_contents($filepathB, '');

    $this->assertTrue(\Drupal::service('file_system')->deleteRecursive($directory));
    $this->assertFileDoesNotExist($filepathA);
    $this->assertFileDoesNotExist($filepathB);
    $this->assertDirectoryDoesNotExist($subdirectory);
    $this->assertDirectoryDoesNotExist($directory);
  }

}
