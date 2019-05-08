<?php

/**
 * @file
 * Test fixture.
 */

use Drupal\Core\Database\Database;

$connection = Database::getConnection();

// Enable Layout Builder on an existing entity view display.
foreach (['article', 'page'] as $bundle) {
  $display = $connection->select('config')
    ->fields('config', ['data'])
    ->condition('collection', '')
    ->condition('name', "core.entity_view_display.node.$bundle.default")
    ->execute()
    ->fetchField();
  $display = unserialize($display);
  $display['third_party_settings']['layout_builder']['enabled'] = TRUE;
  $display['third_party_settings']['layout_builder']['allow_custom'] = TRUE;
  $connection->update('config')
    ->fields([
      'data' => serialize($display),
      'collection' => '',
      'name' => "core.entity_view_display.node.$bundle.default",
    ])
    ->condition('collection', '')
    ->condition('name', "core.entity_view_display.node.$bundle.default")
    ->execute();
}

// Enable Layout Builder on a view display of an entity type that is not yet
// revisionable.
$display = $connection->select('config')
  ->fields('config', ['data'])
  ->condition('collection', '')
  ->condition('name', "core.entity_view_display.taxonomy_term.forums.default")
  ->execute()
  ->fetchField();
$display = unserialize($display);
$display['third_party_settings']['layout_builder']['enabled'] = TRUE;
$display['third_party_settings']['layout_builder']['allow_custom'] = TRUE;
$connection->update('config')
  ->fields([
    'data' => serialize($display),
    'collection' => '',
    'name' => "core.entity_view_display.taxonomy_term.forums.default",
  ])
  ->condition('collection', '')
  ->condition('name', "core.entity_view_display.taxonomy_term.forums.default")
  ->execute();

// Add the layout builder field and field storage.
$connection->insert('config')
  ->fields([
    'collection',
    'name',
    'data',
  ])
  ->values([
    'collection' => '',
    'name' => 'field.field.node.article.layout_builder__layout',
    'data' => 'a:16:{s:4:"uuid";s:36:"3a7fb64f-d1cf-4fd5-bd07-9f81d893021a";s:8:"langcode";s:2:"en";s:6:"status";b:1;s:12:"dependencies";a:2:{s:6:"config";a:2:{i:0;s:41:"field.storage.node.layout_builder__layout";i:1;s:17:"node.type.article";}s:6:"module";a:1:{i:0;s:14:"layout_builder";}}s:2:"id";s:35:"node.article.layout_builder__layout";s:10:"field_name";s:22:"layout_builder__layout";s:11:"entity_type";s:4:"node";s:6:"bundle";s:7:"article";s:5:"label";s:6:"Layout";s:11:"description";s:0:"";s:8:"required";b:0;s:12:"translatable";b:1;s:13:"default_value";a:0:{}s:22:"default_value_callback";s:0:"";s:8:"settings";a:0:{}s:10:"field_type";s:14:"layout_section";}',
  ])
  ->values([
    'collection' => '',
    'name' => 'field.field.node.page.layout_builder__layout',
    'data' => 'a:16:{s:4:"uuid";s:36:"6439079b-0f6f-43aa-8e08-1ae42ba1333f";s:8:"langcode";s:2:"en";s:6:"status";b:1;s:12:"dependencies";a:2:{s:6:"config";a:2:{i:0;s:41:"field.storage.node.layout_builder__layout";i:1;s:14:"node.type.page";}s:6:"module";a:1:{i:0;s:14:"layout_builder";}}s:2:"id";s:32:"node.page.layout_builder__layout";s:10:"field_name";s:22:"layout_builder__layout";s:11:"entity_type";s:4:"node";s:6:"bundle";s:4:"page";s:5:"label";s:6:"Layout";s:11:"description";s:0:"";s:8:"required";b:0;s:12:"translatable";b:1;s:13:"default_value";a:0:{}s:22:"default_value_callback";s:0:"";s:8:"settings";a:0:{}s:10:"field_type";s:14:"layout_section";}',
  ])
  ->values([
    'collection' => '',
    'name' => 'field.storage.node.layout_builder__layout',
    'data' => 'a:16:{s:4:"uuid";s:36:"65b11331-3cd9-4c45-b7a3-6bcfbfd56c6e";s:8:"langcode";s:2:"en";s:6:"status";b:1;s:12:"dependencies";a:1:{s:6:"module";a:2:{i:0;s:14:"layout_builder";i:1;s:4:"node";}}s:2:"id";s:27:"node.layout_builder__layout";s:10:"field_name";s:22:"layout_builder__layout";s:11:"entity_type";s:4:"node";s:4:"type";s:14:"layout_section";s:8:"settings";a:0:{}s:6:"module";s:14:"layout_builder";s:6:"locked";b:1;s:11:"cardinality";i:1;s:12:"translatable";b:1;s:7:"indexes";a:0:{}s:22:"persist_with_no_fields";b:0;s:14:"custom_storage";b:0;}',
  ])
  ->values([
    'collection' => '',
    'name' => 'field.field.taxonomy_term.forums.layout_builder__layout',
    'data' => 'a:16:{s:4:"uuid";s:36:"0f385059-19dd-4bd9-b424-d3deb5aee5e9";s:8:"langcode";s:2:"en";s:6:"status";b:1;s:12:"dependencies";a:2:{s:6:"config";a:2:{i:0;s:50:"field.storage.taxonomy_term.layout_builder__layout";i:1;s:26:"taxonomy.vocabulary.forums";}s:6:"module";a:1:{i:0;s:14:"layout_builder";}}s:2:"id";s:43:"taxonomy_term.forums.layout_builder__layout";s:10:"field_name";s:22:"layout_builder__layout";s:11:"entity_type";s:13:"taxonomy_term";s:6:"bundle";s:6:"forums";s:5:"label";s:6:"Layout";s:11:"description";s:0:"";s:8:"required";b:0;s:12:"translatable";b:1;s:13:"default_value";a:0:{}s:22:"default_value_callback";s:0:"";s:8:"settings";a:0:{}s:10:"field_type";s:14:"layout_section";}',
  ])
  ->values([
    'collection' => '',
    'name' => 'field.storage.taxonomy_term.layout_builder__layout',
    'data' => 'a:16:{s:4:"uuid";s:36:"ca41519a-bc8a-4b04-a050-f61afc61f141";s:8:"langcode";s:2:"en";s:6:"status";b:1;s:12:"dependencies";a:1:{s:6:"module";a:3:{i:0;s:14:"layout_builder";i:1;s:8:"taxonomy";i:2;s:5:"forum";}}s:2:"id";s:36:"taxonomy_term.layout_builder__layout";s:10:"field_name";s:22:"layout_builder__layout";s:11:"entity_type";s:13:"taxonomy_term";s:4:"type";s:14:"layout_section";s:8:"settings";a:0:{}s:6:"module";s:14:"layout_builder";s:6:"locked";b:1;s:11:"cardinality";i:1;s:12:"translatable";b:1;s:7:"indexes";a:0:{}s:22:"persist_with_no_fields";b:0;s:14:"custom_storage";b:0;}',
  ])
  ->execute();
$connection->insert('key_value')
  ->fields([
    'collection',
    'name',
    'value',
  ])
  ->values([
    'collection' => 'config.entity.key_store.field_config',
    'name' => 'uuid:3a7fb64f-d1cf-4fd5-bd07-9f81d893021a',
    'value' => 'a:1:{i:0;s:47:"field.field.node.article.layout_builder__layout";}',
  ])
  ->values([
    'collection' => 'config.entity.key_store.field_config',
    'name' => 'uuid:6439079b-0f6f-43aa-8e08-1ae42ba1333f',
    'value' => 'a:1:{i:0;s:44:"field.field.node.page.layout_builder__layout";}";}',
  ])
  ->values([
    'collection' => 'config.entity.key_store.field_storage_config',
    'name' => 'uuid:65b11331-3cd9-4c45-b7a3-6bcfbfd56c6e',
    'value' => 'a:1:{i:0;s:41:"field.storage.node.layout_builder__layout";}',
  ])
  ->values([
    'collection' => 'config.entity.key_store.field_config',
    'name' => 'uuid:0f385059-19dd-4bd9-b424-d3deb5aee5e9',
    'value' => 'a:1:{i:0;s:55:"field.field.taxonomy_term.forums.layout_builder__layout";}";}',
  ])
  ->values([
    'collection' => 'config.entity.key_store.field_storage_config',
    'name' => 'uuid:ca41519a-bc8a-4b04-a050-f61afc61f141',
    'value' => 'a:1:{i:0;s:50:"field.storage.taxonomy_term.layout_builder__layout";}',
  ])
  ->values([
    'collection' => 'entity.storage_schema.sql',
    'name' => 'node.field_schema_data.layout_builder__layout',
    'value' => 'a:2:{s:28:"node__layout_builder__layout";a:4:{s:11:"description";s:51:"Data storage for node field layout_builder__layout.";s:6:"fields";a:7:{s:6:"bundle";a:5:{s:4:"type";s:13:"varchar_ascii";s:6:"length";i:128;s:8:"not null";b:1;s:7:"default";s:0:"";s:11:"description";s:88:"The field instance bundle to which this row belongs, used when deleting a field instance";}s:7:"deleted";a:5:{s:4:"type";s:3:"int";s:4:"size";s:4:"tiny";s:8:"not null";b:1;s:7:"default";i:0;s:11:"description";s:60:"A boolean indicating whether this data item has been deleted";}s:9:"entity_id";a:4:{s:4:"type";s:3:"int";s:8:"unsigned";b:1;s:8:"not null";b:1;s:11:"description";s:38:"The entity id this data is attached to";}s:11:"revision_id";a:4:{s:4:"type";s:3:"int";s:8:"unsigned";b:1;s:8:"not null";b:1;s:11:"description";s:47:"The entity revision id this data is attached to";}s:8:"langcode";a:5:{s:4:"type";s:13:"varchar_ascii";s:6:"length";i:32;s:8:"not null";b:1;s:7:"default";s:0:"";s:11:"description";s:37:"The language code for this data item.";}s:5:"delta";a:4:{s:4:"type";s:3:"int";s:8:"unsigned";b:1;s:8:"not null";b:1;s:11:"description";s:67:"The sequence number for this data item, used for multi-value fields";}s:30:"layout_builder__layout_section";a:4:{s:4:"type";s:4:"blob";s:4:"size";s:6:"normal";s:9:"serialize";b:1;s:8:"not null";b:0;}}s:11:"primary key";a:4:{i:0;s:9:"entity_id";i:1;s:7:"deleted";i:2;s:5:"delta";i:3;s:8:"langcode";}s:7:"indexes";a:2:{s:6:"bundle";a:1:{i:0;s:6:"bundle";}s:11:"revision_id";a:1:{i:0;s:11:"revision_id";}}}s:37:"node_revision__layout_builder__layout";a:4:{s:11:"description";s:63:"Revision archive storage for node field layout_builder__layout.";s:6:"fields";a:7:{s:6:"bundle";a:5:{s:4:"type";s:13:"varchar_ascii";s:6:"length";i:128;s:8:"not null";b:1;s:7:"default";s:0:"";s:11:"description";s:88:"The field instance bundle to which this row belongs, used when deleting a field instance";}s:7:"deleted";a:5:{s:4:"type";s:3:"int";s:4:"size";s:4:"tiny";s:8:"not null";b:1;s:7:"default";i:0;s:11:"description";s:60:"A boolean indicating whether this data item has been deleted";}s:9:"entity_id";a:4:{s:4:"type";s:3:"int";s:8:"unsigned";b:1;s:8:"not null";b:1;s:11:"description";s:38:"The entity id this data is attached to";}s:11:"revision_id";a:4:{s:4:"type";s:3:"int";s:8:"unsigned";b:1;s:8:"not null";b:1;s:11:"description";s:47:"The entity revision id this data is attached to";}s:8:"langcode";a:5:{s:4:"type";s:13:"varchar_ascii";s:6:"length";i:32;s:8:"not null";b:1;s:7:"default";s:0:"";s:11:"description";s:37:"The language code for this data item.";}s:5:"delta";a:4:{s:4:"type";s:3:"int";s:8:"unsigned";b:1;s:8:"not null";b:1;s:11:"description";s:67:"The sequence number for this data item, used for multi-value fields";}s:30:"layout_builder__layout_section";a:4:{s:4:"type";s:4:"blob";s:4:"size";s:6:"normal";s:9:"serialize";b:1;s:8:"not null";b:0;}}s:11:"primary key";a:5:{i:0;s:9:"entity_id";i:1;s:11:"revision_id";i:2;s:7:"deleted";i:3;s:5:"delta";i:4;s:8:"langcode";}s:7:"indexes";a:2:{s:6:"bundle";a:1:{i:0;s:6:"bundle";}s:11:"revision_id";a:1:{i:0;s:11:"revision_id";}}}}',
  ])
  ->values([
    'collection' => 'entity.storage_schema.sql',
    'name' => 'taxonomy_term.field_schema_data.layout_builder__layout',
    'value' => 'a:1:{s:37:"taxonomy_term__layout_builder__layout";a:4:{s:11:"description";s:60:"Data storage for taxonomy_term field layout_builder__layout.";s:6:"fields";a:7:{s:6:"bundle";a:5:{s:4:"type";s:13:"varchar_ascii";s:6:"length";i:128;s:8:"not null";b:1;s:7:"default";s:0:"";s:11:"description";s:88:"The field instance bundle to which this row belongs, used when deleting a field instance";}s:7:"deleted";a:5:{s:4:"type";s:3:"int";s:4:"size";s:4:"tiny";s:8:"not null";b:1;s:7:"default";i:0;s:11:"description";s:60:"A boolean indicating whether this data item has been deleted";}s:9:"entity_id";a:4:{s:4:"type";s:3:"int";s:8:"unsigned";b:1;s:8:"not null";b:1;s:11:"description";s:38:"The entity id this data is attached to";}s:11:"revision_id";a:4:{s:4:"type";s:3:"int";s:8:"unsigned";b:1;s:8:"not null";b:1;s:11:"description";s:47:"The entity revision id this data is attached to";}s:8:"langcode";a:5:{s:4:"type";s:13:"varchar_ascii";s:6:"length";i:32;s:8:"not null";b:1;s:7:"default";s:0:"";s:11:"description";s:37:"The language code for this data item.";}s:5:"delta";a:4:{s:4:"type";s:3:"int";s:8:"unsigned";b:1;s:8:"not null";b:1;s:11:"description";s:67:"The sequence number for this data item, used for multi-value fields";}s:30:"layout_builder__layout_section";a:4:{s:4:"type";s:4:"blob";s:4:"size";s:6:"normal";s:9:"serialize";b:1;s:8:"not null";b:0;}}s:11:"primary key";a:4:{i:0;s:9:"entity_id";i:1;s:7:"deleted";i:2;s:5:"delta";i:3;s:8:"langcode";}s:7:"indexes";a:2:{s:6:"bundle";a:1:{i:0;s:6:"bundle";}s:11:"revision_id";a:1:{i:0;s:11:"revision_id";}}}}',
  ])
  ->execute();
$connection->update('key_value')
  ->fields([
    'collection' => 'entity.definitions.bundle_field_map',
    'name' => 'node',
    'value' => 'a:5:{s:11:"field_image";a:2:{s:4:"type";s:5:"image";s:7:"bundles";a:1:{s:7:"article";s:7:"article";}}s:7:"comment";a:2:{s:4:"type";s:7:"comment";s:7:"bundles";a:1:{s:7:"article";s:7:"article";}}s:10:"field_tags";a:2:{s:4:"type";s:16:"entity_reference";s:7:"bundles";a:1:{s:7:"article";s:7:"article";}}s:4:"body";a:2:{s:4:"type";s:17:"text_with_summary";s:7:"bundles";a:2:{s:4:"page";s:4:"page";s:7:"article";s:7:"article";}}s:22:"layout_builder__layout";a:2:{s:4:"type";s:14:"layout_section";s:7:"bundles";a:2:{s:7:"article";s:7:"article";s:4:"page";s:4:"page";}}}',
  ])
  ->condition('collection', 'entity.definitions.bundle_field_map')
  ->condition('name', 'node')
  ->execute();

$taxonomy_bundle_field_map = $connection->select('key_value')
  ->fields('key_value', ['value'])
  ->condition('collection', 'entity.definitions.bundle_field_map')
  ->condition('name', 'taxonomy_term')
  ->execute()
  ->fetchField();
$taxonomy_bundle_field_map = unserialize($taxonomy_bundle_field_map);
$taxonomy_bundle_field_map['layout_builder__layout'] = [
  'type' => 'layout_section',
  'bundles' => [
    'forums' => 'forums',
  ],
];
$connection->update('key_value')
  ->fields([
    'collection' => 'entity.definitions.bundle_field_map',
    'name' => 'taxonomy_term',
    'value' => serialize($taxonomy_bundle_field_map),
  ])
  ->condition('collection', 'entity.definitions.bundle_field_map')
  ->condition('name', 'taxonomy_term')
  ->execute();

// Create tables for the layout builder field.
$connection->schema()->createTable('node__layout_builder__layout', [
  'fields' => [
    'bundle' => [
      'type' => 'varchar_ascii',
      'not null' => TRUE,
      'length' => '128',
      'default' => '',
    ],
    'deleted' => [
      'type' => 'int',
      'not null' => TRUE,
      'size' => 'tiny',
      'default' => '0',
    ],
    'entity_id' => [
      'type' => 'int',
      'not null' => TRUE,
      'size' => 'normal',
      'unsigned' => TRUE,
    ],
    'revision_id' => [
      'type' => 'int',
      'not null' => TRUE,
      'size' => 'normal',
      'unsigned' => TRUE,
    ],
    'langcode' => [
      'type' => 'varchar_ascii',
      'not null' => TRUE,
      'length' => '32',
      'default' => '',
    ],
    'delta' => [
      'type' => 'int',
      'not null' => TRUE,
      'size' => 'normal',
      'unsigned' => TRUE,
    ],
    'layout_builder__layout_section' => [
      'type' => 'blob',
      'not null' => FALSE,
      'size' => 'normal',
    ],
  ],
  'primary key' => [
    'entity_id',
    'deleted',
    'delta',
    'langcode',
  ],
  'indexes' => [
    'bundle' => [
      'bundle',
    ],
    'revision_id' => [
      'revision_id',
    ],
  ],
  'mysql_character_set' => 'utf8mb4',
]);
$connection->schema()->createTable('node_revision__layout_builder__layout', [
  'fields' => [
    'bundle' => [
      'type' => 'varchar_ascii',
      'not null' => TRUE,
      'length' => '128',
      'default' => '',
    ],
    'deleted' => [
      'type' => 'int',
      'not null' => TRUE,
      'size' => 'tiny',
      'default' => '0',
    ],
    'entity_id' => [
      'type' => 'int',
      'not null' => TRUE,
      'size' => 'normal',
      'unsigned' => TRUE,
    ],
    'revision_id' => [
      'type' => 'int',
      'not null' => TRUE,
      'size' => 'normal',
      'unsigned' => TRUE,
    ],
    'langcode' => [
      'type' => 'varchar_ascii',
      'not null' => TRUE,
      'length' => '32',
      'default' => '',
    ],
    'delta' => [
      'type' => 'int',
      'not null' => TRUE,
      'size' => 'normal',
      'unsigned' => TRUE,
    ],
    'layout_builder__layout_section' => [
      'type' => 'blob',
      'not null' => FALSE,
      'size' => 'normal',
    ],
  ],
  'primary key' => [
    'entity_id',
    'revision_id',
    'deleted',
    'delta',
    'langcode',
  ],
  'indexes' => [
    'bundle' => [
      'bundle',
    ],
    'revision_id' => [
      'revision_id',
    ],
  ],
  'mysql_character_set' => 'utf8mb4',
]);
$connection->schema()->createTable('taxonomy_term__layout_builder__layout', [
  'fields' => [
    'bundle' => [
      'type' => 'varchar_ascii',
      'not null' => TRUE,
      'length' => '128',
      'default' => '',
    ],
    'deleted' => [
      'type' => 'int',
      'not null' => TRUE,
      'size' => 'tiny',
      'default' => '0',
    ],
    'entity_id' => [
      'type' => 'int',
      'not null' => TRUE,
      'size' => 'normal',
      'unsigned' => TRUE,
    ],
    'revision_id' => [
      'type' => 'int',
      'not null' => TRUE,
      'size' => 'normal',
      'unsigned' => TRUE,
    ],
    'langcode' => [
      'type' => 'varchar_ascii',
      'not null' => TRUE,
      'length' => '32',
      'default' => '',
    ],
    'delta' => [
      'type' => 'int',
      'not null' => TRUE,
      'size' => 'normal',
      'unsigned' => TRUE,
    ],
    'layout_builder__layout_section' => [
      'type' => 'blob',
      'not null' => FALSE,
      'size' => 'normal',
    ],
  ],
  'primary key' => [
    'entity_id',
    'deleted',
    'delta',
    'langcode',
  ],
  'indexes' => [
    'bundle' => [
      'bundle',
    ],
    'revision_id' => [
      'revision_id',
    ],
  ],
  'mysql_character_set' => 'utf8mb4',
]);
