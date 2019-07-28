<?php

/**
 * @file
 * Test fixture.
 */

use Drupal\Core\Database\Database;
use Drupal\layout_builder\Section;

$section_array_default = [
  'layout_id' => 'layout_onecol',
  'layout_settings' => [],
  'components' => [
    'some-uuid' => [
      'uuid' => 'some-uuid',
      'region' => 'content',
      'configuration' => [
        'id' => 'system_powered_by_block',
        'label' => 'This is in English',
        'provider' => 'system',
        'label_display' => 'visible',
      ],
      'additional' => [],
      'weight' => 0,
    ],
  ],
];
$section_array_translation = $section_array_default;
$section_array_translation['components']['some-uuid']['configuration']['label'] = 'This is in Spanish';

$connection = Database::getConnection();
$connection->insert('config')
  ->fields([
    'collection',
    'name',
    'data',
  ])
  ->values([
    'collection' => '',
    'name' => 'language.content_settings.node.article',
    'data' => 'a:10:{s:4:"uuid";s:36:"450e592a-f451-4685-8f56-02b0f5107cb7";s:8:"langcode";s:2:"en";s:6:"status";b:1;s:12:"dependencies";a:2:{s:6:"config";a:1:{i:0;s:17:"node.type.article";}s:6:"module";a:1:{i:0;s:19:"content_translation";}}s:20:"third_party_settings";a:1:{s:19:"content_translation";a:2:{s:7:"enabled";b:1;s:15:"bundle_settings";a:1:{s:26:"untranslatable_fields_hide";s:1:"0";}}}s:2:"id";s:12:"node.article";s:21:"target_entity_type_id";s:4:"node";s:13:"target_bundle";s:7:"article";s:16:"default_langcode";s:12:"site_default";s:18:"language_alterable";b:1;}',
  ])
  ->values([
    'collection' => '',
    'name' => 'language.content_settings.node.page',
    'data' => 'a:10:{s:4:"uuid";s:36:"2b8b721e-59e9-4b57-a026-c4444fd28196";s:8:"langcode";s:2:"en";s:6:"status";b:1;s:12:"dependencies";a:2:{s:6:"config";a:1:{i:0;s:14:"node.type.page";}s:6:"module";a:1:{i:0;s:19:"content_translation";}}s:20:"third_party_settings";a:1:{s:19:"content_translation";a:2:{s:7:"enabled";b:1;s:15:"bundle_settings";a:1:{s:26:"untranslatable_fields_hide";s:1:"0";}}}s:2:"id";s:9:"node.page";s:21:"target_entity_type_id";s:4:"node";s:13:"target_bundle";s:4:"page";s:16:"default_langcode";s:12:"site_default";s:18:"language_alterable";b:1;}',
  ])
  ->values([
    'collection' => '',
    'name' => 'language.content_settings.taxonomy_term.forums',
    'data' => 'a:10:{s:4:"uuid";s:36:"16b8ed8f-cac8-40fd-a441-b7b91bb0012d";s:8:"langcode";s:2:"en";s:6:"status";b:1;s:12:"dependencies";a:2:{s:6:"config";a:1:{i:0;s:26:"taxonomy.vocabulary.forums";}s:6:"module";a:1:{i:0;s:19:"content_translation";}}s:20:"third_party_settings";a:1:{s:19:"content_translation";a:2:{s:7:"enabled";b:1;s:15:"bundle_settings";a:1:{s:26:"untranslatable_fields_hide";s:1:"0";}}}s:2:"id";s:20:"taxonomy_term.forums";s:21:"target_entity_type_id";s:13:"taxonomy_term";s:13:"target_bundle";s:6:"forums";s:16:"default_langcode";s:12:"site_default";s:18:"language_alterable";b:1;}',
  ])
  ->execute();

// Add Layout Builder sections to an existing entity view display.
$display = $connection->select('config')
  ->fields('config', ['data'])
  ->condition('collection', '')
  ->condition('name', 'core.entity_view_display.node.article.default')
  ->execute()
  ->fetchField();
$display = unserialize($display);
$display['third_party_settings']['layout_builder']['sections'][] = $section_array_default;
$connection->update('config')
  ->fields([
    'data' => serialize($display),
    'collection' => '',
    'name' => 'core.entity_view_display.node.article.default',
  ])
  ->condition('collection', '')
  ->condition('name', 'core.entity_view_display.node.article.default')
  ->execute();

$display = $connection->select('config')
  ->fields('config', ['data'])
  ->condition('collection', '')
  ->condition('name', 'core.entity_view_display.node.page.default')
  ->execute()
  ->fetchField();
$display = unserialize($display);
$display['third_party_settings']['layout_builder']['sections'][] = $section_array_default;
$connection->update('config')
  ->fields([
    'data' => serialize($display),
    'collection' => '',
    'name' => 'core.entity_view_display.node.page.default',
  ])
  ->condition('collection', '')
  ->condition('name', 'core.entity_view_display.node.page.default')
  ->execute();

$display = $connection->select('config')
  ->fields('config', ['data'])
  ->condition('collection', '')
  ->condition('name', 'core.entity_view_display.taxonomy_term.forums.default')
  ->execute()
  ->fetchField();
$display = unserialize($display);
$display['third_party_settings']['layout_builder']['sections'][] = $section_array_default;
$connection->update('config')
  ->fields([
    'data' => serialize($display),
    'collection' => '',
    'name' => 'core.entity_view_display.taxonomy_term.forums.default',
  ])
  ->condition('collection', '')
  ->condition('name', 'core.entity_view_display.taxonomy_term.forums.default')
  ->execute();

// Loop over test cases defined in the test class.
// @see \Drupal\Tests\layout_builder\Functional\Update\Translatability\MakeLayoutUntranslatableUpdatePathTestBase
foreach ($this->layoutBuilderTestCases as $bundle => $test_case) {
  if ($test_case['has_layout']) {
    $values_en = [
      'bundle' => $bundle,
      'deleted' => '0',
      'entity_id' => $test_case['nid'],
      'revision_id' => $test_case['vid'],
      'langcode' => 'en',
      'delta' => '0',
      'layout_builder__layout_section' => serialize(Section::fromArray($section_array_default)),
    ];

    // Add the layout data to the node.
    $connection->insert('node__layout_builder__layout')
      ->fields(array_keys($values_en))
      ->values($values_en)
      ->execute();
    $connection->insert('node_revision__layout_builder__layout')
      ->fields(array_keys($values_en))
      ->values($values_en)
      ->execute();
  }

  if ($test_case['has_translation']) {
    $node_field_data = $connection->select('node_field_data')
      ->fields('node_field_data')
      ->condition('nid', $test_case['nid'])
      ->condition('vid', $test_case['vid'])
      ->execute()
      ->fetchAssoc();

    $node_field_data['title'] = "Test: $bundle";
    $node_field_data['langcode'] = 'es';
    $node_field_data['default_langcode'] = 0;
    $node_field_data['revision_translation_affected'] = 1;
    $node_field_data['content_translation_source'] = 'en';
    $connection->insert('node_field_data')
      ->fields(array_keys($node_field_data))
      ->values($node_field_data)
      ->execute();

    $node_field_revision = $connection->select('node_field_revision')
      ->fields('node_field_revision')
      ->condition('nid', $test_case['nid'])
      ->condition('vid', $test_case['vid'])
      ->execute()
      ->fetchAssoc();
    $node_field_revision['title'] = "Test: $bundle";
    $node_field_revision['langcode'] = 'es';
    $node_field_revision['default_langcode'] = 0;
    $node_field_revision['revision_translation_affected'] = 1;
    $node_field_revision['content_translation_source'] = 'en';
    $connection->insert('node_field_revision')
      ->fields(array_keys($node_field_revision))
      ->values($node_field_revision)
      ->execute();
  }
}
