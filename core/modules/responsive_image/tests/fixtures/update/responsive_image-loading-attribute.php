<?php

/**
 * @file
 * Test lazy load update by modifying an image field form display.
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
      'sizes' => '100vw',
      'sizes_image_styles' => [
        'large',
        'medium',
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

// Update article view display to use responsive_image.
$article_form_display = $connection->select('config')
  ->fields('config', ['data'])
  ->condition('collection', '')
  ->condition('name', 'core.entity_view_display.node.article.default')
  ->execute()
  ->fetchField();
$article_form_display = unserialize($article_form_display);
$article_form_display['content']['field_image']['type'] = 'responsive_image';
$article_form_display['content']['field_image']['settings'] = [
  'responsive_image_style' => 'responsive_image_style',
  'image_link' => '',
];
$connection->update('config')
  ->fields(['data' => serialize($article_form_display)])
  ->condition('collection', '')
  ->condition('name', 'core.entity_view_display.node.article.default')
  ->execute();
