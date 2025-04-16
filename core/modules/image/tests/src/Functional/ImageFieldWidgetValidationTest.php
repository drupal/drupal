<?php

declare(strict_types=1);

namespace Drupal\Tests\image\Functional;

/**
 * Tests the image field widget validation.
 *
 * @group image
 */
class ImageFieldWidgetValidationTest extends ImageFieldTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'image_field_property_constraint_validation',
  ];

  /**
   * Tests file widget element.
   */
  public function testWidgetElementValidation(): void {
    $page = $this->getSession()->getPage();

    // Check for image widget in add/node/article page
    $field_name = 'field_image';
    $field_settings = [
      'description' => 'Image test description',
      'alt_field' => 1,
      'alt_field_required' => 0,
      'title_field' => 1,
      'title_field_required' => 0,
    ];
    $this->createImageField($field_name, 'node', 'article', [], $field_settings, [], [], 'Image');
    $this->drupalGet('node/add/article');

    // Verify that the image field widget is found on add/node page.
    $this->assertSession()->elementExists('xpath', '//div[contains(@class, "field--widget-image-image")]');

    // Attach an image.
    $image_media_name = 'example_1.jpeg';
    $page->attachFileToField('files[field_image_0]', $this->root . '/core/modules/image/tests/fixtures/' . $image_media_name);
    $page->pressButton('Save');

    // Alt is marked as errored.
    $altElement = $this->assertSession()->elementExists('css', 'input[data-drupal-selector="edit-field-image-0-alt"]');
    $this->assertTrue(str_contains($altElement->getAttribute('class'), 'error'));

    // Title is not marked as errored
    $titleElement = $this->assertSession()->elementExists('css', 'input[data-drupal-selector="edit-field-image-0-title"]');
    $this->assertFalse(str_contains($titleElement->getAttribute('class'), 'error'));
  }

}
