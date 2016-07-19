<?php

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
    $field_names = [
      0 => strtolower($this->randomMachineName()),
      1 => strtolower($this->randomMachineName()),
      2 => strtolower($this->randomMachineName()),
    ];
    $min_resolution = [
      'width' => 50,
      'height' => 50
    ];
    $max_resolution = [
      'width' => 100,
      'height' => 100
    ];
    $no_height_min_resolution = [
      'width' => 50,
      'height' => NULL
    ];
    $no_height_max_resolution = [
      'width' => 100,
      'height' => NULL
    ];
    $no_width_min_resolution = [
      'width' => NULL,
      'height' => 50
    ];
    $no_width_max_resolution = [
      'width' => NULL,
      'height' => 100
    ];
    $field_settings = [
      0 => $this->getFieldSettings($min_resolution, $max_resolution),
      1 => $this->getFieldSettings($no_height_min_resolution, $no_height_max_resolution),
      2 => $this->getFieldSettings($no_width_min_resolution, $no_width_max_resolution),
    ];
    $this->createImageField($field_names[0], 'article', [], $field_settings[0]);
    $this->createImageField($field_names[1], 'article', [], $field_settings[1]);
    $this->createImageField($field_names[2], 'article', [], $field_settings[2]);

    // We want a test image that is too small, and a test image that is too
    // big, so cycle through test image files until we have what we need.
    $image_that_is_too_big = FALSE;
    $image_that_is_too_small = FALSE;
    $image_factory = $this->container->get('image.factory');
    foreach ($this->drupalGetTestFiles('image') as $image) {
      $image_file = $image_factory->get($image->uri);
      if ($image_file->getWidth() > $max_resolution['width']) {
        $image_that_is_too_big = $image;
      }
      if ($image_file->getWidth() < $min_resolution['width']) {
        $image_that_is_too_small = $image;
      }
      if ($image_that_is_too_small && $image_that_is_too_big) {
        break;
      }
    }
    $this->uploadNodeImage($image_that_is_too_small, $field_names[0], 'article');
    $this->assertRaw(t('The specified file %name could not be uploaded.', ['%name' => $image_that_is_too_small->filename]));
    $this->assertRaw(t('The image is too small; the minimum dimensions are %dimensions pixels.', ['%dimensions' => '50x50']));
    $this->uploadNodeImage($image_that_is_too_big, $field_names[0], 'article');
    $this->assertText(t('The image was resized to fit within the maximum allowed dimensions of 100x100 pixels.'));
    $this->uploadNodeImage($image_that_is_too_small, $field_names[1], 'article');
    $this->assertRaw(t('The specified file %name could not be uploaded.', ['%name' => $image_that_is_too_small->filename]));
    $this->uploadNodeImage($image_that_is_too_big, $field_names[1], 'article');
    $this->assertText(t('The image was resized to fit within the maximum allowed width of 100 pixels.'));
    $this->uploadNodeImage($image_that_is_too_small, $field_names[2], 'article');
    $this->assertRaw(t('The specified file %name could not be uploaded.', ['%name' => $image_that_is_too_small->filename]));
    $this->uploadNodeImage($image_that_is_too_big, $field_names[2], 'article');
    $this->assertText(t('The image was resized to fit within the maximum allowed height of 100 pixels.'));
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
    $elements = $this->xpath('//label[@for="edit-' . $field_name . '-0-alt" and @class="js-form-required form-required"]/following-sibling::input[@id="edit-' . $field_name . '-0-alt"]');

    $this->assertTrue(isset($elements[0]), 'Required marker is shown for the required alt text.');

    $elements = $this->xpath('//label[@for="edit-' . $field_name . '-0-title" and @class="js-form-required form-required"]/following-sibling::input[@id="edit-' . $field_name . '-0-title"]');

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

  /**
   * Returns field settings.
   *
   * @param int[] $min_resolution
   *   The minimum width and height resolution setting.
   * @param int[] $max_resolution
   *   The maximum width and height resolution setting.
   *
   * @return array
   */
  protected function getFieldSettings($min_resolution, $max_resolution) {
    return [
      'max_resolution' => $max_resolution['width'] . 'x' . $max_resolution['height'],
      'min_resolution' => $min_resolution['width'] . 'x' . $min_resolution['height'],
      'alt_field' => 0,
    ];
  }

}
