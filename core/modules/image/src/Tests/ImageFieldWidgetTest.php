<?php

namespace Drupal\image\Tests;

/**
 * Tests the image field widget.
 *
 * @group image
 */
class ImageFieldWidgetTest extends ImageFieldTestBase {

  /**
   * Tests file widget element.
   */
  public function testWidgetElement() {
    // Check for image widget in add/node/article page
    $field_name = strtolower($this->randomMachineName());
    $min_resolution = 50;
    $max_resolution = 100;
    $field_settings = array(
      'max_resolution' => $max_resolution . 'x' . $max_resolution,
      'min_resolution' => $min_resolution . 'x' . $min_resolution,
      'alt_field' => 0,
    );
    $this->createImageField($field_name, 'article', array(), $field_settings, array(), array(), 'Image test on [site:name]');
    $this->drupalGet('node/add/article');
    $this->assertNotEqual(0, count($this->xpath('//div[contains(@class, "field--widget-image-image")]')), 'Image field widget found on add/node page', 'Browser');
    $this->assertNoText('Image test on [site:name]');
  }

}
