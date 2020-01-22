<?php
// @codingStandardsIgnoreFile

/**
 * @file
 * Contains SQL necessary to add a deleted field to the node entity type.
 */

use Drupal\Core\Database\Database;

$connection = Database::getConnection();

// Add the field schema data and the deleted field definitions.
$connection->insert('key_value')
  ->fields([
    'collection',
    'name',
    'value',
  ])
  ->values([
    'collection' => 'entity.storage_schema.sql',
    'name' => 'node.field_schema_data.field_test',
    'value' => 'a:2:{s:16:"node__field_test";a:4:{s:11:"description";s:39:"Data storage for node field field_test.";s:6:"fields";a:7:{s:6:"bundle";a:5:{s:4:"type";s:13:"varchar_ascii";s:6:"length";i:128;s:8:"not null";b:1;s:7:"default";s:0:"";s:11:"description";s:88:"The field instance bundle to which this row belongs, used when deleting a field instance";}s:7:"deleted";a:5:{s:4:"type";s:3:"int";s:4:"size";s:4:"tiny";s:8:"not null";b:1;s:7:"default";i:0;s:11:"description";s:60:"A boolean indicating whether this data item has been deleted";}s:9:"entity_id";a:4:{s:4:"type";s:3:"int";s:8:"unsigned";b:1;s:8:"not null";b:1;s:11:"description";s:38:"The entity id this data is attached to";}s:11:"revision_id";a:4:{s:4:"type";s:3:"int";s:8:"unsigned";b:1;s:8:"not null";b:1;s:11:"description";s:47:"The entity revision id this data is attached to";}s:8:"langcode";a:5:{s:4:"type";s:13:"varchar_ascii";s:6:"length";i:32;s:8:"not null";b:1;s:7:"default";s:0:"";s:11:"description";s:37:"The language code for this data item.";}s:5:"delta";a:4:{s:4:"type";s:3:"int";s:8:"unsigned";b:1;s:8:"not null";b:1;s:11:"description";s:67:"The sequence number for this data item, used for multi-value fields";}s:16:"field_test_value";a:3:{s:4:"type";s:7:"varchar";s:6:"length";i:254;s:8:"not null";b:1;}}s:11:"primary key";a:4:{i:0;s:9:"entity_id";i:1;s:7:"deleted";i:2;s:5:"delta";i:3;s:8:"langcode";}s:7:"indexes";a:2:{s:6:"bundle";a:1:{i:0;s:6:"bundle";}s:11:"revision_id";a:1:{i:0;s:11:"revision_id";}}}s:25:"node_revision__field_test";a:4:{s:11:"description";s:51:"Revision archive storage for node field field_test.";s:6:"fields";a:7:{s:6:"bundle";a:5:{s:4:"type";s:13:"varchar_ascii";s:6:"length";i:128;s:8:"not null";b:1;s:7:"default";s:0:"";s:11:"description";s:88:"The field instance bundle to which this row belongs, used when deleting a field instance";}s:7:"deleted";a:5:{s:4:"type";s:3:"int";s:4:"size";s:4:"tiny";s:8:"not null";b:1;s:7:"default";i:0;s:11:"description";s:60:"A boolean indicating whether this data item has been deleted";}s:9:"entity_id";a:4:{s:4:"type";s:3:"int";s:8:"unsigned";b:1;s:8:"not null";b:1;s:11:"description";s:38:"The entity id this data is attached to";}s:11:"revision_id";a:4:{s:4:"type";s:3:"int";s:8:"unsigned";b:1;s:8:"not null";b:1;s:11:"description";s:47:"The entity revision id this data is attached to";}s:8:"langcode";a:5:{s:4:"type";s:13:"varchar_ascii";s:6:"length";i:32;s:8:"not null";b:1;s:7:"default";s:0:"";s:11:"description";s:37:"The language code for this data item.";}s:5:"delta";a:4:{s:4:"type";s:3:"int";s:8:"unsigned";b:1;s:8:"not null";b:1;s:11:"description";s:67:"The sequence number for this data item, used for multi-value fields";}s:16:"field_test_value";a:3:{s:4:"type";s:7:"varchar";s:6:"length";i:254;s:8:"not null";b:1;}}s:11:"primary key";a:5:{i:0;s:9:"entity_id";i:1;s:11:"revision_id";i:2;s:7:"deleted";i:3;s:5:"delta";i:4;s:8:"langcode";}s:7:"indexes";a:2:{s:6:"bundle";a:1:{i:0;s:6:"bundle";}s:11:"revision_id";a:1:{i:0;s:11:"revision_id";}}}}',
  ])
  ->values([
    'collection' => 'state',
    'name' => 'field.field.deleted',
    'value' => 'a:1:{s:36:"5d0d9870-560b-46c4-b838-0dcded0502dd";a:18:{s:4:"uuid";s:36:"5d0d9870-560b-46c4-b838-0dcded0502dd";s:8:"langcode";s:2:"en";s:6:"status";b:1;s:12:"dependencies";a:1:{s:6:"config";a:2:{i:0;s:29:"field.storage.node.field_test";i:1;s:17:"node.type.article";}}s:2:"id";s:23:"node.article.field_test";s:10:"field_name";s:10:"field_test";s:11:"entity_type";s:4:"node";s:6:"bundle";s:7:"article";s:5:"label";s:4:"Test";s:11:"description";s:0:"";s:8:"required";b:0;s:12:"translatable";b:0;s:13:"default_value";a:0:{}s:22:"default_value_callback";s:0:"";s:8:"settings";a:0:{}s:10:"field_type";s:5:"email";s:7:"deleted";b:1;s:18:"field_storage_uuid";s:36:"ce93d7c2-1da7-4a2c-9e6d-b4925e3b129f";}}',
  ])
  ->values([
    'collection' => 'state',
    'name' => 'field.storage.deleted',
    'value' => 'a:1:{s:36:"ce93d7c2-1da7-4a2c-9e6d-b4925e3b129f";a:18:{s:4:"uuid";s:36:"ce93d7c2-1da7-4a2c-9e6d-b4925e3b129f";s:8:"langcode";s:2:"en";s:6:"status";b:1;s:12:"dependencies";a:1:{s:6:"module";a:1:{i:0;s:4:"node";}}s:2:"id";s:15:"node.field_test";s:10:"field_name";s:10:"field_test";s:11:"entity_type";s:4:"node";s:4:"type";s:5:"email";s:8:"settings";a:0:{}s:6:"module";s:4:"core";s:6:"locked";b:0;s:11:"cardinality";i:1;s:12:"translatable";b:1;s:7:"indexes";a:0:{}s:22:"persist_with_no_fields";b:0;s:14:"custom_storage";b:0;s:7:"deleted";b:1;s:7:"bundles";a:0:{}}}',
  ])
  ->execute();

// Create and populate the deleted field tables.
// @see \Drupal\Core\Entity\Sql\DefaultTableMapping::getDedicatedDataTableName()
$deleted_field_data_table_name = "field_deleted_data_" . substr(hash('sha256', 'ce93d7c2-1da7-4a2c-9e6d-b4925e3b129f'), 0, 10);
$connection->schema()->createTable($deleted_field_data_table_name, array(
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
    'field_test_value' => array(
      'type' => 'varchar',
      'not null' => TRUE,
      'length' => '254',
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
  ),
));

$connection->insert($deleted_field_data_table_name)
->fields(array(
  'bundle',
  'deleted',
  'entity_id',
  'revision_id',
  'langcode',
  'delta',
  'field_test_value',
))
->values(array(
  'bundle' => 'article',
  'deleted' => '1',
  'entity_id' => '1',
  'revision_id' => '1',
  'langcode' => 'en',
  'delta' => '0',
  'field_test_value' => 'test@test.com',
))
->execute();

// @see \Drupal\Core\Entity\Sql\DefaultTableMapping::getDedicatedDataTableName()
$deleted_field_revision_table_name = "field_deleted_revision_" . substr(hash('sha256', 'ce93d7c2-1da7-4a2c-9e6d-b4925e3b129f'), 0, 10);
$connection->schema()->createTable($deleted_field_revision_table_name, array(
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
    'field_test_value' => array(
      'type' => 'varchar',
      'not null' => TRUE,
      'length' => '254',
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
  ),
));

$connection->insert($deleted_field_revision_table_name)
->fields(array(
  'bundle',
  'deleted',
  'entity_id',
  'revision_id',
  'langcode',
  'delta',
  'field_test_value',
))
->values(array(
  'bundle' => 'article',
  'deleted' => '1',
  'entity_id' => '1',
  'revision_id' => '1',
  'langcode' => 'en',
  'delta' => '0',
  'field_test_value' => 'test@test.com',
))
->execute();
