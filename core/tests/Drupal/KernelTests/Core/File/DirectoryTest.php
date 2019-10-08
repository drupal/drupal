<?php

namespace Drupal\KernelTests\Core\File;

use Drupal\Component\FileSecurity\FileSecurity;
use Drupal\Component\FileSystem\FileSystem;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\File\Exception\FileException;
use Drupal\Core\File\FileSystemInterface;

/**
 * Tests operations dealing with directories.
 *
 * @group File
 */
class DirectoryTest extends FileTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['system'];

  protected function setUp() {
    parent::setUp();

    // These additional tables are necessary due to the call to system_cron().
    $this->installSchema('system', ['key_value_expire']);
  }

  /**
   * Test local directory handling functions.
   */
  public function testFileCheckLocalDirectoryHandling() {
    $site_path = $this->container->get('site.path');
    $directory = $site_path . '/files';

    // Check a new recursively created local directory for correct file system
    // permissions.
    $parent = $this->randomMachineName();
    $child = $this->randomMachineName();

    // Files directory already exists.
    $this->assertTrue(is_dir($directory), t('Files directory already exists.'), 'File');
    // Make files directory writable only.
    $old_mode = fileperms($directory);

    // Create the directories.
    $parent_path = $directory . DIRECTORY_SEPARATOR . $parent;
    $child_path = $parent_path . DIRECTORY_SEPARATOR . $child;
    /** @var \Drupal\Core\File\FileSystemInterface $file_system */
    $file_system = \Drupal::service('file_system');
    $this->assertTrue($file_system->mkdir($child_path, 0775, TRUE), t('No error reported when creating new local directories.'), 'File');

    // Ensure new directories also exist.
    $this->assertTrue(is_dir($parent_path), t('New parent directory actually exists.'), 'File');
    $this->assertTrue(is_dir($child_path), t('New child directory actually exists.'), 'File');

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
   * Test directory handling functions.
   */
  public function testFileCheckDirectoryHandling() {
    // A directory to operate on.
    $default_scheme = 'public';
    $directory = $default_scheme . '://' . $this->randomMachineName() . '/' . $this->randomMachineName();
    $this->assertFalse(is_dir($directory), 'Directory does not exist prior to testing.');

    // Non-existent directory.
    /** @var \Drupal\Core\File\FileSystemInterface $file_system */
    $file_system = \Drupal::service('file_system');
    $this->assertFalse($file_system->prepareDirectory($directory, 0), 'Error reported for non-existing directory.', 'File');

    // Make a directory.
    $this->assertTrue($file_system->prepareDirectory($directory, FileSystemInterface::CREATE_DIRECTORY), 'No error reported when creating a new directory.', 'File');

    // Make sure directory actually exists.
    $this->assertTrue(is_dir($directory), 'Directory actually exists.', 'File');
    $file_system = \Drupal::service('file_system');
    if (substr(PHP_OS, 0, 3) != 'WIN') {
      // PHP on Windows doesn't support any kind of useful read-only mode for
      // directories. When executing a chmod() on a directory, PHP only sets the
      // read-only flag, which doesn't prevent files to actually be written
      // in the directory on any recent version of Windows.

      // Make directory read only.
      @$file_system->chmod($directory, 0444);
      $this->assertFalse($file_system->prepareDirectory($directory, 0), 'Error reported for a non-writeable directory.', 'File');

      // Test directory permission modification.
      $this->setSetting('file_chmod_directory', 0777);
      $this->assertTrue($file_system->prepareDirectory($directory, FileSystemInterface::MODIFY_PERMISSIONS), 'No error reported when making directory writeable.', 'File');
    }

    // Test that the directory has the correct permissions.
    $this->assertDirectoryPermissions($directory, 0777, 'file_chmod_directory setting is respected.');

    // Remove .htaccess file to then test that it gets re-created.
    @$file_system->unlink($default_scheme . '://.htaccess');
    $this->assertFalse(is_file($default_scheme . '://.htaccess'), 'Successfully removed the .htaccess file in the files directory.', 'File');
    $this->container->get('file.htaccess_writer')->ensure();
    $this->assertTrue(is_file($default_scheme . '://.htaccess'), 'Successfully re-created the .htaccess file in the files directory.', 'File');

    // Remove .htaccess file again to test that it is re-created by a cron run.
    @$file_system->unlink($default_scheme . '://.htaccess');
    $this->assertFalse(is_file($default_scheme . '://.htaccess'), 'Successfully removed the .htaccess file in the files directory.', 'File');
    system_cron();
    $this->assertTrue(is_file($default_scheme . '://.htaccess'), 'Successfully re-created the .htaccess file in the files directory.', 'File');

    // Verify contents of .htaccess file.
    $file = file_get_contents($default_scheme . '://.htaccess');
    $this->assertEqual($file, FileSecurity::htaccessLines(FALSE), 'The .htaccess file contains the proper content.', 'File');
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
    $this->assertEqual($path, $original, new FormattableMarkup('New filepath %new equals %original.', ['%new' => $path, '%original' => $original]), 'File');

    // Then we test against a file that already exists within that directory.
    $basename = 'druplicon.png';
    $original = $directory . '/' . $basename;
    $expected = $directory . '/druplicon_0.png';
    $path = $file_system->createFilename($basename, $directory);
    $this->assertEqual($path, $expected, new FormattableMarkup('Creating a new filepath from %original equals %new (expected %expected).', ['%new' => $path, '%original' => $original, '%expected' => $expected]), 'File');

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
    $this->assertEqual($path, $destination, 'Non-existing filepath destination is correct with FileSystemInterface::EXISTS_REPLACE.', 'File');
    $path = $file_system->getDestinationFilename($destination, FileSystemInterface::EXISTS_RENAME);
    $this->assertEqual($path, $destination, 'Non-existing filepath destination is correct with FileSystemInterface::EXISTS_RENAME.', 'File');
    $path = $file_system->getDestinationFilename($destination, FileSystemInterface::EXISTS_ERROR);
    $this->assertEqual($path, $destination, 'Non-existing filepath destination is correct with FileSystemInterface::EXISTS_ERROR.', 'File');

    $destination = 'core/misc/druplicon.png';
    $path = $file_system->getDestinationFilename($destination, FileSystemInterface::EXISTS_REPLACE);
    $this->assertEqual($path, $destination, 'Existing filepath destination remains the same with FileSystemInterface::EXISTS_REPLACE.', 'File');
    $path = $file_system->getDestinationFilename($destination, FileSystemInterface::EXISTS_RENAME);
    $this->assertNotEqual($path, $destination, 'A new filepath destination is created when filepath destination already exists with FileSystemInterface::EXISTS_RENAME.', 'File');
    $path = $file_system->getDestinationFilename($destination, FileSystemInterface::EXISTS_ERROR);
    $this->assertEqual($path, FALSE, 'An error is returned when filepath destination already exists with FileSystemInterface::EXISTS_ERROR.', 'File');

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

}
