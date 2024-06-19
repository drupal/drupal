<?php

declare(strict_types=1);

namespace Drupal\Tests\image\Functional;

use Drupal\field\Entity\FieldConfig;

/**
 * Tests the image field widget.
 *
 * @group image
 */
class ImageFieldWidgetTest extends ImageFieldTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests file widget element.
   */
  public function testWidgetElement(): void {
    // Check for image widget in add/node/article page
    $field_name = $this->randomMachineName();
    $min_resolution = 50;
    $max_resolution = 100;
    $field_settings = [
      'max_resolution' => $max_resolution . 'x' . $max_resolution,
      'min_resolution' => $min_resolution . 'x' . $min_resolution,
      'alt_field' => 0,
    ];
    $this->createImageField($field_name, 'node', 'article', [], $field_settings, [], [], 'Image test on [site:name]');
    $this->drupalGet('node/add/article');
    // Verify that the image field widget is found on add/node page.
    $this->assertSession()->elementExists('xpath', '//div[contains(@class, "field--widget-image-image")]');
    // Verify that the image field widget limits accepted files.
    $this->assertSession()->elementExists('xpath', '//input[contains(@accept, "image/*")]');
    $this->assertSession()->pageTextNotContains('Image test on [site:name]');

    // Check for allowed image file extensions - default.
    $this->assertSession()->pageTextContains('Allowed types: png gif jpg jpeg webp.');

    // Try adding to the field config an unsupported extension, should not
    // appear in the allowed types.
    $field_config = FieldConfig::loadByName('node', 'article', $field_name);
    $field_config->setSetting('file_extensions', 'png gif jpg jpeg webp tiff')->save();
    $this->drupalGet('node/add/article');
    $this->assertSession()->pageTextContains('Allowed types: png gif jpg jpeg webp.');

    // Add a supported extension and remove some supported ones, we should see
    // the intersect of those entered in field config with those supported.
    $field_config->setSetting('file_extensions', 'png jpe tiff')->save();
    $this->drupalGet('node/add/article');
    $this->assertSession()->pageTextContains('Allowed types: png jpe.');
  }

}
