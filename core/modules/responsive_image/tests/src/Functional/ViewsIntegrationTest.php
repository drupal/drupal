<?php

declare(strict_types=1);

namespace Drupal\Tests\responsive_image\Functional;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\responsive_image\Entity\ResponsiveImageStyle;
use Drupal\Tests\views\Functional\ViewTestBase;

/**
 * Tests the integration of responsive image with Views.
 *
 * @group responsive_image
 */
class ViewsIntegrationTest extends ViewTestBase {

  /**
   * The responsive image style ID to use.
   */
  const RESPONSIVE_IMAGE_STYLE_ID = 'responsive_image_style_id';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'views',
    'views_ui',
    'responsive_image',
    'field',
    'image',
    'file',
    'entity_test',
    'breakpoint',
    'responsive_image_test_module',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * The test views to enable.
   */
  public static $testViews = ['entity_test_row'];

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE, $modules = ['views_test_config']): void {
    parent::setUp($import_test_views, $modules);

    $this->enableViewsTestModule();

    // Create a responsive image style.
    $responsive_image_style = ResponsiveImageStyle::create([
      'id' => self::RESPONSIVE_IMAGE_STYLE_ID,
      'label' => 'Foo',
      'breakpoint_group' => 'responsive_image_test_module',
    ]);
    // Create an image field to be used with a responsive image formatter.
    FieldStorageConfig::create([
      'type' => 'image',
      'entity_type' => 'entity_test',
      'field_name' => 'bar',
    ])->save();
    FieldConfig::create([
      'entity_type' => 'entity_test',
      'bundle' => 'entity_test',
      'field_name' => 'bar',
    ])->save();

    $responsive_image_style
      ->addImageStyleMapping('responsive_image_test_module.mobile', '1x', [
        'image_mapping_type' => 'image_style',
        'image_mapping' => 'thumbnail',
      ])
      ->addImageStyleMapping('responsive_image_test_module.narrow', '1x', [
        'image_mapping_type' => 'image_style',
        'image_mapping' => 'medium',
      ])
      // Test the normal output of mapping to an image style.
      ->addImageStyleMapping('responsive_image_test_module.wide', '1x', [
        'image_mapping_type' => 'image_style',
        'image_mapping' => 'large',
      ])
      ->save();

    $admin_user = $this->drupalCreateUser(['administer views']);
    $this->drupalLogin($admin_user);
  }

  /**
   * Tests integration with Views.
   */
  public function testViewsAddResponsiveImageField(): void {
    // Add the image field to the View.
    $this->drupalGet('admin/structure/views/nojs/add-handler/entity_test_row/default/field');
    $this->drupalGet('admin/structure/views/nojs/add-handler/entity_test_row/default/field');
    $this->submitForm(['name[entity_test__bar.bar]' => TRUE], 'Add and configure field');
    // Set the formatter to 'Responsive image'.
    $this->submitForm(['options[type]' => 'responsive_image'], 'Apply');
    $this->assertSession()
      ->responseContains('Responsive image style field is required.');
    $this->submitForm(['options[settings][responsive_image_style]' => self::RESPONSIVE_IMAGE_STYLE_ID], 'Apply');
    $this->drupalGet('admin/structure/views/nojs/handler/entity_test_row/default/field/bar');
    // Make sure the selected value is set.
    $this->assertSession()
      ->fieldValueEquals('options[settings][responsive_image_style]', self::RESPONSIVE_IMAGE_STYLE_ID);
  }

}
