<?php

/**
 * @file
 * Provides database changes for testing the daterange formatter upgrade path.
 *
 * @see \Drupal\Tests\datetime_range\Functional\DateRangeFormatterSettingsUpdateTest
 */

use Drupal\Core\Database\Database;
use Drupal\field\Entity\FieldStorageConfig;

$connection = Database::getConnection();

// Add all datetime_range_removed_post_updates() as existing updates.
require_once __DIR__ . '/../../../../datetime_range/datetime_range.post_update.php';
$existing_updates = $connection->select('key_value')
  ->fields('key_value', ['value'])
  ->condition('collection', 'post_update')
  ->condition('name', 'existing_updates')
  ->execute()
  ->fetchField();
$existing_updates = unserialize($existing_updates);
$existing_updates = array_merge(
  $existing_updates,
  array_keys(datetime_range_removed_post_updates())
);
$connection->update('key_value')
  ->fields(['value' => serialize($existing_updates)])
  ->condition('collection', 'post_update')
  ->condition('name', 'existing_updates')
  ->execute();

// Add a new timestamp field 'field_datetime_range'.
$connection->insert('config')
  ->fields(['collection', 'name', 'data'])->values([
    'collection' => '',
    'name' => 'field.storage.node.field_datetime_range',
    'data' => $field_storage = 'a:16:{s:4:"uuid";s:36:"a01264e6-2821-4b94-bc79-ba2b346795bb";s:8:"langcode";s:2:"en";s:6:"status";b:1;s:12:"dependencies";a:1:{s:6:"module";a:2:{i:0;s:14:"datetime_range";i:1;s:4:"node";}}s:2:"id";s:25:"node.field_datetime_range";s:10:"field_name";s:20:"field_datetime_range";s:11:"entity_type";s:4:"node";s:4:"type";s:9:"daterange";s:8:"settings";a:1:{s:13:"datetime_type";s:8:"datetime";}s:6:"module";s:14:"datetime_range";s:6:"locked";b:0;s:11:"cardinality";i:1;s:12:"translatable";b:1;s:7:"indexes";a:0:{}s:22:"persist_with_no_fields";b:0;s:14:"custom_storage";b:0;}',
  ])->values([
    'collection' => '',
    'name' => 'field.field.node.page.field_datetime_range',
    'data' => 'a:16:{s:4:"uuid";s:36:"678b9e68-cff5-4b2e-9111-43e5d9d6c826";s:8:"langcode";s:2:"en";s:6:"status";b:1;s:12:"dependencies";a:2:{s:6:"config";a:2:{i:0;s:39:"field.storage.node.field_datetime_range";i:1;s:14:"node.type.page";}s:6:"module";a:1:{i:0;s:14:"datetime_range";}}s:2:"id";s:30:"node.page.field_datetime_range";s:10:"field_name";s:20:"field_datetime_range";s:11:"entity_type";s:4:"node";s:6:"bundle";s:4:"page";s:5:"label";s:14:"datetime range";s:11:"description";s:0:"";s:8:"required";b:0;s:12:"translatable";b:0;s:13:"default_value";a:0:{}s:22:"default_value_callback";s:0:"";s:8:"settings";a:0:{}s:10:"field_type";s:9:"daterange";}',
  ])->execute();

$connection->insert('key_value')
  ->fields(['collection', 'name', 'value'])
  ->values([
    'collection' => 'config.entity.key_store.field_config',
    'name' => 'uuid:678b9e68-cff5-4b2e-9111-43e5d9d6c826',
    'value' => 'a:1:{i:0;s:42:"field.field.node.page.field_datetime_range";}',
  ])
  ->values([
    'collection' => 'config.entity.key_store.field_storage_config',
    'name' => 'uuid:a01264e6-2821-4b94-bc79-ba2b346795bb',
    'value' => 'a:1:{i:0;s:39:"field.storage.node.field_datetime_range";}',
  ])
  ->values([
    'collection' => 'entity.storage_schema.sql',
    'name' => 'node.field_schema_data.field_datetime_range',
    'value' => 'a:2:{s:26:"node__field_datetime_range";a:4:{s:11:"description";s:49:"Data storage for node field field_datetime_range.";s:6:"fields";a:8:{s:6:"bundle";a:5:{s:4:"type";s:13:"varchar_ascii";s:6:"length";i:128;s:8:"not null";b:1;s:7:"default";s:0:"";s:11:"description";s:88:"The field instance bundle to which this row belongs, used when deleting a field instance";}s:7:"deleted";a:5:{s:4:"type";s:3:"int";s:4:"size";s:4:"tiny";s:8:"not null";b:1;s:7:"default";i:0;s:11:"description";s:60:"A boolean indicating whether this data item has been deleted";}s:9:"entity_id";a:4:{s:4:"type";s:3:"int";s:8:"unsigned";b:1;s:8:"not null";b:1;s:11:"description";s:38:"The entity id this data is attached to";}s:11:"revision_id";a:4:{s:4:"type";s:3:"int";s:8:"unsigned";b:1;s:8:"not null";b:1;s:11:"description";s:47:"The entity revision id this data is attached to";}s:8:"langcode";a:5:{s:4:"type";s:13:"varchar_ascii";s:6:"length";i:32;s:8:"not null";b:1;s:7:"default";s:0:"";s:11:"description";s:37:"The language code for this data item.";}s:5:"delta";a:4:{s:4:"type";s:3:"int";s:8:"unsigned";b:1;s:8:"not null";b:1;s:11:"description";s:67:"The sequence number for this data item, used for multi-value fields";}s:26:"field_datetime_range_value";a:4:{s:11:"description";s:21:"The start date value.";s:4:"type";s:7:"varchar";s:6:"length";i:20;s:8:"not null";b:1;}s:30:"field_datetime_range_end_value";a:4:{s:11:"description";s:19:"The end date value.";s:4:"type";s:7:"varchar";s:6:"length";i:20;s:8:"not null";b:1;}}s:11:"primary key";a:4:{i:0;s:9:"entity_id";i:1;s:7:"deleted";i:2;s:5:"delta";i:3;s:8:"langcode";}s:7:"indexes";a:4:{s:6:"bundle";a:1:{i:0;s:6:"bundle";}s:11:"revision_id";a:1:{i:0;s:11:"revision_id";}s:26:"field_datetime_range_value";a:1:{i:0;s:26:"field_datetime_range_value";}s:30:"field_datetime_range_end_value";a:1:{i:0;s:30:"field_datetime_range_end_value";}}}s:35:"node_revision__field_datetime_range";a:4:{s:11:"description";s:61:"Revision archive storage for node field field_datetime_range.";s:6:"fields";a:8:{s:6:"bundle";a:5:{s:4:"type";s:13:"varchar_ascii";s:6:"length";i:128;s:8:"not null";b:1;s:7:"default";s:0:"";s:11:"description";s:88:"The field instance bundle to which this row belongs, used when deleting a field instance";}s:7:"deleted";a:5:{s:4:"type";s:3:"int";s:4:"size";s:4:"tiny";s:8:"not null";b:1;s:7:"default";i:0;s:11:"description";s:60:"A boolean indicating whether this data item has been deleted";}s:9:"entity_id";a:4:{s:4:"type";s:3:"int";s:8:"unsigned";b:1;s:8:"not null";b:1;s:11:"description";s:38:"The entity id this data is attached to";}s:11:"revision_id";a:4:{s:4:"type";s:3:"int";s:8:"unsigned";b:1;s:8:"not null";b:1;s:11:"description";s:47:"The entity revision id this data is attached to";}s:8:"langcode";a:5:{s:4:"type";s:13:"varchar_ascii";s:6:"length";i:32;s:8:"not null";b:1;s:7:"default";s:0:"";s:11:"description";s:37:"The language code for this data item.";}s:5:"delta";a:4:{s:4:"type";s:3:"int";s:8:"unsigned";b:1;s:8:"not null";b:1;s:11:"description";s:67:"The sequence number for this data item, used for multi-value fields";}s:26:"field_datetime_range_value";a:4:{s:11:"description";s:21:"The start date value.";s:4:"type";s:7:"varchar";s:6:"length";i:20;s:8:"not null";b:1;}s:30:"field_datetime_range_end_value";a:4:{s:11:"description";s:19:"The end date value.";s:4:"type";s:7:"varchar";s:6:"length";i:20;s:8:"not null";b:1;}}s:11:"primary key";a:5:{i:0;s:9:"entity_id";i:1;s:11:"revision_id";i:2;s:7:"deleted";i:3;s:5:"delta";i:4;s:8:"langcode";}s:7:"indexes";a:4:{s:6:"bundle";a:1:{i:0;s:6:"bundle";}s:11:"revision_id";a:1:{i:0;s:11:"revision_id";}s:26:"field_datetime_range_value";a:1:{i:0;s:26:"field_datetime_range_value";}s:30:"field_datetime_range_end_value";a:1:{i:0;s:30:"field_datetime_range_end_value";}}}}',
  ])
  ->execute();

$data = $connection->select('key_value')
  ->fields('key_value', ['value'])
  ->condition('collection', 'entity.definitions.installed')
  ->condition('name', 'node.field_storage_definitions')
  ->execute()
  ->fetchField();
$data = unserialize($data);
$data['field_datetime_range'] = new FieldStorageConfig(unserialize($field_storage));
$connection->update('key_value')
  ->fields(['value' => serialize($data)])
  ->condition('collection', 'entity.definitions.installed')
  ->condition('name', 'node.field_storage_definitions')
  ->execute();

$data = $connection->select('config')
  ->fields('config', ['data'])
  ->condition('collection', '')
  ->condition('name', 'core.entity_view_display.node.page.default')
  ->execute()
  ->fetchField();
$data = unserialize($data);
$data['content']['field_datetime_range'] = [
  'type' => 'daterange_default',
  'label' => 'above',
  'settings' => [
    'timezone_override' => '',
    'format_type' => 'medium',
    'separator' => '-',
  ],
  'third_party_settings' => [],
  'weight' => 102,
  'region' => 'content',
];
$connection->update('config')
  ->fields([
    'data' => serialize($data),
  ])
  ->condition('collection', '')
  ->condition('name', 'core.entity_view_display.node.page.default')
  ->execute();

$extensions = $connection->select('config')
  ->fields('config', ['data'])
  ->condition('collection', '')
  ->condition('name', 'core.extension')
  ->execute()
  ->fetchField();
$extensions = unserialize($extensions);
$extensions['module']['datetime_range'] = 0;
$connection->update('config')
  ->fields([
    'data' => serialize($extensions),
  ])
  ->condition('collection', '')
  ->condition('name', 'core.extension')
  ->execute();
