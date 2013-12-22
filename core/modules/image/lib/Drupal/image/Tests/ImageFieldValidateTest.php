<?php

/**
 * @file
 * Definition of Drupal\image\Tests\ImageFieldValidateTest.
 */

namespace Drupal\image\Tests;

/**
 * Test class to check for various validations.
 */
class ImageFieldValidateTest extends ImageFieldTestBase {
  public static function getInfo() {
    return array(
      'name' => 'Image field validation tests',
      'description' => 'Tests validation functions such as min/max resolution.',
      'group' => 'Image',
    );
  }

  /**
   * Test min/max resolution settings.
   */
  function testResolution() {
    $field_name = strtolower($this->randomName());
    $min_resolution = 50;
    $max_resolution = 100;
    $instance_settings = array(
      'max_resolution' => $max_resolution . 'x' . $max_resolution,
      'min_resolution' => $min_resolution . 'x' . $min_resolution,
    );
    $this->createImageField($field_name, 'article', array(), $instance_settings);

    // We want a test image that is too small, and a test image that is too
    // big, so cycle through test image files until we have what we need.
    $image_that_is_too_big = FALSE;
    $image_that_is_too_small = FALSE;
    $image_factory = $this->container->get('image.factory');
    foreach ($this->drupalGetTestFiles('image') as $image) {
      $image_file = $image_factory->get($image->uri);
      if ($image_file->getWidth() > $max_resolution) {
        $image_that_is_too_big = $image;
      }
      if ($image_file->getWidth() < $min_resolution) {
        $image_that_is_too_small = $image;
      }
      if ($image_that_is_too_small && $image_that_is_too_big) {
        break;
      }
    }
    $this->uploadNodeImage($image_that_is_too_small, $field_name, 'article');
    $this->assertText(t('The specified file ' . $image_that_is_too_small->filename . ' could not be uploaded. The image is too small; the minimum dimensions are 50x50 pixels.'), 'Node save failed when minimum image resolution was not met.');
    $this->uploadNodeImage($image_that_is_too_big, $field_name, 'article');
    $this->assertText(t('The image was resized to fit within the maximum allowed dimensions of 100x100 pixels.'), 'Image exceeding max resolution was properly resized.');
  }

  /**
   * Test that required alt/title fields gets validated right.
   */
  function testRequiredAttributes() {
    $field_name = strtolower($this->randomName());
    $instance_settings = array(
      'alt_field' => 1,
      'alt_field_required' => 1,
      'title_field' => 1,
      'title_field_required' => 1,
    );
    $this->createImageField($field_name, 'article', array(), $instance_settings);
    $images = $this->drupalGetTestFiles('image');
    // Let's just use the first image.
    $image = $images[0];
    $this->uploadNodeImage($image, $field_name, 'article');
    $this->assertText(t('The field Alternate text is required'), 'Node save failed when alt text required was set and alt text was left empty.');
    $this->assertText(t('The field Title is required'), 'Node save failed when title text required was set and title text was left empty.');
  }
}
