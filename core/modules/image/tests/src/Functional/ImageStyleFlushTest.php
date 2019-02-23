<?php

namespace Drupal\Tests\image\Functional;

use Drupal\image\Entity\ImageStyle;
use Drupal\Tests\TestFileCreationTrait;

/**
 * Tests flushing of image styles.
 *
 * @group image
 */
class ImageStyleFlushTest extends ImageFieldTestBase {

  use TestFileCreationTrait {
    getTestFiles as drupalGetTestFiles;
    compareFiles as drupalCompareFiles;
  }

  /**
   * Given an image style and a wrapper, generate an image.
   */
  public function createSampleImage($style, $wrapper) {
    static $file;

    if (!isset($file)) {
      $files = $this->drupalGetTestFiles('image');
      $file = reset($files);
    }

    // Make sure we have an image in our wrapper testing file directory.
    $source_uri = file_unmanaged_copy($file->uri, $wrapper . '://');
    // Build the derivative image.
    $derivative_uri = $style->buildUri($source_uri);
    $derivative = $style->createDerivative($source_uri, $derivative_uri);

    return $derivative ? $derivative_uri : FALSE;
  }

  /**
   * Count the number of images currently created for a style in a wrapper.
   */
  public function getImageCount($style, $wrapper) {
    return count(file_scan_directory($wrapper . '://styles/' . $style->id(), '/.*/'));
  }

  /**
   * General test to flush a style.
   */
  public function testFlush() {

    // Setup a style to be created and effects to add to it.
    $style_name = strtolower($this->randomMachineName(10));
    $style_label = $this->randomString();
    $style_path = 'admin/config/media/image-styles/manage/' . $style_name;
    $effect_edits = [
      'image_resize' => [
        'data[width]' => 100,
        'data[height]' => 101,
      ],
      'image_scale' => [
        'data[width]' => 110,
        'data[height]' => 111,
        'data[upscale]' => 1,
      ],
    ];

    // Add style form.
    $edit = [
      'name' => $style_name,
      'label' => $style_label,
    ];
    $this->drupalPostForm('admin/config/media/image-styles/add', $edit, t('Create new style'));

    // Add each sample effect to the style.
    foreach ($effect_edits as $effect => $edit) {
      // Add the effect.
      $this->drupalPostForm($style_path, ['new' => $effect], t('Add'));
      if (!empty($edit)) {
        $this->drupalPostForm(NULL, $edit, t('Add effect'));
      }
    }

    // Load the saved image style.
    $style = ImageStyle::load($style_name);

    // Create an image for the 'public' wrapper.
    $image_path = $this->createSampleImage($style, 'public');
    // Expecting to find 2 images, one is the sample.png image shown in
    // image style preview.
    $this->assertEqual($this->getImageCount($style, 'public'), 2, format_string('Image style %style image %file successfully generated.', ['%style' => $style->label(), '%file' => $image_path]));

    // Create an image for the 'private' wrapper.
    $image_path = $this->createSampleImage($style, 'private');
    $this->assertEqual($this->getImageCount($style, 'private'), 1, format_string('Image style %style image %file successfully generated.', ['%style' => $style->label(), '%file' => $image_path]));

    // Remove the 'image_scale' effect and updates the style, which in turn
    // forces an image style flush.
    $style_path = 'admin/config/media/image-styles/manage/' . $style->id();
    $uuids = [];
    foreach ($style->getEffects() as $uuid => $effect) {
      $uuids[$effect->getPluginId()] = $uuid;
    }
    $this->drupalPostForm($style_path . '/effects/' . $uuids['image_scale'] . '/delete', [], t('Delete'));
    $this->assertResponse(200);
    $this->drupalPostForm($style_path, [], t('Save'));
    $this->assertResponse(200);

    // Post flush, expected 1 image in the 'public' wrapper (sample.png).
    $this->assertEqual($this->getImageCount($style, 'public'), 1, format_string('Image style %style flushed correctly for %wrapper wrapper.', ['%style' => $style->label(), '%wrapper' => 'public']));

    // Post flush, expected no image in the 'private' wrapper.
    $this->assertEqual($this->getImageCount($style, 'private'), 0, format_string('Image style %style flushed correctly for %wrapper wrapper.', ['%style' => $style->label(), '%wrapper' => 'private']));
  }

}
