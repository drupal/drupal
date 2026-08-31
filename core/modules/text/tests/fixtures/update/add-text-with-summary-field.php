<?php

/**
 * @file
 * Adds a body field of type text_with_summary to the fixture database.
 *
 * Restores the pre-deprecation shape that
 * \Drupal\Tests\text\Functional\Update\TextWithSummaryUpdatePathTest needs to
 * exercise: a field storage and instance using the text_with_summary field
 * type provided by the text module, plus matching form/view display entries.
 *
 * The base fixture no longer ships text_with_summary configuration, so
 * without this script the update path has nothing to migrate.
 *
 * @see https://www.drupal.org/project/drupal/issues/3549134
 */

use Drupal\Core\Database\Database;

$connection = Database::getConnection();

// Create the field storage tables. Drupal's field schema reconciliation
// probes these tables during updates ("does any row have data?"), so they
// must exist even though the test never writes rows.
$body_table = [
  'description' => 'Data storage for node field body.',
  'fields' => [
    'bundle' => [
      'type' => 'varchar_ascii',
      'length' => 128,
      'not null' => TRUE,
      'default' => '',
    ],
    'deleted' => [
      'type' => 'int',
      'size' => 'tiny',
      'not null' => TRUE,
      'default' => 0,
    ],
    'entity_id' => [
      'type' => 'int',
      'unsigned' => TRUE,
      'not null' => TRUE,
    ],
    'revision_id' => [
      'type' => 'int',
      'unsigned' => TRUE,
      'not null' => TRUE,
    ],
    'langcode' => [
      'type' => 'varchar_ascii',
      'length' => 32,
      'not null' => TRUE,
      'default' => '',
    ],
    'delta' => [
      'type' => 'int',
      'unsigned' => TRUE,
      'not null' => TRUE,
    ],
    'body_value' => [
      'type' => 'text',
      'size' => 'big',
      'not null' => TRUE,
    ],
    'body_summary' => [
      'type' => 'text',
      'size' => 'big',
      'not null' => FALSE,
    ],
    'body_format' => [
      'type' => 'varchar_ascii',
      'length' => 255,
      'not null' => FALSE,
    ],
  ],
  'primary key' => ['entity_id', 'deleted', 'delta', 'langcode'],
  'indexes' => [
    'bundle' => ['bundle'],
    'revision_id' => ['revision_id'],
    'body_format' => ['body_format'],
  ],
];
$schema = $connection->schema();
if (!$schema->tableExists('node__body')) {
  $schema->createTable('node__body', $body_table);
}
if (!$schema->tableExists('node_revision__body')) {
  $schema->createTable('node_revision__body', $body_table);
}

$field_storage = [
  'uuid' => 'ed2b1eb9-b633-4f00-9006-6b70e0ffa5a0',
  'langcode' => 'en',
  'status' => TRUE,
  'dependencies' => ['module' => ['node', 'text']],
  'id' => 'node.body',
  'field_name' => 'body',
  'entity_type' => 'node',
  'type' => 'text_with_summary',
  'settings' => [],
  'module' => 'text',
  'locked' => FALSE,
  'cardinality' => 1,
  'translatable' => TRUE,
  'indexes' => [],
  'persist_with_no_fields' => TRUE,
  'custom_storage' => FALSE,
];
$connection->merge('config')
  ->keys(['collection' => '', 'name' => 'field.storage.node.body'])
  ->fields([
    'collection' => '',
    'name' => 'field.storage.node.body',
    'data' => serialize($field_storage),
  ])
  ->execute();

$field_instance = [
  'uuid' => 'e2cf9ffc-2a71-40ea-842d-4566a6aab032',
  'langcode' => 'en',
  'status' => TRUE,
  'dependencies' => [
    'config' => ['field.storage.node.body', 'node.type.article'],
    'module' => ['text'],
  ],
  'id' => 'node.article.body',
  'field_name' => 'body',
  'entity_type' => 'node',
  'bundle' => 'article',
  'label' => 'Body',
  'description' => '',
  'required' => FALSE,
  'translatable' => TRUE,
  'default_value' => [],
  'default_value_callback' => '',
  'settings' => [
    'display_summary' => TRUE,
    'required_summary' => FALSE,
  ],
  'field_type' => 'text_with_summary',
];
$connection->merge('config')
  ->keys(['collection' => '', 'name' => 'field.field.node.article.body'])
  ->fields([
    'collection' => '',
    'name' => 'field.field.node.article.body',
    'data' => serialize($field_instance),
  ])
  ->execute();

$body_form_component = [
  'type' => 'text_textarea_with_summary',
  'weight' => 1,
  'region' => 'content',
  'settings' => [
    'rows' => 9,
    'summary_rows' => 3,
    'placeholder' => '',
    'show_summary' => FALSE,
  ],
  'third_party_settings' => [],
];

$body_view_component = [
  'type' => 'text_summary_or_trimmed',
  'label' => 'hidden',
  'settings' => ['trim_length' => 600],
  'third_party_settings' => [],
  'weight' => 0,
  'region' => 'content',
];

/**
 * Merges a body component and its dependencies into an existing display.
 */
$patch_display = function (string $name, array $component) use ($connection): void {
  $data = $connection->select('config')
    ->fields('config', ['data'])
    ->condition('collection', '')
    ->condition('name', $name)
    ->execute()
    ->fetchField();
  if (!$data) {
    return;
  }
  $display = unserialize($data);
  $display['content']['body'] = $component;
  unset($display['hidden']['body']);
  $config_deps = $display['dependencies']['config'] ?? [];
  if (!in_array('field.field.node.article.body', $config_deps, TRUE)) {
    $config_deps[] = 'field.field.node.article.body';
    sort($config_deps);
    $display['dependencies']['config'] = $config_deps;
  }
  $module_deps = $display['dependencies']['module'] ?? [];
  if (!in_array('text', $module_deps, TRUE)) {
    $module_deps[] = 'text';
    sort($module_deps);
    $display['dependencies']['module'] = $module_deps;
  }
  $connection->update('config')
    ->fields(['data' => serialize($display)])
    ->condition('collection', '')
    ->condition('name', $name)
    ->execute();
};

$patch_display('core.entity_form_display.node.article.default', $body_form_component);
$patch_display('core.entity_view_display.node.article.teaser', $body_view_component);
