<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\File;

use Drupal\Core\File\FileSystemInterface;

/**
 * Tests the legacy file system functions.
 *
 * @group file
 * @group legacy
 * @coversDefaultClass \Drupal\Core\File\FileSystem
 */
class LegacyFileSystemTest extends FileTestBase {

  /**
   * The file system under test.
   */
  protected FileSystemInterface $fileSystem;

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
  public function testCopyWithDeprecatedFileExists(): void {
    $uri = 'public://test.txt';
    touch($uri);
    $this->expectDeprecation('Passing the $fileExists argument as an integer to Drupal\Core\File\FileSystem::copy() is deprecated in drupal:10.3.0 and is removed from drupal:12.0.0. Use \Drupal\Core\File\FileExists enum instead. See https://www.drupal.org/node/3426517');
    $newUri = $this->fileSystem->copy($uri, $uri, FileSystemInterface::EXISTS_RENAME);
    $this->assertFileExists($newUri);
  }

  /**
   * @covers ::move
   */
  public function testMoveWithDeprecatedFileExists(): void {
    $uri = 'public://test.txt';
    touch($uri);
    $this->expectDeprecation('Passing the $fileExists argument as an integer to Drupal\Core\File\FileSystem::move() is deprecated in drupal:10.3.0 and is removed from drupal:12.0.0. Use \Drupal\Core\File\FileExists enum instead. See https://www.drupal.org/node/3426517');
    $newUri = $this->fileSystem->move($uri, $uri, FileSystemInterface::EXISTS_RENAME);
    $this->assertFileExists($newUri);
  }

  /**
   * @covers ::saveData
   */
  public function testSaveDataWithDeprecatedFileExists(): void {
    $data = $this->randomMachineName(8);
    $uri = 'public://test.txt';
    touch($uri);
    $this->expectDeprecation('Passing the $fileExists argument as an integer to Drupal\Core\File\FileSystem::saveData() is deprecated in drupal:10.3.0 and is removed from drupal:12.0.0. Use \Drupal\Core\File\FileExists enum instead. See https://www.drupal.org/node/3426517');
    $newUri = $this->fileSystem->saveData($data, $uri, FileSystemInterface::EXISTS_RENAME);
    $this->assertFileExists($newUri);
  }

  /**
   * @covers ::getDestinationFilename
   */
  public function testGetDestinationFilenameWithDeprecatedFileExists(): void {
    $uri = 'public://test.txt';
    touch($uri);
    $newUri = $this->fileSystem->getDestinationFilename($uri, FileSystemInterface::EXISTS_RENAME);
    $this->assertStringStartsWith('public://test_', $newUri);
    $this->assertNotEquals($newUri, $uri);
  }

  /**
   * @covers ::copy
   */
  public function testCopyWithOutOfBoundsIntPositive(): void {
    $uri = 'public://test.txt';
    $destination = 'public://test2.txt';
    touch($uri);
    $this->expectDeprecation('Passing the $fileExists argument as an integer to Drupal\Core\File\FileSystem::copy() is deprecated in drupal:10.3.0 and is removed from drupal:12.0.0. Use \Drupal\Core\File\FileExists enum instead. See https://www.drupal.org/node/3426517');
    $this->fileSystem->copy($uri, $destination, \PHP_INT_MAX);
  }

  /**
   * @covers ::copy
   */
  public function testCopyWithOutOfBoundsIntNegative(): void {
    $uri = 'public://test.txt';
    $destination = 'public://test2.txt';
    touch($uri);
    $this->expectDeprecation('Passing the $fileExists argument as an integer to Drupal\Core\File\FileSystem::copy() is deprecated in drupal:10.3.0 and is removed from drupal:12.0.0. Use \Drupal\Core\File\FileExists enum instead. See https://www.drupal.org/node/3426517');
    $this->fileSystem->copy($uri, $destination, \PHP_INT_MIN);
  }

}
