<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\File;

use Drupal\Core\File\Exception\FileExistsException;
use Drupal\Core\File\Exception\FileNotExistsException;
use Drupal\Core\File\FileExists;
use Drupal\Core\File\FileSystem;
use Drupal\Core\Site\Settings;

/**
 * Tests the unmanaged file copy function.
 *
 * @group File
 */
class FileCopyTest extends FileTestBase {

  /**
   * Copy a normal file.
   */
  public function testNormal(): void {
    // Create a file for testing
    $uri = $this->createUri();

    // Copying to a new name.
    $desired_filepath = 'public://' . $this->randomMachineName();
    $new_filepath = \Drupal::service('file_system')->copy($uri, $desired_filepath, FileExists::Error);
    $this->assertNotFalse($new_filepath, 'Copy was successful.');
    $this->assertEquals($desired_filepath, $new_filepath, 'Returned expected filepath.');
    $this->assertFileExists($uri);
    $this->assertFileExists($new_filepath);
    $this->assertFilePermissions($new_filepath, Settings::get('file_chmod_file', FileSystem::CHMOD_FILE));

    // Copying with rename.
    $desired_filepath = 'public://' . $this->randomMachineName();
    $this->assertNotFalse(file_put_contents($desired_filepath, ' '), 'Created a file so a rename will have to happen.');
    $newer_filepath = \Drupal::service('file_system')->copy($uri, $desired_filepath, FileExists::Rename);
    $this->assertNotFalse($newer_filepath, 'Copy was successful.');
    $this->assertNotEquals($desired_filepath, $newer_filepath, 'Returned expected filepath.');
    $this->assertFileExists($uri);
    $this->assertFileExists($newer_filepath);
    $this->assertFilePermissions($newer_filepath, Settings::get('file_chmod_file', FileSystem::CHMOD_FILE));

    // @todo Test copying to a directory (rather than full directory/file path)
    // @todo Test copying normal files using normal paths (rather than only streams)
  }

  /**
   * Copy a non-existent file.
   */
  public function testNonExistent(): void {
    // Copy non-existent file
    $desired_filepath = $this->randomMachineName();
    $this->assertFileDoesNotExist($desired_filepath);
    $this->expectException(FileNotExistsException::class);
    $new_filepath = \Drupal::service('file_system')->copy($desired_filepath, $this->randomMachineName());
    $this->assertFalse($new_filepath, 'Copying a missing file fails.');
  }

  /**
   * Copy a file onto itself.
   */
  public function testOverwriteSelf(): void {
    // Create a file for testing
    $uri = $this->createUri();

    // Copy the file onto itself with renaming works.
    /** @var \Drupal\Core\File\FileSystemInterface $file_system */
    $file_system = \Drupal::service('file_system');
    $new_filepath = $file_system->copy($uri, $uri, FileExists::Rename);
    $this->assertNotFalse($new_filepath, 'Copying onto itself with renaming works.');
    $this->assertNotEquals($uri, $new_filepath, 'Copied file has a new name.');
    $this->assertFileExists($uri);
    $this->assertFileExists($new_filepath);
    $this->assertFilePermissions($new_filepath, Settings::get('file_chmod_file', FileSystem::CHMOD_FILE));

    // Copy the file onto itself without renaming fails.
    $this->expectException(FileExistsException::class);
    $new_filepath = $file_system->copy($uri, $uri, FileExists::Error);
    $this->assertFalse($new_filepath, 'Copying onto itself without renaming fails.');
    $this->assertFileExists($uri);

    // Copy the file into same directory without renaming fails.
    $new_filepath = $file_system->copy($uri, $file_system->dirname($uri), FileExists::Error);
    $this->assertFalse($new_filepath, 'Copying onto itself fails.');
    $this->assertFileExists($uri);

    // Copy the file into same directory with renaming works.
    $new_filepath = $file_system->copy($uri, $file_system->dirname($uri), FileExists::Rename);
    $this->assertNotFalse($new_filepath, 'Copying into same directory works.');
    $this->assertNotEquals($uri, $new_filepath, 'Copied file has a new name.');
    $this->assertFileExists($uri);
    $this->assertFileExists($new_filepath);
    $this->assertFilePermissions($new_filepath, Settings::get('file_chmod_file', FileSystem::CHMOD_FILE));
  }

}
