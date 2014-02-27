<?php

/**
 * @file
 * Definition of Drupal\system\Tests\File\DirectoryTest.
 */

namespace Drupal\system\Tests\File;

/**
 * Directory related tests.
 */
class DirectoryTest extends FileTestBase {
  public static function getInfo() {
    return array(
      'name' => 'File paths and directories',
      'description' => 'Tests operations dealing with directories.',
      'group' => 'File API',
    );
  }

  /**
   * Test local directory handling functions.
   */
  function testFileCheckLocalDirectoryHandling() {
    $directory = conf_path() . '/files';

    // Check a new recursively created local directory for correct file system
    // permissions.
    $parent = $this->randomName();
    $child = $this->randomName();

    // Files directory already exists.
    $this->assertTrue(is_dir($directory), t('Files directory already exists.'), 'File');
    // Make files directory writable only.
    $old_mode = fileperms($directory);

    // Create the directories.
    $parent_path = $directory . DIRECTORY_SEPARATOR . $parent;
    $child_path = $parent_path . DIRECTORY_SEPARATOR . $child;
    $this->assertTrue(drupal_mkdir($child_path, 0775, TRUE), t('No error reported when creating new local directories.'), 'File');

    // Ensure new directories also exist.
    $this->assertTrue(is_dir($parent_path), t('New parent directory actually exists.'), 'File');
    $this->assertTrue(is_dir($child_path), t('New child directory actually exists.'), 'File');

    // Check that new directory permissions were set properly.
    $this->assertDirectoryPermissions($parent_path, 0775);
    $this->assertDirectoryPermissions($child_path, 0775);

    // Check that existing directory permissions were not modified.
    $this->assertDirectoryPermissions($directory, $old_mode);

    // Check creating a directory using an absolute path.
    $absolute_path = drupal_realpath($directory) . DIRECTORY_SEPARATOR . $this->randomName() . DIRECTORY_SEPARATOR . $this->randomName();
    $this->assertTrue(drupal_mkdir($absolute_path, 0775, TRUE), 'No error reported when creating new absolute directories.', 'File');
    $this->assertDirectoryPermissions($absolute_path, 0775);
  }

  /**
   * Test directory handling functions.
   */
  function testFileCheckDirectoryHandling() {
    // A directory to operate on.
    $directory = file_default_scheme() . '://' . $this->randomName() . '/' . $this->randomName();
    $this->assertFalse(is_dir($directory), 'Directory does not exist prior to testing.');

    // Non-existent directory.
    $this->assertFalse(file_prepare_directory($directory, 0), 'Error reported for non-existing directory.', 'File');

    // Make a directory.
    $this->assertTrue(file_prepare_directory($directory, FILE_CREATE_DIRECTORY), 'No error reported when creating a new directory.', 'File');

    // Make sure directory actually exists.
    $this->assertTrue(is_dir($directory), 'Directory actually exists.', 'File');

    if (substr(PHP_OS, 0, 3) != 'WIN') {
      // PHP on Windows doesn't support any kind of useful read-only mode for
      // directories. When executing a chmod() on a directory, PHP only sets the
      // read-only flag, which doesn't prevent files to actually be written
      // in the directory on any recent version of Windows.

      // Make directory read only.
      @drupal_chmod($directory, 0444);
      $this->assertFalse(file_prepare_directory($directory, 0), 'Error reported for a non-writeable directory.', 'File');

      // Test directory permission modification.
      $this->settingsSet('file_chmod_directory', 0777);
      $this->assertTrue(file_prepare_directory($directory, FILE_MODIFY_PERMISSIONS), 'No error reported when making directory writeable.', 'File');
    }

    // Test that the directory has the correct permissions.
    $this->assertDirectoryPermissions($directory, 0777, 'file_chmod_directory setting is respected.');

    // Remove .htaccess file to then test that it gets re-created.
    @drupal_unlink(file_default_scheme() . '://.htaccess');
    $this->assertFalse(is_file(file_default_scheme() . '://.htaccess'), 'Successfully removed the .htaccess file in the files directory.', 'File');
    file_ensure_htaccess();
    $this->assertTrue(is_file(file_default_scheme() . '://.htaccess'), 'Successfully re-created the .htaccess file in the files directory.', 'File');
    // Verify contents of .htaccess file.
    $file = file_get_contents(file_default_scheme() . '://.htaccess');
    $this->assertEqual($file, file_htaccess_lines(FALSE), 'The .htaccess file contains the proper content.', 'File');
  }

  /**
   * This will take a directory and path, and find a valid filepath that is not
   * taken by another file.
   */
  function testFileCreateNewFilepath() {
    // First we test against an imaginary file that does not exist in a
    // directory.
    $basename = 'xyz.txt';
    $directory = 'core/misc';
    $original = $directory . '/' . $basename;
    $path = file_create_filename($basename, $directory);
    $this->assertEqual($path, $original, format_string('New filepath %new equals %original.', array('%new' => $path, '%original' => $original)), 'File');

    // Then we test against a file that already exists within that directory.
    $basename = 'druplicon.png';
    $original = $directory . '/' . $basename;
    $expected = $directory . '/druplicon_0.png';
    $path = file_create_filename($basename, $directory);
    $this->assertEqual($path, $expected, format_string('Creating a new filepath from %original equals %new (expected %expected).', array('%new' => $path, '%original' => $original, '%expected' => $expected)), 'File');

    // @TODO: Finally we copy a file into a directory several times, to ensure a properly iterating filename suffix.
  }

  /**
   * This will test the filepath for a destination based on passed flags and
   * whether or not the file exists.
   *
   * If a file exists, file_destination($destination, $replace) will either
   * return:
   * - the existing filepath, if $replace is FILE_EXISTS_REPLACE
   * - a new filepath if FILE_EXISTS_RENAME
   * - an error (returning FALSE) if FILE_EXISTS_ERROR.
   * If the file doesn't currently exist, then it will simply return the
   * filepath.
   */
  function testFileDestination() {
    // First test for non-existent file.
    $destination = 'core/misc/xyz.txt';
    $path = file_destination($destination, FILE_EXISTS_REPLACE);
    $this->assertEqual($path, $destination, 'Non-existing filepath destination is correct with FILE_EXISTS_REPLACE.', 'File');
    $path = file_destination($destination, FILE_EXISTS_RENAME);
    $this->assertEqual($path, $destination, 'Non-existing filepath destination is correct with FILE_EXISTS_RENAME.', 'File');
    $path = file_destination($destination, FILE_EXISTS_ERROR);
    $this->assertEqual($path, $destination, 'Non-existing filepath destination is correct with FILE_EXISTS_ERROR.', 'File');

    $destination = 'core/misc/druplicon.png';
    $path = file_destination($destination, FILE_EXISTS_REPLACE);
    $this->assertEqual($path, $destination, 'Existing filepath destination remains the same with FILE_EXISTS_REPLACE.', 'File');
    $path = file_destination($destination, FILE_EXISTS_RENAME);
    $this->assertNotEqual($path, $destination, 'A new filepath destination is created when filepath destination already exists with FILE_EXISTS_RENAME.', 'File');
    $path = file_destination($destination, FILE_EXISTS_ERROR);
    $this->assertEqual($path, FALSE, 'An error is returned when filepath destination already exists with FILE_EXISTS_ERROR.', 'File');
  }

  /**
   * Ensure that the file_directory_temp() function always returns a value.
   */
  function testFileDirectoryTemp() {
    // Start with an empty variable to ensure we have a clean slate.
    $config = \Drupal::config('system.file');
    $config->set('path.temporary', '')->save();
    $tmp_directory = file_directory_temp();
    $this->assertEqual(empty($tmp_directory), FALSE, 'file_directory_temp() returned a non-empty value.');
    $this->assertEqual($config->get('path.temporary'), $tmp_directory);
  }
}
