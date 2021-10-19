<?php

namespace Drupal\KernelTests\Core\File;

use Drupal\Component\FileSecurity\FileSecurity;
use Drupal\Component\FileSystem\FileSystem;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Database\Database;
use Drupal\Core\File\Exception\FileException;
use Drupal\Core\File\FileSystemInterface;

/**
 * Tests operations dealing with directories.
 *
 * @group File
 */
class DirectoryTest extends FileTestBase {

  /**
   * Tests local directory handling functions.
   */
  public function testFileCheckLocalDirectoryHandling() {
    $site_path = $this->container->getParameter('site.path');
    $directory = $site_path . '/files';

    // Check a new recursively created local directory for correct file system
    // permissions.
    $parent = $this->randomMachineName();
    $child = $this->randomMachineName();

    // Files directory already exists.
    $this->assertDirectoryExists($directory);
    // Make files directory writable only.
    $old_mode = fileperms($directory);

    // Create the directories.
    $parent_path = $directory . DIRECTORY_SEPARATOR . $parent;
    $child_path = $parent_path . DIRECTORY_SEPARATOR . $child;
    /** @var \Drupal\Core\File\FileSystemInterface $file_system */
    $file_system = \Drupal::service('file_system');
    $this->assertTrue($file_system->mkdir($child_path, 0775, TRUE), 'No error reported when creating new local directories.');

    // Ensure new directories also exist.
    $this->assertDirectoryExists($parent_path);
    $this->assertDirectoryExists($child_path);

    // Check that new directory permissions were set properly.
    $this->assertDirectoryPermissions($parent_path, 0775);
    $this->assertDirectoryPermissions($child_path, 0775);

    // Check that existing directory permissions were not modified.
    $this->assertDirectoryPermissions($directory, $old_mode);

    // Check creating a directory using an absolute path.
    $absolute_path = $file_system->realpath($directory) . DIRECTORY_SEPARATOR . $this->randomMachineName() . DIRECTORY_SEPARATOR . $this->randomMachineName();
    $this->assertTrue($file_system->mkdir($absolute_path, 0775, TRUE), 'No error reported when creating new absolute directories.', 'File');
    $this->assertDirectoryPermissions($absolute_path, 0775);
  }

  /**
   * Tests directory handling functions.
   */
  public function testFileCheckDirectoryHandling() {
    // A directory to operate on.
    $default_scheme = 'public';
    $directory = $default_scheme . '://' . $this->randomMachineName() . '/' . $this->randomMachineName();
    $this->assertDirectoryDoesNotExist($directory);

    // Non-existent directory.
    /** @var \Drupal\Core\File\FileSystemInterface $file_system */
    $file_system = \Drupal::service('file_system');
    $this->assertFalse($file_system->prepareDirectory($directory, 0), 'Error reported for non-existing directory.', 'File');

    // Make a directory.
    $this->assertTrue($file_system->prepareDirectory($directory, FileSystemInterface::CREATE_DIRECTORY), 'No error reported when creating a new directory.', 'File');

    // Make sure directory actually exists.
    $this->assertDirectoryExists($directory);
    $file_system = \Drupal::service('file_system');
    if (substr(PHP_OS, 0, 3) != 'WIN') {
      // PHP on Windows doesn't support any kind of useful read-only mode for
      // directories. When executing a chmod() on a directory, PHP only sets the
      // read-only flag, which doesn't prevent files to actually be written
      // in the directory on any recent version of Windows.

      // Make directory read only.
      @$file_system->chmod($directory, 0444);
      $this->assertFalse($file_system->prepareDirectory($directory, 0), 'Error reported for a non-writable directory.', 'File');

      // Test directory permission modification.
      $this->setSetting('file_chmod_directory', 0777);
      $this->assertTrue($file_system->prepareDirectory($directory, FileSystemInterface::MODIFY_PERMISSIONS), 'No error reported when making directory writable.', 'File');
    }

    // Test that the directory has the correct permissions.
    $this->assertDirectoryPermissions($directory, 0777, 'file_chmod_directory setting is respected.');

    // Remove .htaccess file to then test that it gets re-created.
    @$file_system->unlink($default_scheme . '://.htaccess');
    $this->assertFileDoesNotExist($default_scheme . '://.htaccess');
    $this->container->get('file.htaccess_writer')->ensure();
    $this->assertFileExists($default_scheme . '://.htaccess');

    // Remove .htaccess file again to test that it is re-created by a cron run.
    @$file_system->unlink($default_scheme . '://.htaccess');
    $this->assertFileDoesNotExist($default_scheme . '://.htaccess');
    system_cron();
    $this->assertFileExists($default_scheme . '://.htaccess');

    // Verify contents of .htaccess file.
    $file = file_get_contents($default_scheme . '://.htaccess');
    $this->assertEquals(FileSecurity::htaccessLines(FALSE), $file, 'The .htaccess file contains the proper content.');
  }

  /**
   * This will take a directory and path, and find a valid filepath that is not
   * taken by another file.
   */
  public function testFileCreateNewFilepath() {
    // First we test against an imaginary file that does not exist in a
    // directory.
    $basename = 'xyz.txt';
    $directory = 'core/misc';
    $original = $directory . '/' . $basename;
    /** @var \Drupal\Core\File\FileSystemInterface $file_system */
    $file_system = \Drupal::service('file_system');
    $path = $file_system->createFilename($basename, $directory);
    $this->assertEquals($original, $path, new FormattableMarkup('New filepath %new equals %original.', ['%new' => $path, '%original' => $original]));

    // Then we test against a file that already exists within that directory.
    $basename = 'druplicon.png';
    $original = $directory . '/' . $basename;
    $expected = $directory . '/druplicon_0.png';
    $path = $file_system->createFilename($basename, $directory);
    $this->assertEquals($expected, $path, new FormattableMarkup('Creating a new filepath from %original equals %new (expected %expected).', ['%new' => $path, '%original' => $original, '%expected' => $expected]));

    // @TODO: Finally we copy a file into a directory several times, to ensure a properly iterating filename suffix.
  }

  /**
   * This will test the filepath for a destination based on passed flags and
   * whether or not the file exists.
   *
   * If a file exists, ::getDestinationFilename($destination, $replace) will
   * either return:
   * - the existing filepath, if $replace is FileSystemInterface::EXISTS_REPLACE
   * - a new filepath if FileSystemInterface::EXISTS_RENAME
   * - an error (returning FALSE) if FileSystemInterface::EXISTS_ERROR.
   * If the file doesn't currently exist, then it will simply return the
   * filepath.
   */
  public function testFileDestination() {
    // First test for non-existent file.
    $destination = 'core/misc/xyz.txt';
    /** @var \Drupal\Core\File\FileSystemInterface $file_system */
    $file_system = \Drupal::service('file_system');
    $path = $file_system->getDestinationFilename($destination, FileSystemInterface::EXISTS_REPLACE);
    $this->assertEquals($destination, $path, 'Non-existing filepath destination is correct with FileSystemInterface::EXISTS_REPLACE.');
    $path = $file_system->getDestinationFilename($destination, FileSystemInterface::EXISTS_RENAME);
    $this->assertEquals($destination, $path, 'Non-existing filepath destination is correct with FileSystemInterface::EXISTS_RENAME.');
    $path = $file_system->getDestinationFilename($destination, FileSystemInterface::EXISTS_ERROR);
    $this->assertEquals($destination, $path, 'Non-existing filepath destination is correct with FileSystemInterface::EXISTS_ERROR.');

    $destination = 'core/misc/druplicon.png';
    $path = $file_system->getDestinationFilename($destination, FileSystemInterface::EXISTS_REPLACE);
    $this->assertEquals($destination, $path, 'Existing filepath destination remains the same with FileSystemInterface::EXISTS_REPLACE.');
    $path = $file_system->getDestinationFilename($destination, FileSystemInterface::EXISTS_RENAME);
    $this->assertNotEquals($destination, $path, 'A new filepath destination is created when filepath destination already exists with FileSystemInterface::EXISTS_RENAME.');
    $path = $file_system->getDestinationFilename($destination, FileSystemInterface::EXISTS_ERROR);
    $this->assertFalse($path, 'An error is returned when filepath destination already exists with FileSystemInterface::EXISTS_ERROR.');

    // Invalid UTF-8 causes an exception.
    $this->expectException(FileException::class);
    $this->expectExceptionMessage("Invalid filename 'a\xFFtest\x80€.txt'");
    $file_system->getDestinationFilename("core/misc/a\xFFtest\x80€.txt", FileSystemInterface::EXISTS_REPLACE);
  }

  /**
   * Ensure that the getTempDirectory() method always returns a value.
   */
  public function testFileDirectoryTemp() {
    $tmp_directory = \Drupal::service('file_system')->getTempDirectory();
    $this->assertNotEmpty($tmp_directory);
    $this->assertEquals($tmp_directory, FileSystem::getOsTemporaryDirectory());
  }

  /**
   * Tests directory creation.
   */
  public function testDirectoryCreation() {
    /** @var \Drupal\Core\File\FileSystemInterface $file_system */
    $file_system = $this->container->get('file_system');

    // mkdir() recursion should work with or without a trailing slash.
    $dir = $this->siteDirectory . '/files';
    $this->assertTrue($file_system->mkdir($dir . '/foo/bar', 0775, TRUE));
    $this->assertTrue($file_system->mkdir($dir . '/foo/baz/', 0775, TRUE));
  }

  /**
   * Tests asynchronous directory creation.
   *
   * Image style generation can result in many calls to create similar directory
   * paths. This test forks the process to create the same situation.
   */
  public function testMultiplePrepareDirectory() {
    if (!function_exists('pcntl_fork')) {
      $this->markTestSkipped('Requires the pcntl_fork() function');
    }
    $directories = [];
    for ($i = 1; $i <= 10; $i++) {
      $directories[] = 'public://a/b/c/d/e/f/g/h/' . $i;
    }

    $file_system = $this->container->get('file_system');

    $time_to_start = microtime(TRUE) + 0.1;
    // This loop creates a new fork to create each directory.
    foreach ($directories as $directory) {
      $pid = pcntl_fork();
      if ($pid == -1) {
        $this->fail("Error forking");
      }
      elseif ($pid == 0) {
        // Sleep so that all the forks start preparing the directory at the same
        // time.
        usleep((int) (($time_to_start - microtime(TRUE)) * 1000000));
        $file_system->prepareDirectory($directory, FileSystemInterface::CREATE_DIRECTORY);
        exit();
      }
    }

    // This while loop holds the parent process until all the child threads
    // are complete - at which point the script continues to execute.
    while (pcntl_waitpid(0, $status) != -1);

    foreach ($directories as $directory) {
      $this->assertDirectoryExists($directory);
    }

    // Remove the database connection because it will have been destroyed when
    // the forks exited. This allows
    // \Drupal\KernelTests\KernelTestBase::tearDown() to reopen it.
    Database::removeConnection('default');
  }

}
