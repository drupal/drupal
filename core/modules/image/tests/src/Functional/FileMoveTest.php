<?php

namespace Drupal\Tests\image\Functional;

use Drupal\Core\File\FileSystemInterface;
use Drupal\file\Entity\File;
use Drupal\image\Entity\ImageStyle;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\TestFileCreationTrait;

/**
 * Tests the file move function for images and image styles.
 *
 * @group image
 */
class FileMoveTest extends BrowserTestBase {

  use TestFileCreationTrait {
    getTestFiles as drupalGetTestFiles;
    compareFiles as drupalCompareFiles;
  }

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['image'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests moving a randomly generated image.
   */
  public function testNormal() {
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

    // Clone the object so we don't have to worry about the function changing
    // our reference copy.
    $desired_filepath = 'public://' . $this->randomMachineName();
    $result = file_move(clone $file, $desired_filepath, FileSystemInterface::EXISTS_ERROR);

    // Check if image has been moved.
    $this->assertFileExists($result->getFileUri());

    // Check if derivative image has been flushed.
    $this->assertFileDoesNotExist($derivative_uri);
  }

}
