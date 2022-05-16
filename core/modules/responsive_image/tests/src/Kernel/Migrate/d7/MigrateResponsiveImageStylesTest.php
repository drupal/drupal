<?php

namespace Drupal\Tests\responsive_image\Kernel\Migrate\d7;

use Drupal\responsive_image\Entity\ResponsiveImageStyle;
use Drupal\Tests\migrate_drupal\Kernel\d7\MigrateDrupal7TestBase;

/**
 * Tests migration of responsive image styles.
 *
 * @group responsive_image
 */
class MigrateResponsiveImageStylesTest extends MigrateDrupal7TestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['responsive_image', 'breakpoint', 'image'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    // Ensure the 'picture' module is enabled in the source.
    $this->sourceDatabase->update('system')
      ->condition('name', 'picture')
      ->fields(['status' => 1])
      ->execute();
    $this->executeMigrations(['d7_image_styles', 'd7_responsive_image_styles']);
  }

  /**
   * Tests the Drupal 7 to Drupal 8 responsive image styles migration.
   */
  public function testResponsiveImageStyles() {
    $expected_image_style_mappings = [
      [
        'image_mapping_type' => 'image_style',
        'image_mapping' => 'custom_image_style_1',
        'breakpoint_id' => 'responsive_image.computer',
        'multiplier' => 'multiplier_1',
      ],
      [
        'image_mapping_type' => 'sizes',
        'image_mapping' => [
          'sizes' => '2',
          'sizes_image_styles' => [
            'custom_image_style_1',
            'custom_image_style_2',
          ],
        ],
        'breakpoint_id' => 'responsive_image.computer',
        'multiplier' => 'multiplier_2',
      ],
      [
        'image_mapping_type' => 'sizes',
        'image_mapping' => [
          'sizes' => '2',
          'sizes_image_styles' => [
            'custom_image_style_1',
            'custom_image_style_2',
          ],
        ],
        'breakpoint_id' => 'responsive_image.computertwo',
        'multiplier' => 'multiplier_2',
      ],
    ];
    $this->assertSame($expected_image_style_mappings, ResponsiveImageStyle::load('narrow')
      ->getImageStyleMappings());
  }

}
