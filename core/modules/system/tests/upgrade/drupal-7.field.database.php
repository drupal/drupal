<?php

/**
 * @file
 * Database additions for field variables. Used in FieldUpgradePathTest.
 *
 * The drupal-7.bare.database.php file is imported before this dump, so the
 * two form the database structure expected in tests altogether.
 */

// Add a 'bundle settings' variable for article nodes.
$value = array(
  'view_modes' => array(
    'teaser' => array(
      'custom_settings' => 1,
    ),
    'full' => array(
      'custom_settings' => 0,
    ),
    'rss' => array(
      'custom_settings' => 0,
    ),
    'search_index' => array(
      'custom_settings' => 0,
    ),
    'search_result' => array(
      'custom_settings' => 0,
    ),
  ),
  'extra_fields' => array(
    'form' => array(
      'title' => array(
        'weight' => -5,
        'visible' => 1,
      ),
    ),
    'display' => array(
      'language' => array(
        'default' => array(
          'weight' => -1,
          'visible' => 1,
        ),
        'teaser' => array(
          'weight' => 0,
          'visible' => 0,
        ),
      ),
    ),
  ),
);
db_insert('variable')
  ->fields(array(
    'name' => 'field_bundle_settings_node__article',
    'value' => serialize($value),
  ))
  ->execute();

// Add a field shared across different entity types (instance on article nodes
// and users).
$field_id = db_insert('field_config')
  ->fields(array(
    'field_name' => 'test_shared_field',
    'type' => 'text',
    'module' => 'text',
    'active' => 1,
    'storage_type' => 'field_sql_storage',
    'storage_module' => 'field_sql_storage',
    'storage_active' => 1,
    'locked' => 0,
    'data' => serialize(array(
      'entity_types' => array(),
      'settings' => array(
        'max_length' => 255,
      ),
      'storage' => array(
        'type' => 'field_sql_storage',
        'settings' => array(),
        'module' => 'field_sql_storage',
        'active' => 1,
      ),
      'indexes' => array(
        'format' => array(0 => 'format')
      ),
    )),
    'cardinality' => 1,
    'translatable' => 0,
    'deleted' => 0,
  ))
  ->execute();
db_insert('field_config_instance')
  ->fields(array(
    'field_id' => $field_id,
    'field_name' => 'test_shared_field',
    'entity_type' => 'node',
    'bundle' => 'article',
    'data' => serialize(array(
      'label' => 'Long text',
      'description' => '',
      'required' => FALSE,
      'widget' => array(
        'type' => 'text_textfield',
        'weight' => 4,
        'module' => 'text',
        'active' => 1,
        'settings' => array(
          'size' => 60,
        ),
      ),
      'settings' => array(
        'text_processing' => 0,
        'user_register_form' => FALSE,
      ),
      'display' => array(
        'default' => array(
          'label' => 'above',
          'type' => 'text_default',
          'settings' => array(),
          'module' => 'text',
          'weight' => 10,
        ),
      ),
    )),
    'deleted' => 0
  ))
  ->execute();
db_insert('field_config_instance')
  ->fields(array(
    'field_id' => $field_id,
    'field_name' => 'test_shared_field',
    'entity_type' => 'user',
    'bundle' => 'user',
    'data' => serialize(array(
      'label' => 'Shared field',
      'description' => '',
      'required' => FALSE,
      'widget' => array(
        'type' => 'text_textfield',
        'weight' => 4,
        'module' => 'text',
        'active' => 1,
        'settings' => array(
          'size' => 60,
        ),
      ),
      'settings' => array(
        'text_processing' => 0,
        'user_register_form' => FALSE,
      ),
      'display' => array(
        'default' => array(
          'label' => 'above',
          'type' => 'text_default',
          'settings' => array(),
          'module' => 'text',
          'weight' => 10,
        ),
      ),
    )),
    'deleted' => 0
  ))
  ->execute();
// Create the corresponding storage tables.
$schema = array(
  'fields' => array(
    'entity_type' => array(
      'type' => 'varchar',
      'length' => 128,
      'not null' => TRUE,
      'default' => '',
    ),
    'bundle' => array(
      'type' => 'varchar',
      'length' => 128,
      'not null' => TRUE,
      'default' => '',
    ),
    'deleted' => array(
      'type' => 'int',
      'size' => 'tiny',
      'not null' => TRUE,
      'default' => 0,
    ),
    'entity_id' => array(
      'type' => 'int',
      'unsigned' => TRUE,
      'not null' => TRUE,
    ),
    'revision_id' => array(
      'type' => 'int',
      'unsigned' => TRUE,
      'not null' => FALSE,
    ),
    'language' => array(
      'type' => 'varchar',
      'length' => 32,
      'not null' => TRUE,
      'default' => '',
    ),
    'delta' => array(
      'type' => 'int',
      'unsigned' => TRUE,
      'not null' => TRUE,
    ),
    'test_shared_field_value' => array(
      'type' => 'varchar',
      'length' => 255,
      'not null' => FALSE,
    ),
    'test_shared_field_format' => array(
      'type' => 'varchar',
      'length' => 255,
      'not null' => FALSE,
    ),
  ),
  'primary key' => array(
    'entity_type',
    'entity_id',
    'deleted',
    'delta',
    'language',
  ),
  'indexes' => array(
    'entity_type' => array(
      'entity_type',
    ),
    'bundle' => array(
      'bundle',
    ),
    'deleted' => array(
      'deleted',
    ),
    'entity_id' => array(
      'entity_id',
    ),
    'revision_id' => array(
      'revision_id',
    ),
    'language' => array(
      'language',
    ),
    'test_shared_field_format' => array(
      'test_shared_field_format',
    ),
  ),
  'module' => 'field_sql_storage',
  'name' => 'field_data_test_shared_field',
);
db_create_table('field_data_test_shared_field', $schema);
$schema['primary key'] = array(
  'entity_type',
  'entity_id',
  'revision_id',
  'deleted',
  'delta',
  'language',
);
$schema['name'] = 'field_revision_test_shared_field';
db_create_table('field_revision_test_shared_field', $schema);

// Add a value for the 'test_shared_field' field on user 1.
$field_data_row = array(
  'entity_type' => 'user',
  'bundle' => 'user',
  'deleted' => '0',
  'entity_id' => '1',
  'revision_id' => '1',
  'language' => 'und',
  'delta' => '0',
  'test_shared_field_value' => 'Shared field: value for user 1',
  'test_shared_field_format' => 'filtered_html',
);
db_insert('field_data_test_shared_field')
  ->fields($field_data_row)
  ->execute();
db_insert('field_revision_test_shared_field')
  ->fields($field_data_row)
  ->execute();

// Add one node.
db_insert('node')
  ->fields(array(
    'nid' => '1',
    'vid' => '1',
    'type' => 'article',
    'language' => 'und',
    'title' => 'node title 1 rev 1',
    'uid' => '1',
    'status' => '1',
    'created' => '1262754000',
    'changed' => '1338795201',
    'comment' => '0',
    'promote' => '1',
    'sticky' => '0',
    'tnid' => '0',
    'translate' => '0',
  ))
  ->execute();
db_insert('node_revision')
  ->fields(array(
    'nid' => '1',
    'vid' => '1',
    'uid' => '1',
    'title' => 'node title 1 rev 1',
    'log' => 'added 0 node',
    'timestamp' => '1338795201',
    'status' => '1',
    'comment' => '0',
    'promote' => '1',
    'sticky' => '0',
  ))
  ->execute();
// Add a value for the 'body' field.
$field_data_row = array(
  'entity_type' => 'node',
  'bundle' => 'article',
  'deleted' => '0',
  'entity_id' => '1',
  'revision_id' => '1',
  'language' => 'und',
  'delta' => '0',
  'body_value' => 'Some value',
  'body_summary' => 'Some summary',
  'body_format' => 'filtered_html',
);
db_insert('field_data_body')
  ->fields($field_data_row)
  ->execute();
db_insert('field_revision_body')
  ->fields($field_data_row)
  ->execute();
// Add a value for the 'test_shared_field' field.
$field_data_row = array(
  'entity_type' => 'node',
  'bundle' => 'article',
  'deleted' => '0',
  'entity_id' => '1',
  'revision_id' => '1',
  'language' => 'und',
  'delta' => '0',
  'test_shared_field_value' => 'Shared field: value for node 1',
  'test_shared_field_format' => 'filtered_html',
);
db_insert('field_data_test_shared_field')
  ->fields($field_data_row)
  ->execute();
db_insert('field_revision_test_shared_field')
  ->fields($field_data_row)
  ->execute();

// Add a deleted field and instance.
$field_id = db_insert('field_config')
  ->fields(array(
    'field_name' => 'test_deleted_field',
    'type' => 'text',
    'module' => 'text',
    'active' => 1,
    'storage_type' => 'field_sql_storage',
    'storage_module' => 'field_sql_storage',
    'storage_active' => 1,
    'locked' => 0,
    'data' => serialize(array(
      'entity_types' => array(),
      'settings' => array(
        'max_length' => 255,
      ),
      'storage' => array(
        'type' => 'field_sql_storage',
        'settings' => array(),
        'module' => 'field_sql_storage',
        'active' => 1,
      ),
      'indexes' => array(
        'format' => array(0 => 'format')
      ),
    )),
    'cardinality' => 1,
    'translatable' => 0,
    'deleted' => 1,
  ))
  ->execute();
db_insert('field_config_instance')
  ->fields(array(
    'field_id' => $field_id,
    'field_name' => 'test_deleted_field',
    'entity_type' => 'node',
    'bundle' => 'article',
    'data' => serialize(array(
      'label' => 'Long text',
      'description' => '',
      'required' => FALSE,
      'widget' => array(
        'type' => 'text_textarea',
        'weight' => 4,
        'module' => 'text',
        'active' => 1,
        'settings' => array(
          'rows' => 7
        ),
      ),
      'settings' => array(
        'text_processing' => 0,
        'user_register_form' => FALSE,
      ),
      'display' => array(
        'default' => array(
          'label' => 'above',
          'type' => 'text_default',
          'settings' => array(),
          'module' => 'text',
          'weight' => 10,
        ),
      ),
    )),
    'deleted' => 1
  ))
  ->execute();

// Add data tables for the deleted field.
db_create_table("field_deleted_data_{$field_id}", array(
  'fields' => array(
    'entity_type' => array(
      'type' => 'varchar',
      'length' => 128,
      'not null' => TRUE,
      'default' => '',
    ),
    'bundle' => array(
      'type' => 'varchar',
      'length' => 128,
      'not null' => TRUE,
      'default' => '',
    ),
    'deleted' => array(
      'type' => 'int',
      'size' => 'tiny',
      'not null' => TRUE,
      'default' => 0,
    ),
    'entity_id' => array(
      'type' => 'int',
      'unsigned' => TRUE,
      'not null' => TRUE,
    ),
    'revision_id' => array(
      'type' => 'int',
      'unsigned' => TRUE,
      'not null' => FALSE,
    ),
    'language' => array(
      'type' => 'varchar',
      'length' => 32,
      'not null' => TRUE,
      'default' => '',
    ),
    'delta' => array(
      'type' => 'int',
      'unsigned' => TRUE,
      'not null' => TRUE,
    ),
    'test_deleted_field_value' => array(
      'type' => 'text',
      'size' => 'big',
      'not null' => FALSE,
    ),
    'test_deleted_field_format' => array(
      'type' => 'varchar',
      'length' => 255,
      'not null' => FALSE,
    ),
  ),
  'primary key' => array(
    'entity_type',
    'entity_id',
    'deleted',
    'delta',
    'language',
  ),
  'indexes' => array(
    'entity_type' => array(
      'entity_type',
    ),
    'bundle' => array(
      'bundle',
    ),
    'deleted' => array(
      'deleted',
    ),
    'entity_id' => array(
      'entity_id',
    ),
    'revision_id' => array(
      'revision_id',
    ),
    'language' => array(
      'language',
    ),
    'test_deleted_field_format' => array(
      'test_deleted_field_format',
    ),
  ),
  'module' => 'field_sql_storage',
  'name' => "field_deleted_data_{$field_id}",
));
db_create_table("field_deleted_revision_{$field_id}", array(
  'fields' => array(
    'entity_type' => array(
      'type' => 'varchar',
      'length' => 128,
      'not null' => TRUE,
      'default' => '',
    ),
    'bundle' => array(
      'type' => 'varchar',
      'length' => 128,
      'not null' => TRUE,
      'default' => '',
    ),
    'deleted' => array(
      'type' => 'int',
      'size' => 'tiny',
      'not null' => TRUE,
      'default' => 0,
    ),
    'entity_id' => array(
      'type' => 'int',
      'unsigned' => TRUE,
      'not null' => TRUE,
    ),
    'revision_id' => array(
      'type' => 'int',
      'unsigned' => TRUE,
      'not null' => TRUE,
    ),
    'language' => array(
      'type' => 'varchar',
      'length' => 32,
      'not null' => TRUE,
      'default' => '',
    ),
    'delta' => array(
      'type' => 'int',
      'unsigned' => TRUE,
      'not null' => TRUE,
    ),
    'test_deleted_field_value' => array(
      'type' => 'text',
      'size' => 'big',
      'not null' => FALSE,
    ),
    'test_deleted_field_format' => array(
      'type' => 'varchar',
      'length' => 255,
      'not null' => FALSE,
    ),
  ),
  'primary key' => array(
    'entity_type',
    'entity_id',
    'revision_id',
    'deleted',
    'delta',
    'language',
  ),
  'indexes' => array(
    'entity_type' => array(
      'entity_type',
    ),
    'bundle' => array(
      'bundle',
    ),
    'deleted' => array(
      'deleted',
    ),
    'entity_id' => array(
      'entity_id',
    ),
    'revision_id' => array(
      'revision_id',
    ),
    'language' => array(
      'language',
    ),
    'test_deleted_field_format' => array(
      'test_deleted_field_format',
    ),
  ),
  'module' => 'field_sql_storage',
  'name' => "field_deleted_revision_{$field_id}",
));

// Add some deleted field data.
$field_data_row = array(
  'entity_type' => 'node',
  'bundle' => 'article',
  'deleted' => '0',
  'entity_id' => '2',
  'revision_id' => '2',
  'language' => 'und',
  'delta' => '0',
  'test_deleted_field_value' => 'Some deleted value',
);
db_insert("field_deleted_data_{$field_id}")
  ->fields($field_data_row)
  ->execute();
db_insert("field_deleted_revision_{$field_id}")
  ->fields($field_data_row)
  ->execute();

