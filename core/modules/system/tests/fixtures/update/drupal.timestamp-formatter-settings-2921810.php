<?php

/**
 * @file
 * Provides database changes for testing the TimestampFormatter upgrade path.
 *
 * @see \Drupal\Tests\system\Functional\Update\TimestampFormatterSettingsUpdateTest
 */

use Drupal\Core\Database\Database;
use Drupal\field\Entity\FieldStorageConfig;

$connection = Database::getConnection();

// Add a new timestamp field 'field_foo'.
$connection->insert('config')
  ->fields(['collection', 'name', 'data'])->values([
    'collection' => '',
    'name' => 'field.storage.node.field_foo',
    'data' => $field_storage = 'a:16:{s:4:"uuid";s:36:"815278cf-a977-4700-aad9-d58034de0115";s:8:"langcode";s:2:"en";s:6:"status";b:1;s:12:"dependencies";a:1:{s:6:"module";a:1:{i:0;s:4:"node";}}s:2:"id";s:14:"node.field_foo";s:10:"field_name";s:9:"field_foo";s:11:"entity_type";s:4:"node";s:4:"type";s:9:"timestamp";s:8:"settings";a:0:{}s:6:"module";s:4:"core";s:6:"locked";b:0;s:11:"cardinality";i:1;s:12:"translatable";b:1;s:7:"indexes";a:0:{}s:22:"persist_with_no_fields";b:0;s:14:"custom_storage";b:0;}',
  ])->values([
    'collection' => '',
    'name' => 'field.field.node.page.field_foo',
    'data' => 'a:16:{s:4:"uuid";s:36:"ea669e7e-532e-41ad-9322-13ba6a9901b0";s:8:"langcode";s:2:"en";s:6:"status";b:1;s:12:"dependencies";a:1:{s:6:"config";a:2:{i:0;s:28:"field.storage.node.field_foo";i:1;s:14:"node.type.page";}}s:2:"id";s:19:"node.page.field_foo";s:10:"field_name";s:9:"field_foo";s:11:"entity_type";s:4:"node";s:6:"bundle";s:4:"page";s:5:"label";s:3:"Foo";s:11:"description";s:0:"";s:8:"required";b:0;s:12:"translatable";b:0;s:13:"default_value";a:1:{i:0;a:1:{s:5:"value";i:1511630653;}}s:22:"default_value_callback";s:0:"";s:8:"settings";a:0:{}s:10:"field_type";s:9:"timestamp";}',
  ])->execute();

$connection->insert('key_value')
  ->fields(['collection', 'name', 'value'])
  ->values([
    'collection' => 'config.entity.key_store.field_config',
    'name' => 'uuid:ea669e7e-532e-41ad-9322-13ba6a9901b0',
    'value' => 'a:1:{i:0;s:31:"field.field.node.page.field_foo";}',
  ])
  ->values([
    'collection' => 'config.entity.key_store.field_storage_config',
    'name' => 'uuid:815278cf-a977-4700-aad9-d58034de0115',
    'value' => 'a:1:{i:0;s:28:"field.storage.node.field_foo";}',
  ])
  ->values([
    'collection' => 'entity.storage_schema.sql',
    'name' => 'node.field_schema_data.field_foo',
    'value' => 'a:2:{s:15:"node__field_foo";a:4:{s:11:"description";s:38:"Data storage for node field field_foo.";s:6:"fields";a:7:{s:6:"bundle";a:5:{s:4:"type";s:13:"varchar_ascii";s:6:"length";i:128;s:8:"not null";b:1;s:7:"default";s:0:"";s:11:"description";s:88:"The field instance bundle to which this row belongs, used when deleting a field instance";}s:7:"deleted";a:5:{s:4:"type";s:3:"int";s:4:"size";s:4:"tiny";s:8:"not null";b:1;s:7:"default";i:0;s:11:"description";s:60:"A boolean indicating whether this data item has been deleted";}s:9:"entity_id";a:4:{s:4:"type";s:3:"int";s:8:"unsigned";b:1;s:8:"not null";b:1;s:11:"description";s:38:"The entity id this data is attached to";}s:11:"revision_id";a:4:{s:4:"type";s:3:"int";s:8:"unsigned";b:1;s:8:"not null";b:1;s:11:"description";s:47:"The entity revision id this data is attached to";}s:8:"langcode";a:5:{s:4:"type";s:13:"varchar_ascii";s:6:"length";i:32;s:8:"not null";b:1;s:7:"default";s:0:"";s:11:"description";s:37:"The language code for this data item.";}s:5:"delta";a:4:{s:4:"type";s:3:"int";s:8:"unsigned";b:1;s:8:"not null";b:1;s:11:"description";s:67:"The sequence number for this data item, used for multi-value fields";}s:15:"field_foo_value";a:2:{s:4:"type";s:3:"int";s:8:"not null";b:1;}}s:11:"primary key";a:4:{i:0;s:9:"entity_id";i:1;s:7:"deleted";i:2;s:5:"delta";i:3;s:8:"langcode";}s:7:"indexes";a:2:{s:6:"bundle";a:1:{i:0;s:6:"bundle";}s:11:"revision_id";a:1:{i:0;s:11:"revision_id";}}}s:24:"node_revision__field_foo";a:4:{s:11:"description";s:50:"Revision archive storage for node field field_foo.";s:6:"fields";a:7:{s:6:"bundle";a:5:{s:4:"type";s:13:"varchar_ascii";s:6:"length";i:128;s:8:"not null";b:1;s:7:"default";s:0:"";s:11:"description";s:88:"The field instance bundle to which this row belongs, used when deleting a field instance";}s:7:"deleted";a:5:{s:4:"type";s:3:"int";s:4:"size";s:4:"tiny";s:8:"not null";b:1;s:7:"default";i:0;s:11:"description";s:60:"A boolean indicating whether this data item has been deleted";}s:9:"entity_id";a:4:{s:4:"type";s:3:"int";s:8:"unsigned";b:1;s:8:"not null";b:1;s:11:"description";s:38:"The entity id this data is attached to";}s:11:"revision_id";a:4:{s:4:"type";s:3:"int";s:8:"unsigned";b:1;s:8:"not null";b:1;s:11:"description";s:47:"The entity revision id this data is attached to";}s:8:"langcode";a:5:{s:4:"type";s:13:"varchar_ascii";s:6:"length";i:32;s:8:"not null";b:1;s:7:"default";s:0:"";s:11:"description";s:37:"The language code for this data item.";}s:5:"delta";a:4:{s:4:"type";s:3:"int";s:8:"unsigned";b:1;s:8:"not null";b:1;s:11:"description";s:67:"The sequence number for this data item, used for multi-value fields";}s:15:"field_foo_value";a:2:{s:4:"type";s:3:"int";s:8:"not null";b:1;}}s:11:"primary key";a:5:{i:0;s:9:"entity_id";i:1;s:11:"revision_id";i:2;s:7:"deleted";i:3;s:5:"delta";i:4;s:8:"langcode";}s:7:"indexes";a:2:{s:6:"bundle";a:1:{i:0;s:6:"bundle";}s:11:"revision_id";a:1:{i:0;s:11:"revision_id";}}}}',
  ])
  ->execute();

$data = $connection->select('key_value')
  ->fields('key_value', ['value'])
  ->condition('collection', 'entity.definitions.bundle_field_map')
  ->condition('name', 'node')
  ->execute()
  ->fetchField();
$data = unserialize($data);
$data['field_foo']['type'] = 'timestamp';
$data['field_foo']['bundles']['page'] = 'page';
$connection->update('key_value')
  ->fields(['value' => serialize($data)])
  ->condition('collection', 'entity.definitions.bundle_field_map')
  ->condition('name', 'node')
  ->execute();

$data = $connection->select('key_value')
  ->fields('key_value', ['value'])
  ->condition('collection', 'entity.definitions.installed')
  ->condition('name', 'node.field_storage_definitions')
  ->execute()
  ->fetchField();
$data = unserialize($data);
$data['field_foo'] = new FieldStorageConfig(unserialize($field_storage));
$connection->update('key_value')
  ->fields(['value' => serialize($data)])
  ->condition('collection', 'entity.definitions.installed')
  ->condition('name', 'node.field_storage_definitions')
  ->execute();

// Add the new field to default entity view display.
$config = $connection->select('config')
  ->fields('config', ['data'])
  ->condition('collection', '')
  ->condition('name', 'core.entity_view_display.node.page.default')
  ->execute()
  ->fetchField();
$config = unserialize($config);
$config['content']['field_foo'] = [
  'type' => 'timestamp',
  'label' => 'hidden',
  'weight' => 0,
  'region' => 'content',
  'settings' => [
    'date_format' => 'custom',
    'custom_date_format' => 'Y-m-d',
    'timezone' => '',
  ],
  'third_party_settings' => [],
];
$config['third_party_settings']['layout_builder'] = [
  'enabled' => TRUE,
  'allow_custom' => FALSE,
  'sections' => [
    [
      'layout_id' => 'layout_onecol',
      'layout_settings' => [
        'label' => '',
      ],
      'components' => [
        '93bf4359-06a6-4263-bce9-15c90dc8f357' => [
          'uuid' => '93bf4359-06a6-4263-bce9-15c90dc8f357',
          'region' => 'content',
          'configuration' => [
            'id' => 'field_block:node:page:field_foo',
            'label_display' => '0',
            'context_mapping' => [
              'entity' => 'layout_builder.entity',
            ],
            'formatter' => [
              'type' => 'timestamp',
              'label' => 'inline',
              'settings' => [
                'date_format' => 'custom',
                'custom_date_format' => 'Y-m-d',
                'timezone' => '',
              ],
              'third_party_settings' => [],
            ],
          ],
          'weight' => 0,
          'additional' => [],

        ],
      ],
      'third_party_settings' => [],
    ],
  ],
];

$connection->update('config')
  ->fields(['data' => serialize($config)])
  ->condition('collection', '')
  ->condition('name', 'core.entity_view_display.node.page.default')
  ->execute();
