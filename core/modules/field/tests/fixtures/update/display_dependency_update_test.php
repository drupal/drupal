<?php
// phpcs:ignoreFile
/**
 * @file
 * A database agnostic dump for testing purposes.
 */

use Drupal\Core\Database\Database;

$connection = Database::getConnection();

$extensions = $connection->select('config')
  ->fields('config', ['data'])
  ->condition('collection', '')
  ->condition('name', 'core.extension')
  ->execute()
  ->fetchField();
$extensions = unserialize($extensions);
$extensions['module']['field_dynamic_dependencies_test'] = 0;
$connection->update('config')
  ->fields(['data' => serialize($extensions)])
  ->condition('collection', '')
  ->condition('name', 'core.extension')
  ->execute();

$connection->insert('config')
->fields([
  'collection',
  'name',
  'data',
])
->values([
  'collection' => '',
  'name' => 'core.entity_form_display.node.test_content_type.default',
  'data' => 'a:10:{s:4:"uuid";s:36:"dd81b16c-9941-470b-8df9-4b9208cbc597";s:8:"langcode";s:2:"en";s:6:"status";b:1;s:12:"dependencies";a:2:{s:6:"config";a:3:{i:0;s:53:"field.field.node.test_content_type.field_test_field_1";i:1;s:53:"field.field.node.test_content_type.field_test_field_2";i:2;s:27:"node.type.test_content_type";}s:6:"module";a:2:{i:0;s:31:"field_dynamic_dependencies_test";i:1;s:6:"locale";}}s:2:"id";s:30:"node.test_content_type.default";s:16:"targetEntityType";s:4:"node";s:6:"bundle";s:17:"test_content_type";s:4:"mode";s:7:"default";s:7:"content";a:3:{s:18:"field_test_field_1";a:5:{s:4:"type";s:31:"test_field_dynamic_dependencies";s:6:"weight";i:0;s:6:"region";s:7:"content";s:8:"settings";a:1:{s:16:"dependent_module";s:8:"language";}s:20:"third_party_settings";a:0:{}}s:18:"field_test_field_2";a:5:{s:4:"type";s:31:"test_field_dynamic_dependencies";s:6:"weight";i:1;s:6:"region";s:7:"content";s:8:"settings";a:1:{s:16:"dependent_module";s:6:"locale";}s:20:"third_party_settings";a:0:{}}s:8:"langcode";a:5:{s:4:"type";s:15:"language_select";s:6:"weight";i:2;s:6:"region";s:7:"content";s:8:"settings";a:1:{s:14:"include_locked";b:1;}s:20:"third_party_settings";a:0:{}}}s:6:"hidden";a:7:{s:7:"created";b:1;s:4:"path";b:1;s:7:"promote";b:1;s:6:"status";b:1;s:6:"sticky";b:1;s:5:"title";b:1;s:3:"uid";b:1;}}',
])
->values([
  'collection' => '',
  'name' => 'core.entity_view_display.node.test_content_type.default',
  'data' => 'a:10:{s:4:"uuid";s:36:"d6efbc46-bb1c-47b1-b26f-a14d878e4b77";s:8:"langcode";s:2:"en";s:6:"status";b:1;s:12:"dependencies";a:2:{s:6:"config";a:3:{i:0;s:53:"field.field.node.test_content_type.field_test_field_1";i:1;s:53:"field.field.node.test_content_type.field_test_field_2";i:2;s:27:"node.type.test_content_type";}s:6:"module";a:3:{i:0;s:31:"field_dynamic_dependencies_test";i:1;s:6:"locale";i:2;s:4:"user";}}s:2:"id";s:30:"node.test_content_type.default";s:16:"targetEntityType";s:4:"node";s:6:"bundle";s:17:"test_content_type";s:4:"mode";s:7:"default";s:7:"content";a:2:{s:18:"field_test_field_1";a:6:{s:4:"type";s:31:"test_field_dynamic_dependencies";s:5:"label";s:5:"above";s:8:"settings";a:1:{s:16:"dependent_module";s:8:"language";}s:20:"third_party_settings";a:0:{}s:6:"weight";i:0;s:6:"region";s:7:"content";}s:18:"field_test_field_2";a:6:{s:4:"type";s:31:"test_field_dynamic_dependencies";s:5:"label";s:5:"above";s:8:"settings";a:1:{s:16:"dependent_module";s:6:"locale";}s:20:"third_party_settings";a:0:{}s:6:"weight";i:1;s:6:"region";s:7:"content";}}s:6:"hidden";a:2:{s:8:"langcode";b:1;s:5:"links";b:1;}}',
])
->values([
  'collection' => '',
  'name' => 'field.field.node.test_content_type.field_test_field_1',
  'data' => 'a:16:{s:4:"uuid";s:36:"39d3a686-41c0-448c-9ea8-8d99c640a547";s:8:"langcode";s:2:"en";s:6:"status";b:1;s:12:"dependencies";a:2:{s:6:"config";a:2:{i:0;s:37:"field.storage.node.field_test_field_1";i:1;s:27:"node.type.test_content_type";}s:6:"module";a:1:{i:0;s:31:"field_dynamic_dependencies_test";}}s:2:"id";s:41:"node.test_content_type.field_test_field_1";s:10:"field_name";s:18:"field_test_field_1";s:11:"entity_type";s:4:"node";s:6:"bundle";s:17:"test_content_type";s:5:"label";s:12:"test_field_1";s:11:"description";s:0:"";s:8:"required";b:0;s:12:"translatable";b:0;s:13:"default_value";a:0:{}s:22:"default_value_callback";s:0:"";s:8:"settings";a:0:{}s:10:"field_type";s:31:"test_field_dynamic_dependencies";}',
])
->values([
  'collection' => '',
  'name' => 'field.field.node.test_content_type.field_test_field_2',
  'data' => 'a:16:{s:4:"uuid";s:36:"36b69930-c20e-453c-93da-5e2459a6569c";s:8:"langcode";s:2:"en";s:6:"status";b:1;s:12:"dependencies";a:2:{s:6:"config";a:2:{i:0;s:37:"field.storage.node.field_test_field_2";i:1;s:27:"node.type.test_content_type";}s:6:"module";a:1:{i:0;s:31:"field_dynamic_dependencies_test";}}s:2:"id";s:41:"node.test_content_type.field_test_field_2";s:10:"field_name";s:18:"field_test_field_2";s:11:"entity_type";s:4:"node";s:6:"bundle";s:17:"test_content_type";s:5:"label";s:12:"test_field_2";s:11:"description";s:0:"";s:8:"required";b:0;s:12:"translatable";b:0;s:13:"default_value";a:0:{}s:22:"default_value_callback";s:0:"";s:8:"settings";a:0:{}s:10:"field_type";s:31:"test_field_dynamic_dependencies";}',
])
->values([
  'collection' => '',
  'name' => 'field.storage.node.field_test_field_1',
  'data' => 'a:16:{s:4:"uuid";s:36:"38be0325-4846-43ef-82ca-10d27fefb627";s:8:"langcode";s:2:"en";s:6:"status";b:1;s:12:"dependencies";a:1:{s:6:"module";a:2:{i:0;s:31:"field_dynamic_dependencies_test";i:1;s:4:"node";}}s:2:"id";s:23:"node.field_test_field_1";s:10:"field_name";s:18:"field_test_field_1";s:11:"entity_type";s:4:"node";s:4:"type";s:31:"test_field_dynamic_dependencies";s:8:"settings";a:0:{}s:6:"module";s:31:"field_dynamic_dependencies_test";s:6:"locked";b:0;s:11:"cardinality";i:1;s:12:"translatable";b:1;s:7:"indexes";a:0:{}s:22:"persist_with_no_fields";b:0;s:14:"custom_storage";b:0;}',
])
->values([
  'collection' => '',
  'name' => 'field.storage.node.field_test_field_2',
  'data' => 'a:16:{s:4:"uuid";s:36:"2b736a4e-9c04-44a9-ac82-f443e2255afb";s:8:"langcode";s:2:"en";s:6:"status";b:1;s:12:"dependencies";a:1:{s:6:"module";a:2:{i:0;s:31:"field_dynamic_dependencies_test";i:1;s:4:"node";}}s:2:"id";s:23:"node.field_test_field_2";s:10:"field_name";s:18:"field_test_field_2";s:11:"entity_type";s:4:"node";s:4:"type";s:31:"test_field_dynamic_dependencies";s:8:"settings";a:0:{}s:6:"module";s:31:"field_dynamic_dependencies_test";s:6:"locked";b:0;s:11:"cardinality";i:1;s:12:"translatable";b:1;s:7:"indexes";a:0:{}s:22:"persist_with_no_fields";b:0;s:14:"custom_storage";b:0;}',
])
->values([
  'collection' => '',
  'name' => 'node.type.test_content_type',
  'data' => 'a:12:{s:4:"uuid";s:36:"8e1922b6-8ede-4749-87ff-a5ad99e602c6";s:8:"langcode";s:2:"en";s:6:"status";b:1;s:12:"dependencies";a:1:{s:6:"module";a:1:{i:0;s:7:"menu_ui";}}s:20:"third_party_settings";a:1:{s:7:"menu_ui";a:2:{s:15:"available_menus";a:1:{i:0;s:4:"main";}s:6:"parent";s:5:"main:";}}s:4:"name";s:17:"test_content_type";s:4:"type";s:17:"test_content_type";s:11:"description";N;s:4:"help";N;s:12:"new_revision";b:1;s:12:"preview_mode";i:1;s:17:"display_submitted";b:1;}',
])
->execute();

$connection->delete('key_value')
  ->condition('collection', 'entity.definitions.bundle_field_map')
  ->condition('name', 'node')
  ->execute();
$connection->delete('key_value')
  ->condition('collection', 'entity.definitions.installed')
  ->condition('name', 'node.field_storage_definitions')
  ->execute();

$connection->insert('key_value')
->fields([
  'collection',
  'name',
  'value',
])
->values([
  'collection' => 'config.entity.key_store.entity_form_display',
  'name' => 'uuid:dd81b16c-9941-470b-8df9-4b9208cbc597',
  'value' => 'a:1:{i:0;s:55:"core.entity_form_display.node.test_content_type.default";}',
])
->values([
  'collection' => 'config.entity.key_store.entity_view_display',
  'name' => 'uuid:d6efbc46-bb1c-47b1-b26f-a14d878e4b77',
  'value' => 'a:1:{i:0;s:55:"core.entity_view_display.node.test_content_type.default";}',
])
->values([
  'collection' => 'config.entity.key_store.field_config',
  'name' => 'uuid:36b69930-c20e-453c-93da-5e2459a6569c',
  'value' => 'a:1:{i:0;s:53:"field.field.node.test_content_type.field_test_field_2";}',
])
->values([
  'collection' => 'config.entity.key_store.field_config',
  'name' => 'uuid:39d3a686-41c0-448c-9ea8-8d99c640a547',
  'value' => 'a:1:{i:0;s:53:"field.field.node.test_content_type.field_test_field_1";}',
])
->values([
  'collection' => 'config.entity.key_store.field_storage_config',
  'name' => 'uuid:2b736a4e-9c04-44a9-ac82-f443e2255afb',
  'value' => 'a:1:{i:0;s:37:"field.storage.node.field_test_field_2";}',
])
->values([
  'collection' => 'config.entity.key_store.field_storage_config',
  'name' => 'uuid:38be0325-4846-43ef-82ca-10d27fefb627',
  'value' => 'a:1:{i:0;s:37:"field.storage.node.field_test_field_1";}',
])
->values([
  'collection' => 'config.entity.key_store.node_type',
  'name' => 'uuid:8e1922b6-8ede-4749-87ff-a5ad99e602c6',
  'value' => 'a:1:{i:0;s:27:"node.type.test_content_type";}',
])
->values([
  'collection' => 'entity.definitions.bundle_field_map',
  'name' => 'node',
  'value' => 'a:6:{s:11:"field_image";a:2:{s:4:"type";s:5:"image";s:7:"bundles";a:1:{s:7:"article";s:7:"article";}}s:7:"comment";a:2:{s:4:"type";s:7:"comment";s:7:"bundles";a:1:{s:7:"article";s:7:"article";}}s:10:"field_tags";a:2:{s:4:"type";s:16:"entity_reference";s:7:"bundles";a:1:{s:7:"article";s:7:"article";}}s:4:"body";a:2:{s:4:"type";s:17:"text_with_summary";s:7:"bundles";a:2:{s:4:"page";s:4:"page";s:7:"article";s:7:"article";}}s:18:"field_test_field_2";a:2:{s:4:"type";s:31:"test_field_dynamic_dependencies";s:7:"bundles";a:1:{s:17:"test_content_type";s:17:"test_content_type";}}s:18:"field_test_field_1";a:2:{s:4:"type";s:31:"test_field_dynamic_dependencies";s:7:"bundles";a:1:{s:17:"test_content_type";s:17:"test_content_type";}}}',
])
->values([
  'collection' => 'entity.definitions.installed',
  'name' => 'node.field_storage_definitions',
  'value' => 'a:24:{s:3:"nid";O:37:"Drupal\Core\Field\BaseFieldDefinition":5:{s:13:" * definition";a:7:{s:5:"label";O:48:"Drupal\Core\StringTranslation\TranslatableMarkup":3:{s:9:" * string";s:2:"ID";s:12:" * arguments";a:0:{}s:10:" * options";a:0:{}}s:9:"read-only";b:1;s:8:"provider";s:4:"node";s:10:"field_name";s:3:"nid";s:11:"entity_type";s:4:"node";s:6:"bundle";N;s:13:"initial_value";N;}s:17:" * itemDefinition";O:51:"Drupal\Core\Field\TypedData\FieldItemDataDefinition":2:{s:13:" * definition";a:2:{s:4:"type";s:18:"field_item:integer";s:8:"settings";a:6:{s:8:"unsigned";b:1;s:4:"size";s:6:"normal";s:3:"min";s:0:"";s:3:"max";s:0:"";s:6:"prefix";s:0:"";s:6:"suffix";s:0:"";}}s:18:" * fieldDefinition";r:2;}s:7:" * type";s:7:"integer";s:9:" * schema";a:4:{s:7:"columns";a:1:{s:5:"value";a:3:{s:4:"type";s:3:"int";s:8:"unsigned";b:1;s:4:"size";s:6:"normal";}}s:11:"unique keys";a:0:{}s:7:"indexes";a:0:{}s:12:"foreign keys";a:0:{}}s:10:" * indexes";a:0:{}}s:4:"uuid";O:37:"Drupal\Core\Field\BaseFieldDefinition":5:{s:13:" * definition";a:7:{s:5:"label";O:48:"Drupal\Core\StringTranslation\TranslatableMarkup":3:{s:9:" * string";s:4:"UUID";s:12:" * arguments";a:0:{}s:10:" * options";a:0:{}}s:9:"read-only";b:1;s:8:"provider";s:4:"node";s:10:"field_name";s:4:"uuid";s:11:"entity_type";s:4:"node";s:6:"bundle";N;s:13:"initial_value";N;}s:17:" * itemDefinition";O:51:"Drupal\Core\Field\TypedData\FieldItemDataDefinition":2:{s:13:" * definition";a:2:{s:4:"type";s:15:"field_item:uuid";s:8:"settings";a:3:{s:10:"max_length";i:128;s:8:"is_ascii";b:1;s:14:"case_sensitive";b:0;}}s:18:" * fieldDefinition";r:36;}s:7:" * type";s:4:"uuid";s:9:" * schema";a:4:{s:7:"columns";a:1:{s:5:"value";a:3:{s:4:"type";s:13:"varchar_ascii";s:6:"length";i:128;s:6:"binary";b:0;}}s:11:"unique keys";a:1:{s:5:"value";a:1:{i:0;s:5:"value";}}s:7:"indexes";a:0:{}s:12:"foreign keys";a:0:{}}s:10:" * indexes";a:0:{}}s:3:"vid";O:37:"Drupal\Core\Field\BaseFieldDefinition":5:{s:13:" * definition";a:7:{s:5:"label";O:48:"Drupal\Core\StringTranslation\TranslatableMarkup":3:{s:9:" * string";s:11:"Revision ID";s:12:" * arguments";a:0:{}s:10:" * options";a:0:{}}s:9:"read-only";b:1;s:8:"provider";s:4:"node";s:10:"field_name";s:3:"vid";s:11:"entity_type";s:4:"node";s:6:"bundle";N;s:13:"initial_value";N;}s:17:" * itemDefinition";O:51:"Drupal\Core\Field\TypedData\FieldItemDataDefinition":2:{s:13:" * definition";a:2:{s:4:"type";s:18:"field_item:integer";s:8:"settings";a:6:{s:8:"unsigned";b:1;s:4:"size";s:6:"normal";s:3:"min";s:0:"";s:3:"max";s:0:"";s:6:"prefix";s:0:"";s:6:"suffix";s:0:"";}}s:18:" * fieldDefinition";r:69;}s:7:" * type";s:7:"integer";s:9:" * schema";a:4:{s:7:"columns";a:1:{s:5:"value";a:3:{s:4:"type";s:3:"int";s:8:"unsigned";b:1;s:4:"size";s:6:"normal";}}s:11:"unique keys";a:0:{}s:7:"indexes";a:0:{}s:12:"foreign keys";a:0:{}}s:10:" * indexes";a:0:{}}s:8:"langcode";O:37:"Drupal\Core\Field\BaseFieldDefinition":5:{s:13:" * definition";a:9:{s:5:"label";O:48:"Drupal\Core\StringTranslation\TranslatableMarkup":3:{s:9:" * string";s:8:"Language";s:12:" * arguments";a:0:{}s:10:" * options";a:0:{}}s:7:"display";a:2:{s:4:"view";a:2:{s:7:"options";a:1:{s:6:"region";s:6:"hidden";}s:12:"configurable";b:1;}s:4:"form";a:2:{s:7:"options";a:2:{s:4:"type";s:15:"language_select";s:6:"weight";i:2;}s:12:"configurable";b:1;}}s:12:"revisionable";b:1;s:12:"translatable";b:1;s:8:"provider";s:4:"node";s:10:"field_name";s:8:"langcode";s:11:"entity_type";s:4:"node";s:6:"bundle";N;s:13:"initial_value";N;}s:17:" * itemDefinition";O:51:"Drupal\Core\Field\TypedData\FieldItemDataDefinition":2:{s:13:" * definition";a:2:{s:4:"type";s:19:"field_item:language";s:8:"settings";a:0:{}}s:18:" * fieldDefinition";r:103;}s:7:" * type";s:8:"language";s:9:" * schema";a:4:{s:7:"columns";a:1:{s:5:"value";a:2:{s:4:"type";s:13:"varchar_ascii";s:6:"length";i:12;}}s:11:"unique keys";a:0:{}s:7:"indexes";a:0:{}s:12:"foreign keys";a:0:{}}s:10:" * indexes";a:0:{}}s:4:"type";O:37:"Drupal\Core\Field\BaseFieldDefinition":5:{s:13:" * definition";a:8:{s:5:"label";s:12:"Content type";s:8:"required";b:1;s:9:"read-only";b:1;s:8:"provider";s:4:"node";s:10:"field_name";s:4:"type";s:11:"entity_type";s:4:"node";s:6:"bundle";N;s:13:"initial_value";N;}s:17:" * itemDefinition";O:51:"Drupal\Core\Field\TypedData\FieldItemDataDefinition":2:{s:13:" * definition";a:2:{s:4:"type";s:27:"field_item:entity_reference";s:8:"settings";a:3:{s:11:"target_type";s:9:"node_type";s:7:"handler";s:7:"default";s:16:"handler_settings";a:0:{}}}s:18:" * fieldDefinition";r:141;}s:7:" * type";s:16:"entity_reference";s:9:" * schema";a:4:{s:7:"columns";a:1:{s:9:"target_id";a:3:{s:11:"description";s:28:"The ID of the target entity.";s:4:"type";s:13:"varchar_ascii";s:6:"length";i:32;}}s:7:"indexes";a:1:{s:9:"target_id";a:1:{i:0;s:9:"target_id";}}s:11:"unique keys";a:0:{}s:12:"foreign keys";a:0:{}}s:10:" * indexes";a:0:{}}s:18:"revision_timestamp";O:37:"Drupal\Core\Field\BaseFieldDefinition":5:{s:13:" * definition";a:8:{s:5:"label";O:48:"Drupal\Core\StringTranslation\TranslatableMarkup":3:{s:9:" * string";s:20:"Revision create time";s:12:" * arguments";a:0:{}s:10:" * options";a:0:{}}s:11:"description";O:48:"Drupal\Core\StringTranslation\TranslatableMarkup":3:{s:9:" * string";s:47:"The time that the current revision was created.";s:12:" * arguments";a:0:{}s:10:" * options";a:0:{}}s:12:"revisionable";b:1;s:8:"provider";s:4:"node";s:10:"field_name";s:18:"revision_timestamp";s:11:"entity_type";s:4:"node";s:6:"bundle";N;s:13:"initial_value";N;}s:17:" * itemDefinition";O:51:"Drupal\Core\Field\TypedData\FieldItemDataDefinition":2:{s:13:" * definition";a:2:{s:4:"type";s:18:"field_item:created";s:8:"settings";a:0:{}}s:18:" * fieldDefinition";r:172;}s:7:" * type";s:7:"created";s:9:" * schema";a:4:{s:7:"columns";a:1:{s:5:"value";a:1:{s:4:"type";s:3:"int";}}s:11:"unique keys";a:0:{}s:7:"indexes";a:0:{}s:12:"foreign keys";a:0:{}}s:10:" * indexes";a:0:{}}s:12:"revision_uid";O:37:"Drupal\Core\Field\BaseFieldDefinition":5:{s:13:" * definition";a:8:{s:5:"label";O:48:"Drupal\Core\StringTranslation\TranslatableMarkup":3:{s:9:" * string";s:13:"Revision user";s:12:" * arguments";a:0:{}s:10:" * options";a:0:{}}s:11:"description";O:48:"Drupal\Core\StringTranslation\TranslatableMarkup":3:{s:9:" * string";s:50:"The user ID of the author of the current revision.";s:12:" * arguments";a:0:{}s:10:" * options";a:0:{}}s:12:"revisionable";b:1;s:8:"provider";s:4:"node";s:10:"field_name";s:12:"revision_uid";s:11:"entity_type";s:4:"node";s:6:"bundle";N;s:13:"initial_value";N;}s:17:" * itemDefinition";O:51:"Drupal\Core\Field\TypedData\FieldItemDataDefinition":2:{s:13:" * definition";a:2:{s:4:"type";s:27:"field_item:entity_reference";s:8:"settings";a:3:{s:11:"target_type";s:4:"user";s:7:"handler";s:7:"default";s:16:"handler_settings";a:0:{}}}s:18:" * fieldDefinition";r:202;}s:7:" * type";s:16:"entity_reference";s:9:" * schema";a:4:{s:7:"columns";a:1:{s:9:"target_id";a:3:{s:11:"description";s:28:"The ID of the target entity.";s:4:"type";s:3:"int";s:8:"unsigned";b:1;}}s:7:"indexes";a:1:{s:9:"target_id";a:1:{i:0;s:9:"target_id";}}s:11:"unique keys";a:0:{}s:12:"foreign keys";a:0:{}}s:10:" * indexes";a:0:{}}s:12:"revision_log";O:37:"Drupal\Core\Field\BaseFieldDefinition":5:{s:13:" * definition";a:10:{s:5:"label";O:48:"Drupal\Core\StringTranslation\TranslatableMarkup":3:{s:9:" * string";s:20:"Revision log message";s:12:" * arguments";a:0:{}s:10:" * options";a:0:{}}s:11:"description";O:48:"Drupal\Core\StringTranslation\TranslatableMarkup":3:{s:9:" * string";s:43:"Briefly describe the changes you have made.";s:12:" * arguments";a:0:{}s:10:" * options";a:0:{}}s:12:"revisionable";b:1;s:13:"default_value";a:1:{i:0;a:1:{s:5:"value";s:0:"";}}s:7:"display";a:1:{s:4:"form";a:1:{s:7:"options";a:3:{s:4:"type";s:15:"string_textarea";s:6:"weight";i:25;s:8:"settings";a:1:{s:4:"rows";i:4;}}}}s:8:"provider";s:4:"node";s:10:"field_name";s:12:"revision_log";s:11:"entity_type";s:4:"node";s:6:"bundle";N;s:13:"initial_value";N;}s:17:" * itemDefinition";O:51:"Drupal\Core\Field\TypedData\FieldItemDataDefinition":2:{s:13:" * definition";a:2:{s:4:"type";s:22:"field_item:string_long";s:8:"settings";a:1:{s:14:"case_sensitive";b:0;}}s:18:" * fieldDefinition";r:239;}s:7:" * type";s:11:"string_long";s:9:" * schema";a:4:{s:7:"columns";a:1:{s:5:"value";a:2:{s:4:"type";s:4:"text";s:4:"size";s:3:"big";}}s:11:"unique keys";a:0:{}s:7:"indexes";a:0:{}s:12:"foreign keys";a:0:{}}s:10:" * indexes";a:0:{}}s:6:"status";O:37:"Drupal\Core\Field\BaseFieldDefinition":5:{s:13:" * definition";a:10:{s:5:"label";O:48:"Drupal\Core\StringTranslation\TranslatableMarkup":3:{s:9:" * string";s:9:"Published";s:12:" * arguments";a:0:{}s:10:" * options";a:0:{}}s:12:"revisionable";b:1;s:12:"translatable";b:1;s:13:"default_value";a:1:{i:0;a:1:{s:5:"value";b:1;}}s:7:"display";a:1:{s:4:"form";a:2:{s:7:"options";a:3:{s:4:"type";s:16:"boolean_checkbox";s:8:"settings";a:1:{s:13:"display_label";b:1;}s:6:"weight";i:120;}s:12:"configurable";b:1;}}s:8:"provider";s:4:"node";s:10:"field_name";s:6:"status";s:11:"entity_type";s:4:"node";s:6:"bundle";N;s:13:"initial_value";N;}s:17:" * itemDefinition";O:51:"Drupal\Core\Field\TypedData\FieldItemDataDefinition":2:{s:13:" * definition";a:2:{s:4:"type";s:18:"field_item:boolean";s:8:"settings";a:2:{s:8:"on_label";O:48:"Drupal\Core\StringTranslation\TranslatableMarkup":3:{s:9:" * string";s:2:"On";s:12:" * arguments";a:0:{}s:10:" * options";a:0:{}}s:9:"off_label";O:48:"Drupal\Core\StringTranslation\TranslatableMarkup":3:{s:9:" * string";s:3:"Off";s:12:" * arguments";a:0:{}s:10:" * options";a:0:{}}}}s:18:" * fieldDefinition";r:281;}s:7:" * type";s:7:"boolean";s:9:" * schema";a:4:{s:7:"columns";a:1:{s:5:"value";a:2:{s:4:"type";s:3:"int";s:4:"size";s:4:"tiny";}}s:11:"unique keys";a:0:{}s:7:"indexes";a:0:{}s:12:"foreign keys";a:0:{}}s:10:" * indexes";a:0:{}}s:3:"uid";O:37:"Drupal\Core\Field\BaseFieldDefinition":5:{s:13:" * definition";a:11:{s:5:"label";O:48:"Drupal\Core\StringTranslation\TranslatableMarkup":3:{s:9:" * string";s:11:"Authored by";s:12:" * arguments";a:0:{}s:10:" * options";a:0:{}}s:12:"translatable";b:1;s:22:"default_value_callback";s:46:"Drupal\node\Entity\Node::getDefaultEntityOwner";s:11:"description";O:48:"Drupal\Core\StringTranslation\TranslatableMarkup":3:{s:9:" * string";s:35:"The username of the content author.";s:12:" * arguments";a:0:{}s:10:" * options";a:0:{}}s:12:"revisionable";b:1;s:7:"display";a:2:{s:4:"view";a:1:{s:7:"options";a:3:{s:5:"label";s:6:"hidden";s:4:"type";s:6:"author";s:6:"weight";i:0;}}s:4:"form";a:2:{s:7:"options";a:3:{s:4:"type";s:29:"entity_reference_autocomplete";s:6:"weight";i:5;s:8:"settings";a:3:{s:14:"match_operator";s:8:"CONTAINS";s:4:"size";s:2:"60";s:11:"placeholder";s:0:"";}}s:12:"configurable";b:1;}}s:8:"provider";s:4:"node";s:10:"field_name";s:3:"uid";s:11:"entity_type";s:4:"node";s:6:"bundle";N;s:13:"initial_value";N;}s:17:" * itemDefinition";O:51:"Drupal\Core\Field\TypedData\FieldItemDataDefinition":2:{s:13:" * definition";a:2:{s:4:"type";s:27:"field_item:entity_reference";s:8:"settings";a:3:{s:11:"target_type";s:4:"user";s:7:"handler";s:7:"default";s:16:"handler_settings";a:0:{}}}s:18:" * fieldDefinition";r:328;}s:7:" * type";s:16:"entity_reference";s:9:" * schema";a:4:{s:7:"columns";a:1:{s:9:"target_id";a:3:{s:11:"description";s:28:"The ID of the target entity.";s:4:"type";s:3:"int";s:8:"unsigned";b:1;}}s:7:"indexes";a:1:{s:9:"target_id";a:1:{i:0;s:9:"target_id";}}s:11:"unique keys";a:0:{}s:12:"foreign keys";a:0:{}}s:10:" * indexes";a:0:{}}s:5:"title";O:37:"Drupal\Core\Field\BaseFieldDefinition":5:{s:13:" * definition";a:10:{s:5:"label";O:48:"Drupal\Core\StringTranslation\TranslatableMarkup":3:{s:9:" * string";s:5:"Title";s:12:" * arguments";a:0:{}s:10:" * options";a:0:{}}s:8:"required";b:1;s:12:"translatable";b:1;s:12:"revisionable";b:1;s:7:"display";a:2:{s:4:"view";a:1:{s:7:"options";a:3:{s:5:"label";s:6:"hidden";s:4:"type";s:6:"string";s:6:"weight";i:-5;}}s:4:"form";a:2:{s:7:"options";a:2:{s:4:"type";s:16:"string_textfield";s:6:"weight";i:-5;}s:12:"configurable";b:1;}}s:8:"provider";s:4:"node";s:10:"field_name";s:5:"title";s:11:"entity_type";s:4:"node";s:6:"bundle";N;s:13:"initial_value";N;}s:17:" * itemDefinition";O:51:"Drupal\Core\Field\TypedData\FieldItemDataDefinition":2:{s:13:" * definition";a:2:{s:4:"type";s:17:"field_item:string";s:8:"settings";a:3:{s:10:"max_length";i:255;s:8:"is_ascii";b:0;s:14:"case_sensitive";b:0;}}s:18:" * fieldDefinition";r:382;}s:7:" * type";s:6:"string";s:9:" * schema";a:4:{s:7:"columns";a:1:{s:5:"value";a:3:{s:4:"type";s:7:"varchar";s:6:"length";i:255;s:6:"binary";b:0;}}s:11:"unique keys";a:0:{}s:7:"indexes";a:0:{}s:12:"foreign keys";a:0:{}}s:10:" * indexes";a:0:{}}s:7:"created";O:37:"Drupal\Core\Field\BaseFieldDefinition":5:{s:13:" * definition";a:10:{s:5:"label";O:48:"Drupal\Core\StringTranslation\TranslatableMarkup":3:{s:9:" * string";s:11:"Authored on";s:12:" * arguments";a:0:{}s:10:" * options";a:0:{}}s:11:"description";O:48:"Drupal\Core\StringTranslation\TranslatableMarkup":3:{s:9:" * string";s:47:"The date and time that the content was created.";s:12:" * arguments";a:0:{}s:10:" * options";a:0:{}}s:12:"revisionable";b:1;s:12:"translatable";b:1;s:7:"display";a:2:{s:4:"view";a:1:{s:7:"options";a:3:{s:5:"label";s:6:"hidden";s:4:"type";s:9:"timestamp";s:6:"weight";i:0;}}s:4:"form";a:2:{s:7:"options";a:2:{s:4:"type";s:18:"datetime_timestamp";s:6:"weight";i:10;}s:12:"configurable";b:1;}}s:8:"provider";s:4:"node";s:10:"field_name";s:7:"created";s:11:"entity_type";s:4:"node";s:6:"bundle";N;s:13:"initial_value";N;}s:17:" * itemDefinition";O:51:"Drupal\Core\Field\TypedData\FieldItemDataDefinition":2:{s:13:" * definition";a:2:{s:4:"type";s:18:"field_item:created";s:8:"settings";a:0:{}}s:18:" * fieldDefinition";r:426;}s:7:" * type";s:7:"created";s:9:" * schema";a:4:{s:7:"columns";a:1:{s:5:"value";a:1:{s:4:"type";s:3:"int";}}s:11:"unique keys";a:0:{}s:7:"indexes";a:0:{}s:12:"foreign keys";a:0:{}}s:10:" * indexes";a:0:{}}s:7:"changed";O:37:"Drupal\Core\Field\BaseFieldDefinition":5:{s:13:" * definition";a:9:{s:5:"label";O:48:"Drupal\Core\StringTranslation\TranslatableMarkup":3:{s:9:" * string";s:7:"Changed";s:12:" * arguments";a:0:{}s:10:" * options";a:0:{}}s:11:"description";O:48:"Drupal\Core\StringTranslation\TranslatableMarkup":3:{s:9:" * string";s:39:"The time that the node was last edited.";s:12:" * arguments";a:0:{}s:10:" * options";a:0:{}}s:12:"revisionable";b:1;s:12:"translatable";b:1;s:8:"provider";s:4:"node";s:10:"field_name";s:7:"changed";s:11:"entity_type";s:4:"node";s:6:"bundle";N;s:13:"initial_value";N;}s:17:" * itemDefinition";O:51:"Drupal\Core\Field\TypedData\FieldItemDataDefinition":2:{s:13:" * definition";a:2:{s:4:"type";s:18:"field_item:changed";s:8:"settings";a:0:{}}s:18:" * fieldDefinition";r:468;}s:7:" * type";s:7:"changed";s:9:" * schema";a:4:{s:7:"columns";a:1:{s:5:"value";a:1:{s:4:"type";s:3:"int";}}s:11:"unique keys";a:0:{}s:7:"indexes";a:0:{}s:12:"foreign keys";a:0:{}}s:10:" * indexes";a:0:{}}s:7:"promote";O:37:"Drupal\Core\Field\BaseFieldDefinition":5:{s:13:" * definition";a:10:{s:5:"label";O:48:"Drupal\Core\StringTranslation\TranslatableMarkup":3:{s:9:" * string";s:22:"Promoted to front page";s:12:" * arguments";a:0:{}s:10:" * options";a:0:{}}s:12:"revisionable";b:1;s:12:"translatable";b:1;s:13:"default_value";a:1:{i:0;a:1:{s:5:"value";b:0;}}s:7:"display";a:1:{s:4:"form";a:2:{s:7:"options";a:1:{s:6:"region";s:6:"hidden";}s:12:"configurable";b:1;}}s:8:"provider";s:4:"node";s:10:"field_name";s:7:"promote";s:11:"entity_type";s:4:"node";s:6:"bundle";N;s:13:"initial_value";N;}s:17:" * itemDefinition";O:51:"Drupal\Core\Field\TypedData\FieldItemDataDefinition":2:{s:13:" * definition";a:2:{s:4:"type";s:18:"field_item:boolean";s:8:"settings";a:2:{s:8:"on_label";O:48:"Drupal\Core\StringTranslation\TranslatableMarkup":3:{s:9:" * string";s:2:"On";s:12:" * arguments";a:0:{}s:10:" * options";a:0:{}}s:9:"off_label";O:48:"Drupal\Core\StringTranslation\TranslatableMarkup":3:{s:9:" * string";s:3:"Off";s:12:" * arguments";a:0:{}s:10:" * options";a:0:{}}}}s:18:" * fieldDefinition";r:499;}s:7:" * type";s:7:"boolean";s:9:" * schema";a:4:{s:7:"columns";a:1:{s:5:"value";a:2:{s:4:"type";s:3:"int";s:4:"size";s:4:"tiny";}}s:11:"unique keys";a:0:{}s:7:"indexes";a:0:{}s:12:"foreign keys";a:0:{}}s:10:" * indexes";a:0:{}}s:6:"sticky";O:37:"Drupal\Core\Field\BaseFieldDefinition":5:{s:13:" * definition";a:10:{s:5:"label";O:48:"Drupal\Core\StringTranslation\TranslatableMarkup":3:{s:9:" * string";s:22:"Sticky at top of lists";s:12:" * arguments";a:0:{}s:10:" * options";a:0:{}}s:12:"revisionable";b:1;s:12:"translatable";b:1;s:13:"default_value";a:1:{i:0;a:1:{s:5:"value";b:0;}}s:7:"display";a:1:{s:4:"form";a:2:{s:7:"options";a:1:{s:6:"region";s:6:"hidden";}s:12:"configurable";b:1;}}s:8:"provider";s:4:"node";s:10:"field_name";s:6:"sticky";s:11:"entity_type";s:4:"node";s:6:"bundle";N;s:13:"initial_value";N;}s:17:" * itemDefinition";O:51:"Drupal\Core\Field\TypedData\FieldItemDataDefinition":2:{s:13:" * definition";a:2:{s:4:"type";s:18:"field_item:boolean";s:8:"settings";a:2:{s:8:"on_label";O:48:"Drupal\Core\StringTranslation\TranslatableMarkup":3:{s:9:" * string";s:2:"On";s:12:" * arguments";a:0:{}s:10:" * options";a:0:{}}s:9:"off_label";O:48:"Drupal\Core\StringTranslation\TranslatableMarkup":3:{s:9:" * string";s:3:"Off";s:12:" * arguments";a:0:{}s:10:" * options";a:0:{}}}}s:18:" * fieldDefinition";r:543;}s:7:" * type";s:7:"boolean";s:9:" * schema";a:4:{s:7:"columns";a:1:{s:5:"value";a:2:{s:4:"type";s:3:"int";s:4:"size";s:4:"tiny";}}s:11:"unique keys";a:0:{}s:7:"indexes";a:0:{}s:12:"foreign keys";a:0:{}}s:10:" * indexes";a:0:{}}s:16:"default_langcode";O:37:"Drupal\Core\Field\BaseFieldDefinition":5:{s:13:" * definition";a:10:{s:5:"label";O:48:"Drupal\Core\StringTranslation\TranslatableMarkup":3:{s:9:" * string";s:19:"Default translation";s:12:" * arguments";a:0:{}s:10:" * options";a:0:{}}s:11:"description";O:48:"Drupal\Core\StringTranslation\TranslatableMarkup":3:{s:9:" * string";s:58:"A flag indicating whether this is the default translation.";s:12:" * arguments";a:0:{}s:10:" * options";a:0:{}}s:12:"translatable";b:1;s:12:"revisionable";b:1;s:13:"default_value";a:1:{i:0;a:1:{s:5:"value";b:1;}}s:8:"provider";s:4:"node";s:10:"field_name";s:16:"default_langcode";s:11:"entity_type";s:4:"node";s:6:"bundle";N;s:13:"initial_value";N;}s:17:" * itemDefinition";O:51:"Drupal\Core\Field\TypedData\FieldItemDataDefinition":2:{s:13:" * definition";a:2:{s:4:"type";s:18:"field_item:boolean";s:8:"settings";a:2:{s:8:"on_label";O:48:"Drupal\Core\StringTranslation\TranslatableMarkup":3:{s:9:" * string";s:2:"On";s:12:" * arguments";a:0:{}s:10:" * options";a:0:{}}s:9:"off_label";O:48:"Drupal\Core\StringTranslation\TranslatableMarkup":3:{s:9:" * string";s:3:"Off";s:12:" * arguments";a:0:{}s:10:" * options";a:0:{}}}}s:18:" * fieldDefinition";r:587;}s:7:" * type";s:7:"boolean";s:9:" * schema";a:4:{s:7:"columns";a:1:{s:5:"value";a:2:{s:4:"type";s:3:"int";s:4:"size";s:4:"tiny";}}s:11:"unique keys";a:0:{}s:7:"indexes";a:0:{}s:12:"foreign keys";a:0:{}}s:10:" * indexes";a:0:{}}s:16:"revision_default";O:37:"Drupal\Core\Field\BaseFieldDefinition":5:{s:13:" * definition";a:11:{s:5:"label";O:48:"Drupal\Core\StringTranslation\TranslatableMarkup":3:{s:9:" * string";s:16:"Default revision";s:12:" * arguments";a:0:{}s:10:" * options";a:0:{}}s:11:"description";O:48:"Drupal\Core\StringTranslation\TranslatableMarkup":3:{s:9:" * string";s:72:"A flag indicating whether this was a default revision when it was saved.";s:12:" * arguments";a:0:{}s:10:" * options";a:0:{}}s:16:"storage_required";b:1;s:8:"internal";b:1;s:12:"translatable";b:0;s:12:"revisionable";b:1;s:8:"provider";s:4:"node";s:10:"field_name";s:16:"revision_default";s:11:"entity_type";s:4:"node";s:6:"bundle";N;s:13:"initial_value";N;}s:17:" * itemDefinition";O:51:"Drupal\Core\Field\TypedData\FieldItemDataDefinition":2:{s:13:" * definition";a:2:{s:4:"type";s:18:"field_item:boolean";s:8:"settings";a:2:{s:8:"on_label";O:48:"Drupal\Core\StringTranslation\TranslatableMarkup":3:{s:9:" * string";s:2:"On";s:12:" * arguments";a:0:{}s:10:" * options";a:0:{}}s:9:"off_label";O:48:"Drupal\Core\StringTranslation\TranslatableMarkup":3:{s:9:" * string";s:3:"Off";s:12:" * arguments";a:0:{}s:10:" * options";a:0:{}}}}s:18:" * fieldDefinition";r:630;}s:7:" * type";s:7:"boolean";s:9:" * schema";a:4:{s:7:"columns";a:1:{s:5:"value";a:2:{s:4:"type";s:3:"int";s:4:"size";s:4:"tiny";}}s:11:"unique keys";a:0:{}s:7:"indexes";a:0:{}s:12:"foreign keys";a:0:{}}s:10:" * indexes";a:0:{}}s:29:"revision_translation_affected";O:37:"Drupal\Core\Field\BaseFieldDefinition":5:{s:13:" * definition";a:10:{s:5:"label";O:48:"Drupal\Core\StringTranslation\TranslatableMarkup":3:{s:9:" * string";s:29:"Revision translation affected";s:12:" * arguments";a:0:{}s:10:" * options";a:0:{}}s:11:"description";O:48:"Drupal\Core\StringTranslation\TranslatableMarkup":3:{s:9:" * string";s:72:"Indicates if the last edit of a translation belongs to current revision.";s:12:" * arguments";a:0:{}s:10:" * options";a:0:{}}s:9:"read-only";b:1;s:12:"revisionable";b:1;s:12:"translatable";b:1;s:8:"provider";s:4:"node";s:10:"field_name";s:29:"revision_translation_affected";s:11:"entity_type";s:4:"node";s:6:"bundle";N;s:13:"initial_value";N;}s:17:" * itemDefinition";O:51:"Drupal\Core\Field\TypedData\FieldItemDataDefinition":2:{s:13:" * definition";a:2:{s:4:"type";s:18:"field_item:boolean";s:8:"settings";a:2:{s:8:"on_label";O:48:"Drupal\Core\StringTranslation\TranslatableMarkup":3:{s:9:" * string";s:2:"On";s:12:" * arguments";a:0:{}s:10:" * options";a:0:{}}s:9:"off_label";O:48:"Drupal\Core\StringTranslation\TranslatableMarkup":3:{s:9:" * string";s:3:"Off";s:12:" * arguments";a:0:{}s:10:" * options";a:0:{}}}}s:18:" * fieldDefinition";r:672;}s:7:" * type";s:7:"boolean";s:9:" * schema";a:4:{s:7:"columns";a:1:{s:5:"value";a:2:{s:4:"type";s:3:"int";s:4:"size";s:4:"tiny";}}s:11:"unique keys";a:0:{}s:7:"indexes";a:0:{}s:12:"foreign keys";a:0:{}}s:10:" * indexes";a:0:{}}s:11:"field_image";O:38:"Drupal\field\Entity\FieldStorageConfig":31:{s:15:" * entityTypeId";s:20:"field_storage_config";s:15:" * enforceIsNew";b:1;s:12:" * typedData";N;s:17:" * originalEntity";O:38:"Drupal\field\Entity\FieldStorageConfig":31:{s:15:" * entityTypeId";s:20:"field_storage_config";s:15:" * enforceIsNew";N;s:12:" * typedData";N;s:17:" * originalEntity";N;s:16:" * cacheContexts";a:0:{}s:12:" * cacheTags";a:0:{}s:14:" * cacheMaxAge";i:-1;s:14:" * _serviceIds";a:0:{}s:18:" * _entityStorages";a:0:{}s:13:" * originalId";s:16:"node.field_image";s:9:" * status";b:1;s:7:" * uuid";s:36:"e8e8c209-b5d7-47eb-bd43-56770d0e0d5f";s:11:" * langcode";s:2:"en";s:23:" * third_party_settings";a:0:{}s:8:" * _core";a:1:{s:19:"default_config_hash";s:43:"EymokncRIZ7SgQT2IdOQhQJicX4nNc0K89ik-LxmOHE";}s:14:" * trustedData";b:0;s:15:" * dependencies";a:1:{s:6:"module";a:3:{i:0;s:4:"file";i:1;s:5:"image";i:2;s:4:"node";}}s:12:" * isSyncing";b:0;s:5:" * id";s:16:"node.field_image";s:13:" * field_name";s:11:"field_image";s:14:" * entity_type";s:4:"node";s:7:" * type";s:5:"image";s:9:" * module";s:5:"image";s:11:" * settings";a:5:{s:11:"target_type";s:4:"file";s:13:"display_field";b:0;s:15:"display_default";b:0;s:10:"uri_scheme";s:6:"public";s:13:"default_image";a:5:{s:4:"uuid";N;s:3:"alt";s:0:"";s:5:"title";s:0:"";s:5:"width";N;s:6:"height";N;}}s:14:" * cardinality";i:1;s:15:" * translatable";b:1;s:9:" * locked";b:0;s:25:" * persist_with_no_fields";b:0;s:14:"custom_storage";b:0;s:10:" * indexes";a:1:{s:9:"target_id";a:1:{i:0;s:9:"target_id";}}s:10:" * deleted";b:0;}s:16:" * cacheContexts";a:0:{}s:12:" * cacheTags";a:0:{}s:14:" * cacheMaxAge";i:-1;s:14:" * _serviceIds";a:0:{}s:18:" * _entityStorages";a:0:{}s:13:" * originalId";s:16:"node.field_image";s:9:" * status";b:1;s:7:" * uuid";s:36:"e8e8c209-b5d7-47eb-bd43-56770d0e0d5f";s:11:" * langcode";s:2:"en";s:23:" * third_party_settings";a:0:{}s:8:" * _core";a:1:{s:19:"default_config_hash";s:43:"EymokncRIZ7SgQT2IdOQhQJicX4nNc0K89ik-LxmOHE";}s:14:" * trustedData";b:0;s:15:" * dependencies";a:1:{s:6:"module";a:3:{i:0;s:4:"file";i:1;s:5:"image";i:2;s:4:"node";}}s:12:" * isSyncing";b:1;s:5:" * id";s:16:"node.field_image";s:13:" * field_name";s:11:"field_image";s:14:" * entity_type";s:4:"node";s:7:" * type";s:5:"image";s:9:" * module";s:5:"image";s:11:" * settings";a:5:{s:11:"target_type";s:4:"file";s:13:"display_field";b:0;s:15:"display_default";b:0;s:10:"uri_scheme";s:6:"public";s:13:"default_image";a:5:{s:4:"uuid";N;s:3:"alt";s:0:"";s:5:"title";s:0:"";s:5:"width";N;s:6:"height";N;}}s:14:" * cardinality";i:1;s:15:" * translatable";b:1;s:9:" * locked";b:0;s:25:" * persist_with_no_fields";b:0;s:14:"custom_storage";b:0;s:10:" * indexes";a:1:{s:9:"target_id";a:1:{i:0;s:9:"target_id";}}s:10:" * deleted";b:0;}s:7:"comment";O:38:"Drupal\field\Entity\FieldStorageConfig":31:{s:15:" * entityTypeId";s:20:"field_storage_config";s:15:" * enforceIsNew";b:1;s:12:" * typedData";N;s:17:" * originalEntity";O:38:"Drupal\field\Entity\FieldStorageConfig":31:{s:15:" * entityTypeId";s:20:"field_storage_config";s:15:" * enforceIsNew";N;s:12:" * typedData";N;s:17:" * originalEntity";N;s:16:" * cacheContexts";a:0:{}s:12:" * cacheTags";a:0:{}s:14:" * cacheMaxAge";i:-1;s:14:" * _serviceIds";a:0:{}s:18:" * _entityStorages";a:0:{}s:13:" * originalId";s:12:"node.comment";s:9:" * status";b:1;s:7:" * uuid";s:36:"19398e9d-0972-481c-bb6e-2c7092cfb5c1";s:11:" * langcode";s:2:"en";s:23:" * third_party_settings";a:0:{}s:8:" * _core";a:1:{s:19:"default_config_hash";s:43:"ktCna9xmWvYZIUfOCUyDQvedn5RtnS4CRmEIwNmvYjc";}s:14:" * trustedData";b:0;s:15:" * dependencies";a:1:{s:6:"module";a:2:{i:0;s:7:"comment";i:1;s:4:"node";}}s:12:" * isSyncing";b:0;s:5:" * id";s:12:"node.comment";s:13:" * field_name";s:7:"comment";s:14:" * entity_type";s:4:"node";s:7:" * type";s:7:"comment";s:9:" * module";s:7:"comment";s:11:" * settings";a:1:{s:12:"comment_type";s:7:"comment";}s:14:" * cardinality";i:1;s:15:" * translatable";b:1;s:9:" * locked";b:0;s:25:" * persist_with_no_fields";b:0;s:14:"custom_storage";b:0;s:10:" * indexes";a:0:{}s:10:" * deleted";b:0;}s:16:" * cacheContexts";a:0:{}s:12:" * cacheTags";a:0:{}s:14:" * cacheMaxAge";i:-1;s:14:" * _serviceIds";a:0:{}s:18:" * _entityStorages";a:0:{}s:13:" * originalId";s:12:"node.comment";s:9:" * status";b:1;s:7:" * uuid";s:36:"19398e9d-0972-481c-bb6e-2c7092cfb5c1";s:11:" * langcode";s:2:"en";s:23:" * third_party_settings";a:0:{}s:8:" * _core";a:1:{s:19:"default_config_hash";s:43:"ktCna9xmWvYZIUfOCUyDQvedn5RtnS4CRmEIwNmvYjc";}s:14:" * trustedData";b:0;s:15:" * dependencies";a:1:{s:6:"module";a:2:{i:0;s:7:"comment";i:1;s:4:"node";}}s:12:" * isSyncing";b:1;s:5:" * id";s:12:"node.comment";s:13:" * field_name";s:7:"comment";s:14:" * entity_type";s:4:"node";s:7:" * type";s:7:"comment";s:9:" * module";s:7:"comment";s:11:" * settings";a:1:{s:12:"comment_type";s:7:"comment";}s:14:" * cardinality";i:1;s:15:" * translatable";b:1;s:9:" * locked";b:0;s:25:" * persist_with_no_fields";b:0;s:14:"custom_storage";b:0;s:10:" * indexes";a:0:{}s:10:" * deleted";b:0;}s:10:"field_tags";O:38:"Drupal\field\Entity\FieldStorageConfig":31:{s:15:" * entityTypeId";s:20:"field_storage_config";s:15:" * enforceIsNew";b:1;s:12:" * typedData";N;s:17:" * originalEntity";O:38:"Drupal\field\Entity\FieldStorageConfig":31:{s:15:" * entityTypeId";s:20:"field_storage_config";s:15:" * enforceIsNew";N;s:12:" * typedData";N;s:17:" * originalEntity";N;s:16:" * cacheContexts";a:0:{}s:12:" * cacheTags";a:0:{}s:14:" * cacheMaxAge";i:-1;s:14:" * _serviceIds";a:0:{}s:18:" * _entityStorages";a:0:{}s:13:" * originalId";s:15:"node.field_tags";s:9:" * status";b:1;s:7:" * uuid";s:36:"e3230574-78c5-4ecd-8de9-b084c55b5bb4";s:11:" * langcode";s:2:"en";s:23:" * third_party_settings";a:0:{}s:8:" * _core";a:1:{s:19:"default_config_hash";s:43:"WpOE_bs8Bs_HY2ns7n2r__de-xno0-Bxkqep5-MsHAs";}s:14:" * trustedData";b:0;s:15:" * dependencies";a:1:{s:6:"module";a:2:{i:0;s:4:"node";i:1;s:8:"taxonomy";}}s:12:" * isSyncing";b:0;s:5:" * id";s:15:"node.field_tags";s:13:" * field_name";s:10:"field_tags";s:14:" * entity_type";s:4:"node";s:7:" * type";s:16:"entity_reference";s:9:" * module";s:4:"core";s:11:" * settings";a:1:{s:11:"target_type";s:13:"taxonomy_term";}s:14:" * cardinality";i:-1;s:15:" * translatable";b:1;s:9:" * locked";b:0;s:25:" * persist_with_no_fields";b:0;s:14:"custom_storage";b:0;s:10:" * indexes";a:0:{}s:10:" * deleted";b:0;}s:16:" * cacheContexts";a:0:{}s:12:" * cacheTags";a:0:{}s:14:" * cacheMaxAge";i:-1;s:14:" * _serviceIds";a:0:{}s:18:" * _entityStorages";a:0:{}s:13:" * originalId";s:15:"node.field_tags";s:9:" * status";b:1;s:7:" * uuid";s:36:"e3230574-78c5-4ecd-8de9-b084c55b5bb4";s:11:" * langcode";s:2:"en";s:23:" * third_party_settings";a:0:{}s:8:" * _core";a:1:{s:19:"default_config_hash";s:43:"WpOE_bs8Bs_HY2ns7n2r__de-xno0-Bxkqep5-MsHAs";}s:14:" * trustedData";b:0;s:15:" * dependencies";a:1:{s:6:"module";a:2:{i:0;s:4:"node";i:1;s:8:"taxonomy";}}s:12:" * isSyncing";b:1;s:5:" * id";s:15:"node.field_tags";s:13:" * field_name";s:10:"field_tags";s:14:" * entity_type";s:4:"node";s:7:" * type";s:16:"entity_reference";s:9:" * module";s:4:"core";s:11:" * settings";a:1:{s:11:"target_type";s:13:"taxonomy_term";}s:14:" * cardinality";i:-1;s:15:" * translatable";b:1;s:9:" * locked";b:0;s:25:" * persist_with_no_fields";b:0;s:14:"custom_storage";b:0;s:10:" * indexes";a:0:{}s:10:" * deleted";b:0;}s:4:"body";O:38:"Drupal\field\Entity\FieldStorageConfig":31:{s:15:" * entityTypeId";s:20:"field_storage_config";s:15:" * enforceIsNew";b:1;s:12:" * typedData";N;s:17:" * originalEntity";O:38:"Drupal\field\Entity\FieldStorageConfig":31:{s:15:" * entityTypeId";s:20:"field_storage_config";s:15:" * enforceIsNew";N;s:12:" * typedData";N;s:17:" * originalEntity";N;s:16:" * cacheContexts";a:0:{}s:12:" * cacheTags";a:0:{}s:14:" * cacheMaxAge";i:-1;s:14:" * _serviceIds";a:0:{}s:18:" * _entityStorages";a:0:{}s:13:" * originalId";s:9:"node.body";s:9:" * status";b:1;s:7:" * uuid";s:36:"48a4e5eb-2ad1-4c5c-8902-fa1a571979ff";s:11:" * langcode";s:2:"en";s:23:" * third_party_settings";a:0:{}s:8:" * _core";a:1:{s:19:"default_config_hash";s:43:"EBUo7qOWqaiZaQ_RC9sLY5IoDKphS34v77VIHSACmVY";}s:14:" * trustedData";b:0;s:15:" * dependencies";a:1:{s:6:"module";a:2:{i:0;s:4:"node";i:1;s:4:"text";}}s:12:" * isSyncing";b:0;s:5:" * id";s:9:"node.body";s:13:" * field_name";s:4:"body";s:14:" * entity_type";s:4:"node";s:7:" * type";s:17:"text_with_summary";s:9:" * module";s:4:"text";s:11:" * settings";a:0:{}s:14:" * cardinality";i:1;s:15:" * translatable";b:1;s:9:" * locked";b:0;s:25:" * persist_with_no_fields";b:1;s:14:"custom_storage";b:0;s:10:" * indexes";a:0:{}s:10:" * deleted";b:0;}s:16:" * cacheContexts";a:0:{}s:12:" * cacheTags";a:0:{}s:14:" * cacheMaxAge";i:-1;s:14:" * _serviceIds";a:0:{}s:18:" * _entityStorages";a:0:{}s:13:" * originalId";s:9:"node.body";s:9:" * status";b:1;s:7:" * uuid";s:36:"48a4e5eb-2ad1-4c5c-8902-fa1a571979ff";s:11:" * langcode";s:2:"en";s:23:" * third_party_settings";a:0:{}s:8:" * _core";a:1:{s:19:"default_config_hash";s:43:"EBUo7qOWqaiZaQ_RC9sLY5IoDKphS34v77VIHSACmVY";}s:14:" * trustedData";b:0;s:15:" * dependencies";a:1:{s:6:"module";a:2:{i:0;s:4:"node";i:1;s:4:"text";}}s:12:" * isSyncing";b:1;s:5:" * id";s:9:"node.body";s:13:" * field_name";s:4:"body";s:14:" * entity_type";s:4:"node";s:7:" * type";s:17:"text_with_summary";s:9:" * module";s:4:"text";s:11:" * settings";a:0:{}s:14:" * cardinality";i:1;s:15:" * translatable";b:1;s:9:" * locked";b:0;s:25:" * persist_with_no_fields";b:1;s:14:"custom_storage";b:0;s:10:" * indexes";a:0:{}s:10:" * deleted";b:0;}s:18:"field_test_field_2";O:38:"Drupal\field\Entity\FieldStorageConfig":31:{s:15:" * entityTypeId";s:20:"field_storage_config";s:15:" * enforceIsNew";b:1;s:12:" * typedData";N;s:17:" * originalEntity";O:38:"Drupal\field\Entity\FieldStorageConfig":31:{s:15:" * entityTypeId";s:20:"field_storage_config";s:15:" * enforceIsNew";N;s:12:" * typedData";N;s:17:" * originalEntity";N;s:16:" * cacheContexts";a:0:{}s:12:" * cacheTags";a:0:{}s:14:" * cacheMaxAge";i:-1;s:14:" * _serviceIds";a:0:{}s:18:" * _entityStorages";a:0:{}s:13:" * originalId";s:23:"node.field_test_field_2";s:9:" * status";b:1;s:7:" * uuid";s:36:"2b736a4e-9c04-44a9-ac82-f443e2255afb";s:11:" * langcode";s:2:"en";s:23:" * third_party_settings";a:0:{}s:8:" * _core";a:0:{}s:14:" * trustedData";b:0;s:15:" * dependencies";a:1:{s:6:"module";a:2:{i:0;s:31:"field_dynamic_dependencies_test";i:1;s:4:"node";}}s:12:" * isSyncing";b:0;s:5:" * id";s:23:"node.field_test_field_2";s:13:" * field_name";s:18:"field_test_field_2";s:14:" * entity_type";s:4:"node";s:7:" * type";s:31:"test_field_dynamic_dependencies";s:9:" * module";s:31:"field_dynamic_dependencies_test";s:11:" * settings";a:0:{}s:14:" * cardinality";i:1;s:15:" * translatable";b:1;s:9:" * locked";b:0;s:25:" * persist_with_no_fields";b:0;s:14:"custom_storage";b:0;s:10:" * indexes";a:0:{}s:10:" * deleted";b:0;}s:16:" * cacheContexts";a:0:{}s:12:" * cacheTags";a:0:{}s:14:" * cacheMaxAge";i:-1;s:14:" * _serviceIds";a:0:{}s:18:" * _entityStorages";a:0:{}s:13:" * originalId";s:23:"node.field_test_field_2";s:9:" * status";b:1;s:7:" * uuid";s:36:"2b736a4e-9c04-44a9-ac82-f443e2255afb";s:11:" * langcode";s:2:"en";s:23:" * third_party_settings";a:0:{}s:8:" * _core";a:0:{}s:14:" * trustedData";b:0;s:15:" * dependencies";a:1:{s:6:"module";a:2:{i:0;s:31:"field_dynamic_dependencies_test";i:1;s:4:"node";}}s:12:" * isSyncing";b:1;s:5:" * id";s:23:"node.field_test_field_2";s:13:" * field_name";s:18:"field_test_field_2";s:14:" * entity_type";s:4:"node";s:7:" * type";s:31:"test_field_dynamic_dependencies";s:9:" * module";s:31:"field_dynamic_dependencies_test";s:11:" * settings";a:0:{}s:14:" * cardinality";i:1;s:15:" * translatable";b:1;s:9:" * locked";b:0;s:25:" * persist_with_no_fields";b:0;s:14:"custom_storage";b:0;s:10:" * indexes";a:0:{}s:10:" * deleted";b:0;}s:18:"field_test_field_1";O:38:"Drupal\field\Entity\FieldStorageConfig":31:{s:15:" * entityTypeId";s:20:"field_storage_config";s:15:" * enforceIsNew";b:1;s:12:" * typedData";N;s:17:" * originalEntity";O:38:"Drupal\field\Entity\FieldStorageConfig":31:{s:15:" * entityTypeId";s:20:"field_storage_config";s:15:" * enforceIsNew";N;s:12:" * typedData";N;s:17:" * originalEntity";N;s:16:" * cacheContexts";a:0:{}s:12:" * cacheTags";a:0:{}s:14:" * cacheMaxAge";i:-1;s:14:" * _serviceIds";a:0:{}s:18:" * _entityStorages";a:0:{}s:13:" * originalId";s:23:"node.field_test_field_1";s:9:" * status";b:1;s:7:" * uuid";s:36:"38be0325-4846-43ef-82ca-10d27fefb627";s:11:" * langcode";s:2:"en";s:23:" * third_party_settings";a:0:{}s:8:" * _core";a:0:{}s:14:" * trustedData";b:0;s:15:" * dependencies";a:1:{s:6:"module";a:2:{i:0;s:31:"field_dynamic_dependencies_test";i:1;s:4:"node";}}s:12:" * isSyncing";b:0;s:5:" * id";s:23:"node.field_test_field_1";s:13:" * field_name";s:18:"field_test_field_1";s:14:" * entity_type";s:4:"node";s:7:" * type";s:31:"test_field_dynamic_dependencies";s:9:" * module";s:31:"field_dynamic_dependencies_test";s:11:" * settings";a:0:{}s:14:" * cardinality";i:1;s:15:" * translatable";b:1;s:9:" * locked";b:0;s:25:" * persist_with_no_fields";b:0;s:14:"custom_storage";b:0;s:10:" * indexes";a:0:{}s:10:" * deleted";b:0;}s:16:" * cacheContexts";a:0:{}s:12:" * cacheTags";a:0:{}s:14:" * cacheMaxAge";i:-1;s:14:" * _serviceIds";a:0:{}s:18:" * _entityStorages";a:0:{}s:13:" * originalId";s:23:"node.field_test_field_1";s:9:" * status";b:1;s:7:" * uuid";s:36:"38be0325-4846-43ef-82ca-10d27fefb627";s:11:" * langcode";s:2:"en";s:23:" * third_party_settings";a:0:{}s:8:" * _core";a:0:{}s:14:" * trustedData";b:0;s:15:" * dependencies";a:1:{s:6:"module";a:2:{i:0;s:31:"field_dynamic_dependencies_test";i:1;s:4:"node";}}s:12:" * isSyncing";b:1;s:5:" * id";s:23:"node.field_test_field_1";s:13:" * field_name";s:18:"field_test_field_1";s:14:" * entity_type";s:4:"node";s:7:" * type";s:31:"test_field_dynamic_dependencies";s:9:" * module";s:31:"field_dynamic_dependencies_test";s:11:" * settings";a:0:{}s:14:" * cardinality";i:1;s:15:" * translatable";b:1;s:9:" * locked";b:0;s:25:" * persist_with_no_fields";b:0;s:14:"custom_storage";b:0;s:10:" * indexes";a:0:{}s:10:" * deleted";b:0;}}',
])
->values([
  'collection' => 'entity.storage_schema.sql',
  'name' => 'node.field_schema_data.field_test_field_1',
  'value' => 'a:2:{s:24:"node__field_test_field_1";a:4:{s:11:"description";s:47:"Data storage for node field field_test_field_1.";s:6:"fields";a:6:{s:6:"bundle";a:5:{s:4:"type";s:13:"varchar_ascii";s:6:"length";i:128;s:8:"not null";b:1;s:7:"default";s:0:"";s:11:"description";s:88:"The field instance bundle to which this row belongs, used when deleting a field instance";}s:7:"deleted";a:5:{s:4:"type";s:3:"int";s:4:"size";s:4:"tiny";s:8:"not null";b:1;s:7:"default";i:0;s:11:"description";s:60:"A boolean indicating whether this data item has been deleted";}s:9:"entity_id";a:4:{s:4:"type";s:3:"int";s:8:"unsigned";b:1;s:8:"not null";b:1;s:11:"description";s:38:"The entity id this data is attached to";}s:11:"revision_id";a:4:{s:4:"type";s:3:"int";s:8:"unsigned";b:1;s:8:"not null";b:1;s:11:"description";s:47:"The entity revision id this data is attached to";}s:8:"langcode";a:5:{s:4:"type";s:13:"varchar_ascii";s:6:"length";i:32;s:8:"not null";b:1;s:7:"default";s:0:"";s:11:"description";s:37:"The language code for this data item.";}s:5:"delta";a:4:{s:4:"type";s:3:"int";s:8:"unsigned";b:1;s:8:"not null";b:1;s:11:"description";s:67:"The sequence number for this data item, used for multi-value fields";}}s:11:"primary key";a:4:{i:0;s:9:"entity_id";i:1;s:7:"deleted";i:2;s:5:"delta";i:3;s:8:"langcode";}s:7:"indexes";a:2:{s:6:"bundle";a:1:{i:0;s:6:"bundle";}s:11:"revision_id";a:1:{i:0;s:11:"revision_id";}}}s:33:"node_revision__field_test_field_1";a:4:{s:11:"description";s:59:"Revision archive storage for node field field_test_field_1.";s:6:"fields";a:6:{s:6:"bundle";a:5:{s:4:"type";s:13:"varchar_ascii";s:6:"length";i:128;s:8:"not null";b:1;s:7:"default";s:0:"";s:11:"description";s:88:"The field instance bundle to which this row belongs, used when deleting a field instance";}s:7:"deleted";a:5:{s:4:"type";s:3:"int";s:4:"size";s:4:"tiny";s:8:"not null";b:1;s:7:"default";i:0;s:11:"description";s:60:"A boolean indicating whether this data item has been deleted";}s:9:"entity_id";a:4:{s:4:"type";s:3:"int";s:8:"unsigned";b:1;s:8:"not null";b:1;s:11:"description";s:38:"The entity id this data is attached to";}s:11:"revision_id";a:4:{s:4:"type";s:3:"int";s:8:"unsigned";b:1;s:8:"not null";b:1;s:11:"description";s:47:"The entity revision id this data is attached to";}s:8:"langcode";a:5:{s:4:"type";s:13:"varchar_ascii";s:6:"length";i:32;s:8:"not null";b:1;s:7:"default";s:0:"";s:11:"description";s:37:"The language code for this data item.";}s:5:"delta";a:4:{s:4:"type";s:3:"int";s:8:"unsigned";b:1;s:8:"not null";b:1;s:11:"description";s:67:"The sequence number for this data item, used for multi-value fields";}}s:11:"primary key";a:5:{i:0;s:9:"entity_id";i:1;s:11:"revision_id";i:2;s:7:"deleted";i:3;s:5:"delta";i:4;s:8:"langcode";}s:7:"indexes";a:2:{s:6:"bundle";a:1:{i:0;s:6:"bundle";}s:11:"revision_id";a:1:{i:0;s:11:"revision_id";}}}}',
])
->values([
  'collection' => 'entity.storage_schema.sql',
  'name' => 'node.field_schema_data.field_test_field_2',
  'value' => 'a:2:{s:24:"node__field_test_field_2";a:4:{s:11:"description";s:47:"Data storage for node field field_test_field_2.";s:6:"fields";a:6:{s:6:"bundle";a:5:{s:4:"type";s:13:"varchar_ascii";s:6:"length";i:128;s:8:"not null";b:1;s:7:"default";s:0:"";s:11:"description";s:88:"The field instance bundle to which this row belongs, used when deleting a field instance";}s:7:"deleted";a:5:{s:4:"type";s:3:"int";s:4:"size";s:4:"tiny";s:8:"not null";b:1;s:7:"default";i:0;s:11:"description";s:60:"A boolean indicating whether this data item has been deleted";}s:9:"entity_id";a:4:{s:4:"type";s:3:"int";s:8:"unsigned";b:1;s:8:"not null";b:1;s:11:"description";s:38:"The entity id this data is attached to";}s:11:"revision_id";a:4:{s:4:"type";s:3:"int";s:8:"unsigned";b:1;s:8:"not null";b:1;s:11:"description";s:47:"The entity revision id this data is attached to";}s:8:"langcode";a:5:{s:4:"type";s:13:"varchar_ascii";s:6:"length";i:32;s:8:"not null";b:1;s:7:"default";s:0:"";s:11:"description";s:37:"The language code for this data item.";}s:5:"delta";a:4:{s:4:"type";s:3:"int";s:8:"unsigned";b:1;s:8:"not null";b:1;s:11:"description";s:67:"The sequence number for this data item, used for multi-value fields";}}s:11:"primary key";a:4:{i:0;s:9:"entity_id";i:1;s:7:"deleted";i:2;s:5:"delta";i:3;s:8:"langcode";}s:7:"indexes";a:2:{s:6:"bundle";a:1:{i:0;s:6:"bundle";}s:11:"revision_id";a:1:{i:0;s:11:"revision_id";}}}s:33:"node_revision__field_test_field_2";a:4:{s:11:"description";s:59:"Revision archive storage for node field field_test_field_2.";s:6:"fields";a:6:{s:6:"bundle";a:5:{s:4:"type";s:13:"varchar_ascii";s:6:"length";i:128;s:8:"not null";b:1;s:7:"default";s:0:"";s:11:"description";s:88:"The field instance bundle to which this row belongs, used when deleting a field instance";}s:7:"deleted";a:5:{s:4:"type";s:3:"int";s:4:"size";s:4:"tiny";s:8:"not null";b:1;s:7:"default";i:0;s:11:"description";s:60:"A boolean indicating whether this data item has been deleted";}s:9:"entity_id";a:4:{s:4:"type";s:3:"int";s:8:"unsigned";b:1;s:8:"not null";b:1;s:11:"description";s:38:"The entity id this data is attached to";}s:11:"revision_id";a:4:{s:4:"type";s:3:"int";s:8:"unsigned";b:1;s:8:"not null";b:1;s:11:"description";s:47:"The entity revision id this data is attached to";}s:8:"langcode";a:5:{s:4:"type";s:13:"varchar_ascii";s:6:"length";i:32;s:8:"not null";b:1;s:7:"default";s:0:"";s:11:"description";s:37:"The language code for this data item.";}s:5:"delta";a:4:{s:4:"type";s:3:"int";s:8:"unsigned";b:1;s:8:"not null";b:1;s:11:"description";s:67:"The sequence number for this data item, used for multi-value fields";}}s:11:"primary key";a:5:{i:0;s:9:"entity_id";i:1;s:11:"revision_id";i:2;s:7:"deleted";i:3;s:5:"delta";i:4;s:8:"langcode";}s:7:"indexes";a:2:{s:6:"bundle";a:1:{i:0;s:6:"bundle";}s:11:"revision_id";a:1:{i:0;s:11:"revision_id";}}}}',
])
->execute();

$connection->schema()->createTable('node__field_test_field_1', [
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

$connection->schema()->createTable('node__field_test_field_2', [
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

$connection->schema()->createTable('node_revision__field_test_field_1', [
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

$connection->schema()->createTable('node_revision__field_test_field_2', [
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
