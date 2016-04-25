<?php

namespace Drupal\image\Tests;

use Drupal\file\Entity\File;
use Drupal\simpletest\WebTestBase;
use Drupal\image\Entity\ImageStyle;

/**
 * Tests the file move function for images and image styles.
 *
 * @group image
 */
class FileMoveTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('image');

  /**
   * Tests moving a randomly generated image.
   */
  function testNormal() {
    // Pick a file for testing.
    $file = File::create((array) current($this->drupalGetTestFiles('image')));

    // Create derivative image.
    $styles = ImageStyle::loadMultiple();
    $style = reset($styles);
    $original_uri = $file->getFileUri();
    $derivative_uri = $style->buildUri($original_uri);
    $style->createDerivative($original_uri, $derivative_uri);

    // Check if derivative image exists.
    $this->assertTrue(file_exists($derivative_uri), 'Make sure derivative image is generated successfully.');

    // Clone the object so we don't have to worry about the function changing
    // our reference copy.
    $desired_filepath = 'public://' . $this->randomMachineName();
    $result = file_move(clone $file, $desired_filepath, FILE_EXISTS_ERROR);

    // Check if image has been moved.
    $this->assertTrue(file_exists($result->getFileUri()), 'Make sure image is moved successfully.');

    // Check if derivative image has been flushed.
    $this->assertFalse(file_exists($derivative_uri), 'Make sure derivative image has been flushed.');
  }
}
