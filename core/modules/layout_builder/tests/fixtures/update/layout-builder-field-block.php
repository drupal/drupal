<?php

/**
 * @file
 * Test fixture.
 */

use Drupal\Core\Database\Database;
use Drupal\layout_builder\Section;

// Create a section with a context-aware block with a layout_builder.entity
// context mapping. It is needed in array form for the defaults and string form
// for the overrides.
$section_array = [
  'layout_id' => 'layout_onecol',
  'layout_settings' => [],
  'components' => [
    'some-uuid' => [
      'uuid' => 'some-uuid',
      'region' => 'content',
      'configuration' => [
        'id' => 'field_block:node:article:body',
        'label' => 'Body',
        'provider' => 'layout_builder',
        'label_display' => 'visible',
        'formatter' => [
          'label' => 'above',
          'type' => 'text_default',
          'settings' => [],
          'third_party_settings' => [],
        ],
        'context_mapping' => [
          'entity' => 'layout_builder.entity',
        ],
      ],
      'additional' => [],
      'weight' => 0,
    ],
  ],
];
$section_string = serialize(Section::fromArray($section_array));

$connection = Database::getConnection();

// Add Layout Builder sections to an existing entity view display.
$display = $connection->select('config')
  ->fields('config', ['data'])
  ->condition('collection', '')
  ->condition('name', 'core.entity_view_display.node.article.default')
  ->execute()
  ->fetchField();
$display = unserialize($display);
$display['third_party_settings']['layout_builder']['sections'][] = $section_array;
$connection->update('config')
  ->fields([
    'data' => serialize($display),
    'collection' => '',
    'name' => 'core.entity_view_display.node.article.default',
  ])
  ->condition('collection', '')
  ->condition('name', 'core.entity_view_display.node.article.default')
  ->execute();

// Add the layout data to the node.
$connection->insert('node__layout_builder__layout')
  ->fields([
    'bundle',
    'deleted',
    'entity_id',
    'revision_id',
    'langcode',
    'delta',
    'layout_builder__layout_section',
  ])
  ->values([
    'bundle' => 'article',
    'deleted' => '0',
    'entity_id' => '1',
    'revision_id' => '1',
    'langcode' => 'en',
    'delta' => '0',
    'layout_builder__layout_section' => $section_string,
  ])
  ->execute();
$connection->insert('node_revision__layout_builder__layout')
  ->fields([
    'bundle',
    'deleted',
    'entity_id',
    'revision_id',
    'langcode',
    'delta',
    'layout_builder__layout_section',
  ])
  ->values([
    'bundle' => 'article',
    'deleted' => '0',
    'entity_id' => '1',
    'revision_id' => '1',
    'langcode' => 'en',
    'delta' => '0',
    'layout_builder__layout_section' => $section_string,
  ])
  ->execute();
