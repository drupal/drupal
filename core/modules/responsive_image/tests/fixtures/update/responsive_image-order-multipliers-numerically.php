<?php

/**
 * @file
 * Test fixture for re-ordering responsive image style multipliers numerically.
 */

use Drupal\Core\Database\Database;

$connection = Database::getConnection();

// Add a responsive image style.
$styles = [];
$styles['langcode'] = 'en';
$styles['status'] = TRUE;
$styles['dependencies']['config'][] = 'image.style.large';
$styles['dependencies']['config'][] = 'image.style.medium';
$styles['dependencies']['config'][] = 'image.style.thumbnail';
$styles['id'] = 'responsive_image_style';
$styles['uuid'] = '46225242-eb4c-4b10-9a8c-966130b18630';
$styles['label'] = 'Responsive Image Style';
$styles['breakpoint_group'] = 'responsive_image';
$styles['fallback_image_style'] = 'medium';
$styles['image_style_mappings'] = [
  [
    'image_mapping_type' => 'sizes',
    'image_mapping' => [
      'sizes' => '75vw',
      'sizes_image_styles' => [
        'medium',
      ],
    ],
    'breakpoint_id' => 'responsive_image.viewport_sizing',
    'multiplier' => '1.5x',
  ],
  [
    'image_mapping_type' => 'sizes',
    'image_mapping' => [
      'sizes' => '100vw',
      'sizes_image_styles' => [
        'large',
      ],
    ],
    'breakpoint_id' => 'responsive_image.viewport_sizing',
    'multiplier' => '2x',
  ],
  [
    'image_mapping_type' => 'sizes',
    'image_mapping' => [
      'sizes' => '50vw',
      'sizes_image_styles' => [
        'thumbnail',
      ],
    ],
    'breakpoint_id' => 'responsive_image.viewport_sizing',
    'multiplier' => '1x',
  ],
];

$connection->insert('config')
  ->fields([
    'collection',
    'name',
    'data',
  ])
  ->values([
    'collection' => '',
    'name' => 'responsive_image.styles.responsive_image_style',
    'data' => serialize($styles),
  ])
  ->execute();
