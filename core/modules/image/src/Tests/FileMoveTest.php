<?php

/**
 * @file
 * Contains \Drupal\image\Tests\FileMoveTest.
 */

namespace Drupal\image\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests the file move function for images and image styles.
 */
class FileMoveTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('image');

  public static function getInfo() {
    return array(
      'name' => 'Image moving',
      'description' => 'Tests the file move function for managed files.',
      'group' => 'Image',
    );
  }

  /**
   * Tests moving a randomly generated image.
   */
  function testNormal() {
    // Pick a file for testing.
    $file = entity_create('file', (array) current($this->drupalGetTestFiles('image')));

    // Create derivative image.
    $styles = entity_load_multiple('image_style');
    $style = reset($styles);
    $original_uri = $file->getFileUri();
    $derivative_uri = $style->buildUri($original_uri);
    $style->createDerivative($original_uri, $derivative_uri);

    // Check if derivative image exists.
    $this->assertTrue(file_exists($derivative_uri), 'Make sure derivative image is generated successfully.');

    // Clone the object so we don't have to worry about the function changing
    // our reference copy.
    $desired_filepath = 'public://' . $this->randomName();
    $result = file_move(clone $file, $desired_filepath, FILE_EXISTS_ERROR);

    // Check if image has been moved.
    $this->assertTrue(file_exists($result->getFileUri()), 'Make sure image is moved successfully.');

    // Check if derivative image has been flushed.
    $this->assertFalse(file_exists($derivative_uri), 'Make sure derivative image has been flushed.');
  }
}
