<?php

/**
 * @file
 * Definition of Drupal\image\Tests\ImageFieldValidateTest.
 */

namespace Drupal\image\Tests;

/**
 * Tests validation functions such as min/max resolution.
 *
 * @group image
 */
class ImageFieldValidateTest extends ImageFieldTestBase {
  /**
   * Test min/max resolution settings.
   */
  function testResolution() {
    $field_name = strtolower($this->randomMachineName());
    $min_resolution = 50;
    $max_resolution = 100;
    $field_settings = array(
      'max_resolution' => $max_resolution . 'x' . $max_resolution,
      'min_resolution' => $min_resolution . 'x' . $min_resolution,
      'alt_field' => 0,
    );
    $this->createImageField($field_name, 'article', array(), $field_settings);

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
    $this->assertRaw(t('The specified file %name could not be uploaded.', array('%name' => $image_that_is_too_small->filename)) . ' ' . t('The image is too small; the minimum dimensions are %dimensions pixels.', array('%dimensions' => '50x50')), 'Node save failed when minimum image resolution was not met.');
    $this->uploadNodeImage($image_that_is_too_big, $field_name, 'article');
    $this->assertText(t('The image was resized to fit within the maximum allowed dimensions of 100x100 pixels.'), 'Image exceeding max resolution was properly resized.');
  }

  /**
   * Test that required alt/title fields gets validated right.
   */
  function testRequiredAttributes() {
    $field_name = strtolower($this->randomMachineName());
    $field_settings = array(
      'alt_field' => 1,
      'alt_field_required' => 1,
      'title_field' => 1,
      'title_field_required' => 1,
      'required' => 1,
    );
    $instance = $this->createImageField($field_name, 'article', array(), $field_settings);
    $images = $this->drupalGetTestFiles('image');
    // Let's just use the first image.
    $image = $images[0];
    $this->uploadNodeImage($image, $field_name, 'article');

    // Look for form-required for the alt text.
    $elements = $this->xpath('//label[@for="edit-' . $field_name . '-0-alt" and @class="form-required"]/following-sibling::input[@id="edit-' . $field_name . '-0-alt"]');

    $this->assertTrue(isset($elements[0]),'Required marker is shown for the required alt text.');

    $elements = $this->xpath('//label[@for="edit-' . $field_name . '-0-title" and @class="form-required"]/following-sibling::input[@id="edit-' . $field_name . '-0-title"]');

    $this->assertTrue(isset($elements[0]), 'Required marker is shown for the required title text.');

    $this->assertText(t('Alternative text field is required.'));
    $this->assertText(t('Title field is required.'));

    $instance->setSetting('alt_field_required', 0);
    $instance->setSetting('title_field_required', 0);
    $instance->save();

    $edit = array(
      'title[0][value]' => $this->randomMachineName(),
    );
    $this->drupalPostForm('node/add/article', $edit, t('Save and publish'));

    $this->assertNoText(t('Alternative text field is required.'));
    $this->assertNoText(t('Title field is required.'));

    $instance->setSetting('required', 0);
    $instance->setSetting('alt_field_required', 1);
    $instance->setSetting('title_field_required', 1);
    $instance->save();

    $edit = array(
      'title[0][value]' => $this->randomMachineName(),
    );
    $this->drupalPostForm('node/add/article', $edit, t('Save and publish'));

    $this->assertNoText(t('Alternative text field is required.'));
    $this->assertNoText(t('Title field is required.'));
  }
}
