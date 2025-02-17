<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\File;

/**
 * Tests the unmanaged file delete recursive function.
 *
 * @group File
 */
class FileDeleteRecursiveTest extends FileTestBase {

  /**
   * Delete a normal file.
   */
  public function testSingleFile(): void {
    // Create a file for testing
    $filepath = 'public://' . $this->randomMachineName();
    file_put_contents($filepath, '');

    // Delete the file.
    $this->assertTrue(\Drupal::service('file_system')->deleteRecursive($filepath), 'Function reported success.');
    $this->assertFileDoesNotExist($filepath);
  }

  /**
   * Try deleting an empty directory.
   */
  public function testEmptyDirectory(): void {
    // A directory to operate on.
    $directory = $this->createDirectory();

    // Delete the directory.
    $this->assertTrue(\Drupal::service('file_system')->deleteRecursive($directory), 'Function reported success.');
    $this->assertDirectoryDoesNotExist($directory);
  }

  /**
   * Try deleting a directory with some files.
   */
  public function testDirectory(): void {
    // A directory to operate on.
    $directory = $this->createDirectory();
    $filepathA = $directory . '/A';
    $filepathB = $directory . '/B';
    file_put_contents($filepathA, '');
    file_put_contents($filepathB, '');

    // Delete the directory.
    $this->assertTrue(\Drupal::service('file_system')->deleteRecursive($directory), 'Function reported success.');
    $this->assertFileDoesNotExist($filepathA);
    $this->assertFileDoesNotExist($filepathB);
    $this->assertDirectoryDoesNotExist($directory);
  }

  /**
   * Try deleting subdirectories with some files.
   */
  public function testSubDirectory(): void {
    // A directory to operate on.
    $directory = $this->createDirectory();
    $subdirectory = $this->createDirectory($directory . '/sub');
    $filepathA = $directory . '/A';
    $filepathB = $subdirectory . '/B';
    file_put_contents($filepathA, '');
    file_put_contents($filepathB, '');

    // Delete the directory.
    $this->assertTrue(\Drupal::service('file_system')->deleteRecursive($directory), 'Function reported success.');
    $this->assertFileDoesNotExist($filepathA);
    $this->assertFileDoesNotExist($filepathB);
    $this->assertDirectoryDoesNotExist($subdirectory);
    $this->assertDirectoryDoesNotExist($directory);
  }

  /**
   * Tests symlinks in directories do not result in unexpected deletions.
   */
  public function testSymlinksInDirectory(): void {
    // Create files to link to.
    mkdir($this->siteDirectory . '/dir1');
    touch($this->siteDirectory . '/dir1/test1.txt');
    touch($this->siteDirectory . '/test2.txt');

    // Create directory to be deleted.
    mkdir($this->siteDirectory . '/dir2');
    // Symlink to a directory outside dir2.
    symlink(realpath($this->siteDirectory . '/dir1'), $this->siteDirectory . '/dir2/subdir');
    // Symlink to a file outside dir2.
    symlink(realpath($this->siteDirectory . '/test2.txt'), $this->siteDirectory . '/dir2/test2.text');
    $this->assertFileExists($this->siteDirectory . '/dir2/subdir/test1.txt');
    $this->assertFileExists($this->siteDirectory . '/dir2/test2.text');

    $this->container->get('file_system')->deleteRecursive($this->siteDirectory . '/dir2');
    $this->assertFileExists($this->siteDirectory . '/dir1/test1.txt');
    $this->assertFileExists($this->siteDirectory . '/test2.txt');
    $this->assertDirectoryDoesNotExist($this->siteDirectory . '/dir2');
  }

  /**
   * Tests symlinks in directories do not result in unexpected deletions.
   */
  public function testSymlinksInDirectoryViaStreamWrappers(): void {
    // Create files to link to.
    mkdir($this->siteDirectory . '/files/dir1');
    touch($this->siteDirectory . '/files/dir1/test1.txt');
    touch($this->siteDirectory . '/files/test2.txt');

    // Create directory to be deleted.
    mkdir($this->siteDirectory . '/files/dir2');
    // Symlink to a directory outside dir2.
    symlink(realpath($this->siteDirectory . '/files/dir1'), $this->siteDirectory . '/files/dir2/subdir');
    // Symlink to a file outside dir2.
    symlink(realpath($this->siteDirectory . '/files/test2.txt'), $this->siteDirectory . '/files/dir2/test2.text');
    $this->assertFileExists($this->siteDirectory . '/files/dir2/subdir/test1.txt');
    $this->assertFileExists($this->siteDirectory . '/files/dir2/test2.text');

    // Use the stream wrapper to delete.
    $this->container->get('file_system')->deleteRecursive('public://dir2');
    $this->assertFileExists($this->siteDirectory . '/files/dir1/test1.txt');
    $this->assertFileExists($this->siteDirectory . '/files/test2.txt');
    $this->assertDirectoryDoesNotExist($this->siteDirectory . '/files/dir2');
  }

  /**
   * Tests symlinks to directories do not result in unexpected deletions.
   */
  public function testSymlinksToDirectory(): void {
    // Create files to link to.
    mkdir($this->siteDirectory . '/dir1');
    touch($this->siteDirectory . '/dir1/test1.txt');
    // Symlink to a directory outside dir2.
    symlink(realpath($this->siteDirectory . '/dir1'), $this->siteDirectory . '/dir2');
    $this->assertFileExists($this->siteDirectory . '/dir2/test1.txt');

    $this->container->get('file_system')->deleteRecursive($this->siteDirectory . '/dir2');
    $this->assertFileExists($this->siteDirectory . '/dir1/test1.txt');
    $this->assertDirectoryDoesNotExist($this->siteDirectory . '/dir2');
  }

  /**
   * Tests trying to delete symlinks to directories via stream wrappers.
   *
   * Note that this tests unexpected behavior.
   */
  public function testSymlinksToDirectoryViaStreamWrapper(): void {
    $file_system = $this->container->get('file_system');

    // Create files to link to.
    $file_system->mkdir('public://dir1');
    file_put_contents('public://dir1/test1.txt', 'test');

    // Symlink to a directory outside dir2.
    $public_path = realpath($this->siteDirectory . '/files');
    symlink($public_path . '/dir1', $public_path . '/dir2');
    $this->assertFileExists($public_path . '/dir1/test1.txt');
    $this->assertFileExists('public://dir2/test1.txt');

    // The stream wrapper system resolves 'public://dir2' to 'files/dir1'.
    // Therefore, this call results in removing dir1 and does not remove the
    // dir2 symlink.
    $this->container->get('file_system')->deleteRecursive('public://dir2');
    $this->assertFileDoesNotExist($public_path . '/dir1/test1.txt');
    // The directory is now a broken link.
    $this->assertTrue(is_link($public_path . '/dir2'));
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown(): void {
    $this->assertDirectoryExists($this->siteDirectory);
    parent::tearDown();

    // Ensure \Drupal\KernelTests\KernelTestBase::tearDown() has cleaned up the
    // file system.
    $this->assertDirectoryDoesNotExist($this->siteDirectory);
  }

}
