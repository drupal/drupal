<?php

declare(strict_types=1);

namespace Drupal\Tests\image\Kernel;

use Drupal\Core\File\FileExists;
use Drupal\file\Entity\File;
use Drupal\file\FileRepository;
use Drupal\image\Entity\ImageStyle;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\TestFileCreationTrait;

/**
 * Tests the file move function for images and image styles.
 *
 * @group image
 */
class FileMoveTest extends KernelTestBase {

  use TestFileCreationTrait {
    getTestFiles as drupalGetTestFiles;
    compareFiles as drupalCompareFiles;
  }

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'file',
    'image',
    'system',
    'user',
  ];

  /**
   * The file repository service.
   */
  protected FileRepository $fileRepository;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installConfig(['system']);
    $this->installEntitySchema('file');
    $this->installEntitySchema('user');
    $this->installSchema('file', ['file_usage']);

    ImageStyle::create([
      'name' => 'main_style',
      'label' => 'Main',
    ])->save();

    $this->fileRepository = $this->container->get('file.repository');
  }

  /**
   * Tests moving a randomly generated image.
   */
  public function testNormal(): void {
    // Pick a file for testing.
    $file = File::create((array) current($this->drupalGetTestFiles('image')));

    // Create derivative image.
    $styles = ImageStyle::loadMultiple();
    $style = reset($styles);
    $original_uri = $file->getFileUri();
    $derivative_uri = $style->buildUri($original_uri);
    $style->createDerivative($original_uri, $derivative_uri);

    // Check if derivative image exists.
    $this->assertFileExists($derivative_uri);

    // Clone the object, so we don't have to worry about the function changing
    // our reference copy.
    $desired_filepath = 'public://' . $this->randomMachineName();
    $result = $this->fileRepository->move(clone $file, $desired_filepath, FileExists::Error);

    // Check if image has been moved.
    $this->assertFileExists($result->getFileUri());

    // Check if derivative image has been flushed.
    $this->assertFileDoesNotExist($derivative_uri);
  }

}
