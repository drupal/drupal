<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Image\FileMoveTest.
 */

namespace Drupal\system\Tests\Image;

/**
 * Tests the file move function for managed files.
 *
 * @todo This test belongs to File module.
 */
class FileMoveTest extends ToolkitTestBase {
  protected $profile = 'standard';

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
    $style = image_style_load(key(image_styles()));
    $derivative_uri = image_style_path($style['name'], $file->uri);
    image_style_create_derivative($style, $file->uri, $derivative_uri);

    // Check if derivative image exists.
    $this->assertTrue(file_exists($derivative_uri), 'Make sure derivative image is generated successfully.');

    // Clone the object so we don't have to worry about the function changing
    // our reference copy.
    $desired_filepath = 'public://' . $this->randomName();
    $result = file_move(clone $file, $desired_filepath, FILE_EXISTS_ERROR);

    // Check if image has been moved.
    $this->assertTrue(file_exists($result->uri), 'Make sure image is moved successfully.');

    // Check if derivative image has been flushed.
    $this->assertFalse(file_exists($derivative_uri), 'Make sure derivative image has been flushed.');
  }
}
