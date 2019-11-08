<?php

namespace Drupal\KernelTests\Core\File;

use Drupal\Core\File\Exception\FileException;
use Drupal\Core\File\Exception\FileNotExistsException;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Site\Settings;
use Drupal\Core\File\FileSystem;

/**
 * Tests the unmanaged file move function.
 *
 * @group File
 */
class FileMoveTest extends FileTestBase {

  /**
   * Move a normal file.
   */
  public function testNormal() {
    // Create a file for testing
    $uri = $this->createUri();

    // Moving to a new name.
    $desired_filepath = 'public://' . $this->randomMachineName();
    /** @var \Drupal\Core\File\FileSystemInterface $file_system */
    $file_system = \Drupal::service('file_system');
    $new_filepath = $file_system->move($uri, $desired_filepath, FileSystemInterface::EXISTS_ERROR);
    $this->assertNotFalse($new_filepath, 'Move was successful.');
    $this->assertEqual($new_filepath, $desired_filepath, 'Returned expected filepath.');
    $this->assertTrue(file_exists($new_filepath), 'File exists at the new location.');
    $this->assertFalse(file_exists($uri), 'No file remains at the old location.');
    $this->assertFilePermissions($new_filepath, Settings::get('file_chmod_file', FileSystem::CHMOD_FILE));

    // Moving with rename.
    $desired_filepath = 'public://' . $this->randomMachineName();
    $this->assertTrue(file_exists($new_filepath), 'File exists before moving.');
    $this->assertNotFalse(file_put_contents($desired_filepath, ' '), 'Created a file so a rename will have to happen.');
    $newer_filepath = $file_system->move($new_filepath, $desired_filepath, FileSystemInterface::EXISTS_RENAME);
    $this->assertNotFalse($newer_filepath, 'Move was successful.');
    $this->assertNotEqual($newer_filepath, $desired_filepath, 'Returned expected filepath.');
    $this->assertTrue(file_exists($newer_filepath), 'File exists at the new location.');
    $this->assertFalse(file_exists($new_filepath), 'No file remains at the old location.');
    $this->assertFilePermissions($newer_filepath, Settings::get('file_chmod_file', FileSystem::CHMOD_FILE));

    // TODO: test moving to a directory (rather than full directory/file path)
    // TODO: test creating and moving normal files (rather than streams)
  }

  /**
   * Try to move a missing file.
   */
  public function testMissing() {
    // Move non-existent file.
    $this->expectException(FileNotExistsException::class);
    \Drupal::service('file_system')->move($this->randomMachineName(), $this->randomMachineName());
  }

  /**
   * Try to move a file onto itself.
   */
  public function testOverwriteSelf() {
    // Create a file for testing.
    $uri = $this->createUri();

    // Move the file onto itself without renaming shouldn't make changes.
    /** @var \Drupal\Core\File\FileSystemInterface $file_system */
    $file_system = \Drupal::service('file_system');
    $this->expectException(FileException::class);
    $new_filepath = $file_system->move($uri, $uri, FileSystemInterface::EXISTS_REPLACE);
    $this->assertFalse($new_filepath, 'Moving onto itself without renaming fails.');
    $this->assertTrue(file_exists($uri), 'File exists after moving onto itself.');

    // Move the file onto itself with renaming will result in a new filename.
    $new_filepath = $file_system->move($uri, $uri, FileSystemInterface::EXISTS_RENAME);
    $this->assertNotFalse($new_filepath, 'Moving onto itself with renaming works.');
    $this->assertFalse(file_exists($uri), 'Original file has been removed.');
    $this->assertTrue(file_exists($new_filepath), 'File exists after moving onto itself.');
  }

}
