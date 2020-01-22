<?php
// @codingStandardsIgnoreFile

/**
 * @file
 * Contains database additions to drupal-8.bare.standard.php.gz for testing the
 * upgrade path of https://www.drupal.org/node/2786577.
 */

use Drupal\Core\Database\Database;
use Drupal\Core\Serialization\Yaml;

$connection = Database::getConnection();

// Configuration for an datetime_range field storage.
$field_storage_datetime_range = Yaml::decode(file_get_contents(__DIR__ . '/field.storage.node.field_range.yml'));

// Configuration for a datetime_range field on 'page' node bundle.
$field_datetime_range = Yaml::decode(file_get_contents(__DIR__ . '/field.field.node.page.field_range.yml'));

// Configuration for a View using datetime_range plugins.
$views_datetime_range = Yaml::decode(file_get_contents(__DIR__ . '/views.view.test_datetime_range_filter_values.yml'));

// Update core.entity_form_display.node.page.default
$data = $connection->select('config')
  ->fields('config', ['data'])
  ->condition('collection', '')
  ->condition('name', 'core.entity_form_display.node.page.default')
  ->execute()
  ->fetchField();

$data = unserialize($data);
$data['dependencies']['config'][] = 'field.field.' . $field_datetime_range['id'];
$data['dependencies']['module'][] = 'datetime_range';
$data['content'][$field_datetime_range['field_name']] = array(
    "weight"=> 27,
    "settings" => array(),
    "third_party_settings" => array(),
    "type" => "daterange_default",
    "region" => "content"
);
$connection->update('config')
  ->fields([
    'data' => serialize($data),
  ])
  ->condition('collection', '')
  ->condition('name', 'core.entity_form_display.node.page.default')
  ->execute();

// Update core.entity_view_display.node.page.default
$data = $connection->select('config')
  ->fields('config', ['data'])
  ->condition('collection', '')
  ->condition('name', 'core.entity_view_display.node.page.default')
  ->execute()
  ->fetchField();

$data = unserialize($data);
$data['dependencies']['config'][] = 'field.field.' . $field_datetime_range['id'];
$data['dependencies']['module'][] = 'datetime_range';
$data['content'][$field_datetime_range['field_name']] = array(
    "weight"=> 102,
    "label"=> "above",
    "settings" => array("separator"=> "-", "format_type" => "medium", "timezone_override" => ""),
    "third_party_settings" => array(),
    "type" => "daterange_default",
    "region" => "content"
);
$connection->update('config')
  ->fields([
    'data' => serialize($data),
  ])
  ->condition('collection', '')
  ->condition('name', 'core.entity_view_display.node.page.default')
  ->execute();

$connection->insert('config')
->fields(array(
  'collection',
  'name',
  'data',
))
->values(array(
  'collection' => '',
  'name' => 'field.field.' . $field_datetime_range['id'],
  'data' => serialize($field_datetime_range),
))
->values(array(
  'collection' => '',
  'name' => 'field.storage.' . $field_storage_datetime_range['id'],
  'data' => serialize($field_storage_datetime_range),
))
->values(array(
  'collection' => '',
  'name' => 'views.view.' . $views_datetime_range['id'],
  'data' => serialize($views_datetime_range),
))
->execute();

// Update core.extension.
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

$connection->insert('key_value')
->fields(array(
  'collection',
  'name',
  'value',
))
->values(array(
  'collection' => 'config.entity.key_store.field_config',
  'name' => 'uuid:87dc4221-8d56-4112-8a7f-7a855ac35d08',
  'value' => 'a:1:{i:0;s:33:"field.field.' . $field_datetime_range['id'] . '";}',
))
->values(array(
  'collection' => 'config.entity.key_store.field_storage_config',
  'name' => 'uuid:2190ad8c-39dd-4eb1-b189-1bfc0c244a40',
  'value' => 'a:1:{i:0;s:30:"field.storage.' . $field_storage_datetime_range['id'] . '";}',
))
->values(array(
  'collection' => 'config.entity.key_store.view',
  'name' => 'uuid:d20760b6-7cc4-4844-ae04-96da7225a46f',
  'value' => 'a:1:{i:0;s:44:"views.view.' . $views_datetime_range['id'] . '";}',
))
->values(array(
  'collection' => 'entity.storage_schema.sql',
  'name' => 'node.field_schema_data.field_range',
  'value' => 'a:2:{s:17:"node__field_range";a:4:{s:11:"description";s:40:"Data storage for node field field_range.";s:6:"fields";a:8:{s:6:"bundle";a:5:{s:4:"type";s:13:"varchar_ascii";s:6:"length";i:128;s:8:"not null";b:1;s:7:"default";s:0:"";s:11:"description";s:88:"The field instance bundle to which this row belongs, used when deleting a field instance";}s:7:"deleted";a:5:{s:4:"type";s:3:"int";s:4:"size";s:4:"tiny";s:8:"not null";b:1;s:7:"default";i:0;s:11:"description";s:60:"A boolean indicating whether this data item has been deleted";}s:9:"entity_id";a:4:{s:4:"type";s:3:"int";s:8:"unsigned";b:1;s:8:"not null";b:1;s:11:"description";s:38:"The entity id this data is attached to";}s:11:"revision_id";a:4:{s:4:"type";s:3:"int";s:8:"unsigned";b:1;s:8:"not null";b:1;s:11:"description";s:47:"The entity revision id this data is attached to";}s:8:"langcode";a:5:{s:4:"type";s:13:"varchar_ascii";s:6:"length";i:32;s:8:"not null";b:1;s:7:"default";s:0:"";s:11:"description";s:37:"The language code for this data item.";}s:5:"delta";a:4:{s:4:"type";s:3:"int";s:8:"unsigned";b:1;s:8:"not null";b:1;s:11:"description";s:67:"The sequence number for this data item, used for multi-value fields";}s:17:"field_range_value";a:4:{s:11:"description";s:21:"The start date value.";s:4:"type";s:7:"varchar";s:6:"length";i:20;s:8:"not null";b:1;}s:21:"field_range_end_value";a:4:{s:11:"description";s:19:"The end date value.";s:4:"type";s:7:"varchar";s:6:"length";i:20;s:8:"not null";b:1;}}s:11:"primary key";a:4:{i:0;s:9:"entity_id";i:1;s:7:"deleted";i:2;s:5:"delta";i:3;s:8:"langcode";}s:7:"indexes";a:4:{s:6:"bundle";a:1:{i:0;s:6:"bundle";}s:11:"revision_id";a:1:{i:0;s:11:"revision_id";}s:17:"field_range_value";a:1:{i:0;s:17:"field_range_value";}s:21:"field_range_end_value";a:1:{i:0;s:21:"field_range_end_value";}}}s:26:"node_revision__field_range";a:4:{s:11:"description";s:52:"Revision archive storage for node field field_range.";s:6:"fields";a:8:{s:6:"bundle";a:5:{s:4:"type";s:13:"varchar_ascii";s:6:"length";i:128;s:8:"not null";b:1;s:7:"default";s:0:"";s:11:"description";s:88:"The field instance bundle to which this row belongs, used when deleting a field instance";}s:7:"deleted";a:5:{s:4:"type";s:3:"int";s:4:"size";s:4:"tiny";s:8:"not null";b:1;s:7:"default";i:0;s:11:"description";s:60:"A boolean indicating whether this data item has been deleted";}s:9:"entity_id";a:4:{s:4:"type";s:3:"int";s:8:"unsigned";b:1;s:8:"not null";b:1;s:11:"description";s:38:"The entity id this data is attached to";}s:11:"revision_id";a:4:{s:4:"type";s:3:"int";s:8:"unsigned";b:1;s:8:"not null";b:1;s:11:"description";s:47:"The entity revision id this data is attached to";}s:8:"langcode";a:5:{s:4:"type";s:13:"varchar_ascii";s:6:"length";i:32;s:8:"not null";b:1;s:7:"default";s:0:"";s:11:"description";s:37:"The language code for this data item.";}s:5:"delta";a:4:{s:4:"type";s:3:"int";s:8:"unsigned";b:1;s:8:"not null";b:1;s:11:"description";s:67:"The sequence number for this data item, used for multi-value fields";}s:17:"field_range_value";a:4:{s:11:"description";s:21:"The start date value.";s:4:"type";s:7:"varchar";s:6:"length";i:20;s:8:"not null";b:1;}s:21:"field_range_end_value";a:4:{s:11:"description";s:19:"The end date value.";s:4:"type";s:7:"varchar";s:6:"length";i:20;s:8:"not null";b:1;}}s:11:"primary key";a:5:{i:0;s:9:"entity_id";i:1;s:11:"revision_id";i:2;s:7:"deleted";i:3;s:5:"delta";i:4;s:8:"langcode";}s:7:"indexes";a:4:{s:6:"bundle";a:1:{i:0;s:6:"bundle";}s:11:"revision_id";a:1:{i:0;s:11:"revision_id";}s:17:"field_range_value";a:1:{i:0;s:17:"field_range_value";}s:21:"field_range_end_value";a:1:{i:0;s:21:"field_range_end_value";}}}}',
))
->values(array(
  'collection' => 'system.schema',
  'name' => 'datetime_range',
  'value' => 'i:8000;',
))
->execute();

// Update entity.definitions.bundle_field_map
$value = $connection->select('key_value')
  ->fields('key_value', ['value'])
  ->condition('collection', 'entity.definitions.bundle_field_map')
  ->condition('name', 'node')
  ->execute()
  ->fetchField();

$value = unserialize($value);
$value["field_range"] = array("type" => "daterange", "bundles" => array("page" => "page"));

$connection->update('key_value')
  ->fields([
    'value' => serialize($value),
  ])
  ->condition('collection', 'entity.definitions.bundle_field_map')
  ->condition('name', 'node')
  ->execute();

// Update system.module.files
$files = $connection->select('key_value')
  ->fields('key_value', ['value'])
  ->condition('collection', 'state')
  ->condition('name', 'system.module.files')
  ->execute()
  ->fetchField();

$files = unserialize($files);
$files["datetime_range"] = "core/modules/datetime_range/datetime_range.info.yml";

$connection->update('key_value')
  ->fields([
    'value' => serialize($files),
  ])
  ->condition('collection', 'state')
  ->condition('name', 'system.module.files')
  ->execute();

$connection->schema()->createTable('node__field_range', array(
  'fields' => array(
    'bundle' => array(
      'type' => 'varchar_ascii',
      'not null' => TRUE,
      'length' => '128',
      'default' => '',
    ),
    'deleted' => array(
      'type' => 'int',
      'not null' => TRUE,
      'size' => 'tiny',
      'default' => '0',
    ),
    'entity_id' => array(
      'type' => 'int',
      'not null' => TRUE,
      'size' => 'normal',
      'unsigned' => TRUE,
    ),
    'revision_id' => array(
      'type' => 'int',
      'not null' => TRUE,
      'size' => 'normal',
      'unsigned' => TRUE,
    ),
    'langcode' => array(
      'type' => 'varchar_ascii',
      'not null' => TRUE,
      'length' => '32',
      'default' => '',
    ),
    'delta' => array(
      'type' => 'int',
      'not null' => TRUE,
      'size' => 'normal',
      'unsigned' => TRUE,
    ),
    'field_range_value' => array(
      'type' => 'varchar',
      'not null' => TRUE,
      'length' => '20',
    ),
    'field_range_end_value' => array(
      'type' => 'varchar',
      'not null' => TRUE,
      'length' => '20',
    ),
  ),
  'primary key' => array(
    'entity_id',
    'deleted',
    'delta',
    'langcode',
  ),
  'indexes' => array(
    'bundle' => array(
      'bundle',
    ),
    'revision_id' => array(
      'revision_id',
    ),
    'field_range_value' => array(
      'field_range_value',
    ),
    'field_range_end_value' => array(
      'field_range_end_value',
    ),
  ),
  'mysql_character_set' => 'utf8mb4',
));

$connection->schema()->createTable('node_revision__field_range', array(
  'fields' => array(
    'bundle' => array(
      'type' => 'varchar_ascii',
      'not null' => TRUE,
      'length' => '128',
      'default' => '',
    ),
    'deleted' => array(
      'type' => 'int',
      'not null' => TRUE,
      'size' => 'tiny',
      'default' => '0',
    ),
    'entity_id' => array(
      'type' => 'int',
      'not null' => TRUE,
      'size' => 'normal',
      'unsigned' => TRUE,
    ),
    'revision_id' => array(
      'type' => 'int',
      'not null' => TRUE,
      'size' => 'normal',
      'unsigned' => TRUE,
    ),
    'langcode' => array(
      'type' => 'varchar_ascii',
      'not null' => TRUE,
      'length' => '32',
      'default' => '',
    ),
    'delta' => array(
      'type' => 'int',
      'not null' => TRUE,
      'size' => 'normal',
      'unsigned' => TRUE,
    ),
    'field_range_value' => array(
      'type' => 'varchar',
      'not null' => TRUE,
      'length' => '20',
    ),
    'field_range_end_value' => array(
      'type' => 'varchar',
      'not null' => TRUE,
      'length' => '20',
    ),
  ),
  'primary key' => array(
    'entity_id',
    'revision_id',
    'deleted',
    'delta',
    'langcode',
  ),
  'indexes' => array(
    'bundle' => array(
      'bundle',
    ),
    'revision_id' => array(
      'revision_id',
    ),
    'field_range_value' => array(
      'field_range_value',
    ),
    'field_range_end_value' => array(
      'field_range_end_value',
    ),
  ),
  'mysql_character_set' => 'utf8mb4',
));

