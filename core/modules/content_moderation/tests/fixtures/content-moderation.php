<?php

// @codingStandardsIgnoreFile
// cspell:disable

/**
 * @file
 * Test fixture to enable content_moderation and workflows modules.
 */

use Drupal\Core\Database\Database;

$connection = Database::getConnection();

// Update core.extension.
$extensions = $connection->select('config')
  ->fields('config', ['data'])
  ->condition('collection', '')
  ->condition('name', 'core.extension')
  ->execute()
  ->fetchField();
$extensions = unserialize($extensions);
$extensions['module']['content_moderation'] = 0;
$extensions['module']['workflows'] = 0;
$connection->update('config')
  ->fields(['data' => serialize($extensions)])
  ->condition('collection', '')
  ->condition('name', 'core.extension')
  ->execute();

// Add all the removed updates as existing updates.
require_once __DIR__ . '/../../content_moderation.post_update.php';
$existing_updates = $connection->select('key_value')
  ->fields('key_value', ['value'])
  ->condition('collection', 'post_update')
  ->condition('name', 'existing_updates')
  ->execute()
  ->fetchField();
$existing_updates = unserialize($existing_updates);
$existing_updates = array_merge(
  $existing_updates,
  array_keys(content_moderation_removed_post_updates())
);
$connection->update('key_value')
  ->fields(['value' => serialize($existing_updates)])
  ->condition('collection', 'post_update')
  ->condition('name', 'existing_updates')
  ->execute();


$connection->schema()->createTable('content_moderation_state_field_data', array(
  'fields' => array(
    'id' => array(
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
      'length' => '12',
    ),
    'uid' => array(
      'type' => 'int',
      'not null' => TRUE,
      'size' => 'normal',
      'unsigned' => TRUE,
    ),
    'workflow' => array(
      'type' => 'varchar_ascii',
      'not null' => FALSE,
      'length' => '255',
    ),
    'moderation_state' => array(
      'type' => 'varchar',
      'not null' => FALSE,
      'length' => '255',
    ),
    'content_entity_type_id' => array(
      'type' => 'varchar',
      'not null' => FALSE,
      'length' => '32',
    ),
    'content_entity_id' => array(
      'type' => 'int',
      'not null' => FALSE,
      'size' => 'normal',
    ),
    'content_entity_revision_id' => array(
      'type' => 'int',
      'not null' => FALSE,
      'size' => 'normal',
    ),
    'default_langcode' => array(
      'type' => 'int',
      'not null' => TRUE,
      'size' => 'tiny',
    ),
    'revision_translation_affected' => array(
      'type' => 'int',
      'not null' => FALSE,
      'size' => 'tiny',
    ),
  ),
  'primary key' => array(
    'id',
    'langcode',
  ),
  'unique keys' => array(
    'content_moderation_state__lookup' => array(
      'content_entity_type_id',
      'content_entity_id',
      'content_entity_revision_id',
      'workflow',
      'langcode',
    ),
  ),
  'indexes' => array(
    'content_moderation_state__id__default_langcode__langcode' => array(
      'id',
      'default_langcode',
      'langcode',
    ),
    'content_moderation_state__revision_id' => array(
      'revision_id',
    ),
    'content_moderation_state_field__uid__target_id' => array(
      'uid',
    ),
    'content_moderation_state__09628d8dbc' => array(
      'workflow',
    ),
  ),
  'mysql_character_set' => 'utf8mb4',
));

$connection->schema()->createTable('content_moderation_state', array(
  'fields' => array(
    'id' => array(
      'type' => 'serial',
      'not null' => TRUE,
      'size' => 'normal',
      'unsigned' => TRUE,
    ),
    'revision_id' => array(
      'type' => 'int',
      'not null' => FALSE,
      'size' => 'normal',
      'unsigned' => TRUE,
    ),
    'uuid' => array(
      'type' => 'varchar_ascii',
      'not null' => TRUE,
      'length' => '128',
    ),
    'langcode' => array(
      'type' => 'varchar_ascii',
      'not null' => TRUE,
      'length' => '12',
    ),
  ),
  'primary key' => array(
    'id',
  ),
  'unique keys' => array(
    'content_moderation_state_field__uuid__value' => array(
      'uuid',
    ),
    'content_moderation_state__revision_id' => array(
      'revision_id',
    ),
  ),
  'mysql_character_set' => 'utf8mb4',
));

$connection->schema()->createTable('content_moderation_state_field_revision', array(
  'fields' => array(
    'id' => array(
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
      'length' => '12',
    ),
    'uid' => array(
      'type' => 'int',
      'not null' => TRUE,
      'size' => 'normal',
      'unsigned' => TRUE,
    ),
    'workflow' => array(
      'type' => 'varchar_ascii',
      'not null' => FALSE,
      'length' => '255',
    ),
    'moderation_state' => array(
      'type' => 'varchar',
      'not null' => FALSE,
      'length' => '255',
    ),
    'content_entity_type_id' => array(
      'type' => 'varchar',
      'not null' => FALSE,
      'length' => '32',
    ),
    'content_entity_id' => array(
      'type' => 'int',
      'not null' => FALSE,
      'size' => 'normal',
    ),
    'content_entity_revision_id' => array(
      'type' => 'int',
      'not null' => FALSE,
      'size' => 'normal',
    ),
    'default_langcode' => array(
      'type' => 'int',
      'not null' => TRUE,
      'size' => 'tiny',
    ),
    'revision_translation_affected' => array(
      'type' => 'int',
      'not null' => FALSE,
      'size' => 'tiny',
    ),
  ),
  'primary key' => array(
    'revision_id',
    'langcode',
  ),
  'unique keys' => array(
    'content_moderation_state__lookup' => array(
      'content_entity_type_id',
      'content_entity_id',
      'content_entity_revision_id',
      'workflow',
      'langcode',
    ),
  ),
  'indexes' => array(
    'content_moderation_state__id__default_langcode__langcode' => array(
      'id',
      'default_langcode',
      'langcode',
    ),
    'content_moderation_state_field__uid__target_id' => array(
      'uid',
    ),
    'content_moderation_state__09628d8dbc' => array(
      'workflow',
    ),
  ),
  'mysql_character_set' => 'utf8mb4',
));

$connection->schema()->createTable('content_moderation_state_revision', array(
  'fields' => array(
    'id' => array(
      'type' => 'int',
      'not null' => TRUE,
      'size' => 'normal',
      'unsigned' => TRUE,
    ),
    'revision_id' => array(
      'type' => 'serial',
      'not null' => TRUE,
      'size' => 'normal',
      'unsigned' => TRUE,
    ),
    'langcode' => array(
      'type' => 'varchar_ascii',
      'not null' => TRUE,
      'length' => '12',
    ),
    'revision_default' => array(
      'type' => 'int',
      'not null' => FALSE,
      'size' => 'tiny',
    ),
  ),
  'primary key' => array(
    'revision_id',
  ),
  'indexes' => array(
    'content_moderation_state__id' => array(
      'id',
    ),
  ),
  'mysql_character_set' => 'utf8mb4',
));

$connection->insert('key_value')
  ->fields(array(
    'collection',
    'name',
    'value',
  ))
  ->values(array(
    'collection' => 'config.entity.key_store.view',
    'name' => 'uuid:57ce6f5c-08eb-4c99-a7e6-2375968ce2a4',
    'value' => 'a:1:{i:0;s:28:"views.view.moderated_content";}',
  ))
  ->values(array(
    'collection' => 'config.entity.key_store.workflow',
    'name' => 'uuid:9b93bdcc-4694-4a24-b632-3836e738fc8f',
    'value' => 'a:1:{i:0;s:28:"workflows.workflow.editorial";}',
  ))
  ->values(array(
    'collection' => 'entity.definitions.installed',
    'name' => 'content_moderation_state.entity_type',
    'value' => 'O:36:"Drupal\Core\Entity\ContentEntityType":40:{s:25:" * revision_metadata_keys";a:1:{s:16:"revision_default";s:16:"revision_default";}s:15:" * static_cache";b:1;s:15:" * render_cache";b:1;s:19:" * persistent_cache";b:1;s:14:" * entity_keys";a:9:{s:2:"id";s:2:"id";s:8:"revision";s:11:"revision_id";s:4:"uuid";s:4:"uuid";s:3:"uid";s:3:"uid";s:5:"owner";s:3:"uid";s:8:"langcode";s:8:"langcode";s:6:"bundle";s:0:"";s:16:"default_langcode";s:16:"default_langcode";s:29:"revision_translation_affected";s:29:"revision_translation_affected";}s:5:" * id";s:24:"content_moderation_state";s:16:" * originalClass";s:55:"Drupal\content_moderation\Entity\ContentModerationState";s:11:" * handlers";a:5:{s:14:"storage_schema";s:61:"Drupal\content_moderation\ContentModerationStateStorageSchema";s:10:"views_data";s:29:"\Drupal\views\EntityViewsData";s:6:"access";s:68:"Drupal\content_moderation\ContentModerationStateAccessControlHandler";s:7:"storage";s:46:"Drupal\Core\Entity\Sql\SqlContentEntityStorage";s:12:"view_builder";s:36:"Drupal\Core\Entity\EntityViewBuilder";}s:19:" * admin_permission";N;s:25:" * permission_granularity";s:11:"entity_type";s:8:" * links";a:0:{}s:21:" * bundle_entity_type";N;s:12:" * bundle_of";N;s:15:" * bundle_label";N;s:13:" * base_table";s:24:"content_moderation_state";s:22:" * revision_data_table";s:39:"content_moderation_state_field_revision";s:17:" * revision_table";s:33:"content_moderation_state_revision";s:13:" * data_table";s:35:"content_moderation_state_field_data";s:11:" * internal";b:1;s:15:" * translatable";b:1;s:19:" * show_revision_ui";b:0;s:8:" * label";O:48:"Drupal\Core\StringTranslation\TranslatableMarkup":3:{s:9:" * string";s:24:"Content moderation state";s:12:" * arguments";a:0:{}s:10:" * options";a:0:{}}s:19:" * label_collection";s:0:"";s:17:" * label_singular";O:48:"Drupal\Core\StringTranslation\TranslatableMarkup":3:{s:9:" * string";s:24:"content moderation state";s:12:" * arguments";a:0:{}s:10:" * options";a:0:{}}s:15:" * label_plural";O:48:"Drupal\Core\StringTranslation\TranslatableMarkup":3:{s:9:" * string";s:25:"content moderation states";s:12:" * arguments";a:0:{}s:10:" * options";a:0:{}}s:14:" * label_count";a:3:{s:8:"singular";s:31:"@count content moderation state";s:6:"plural";s:32:"@count content moderation states";s:7:"context";N;}s:15:" * uri_callback";N;s:8:" * group";s:7:"content";s:14:" * group_label";O:48:"Drupal\Core\StringTranslation\TranslatableMarkup":3:{s:9:" * string";s:7:"Content";s:12:" * arguments";a:0:{}s:10:" * options";a:1:{s:7:"context";s:17:"Entity type group";}}s:22:" * field_ui_base_route";N;s:26:" * common_reference_target";b:0;s:22:" * list_cache_contexts";a:0:{}s:18:" * list_cache_tags";a:1:{i:0;s:29:"content_moderation_state_list";}s:14:" * constraints";a:1:{s:26:"EntityUntranslatableFields";N;}s:13:" * additional";a:0:{}s:8:" * class";s:55:"Drupal\content_moderation\Entity\ContentModerationState";s:11:" * provider";s:18:"content_moderation";s:14:" * _serviceIds";a:0:{}s:18:" * _entityStorages";a:0:{}s:20:" * stringTranslation";N;}',
  ))
  ->values(array(
    'collection' => 'entity.definitions.installed',
    'name' => 'content_moderation_state.field_storage_definitions',
    'value' => 'a:13:{s:2:"id";O:37:"Drupal\Core\Field\BaseFieldDefinition":5:{s:7:" * type";s:7:"integer";s:9:" * schema";a:4:{s:7:"columns";a:1:{s:5:"value";a:3:{s:4:"type";s:3:"int";s:8:"unsigned";b:1;s:4:"size";s:6:"normal";}}s:11:"unique keys";a:0:{}s:7:"indexes";a:0:{}s:12:"foreign keys";a:0:{}}s:10:" * indexes";a:0:{}s:17:" * itemDefinition";O:51:"Drupal\Core\Field\TypedData\FieldItemDataDefinition":2:{s:18:" * fieldDefinition";r:2;s:13:" * definition";a:2:{s:4:"type";s:18:"field_item:integer";s:8:"settings";a:6:{s:8:"unsigned";b:1;s:4:"size";s:6:"normal";s:3:"min";s:0:"";s:3:"max";s:0:"";s:6:"prefix";s:0:"";s:6:"suffix";s:0:"";}}}s:13:" * definition";a:7:{s:5:"label";O:48:"Drupal\Core\StringTranslation\TranslatableMarkup":3:{s:9:" * string";s:2:"ID";s:12:" * arguments";a:0:{}s:10:" * options";a:0:{}}s:9:"read-only";b:1;s:8:"provider";s:18:"content_moderation";s:10:"field_name";s:2:"id";s:11:"entity_type";s:24:"content_moderation_state";s:6:"bundle";N;s:13:"initial_value";N;}}s:4:"uuid";O:37:"Drupal\Core\Field\BaseFieldDefinition":5:{s:7:" * type";s:4:"uuid";s:9:" * schema";a:4:{s:7:"columns";a:1:{s:5:"value";a:3:{s:4:"type";s:13:"varchar_ascii";s:6:"length";i:128;s:6:"binary";b:0;}}s:11:"unique keys";a:1:{s:5:"value";a:1:{i:0;s:5:"value";}}s:7:"indexes";a:0:{}s:12:"foreign keys";a:0:{}}s:10:" * indexes";a:0:{}s:17:" * itemDefinition";O:51:"Drupal\Core\Field\TypedData\FieldItemDataDefinition":2:{s:18:" * fieldDefinition";r:36;s:13:" * definition";a:2:{s:4:"type";s:15:"field_item:uuid";s:8:"settings";a:3:{s:10:"max_length";i:128;s:8:"is_ascii";b:1;s:14:"case_sensitive";b:0;}}}s:13:" * definition";a:7:{s:5:"label";O:48:"Drupal\Core\StringTranslation\TranslatableMarkup":3:{s:9:" * string";s:4:"UUID";s:12:" * arguments";a:0:{}s:10:" * options";a:0:{}}s:9:"read-only";b:1;s:8:"provider";s:18:"content_moderation";s:10:"field_name";s:4:"uuid";s:11:"entity_type";s:24:"content_moderation_state";s:6:"bundle";N;s:13:"initial_value";N;}}s:11:"revision_id";O:37:"Drupal\Core\Field\BaseFieldDefinition":5:{s:7:" * type";s:7:"integer";s:9:" * schema";a:4:{s:7:"columns";a:1:{s:5:"value";a:3:{s:4:"type";s:3:"int";s:8:"unsigned";b:1;s:4:"size";s:6:"normal";}}s:11:"unique keys";a:0:{}s:7:"indexes";a:0:{}s:12:"foreign keys";a:0:{}}s:10:" * indexes";a:0:{}s:17:" * itemDefinition";O:51:"Drupal\Core\Field\TypedData\FieldItemDataDefinition":2:{s:18:" * fieldDefinition";r:69;s:13:" * definition";a:2:{s:4:"type";s:18:"field_item:integer";s:8:"settings";a:6:{s:8:"unsigned";b:1;s:4:"size";s:6:"normal";s:3:"min";s:0:"";s:3:"max";s:0:"";s:6:"prefix";s:0:"";s:6:"suffix";s:0:"";}}}s:13:" * definition";a:7:{s:5:"label";O:48:"Drupal\Core\StringTranslation\TranslatableMarkup":3:{s:9:" * string";s:11:"Revision ID";s:12:" * arguments";a:0:{}s:10:" * options";a:0:{}}s:9:"read-only";b:1;s:8:"provider";s:18:"content_moderation";s:10:"field_name";s:11:"revision_id";s:11:"entity_type";s:24:"content_moderation_state";s:6:"bundle";N;s:13:"initial_value";N;}}s:8:"langcode";O:37:"Drupal\Core\Field\BaseFieldDefinition":5:{s:7:" * type";s:8:"language";s:9:" * schema";a:4:{s:7:"columns";a:1:{s:5:"value";a:2:{s:4:"type";s:13:"varchar_ascii";s:6:"length";i:12;}}s:11:"unique keys";a:0:{}s:7:"indexes";a:0:{}s:12:"foreign keys";a:0:{}}s:10:" * indexes";a:0:{}s:17:" * itemDefinition";O:51:"Drupal\Core\Field\TypedData\FieldItemDataDefinition":2:{s:18:" * fieldDefinition";r:103;s:13:" * definition";a:2:{s:4:"type";s:19:"field_item:language";s:8:"settings";a:0:{}}}s:13:" * definition";a:9:{s:5:"label";O:48:"Drupal\Core\StringTranslation\TranslatableMarkup":3:{s:9:" * string";s:8:"Language";s:12:" * arguments";a:0:{}s:10:" * options";a:0:{}}s:7:"display";a:2:{s:4:"view";a:1:{s:7:"options";a:1:{s:6:"region";s:6:"hidden";}}s:4:"form";a:1:{s:7:"options";a:2:{s:4:"type";s:15:"language_select";s:6:"weight";i:2;}}}s:12:"revisionable";b:1;s:12:"translatable";b:1;s:8:"provider";s:18:"content_moderation";s:10:"field_name";s:8:"langcode";s:11:"entity_type";s:24:"content_moderation_state";s:6:"bundle";N;s:13:"initial_value";N;}}s:3:"uid";O:37:"Drupal\Core\Field\BaseFieldDefinition":5:{s:7:" * type";s:16:"entity_reference";s:9:" * schema";a:4:{s:7:"columns";a:1:{s:9:"target_id";a:3:{s:11:"description";s:28:"The ID of the target entity.";s:4:"type";s:3:"int";s:8:"unsigned";b:1;}}s:7:"indexes";a:1:{s:9:"target_id";a:1:{i:0;s:9:"target_id";}}s:11:"unique keys";a:0:{}s:12:"foreign keys";a:0:{}}s:10:" * indexes";a:0:{}s:17:" * itemDefinition";O:51:"Drupal\Core\Field\TypedData\FieldItemDataDefinition":2:{s:18:" * fieldDefinition";r:139;s:13:" * definition";a:2:{s:4:"type";s:27:"field_item:entity_reference";s:8:"settings";a:3:{s:11:"target_type";s:4:"user";s:7:"handler";s:7:"default";s:16:"handler_settings";a:0:{}}}}s:13:" * definition";a:10:{s:5:"label";O:48:"Drupal\Core\StringTranslation\TranslatableMarkup":3:{s:9:" * string";s:4:"User";s:12:" * arguments";a:0:{}s:10:" * options";a:0:{}}s:12:"translatable";b:1;s:22:"default_value_callback";s:78:"Drupal\content_moderation\Entity\ContentModerationState::getDefaultEntityOwner";s:11:"description";O:48:"Drupal\Core\StringTranslation\TranslatableMarkup":3:{s:9:" * string";s:35:"The username of the entity creator.";s:12:" * arguments";a:0:{}s:10:" * options";a:0:{}}s:12:"revisionable";b:1;s:8:"provider";s:18:"content_moderation";s:10:"field_name";s:3:"uid";s:11:"entity_type";s:24:"content_moderation_state";s:6:"bundle";N;s:13:"initial_value";N;}}s:8:"workflow";O:37:"Drupal\Core\Field\BaseFieldDefinition":5:{s:7:" * type";s:16:"entity_reference";s:9:" * schema";a:4:{s:7:"columns";a:1:{s:9:"target_id";a:3:{s:11:"description";s:28:"The ID of the target entity.";s:4:"type";s:13:"varchar_ascii";s:6:"length";i:255;}}s:7:"indexes";a:1:{s:9:"target_id";a:1:{i:0;s:9:"target_id";}}s:11:"unique keys";a:0:{}s:12:"foreign keys";a:0:{}}s:10:" * indexes";a:0:{}s:17:" * itemDefinition";O:51:"Drupal\Core\Field\TypedData\FieldItemDataDefinition":2:{s:18:" * fieldDefinition";r:178;s:13:" * definition";a:2:{s:4:"type";s:27:"field_item:entity_reference";s:8:"settings";a:3:{s:11:"target_type";s:8:"workflow";s:7:"handler";s:7:"default";s:16:"handler_settings";a:0:{}}}}s:13:" * definition";a:9:{s:5:"label";O:48:"Drupal\Core\StringTranslation\TranslatableMarkup":3:{s:9:" * string";s:8:"Workflow";s:12:" * arguments";a:0:{}s:10:" * options";a:0:{}}s:11:"description";O:48:"Drupal\Core\StringTranslation\TranslatableMarkup":3:{s:9:" * string";s:40:"The workflow the moderation state is in.";s:12:" * arguments";a:0:{}s:10:" * options";a:0:{}}s:8:"required";b:1;s:12:"revisionable";b:1;s:8:"provider";s:18:"content_moderation";s:10:"field_name";s:8:"workflow";s:11:"entity_type";s:24:"content_moderation_state";s:6:"bundle";N;s:13:"initial_value";N;}}s:16:"moderation_state";O:37:"Drupal\Core\Field\BaseFieldDefinition":5:{s:7:" * type";s:6:"string";s:9:" * schema";a:4:{s:7:"columns";a:1:{s:5:"value";a:3:{s:4:"type";s:7:"varchar";s:6:"length";i:255;s:6:"binary";b:0;}}s:11:"unique keys";a:0:{}s:7:"indexes";a:0:{}s:12:"foreign keys";a:0:{}}s:10:" * indexes";a:0:{}s:17:" * itemDefinition";O:51:"Drupal\Core\Field\TypedData\FieldItemDataDefinition":2:{s:18:" * fieldDefinition";r:216;s:13:" * definition";a:2:{s:4:"type";s:17:"field_item:string";s:8:"settings";a:3:{s:10:"max_length";i:255;s:8:"is_ascii";b:0;s:14:"case_sensitive";b:0;}}}s:13:" * definition";a:10:{s:5:"label";O:48:"Drupal\Core\StringTranslation\TranslatableMarkup":3:{s:9:" * string";s:16:"Moderation state";s:12:" * arguments";a:0:{}s:10:" * options";a:0:{}}s:11:"description";O:48:"Drupal\Core\StringTranslation\TranslatableMarkup":3:{s:9:" * string";s:47:"The moderation state of the referenced content.";s:12:" * arguments";a:0:{}s:10:" * options";a:0:{}}s:8:"required";b:1;s:12:"translatable";b:1;s:12:"revisionable";b:1;s:8:"provider";s:18:"content_moderation";s:10:"field_name";s:16:"moderation_state";s:11:"entity_type";s:24:"content_moderation_state";s:6:"bundle";N;s:13:"initial_value";N;}}s:22:"content_entity_type_id";O:37:"Drupal\Core\Field\BaseFieldDefinition":5:{s:7:" * type";s:6:"string";s:9:" * schema";a:4:{s:7:"columns";a:1:{s:5:"value";a:3:{s:4:"type";s:7:"varchar";s:6:"length";i:32;s:6:"binary";b:0;}}s:11:"unique keys";a:0:{}s:7:"indexes";a:0:{}s:12:"foreign keys";a:0:{}}s:10:" * indexes";a:0:{}s:17:" * itemDefinition";O:51:"Drupal\Core\Field\TypedData\FieldItemDataDefinition":2:{s:18:" * fieldDefinition";r:253;s:13:" * definition";a:2:{s:4:"type";s:17:"field_item:string";s:8:"settings";a:3:{s:10:"max_length";i:32;s:8:"is_ascii";b:0;s:14:"case_sensitive";b:0;}}}s:13:" * definition";a:9:{s:5:"label";O:48:"Drupal\Core\StringTranslation\TranslatableMarkup":3:{s:9:" * string";s:22:"Content entity type ID";s:12:" * arguments";a:0:{}s:10:" * options";a:0:{}}s:11:"description";O:48:"Drupal\Core\StringTranslation\TranslatableMarkup":3:{s:9:" * string";s:63:"The ID of the content entity type this moderation state is for.";s:12:" * arguments";a:0:{}s:10:" * options";a:0:{}}s:8:"required";b:1;s:12:"revisionable";b:1;s:8:"provider";s:18:"content_moderation";s:10:"field_name";s:22:"content_entity_type_id";s:11:"entity_type";s:24:"content_moderation_state";s:6:"bundle";N;s:13:"initial_value";N;}}s:17:"content_entity_id";O:37:"Drupal\Core\Field\BaseFieldDefinition":5:{s:7:" * type";s:7:"integer";s:9:" * schema";a:4:{s:7:"columns";a:1:{s:5:"value";a:3:{s:4:"type";s:3:"int";s:8:"unsigned";b:0;s:4:"size";s:6:"normal";}}s:11:"unique keys";a:0:{}s:7:"indexes";a:0:{}s:12:"foreign keys";a:0:{}}s:10:" * indexes";a:0:{}s:17:" * itemDefinition";O:51:"Drupal\Core\Field\TypedData\FieldItemDataDefinition":2:{s:18:" * fieldDefinition";r:289;s:13:" * definition";a:2:{s:4:"type";s:18:"field_item:integer";s:8:"settings";a:6:{s:8:"unsigned";b:0;s:4:"size";s:6:"normal";s:3:"min";s:0:"";s:3:"max";s:0:"";s:6:"prefix";s:0:"";s:6:"suffix";s:0:"";}}}s:13:" * definition";a:9:{s:5:"label";O:48:"Drupal\Core\StringTranslation\TranslatableMarkup":3:{s:9:" * string";s:17:"Content entity ID";s:12:" * arguments";a:0:{}s:10:" * options";a:0:{}}s:11:"description";O:48:"Drupal\Core\StringTranslation\TranslatableMarkup":3:{s:9:" * string";s:58:"The ID of the content entity this moderation state is for.";s:12:" * arguments";a:0:{}s:10:" * options";a:0:{}}s:8:"required";b:1;s:12:"revisionable";b:1;s:8:"provider";s:18:"content_moderation";s:10:"field_name";s:17:"content_entity_id";s:11:"entity_type";s:24:"content_moderation_state";s:6:"bundle";N;s:13:"initial_value";N;}}s:26:"content_entity_revision_id";O:37:"Drupal\Core\Field\BaseFieldDefinition":5:{s:7:" * type";s:7:"integer";s:9:" * schema";a:4:{s:7:"columns";a:1:{s:5:"value";a:3:{s:4:"type";s:3:"int";s:8:"unsigned";b:0;s:4:"size";s:6:"normal";}}s:11:"unique keys";a:0:{}s:7:"indexes";a:0:{}s:12:"foreign keys";a:0:{}}s:10:" * indexes";a:0:{}s:17:" * itemDefinition";O:51:"Drupal\Core\Field\TypedData\FieldItemDataDefinition":2:{s:18:" * fieldDefinition";r:328;s:13:" * definition";a:2:{s:4:"type";s:18:"field_item:integer";s:8:"settings";a:6:{s:8:"unsigned";b:0;s:4:"size";s:6:"normal";s:3:"min";s:0:"";s:3:"max";s:0:"";s:6:"prefix";s:0:"";s:6:"suffix";s:0:"";}}}s:13:" * definition";a:9:{s:5:"label";O:48:"Drupal\Core\StringTranslation\TranslatableMarkup":3:{s:9:" * string";s:26:"Content entity revision ID";s:12:" * arguments";a:0:{}s:10:" * options";a:0:{}}s:11:"description";O:48:"Drupal\Core\StringTranslation\TranslatableMarkup":3:{s:9:" * string";s:67:"The revision ID of the content entity this moderation state is for.";s:12:" * arguments";a:0:{}s:10:" * options";a:0:{}}s:8:"required";b:1;s:12:"revisionable";b:1;s:8:"provider";s:18:"content_moderation";s:10:"field_name";s:26:"content_entity_revision_id";s:11:"entity_type";s:24:"content_moderation_state";s:6:"bundle";N;s:13:"initial_value";N;}}s:16:"default_langcode";O:37:"Drupal\Core\Field\BaseFieldDefinition":5:{s:7:" * type";s:7:"boolean";s:9:" * schema";a:4:{s:7:"columns";a:1:{s:5:"value";a:2:{s:4:"type";s:3:"int";s:4:"size";s:4:"tiny";}}s:11:"unique keys";a:0:{}s:7:"indexes";a:0:{}s:12:"foreign keys";a:0:{}}s:10:" * indexes";a:0:{}s:17:" * itemDefinition";O:51:"Drupal\Core\Field\TypedData\FieldItemDataDefinition":2:{s:18:" * fieldDefinition";r:367;s:13:" * definition";a:2:{s:4:"type";s:18:"field_item:boolean";s:8:"settings";a:2:{s:8:"on_label";O:48:"Drupal\Core\StringTranslation\TranslatableMarkup":3:{s:9:" * string";s:2:"On";s:12:" * arguments";a:0:{}s:10:" * options";a:0:{}}s:9:"off_label";O:48:"Drupal\Core\StringTranslation\TranslatableMarkup":3:{s:9:" * string";s:3:"Off";s:12:" * arguments";a:0:{}s:10:" * options";a:0:{}}}}}s:13:" * definition";a:10:{s:5:"label";O:48:"Drupal\Core\StringTranslation\TranslatableMarkup":3:{s:9:" * string";s:19:"Default translation";s:12:" * arguments";a:0:{}s:10:" * options";a:0:{}}s:11:"description";O:48:"Drupal\Core\StringTranslation\TranslatableMarkup":3:{s:9:" * string";s:58:"A flag indicating whether this is the default translation.";s:12:" * arguments";a:0:{}s:10:" * options";a:0:{}}s:12:"translatable";b:1;s:12:"revisionable";b:1;s:13:"default_value";a:1:{i:0;a:1:{s:5:"value";b:1;}}s:8:"provider";s:18:"content_moderation";s:10:"field_name";s:16:"default_langcode";s:11:"entity_type";s:24:"content_moderation_state";s:6:"bundle";N;s:13:"initial_value";N;}}s:16:"revision_default";O:37:"Drupal\Core\Field\BaseFieldDefinition":5:{s:7:" * type";s:7:"boolean";s:9:" * schema";a:4:{s:7:"columns";a:1:{s:5:"value";a:2:{s:4:"type";s:3:"int";s:4:"size";s:4:"tiny";}}s:11:"unique keys";a:0:{}s:7:"indexes";a:0:{}s:12:"foreign keys";a:0:{}}s:10:" * indexes";a:0:{}s:17:" * itemDefinition";O:51:"Drupal\Core\Field\TypedData\FieldItemDataDefinition":2:{s:18:" * fieldDefinition";r:410;s:13:" * definition";a:2:{s:4:"type";s:18:"field_item:boolean";s:8:"settings";a:2:{s:8:"on_label";O:48:"Drupal\Core\StringTranslation\TranslatableMarkup":3:{s:9:" * string";s:2:"On";s:12:" * arguments";a:0:{}s:10:" * options";a:0:{}}s:9:"off_label";O:48:"Drupal\Core\StringTranslation\TranslatableMarkup":3:{s:9:" * string";s:3:"Off";s:12:" * arguments";a:0:{}s:10:" * options";a:0:{}}}}}s:13:" * definition";a:11:{s:5:"label";O:48:"Drupal\Core\StringTranslation\TranslatableMarkup":3:{s:9:" * string";s:16:"Default revision";s:12:" * arguments";a:0:{}s:10:" * options";a:0:{}}s:11:"description";O:48:"Drupal\Core\StringTranslation\TranslatableMarkup":3:{s:9:" * string";s:72:"A flag indicating whether this was a default revision when it was saved.";s:12:" * arguments";a:0:{}s:10:" * options";a:0:{}}s:16:"storage_required";b:1;s:8:"internal";b:1;s:12:"translatable";b:0;s:12:"revisionable";b:1;s:8:"provider";s:18:"content_moderation";s:10:"field_name";s:16:"revision_default";s:11:"entity_type";s:24:"content_moderation_state";s:6:"bundle";N;s:13:"initial_value";N;}}s:29:"revision_translation_affected";O:37:"Drupal\Core\Field\BaseFieldDefinition":5:{s:7:" * type";s:7:"boolean";s:9:" * schema";a:4:{s:7:"columns";a:1:{s:5:"value";a:2:{s:4:"type";s:3:"int";s:4:"size";s:4:"tiny";}}s:11:"unique keys";a:0:{}s:7:"indexes";a:0:{}s:12:"foreign keys";a:0:{}}s:10:" * indexes";a:0:{}s:17:" * itemDefinition";O:51:"Drupal\Core\Field\TypedData\FieldItemDataDefinition":2:{s:18:" * fieldDefinition";r:452;s:13:" * definition";a:2:{s:4:"type";s:18:"field_item:boolean";s:8:"settings";a:2:{s:8:"on_label";O:48:"Drupal\Core\StringTranslation\TranslatableMarkup":3:{s:9:" * string";s:2:"On";s:12:" * arguments";a:0:{}s:10:" * options";a:0:{}}s:9:"off_label";O:48:"Drupal\Core\StringTranslation\TranslatableMarkup":3:{s:9:" * string";s:3:"Off";s:12:" * arguments";a:0:{}s:10:" * options";a:0:{}}}}}s:13:" * definition";a:10:{s:5:"label";O:48:"Drupal\Core\StringTranslation\TranslatableMarkup":3:{s:9:" * string";s:29:"Revision translation affected";s:12:" * arguments";a:0:{}s:10:" * options";a:0:{}}s:11:"description";O:48:"Drupal\Core\StringTranslation\TranslatableMarkup":3:{s:9:" * string";s:72:"Indicates if the last edit of a translation belongs to current revision.";s:12:" * arguments";a:0:{}s:10:" * options";a:0:{}}s:9:"read-only";b:1;s:12:"revisionable";b:1;s:12:"translatable";b:1;s:8:"provider";s:18:"content_moderation";s:10:"field_name";s:29:"revision_translation_affected";s:11:"entity_type";s:24:"content_moderation_state";s:6:"bundle";N;s:13:"initial_value";N;}}}',
  ))
  ->values(array(
    'collection' => 'entity.definitions.installed',
    'name' => 'workflow.entity_type',
    'value' => 'O:42:"Drupal\Core\Config\Entity\ConfigEntityType":43:{s:16:" * config_prefix";s:8:"workflow";s:15:" * static_cache";b:0;s:14:" * lookup_keys";a:1:{i:0;s:4:"uuid";}s:16:" * config_export";a:4:{i:0;s:2:"id";i:1;s:5:"label";i:2;s:4:"type";i:3;s:13:"type_settings";}s:21:" * mergedConfigExport";a:0:{}s:15:" * render_cache";b:1;s:19:" * persistent_cache";b:1;s:14:" * entity_keys";a:8:{s:2:"id";s:2:"id";s:5:"label";s:5:"label";s:4:"uuid";s:4:"uuid";s:8:"revision";s:0:"";s:6:"bundle";s:0:"";s:8:"langcode";s:8:"langcode";s:16:"default_langcode";s:16:"default_langcode";s:29:"revision_translation_affected";s:29:"revision_translation_affected";}s:5:" * id";s:8:"workflow";s:16:" * originalClass";s:32:"Drupal\workflows\Entity\Workflow";s:11:" * handlers";a:5:{s:6:"access";s:45:"Drupal\workflows\WorkflowAccessControlHandler";s:12:"list_builder";s:36:"Drupal\workflows\WorkflowListBuilder";s:4:"form";a:9:{s:3:"add";s:37:"Drupal\workflows\Form\WorkflowAddForm";s:4:"edit";s:38:"Drupal\workflows\Form\WorkflowEditForm";s:6:"delete";s:40:"Drupal\workflows\Form\WorkflowDeleteForm";s:9:"add-state";s:42:"Drupal\workflows\Form\WorkflowStateAddForm";s:10:"edit-state";s:43:"Drupal\workflows\Form\WorkflowStateEditForm";s:12:"delete-state";s:45:"Drupal\workflows\Form\WorkflowStateDeleteForm";s:14:"add-transition";s:47:"Drupal\workflows\Form\WorkflowTransitionAddForm";s:15:"edit-transition";s:48:"Drupal\workflows\Form\WorkflowTransitionEditForm";s:17:"delete-transition";s:50:"Drupal\workflows\Form\WorkflowTransitionDeleteForm";}s:14:"route_provider";a:1:{s:4:"html";s:49:"Drupal\Core\Entity\Routing\AdminHtmlRouteProvider";}s:7:"storage";s:45:"Drupal\Core\Config\Entity\ConfigEntityStorage";}s:19:" * admin_permission";s:20:"administer workflows";s:25:" * permission_granularity";s:11:"entity_type";s:8:" * links";a:6:{s:8:"add-form";s:36:"/admin/config/workflow/workflows/add";s:9:"edit-form";s:50:"/admin/config/workflow/workflows/manage/{workflow}";s:11:"delete-form";s:57:"/admin/config/workflow/workflows/manage/{workflow}/delete";s:14:"add-state-form";s:60:"/admin/config/workflow/workflows/manage/{workflow}/add_state";s:19:"add-transition-form";s:65:"/admin/config/workflow/workflows/manage/{workflow}/add_transition";s:10:"collection";s:32:"/admin/config/workflow/workflows";}s:21:" * bundle_entity_type";N;s:12:" * bundle_of";N;s:15:" * bundle_label";N;s:13:" * base_table";N;s:22:" * revision_data_table";N;s:17:" * revision_table";N;s:13:" * data_table";N;s:11:" * internal";b:0;s:15:" * translatable";b:0;s:19:" * show_revision_ui";b:0;s:8:" * label";O:48:"Drupal\Core\StringTranslation\TranslatableMarkup":3:{s:9:" * string";s:8:"Workflow";s:12:" * arguments";a:0:{}s:10:" * options";a:0:{}}s:19:" * label_collection";O:48:"Drupal\Core\StringTranslation\TranslatableMarkup":3:{s:9:" * string";s:9:"Workflows";s:12:" * arguments";a:0:{}s:10:" * options";a:0:{}}s:17:" * label_singular";O:48:"Drupal\Core\StringTranslation\TranslatableMarkup":3:{s:9:" * string";s:8:"workflow";s:12:" * arguments";a:0:{}s:10:" * options";a:0:{}}s:15:" * label_plural";O:48:"Drupal\Core\StringTranslation\TranslatableMarkup":3:{s:9:" * string";s:9:"workflows";s:12:" * arguments";a:0:{}s:10:" * options";a:0:{}}s:14:" * label_count";a:3:{s:8:"singular";s:15:"@count workflow";s:6:"plural";s:16:"@count workflows";s:7:"context";N;}s:15:" * uri_callback";N;s:8:" * group";s:13:"configuration";s:14:" * group_label";O:48:"Drupal\Core\StringTranslation\TranslatableMarkup":3:{s:9:" * string";s:13:"Configuration";s:12:" * arguments";a:0:{}s:10:" * options";a:1:{s:7:"context";s:17:"Entity type group";}}s:22:" * field_ui_base_route";N;s:26:" * common_reference_target";b:0;s:22:" * list_cache_contexts";a:0:{}s:18:" * list_cache_tags";a:1:{i:0;s:20:"config:workflow_list";}s:14:" * constraints";a:0:{}s:13:" * additional";a:0:{}s:8:" * class";s:32:"Drupal\workflows\Entity\Workflow";s:11:" * provider";s:9:"workflows";s:14:" * _serviceIds";a:0:{}s:18:" * _entityStorages";a:0:{}s:20:" * stringTranslation";N;}',
  ))
  ->values(array(
    'collection' => 'entity.storage_schema.sql',
    'name' => 'content_moderation_state.entity_schema_data',
    'value' => 'a:4:{s:24:"content_moderation_state";a:2:{s:11:"primary key";a:1:{i:0;s:2:"id";}s:11:"unique keys";a:1:{s:37:"content_moderation_state__revision_id";a:1:{i:0;s:11:"revision_id";}}}s:33:"content_moderation_state_revision";a:2:{s:11:"primary key";a:1:{i:0;s:11:"revision_id";}s:7:"indexes";a:1:{s:28:"content_moderation_state__id";a:1:{i:0;s:2:"id";}}}s:35:"content_moderation_state_field_data";a:3:{s:11:"primary key";a:2:{i:0;s:2:"id";i:1;s:8:"langcode";}s:7:"indexes";a:2:{s:56:"content_moderation_state__id__default_langcode__langcode";a:3:{i:0;s:2:"id";i:1;s:16:"default_langcode";i:2;s:8:"langcode";}s:37:"content_moderation_state__revision_id";a:1:{i:0;s:11:"revision_id";}}s:11:"unique keys";a:1:{s:32:"content_moderation_state__lookup";a:5:{i:0;s:22:"content_entity_type_id";i:1;s:17:"content_entity_id";i:2;s:26:"content_entity_revision_id";i:3;s:8:"workflow";i:4;s:8:"langcode";}}}s:39:"content_moderation_state_field_revision";a:3:{s:11:"primary key";a:2:{i:0;s:11:"revision_id";i:1;s:8:"langcode";}s:7:"indexes";a:1:{s:56:"content_moderation_state__id__default_langcode__langcode";a:3:{i:0;s:2:"id";i:1;s:16:"default_langcode";i:2;s:8:"langcode";}}s:11:"unique keys";a:1:{s:32:"content_moderation_state__lookup";a:5:{i:0;s:22:"content_entity_type_id";i:1;s:17:"content_entity_id";i:2;s:26:"content_entity_revision_id";i:3;s:8:"workflow";i:4;s:8:"langcode";}}}}',
  ))
  ->values(array(
    'collection' => 'entity.storage_schema.sql',
    'name' => 'content_moderation_state.field_schema_data.content_entity_id',
    'value' => 'a:2:{s:35:"content_moderation_state_field_data";a:1:{s:6:"fields";a:1:{s:17:"content_entity_id";a:4:{s:4:"type";s:3:"int";s:8:"unsigned";b:0;s:4:"size";s:6:"normal";s:8:"not null";b:0;}}}s:39:"content_moderation_state_field_revision";a:1:{s:6:"fields";a:1:{s:17:"content_entity_id";a:4:{s:4:"type";s:3:"int";s:8:"unsigned";b:0;s:4:"size";s:6:"normal";s:8:"not null";b:0;}}}}',
  ))
  ->values(array(
    'collection' => 'entity.storage_schema.sql',
    'name' => 'content_moderation_state.field_schema_data.content_entity_revision_id',
    'value' => 'a:2:{s:35:"content_moderation_state_field_data";a:1:{s:6:"fields";a:1:{s:26:"content_entity_revision_id";a:4:{s:4:"type";s:3:"int";s:8:"unsigned";b:0;s:4:"size";s:6:"normal";s:8:"not null";b:0;}}}s:39:"content_moderation_state_field_revision";a:1:{s:6:"fields";a:1:{s:26:"content_entity_revision_id";a:4:{s:4:"type";s:3:"int";s:8:"unsigned";b:0;s:4:"size";s:6:"normal";s:8:"not null";b:0;}}}}',
  ))
  ->values(array(
    'collection' => 'entity.storage_schema.sql',
    'name' => 'content_moderation_state.field_schema_data.content_entity_type_id',
    'value' => 'a:2:{s:35:"content_moderation_state_field_data";a:1:{s:6:"fields";a:1:{s:22:"content_entity_type_id";a:4:{s:4:"type";s:7:"varchar";s:6:"length";i:32;s:6:"binary";b:0;s:8:"not null";b:0;}}}s:39:"content_moderation_state_field_revision";a:1:{s:6:"fields";a:1:{s:22:"content_entity_type_id";a:4:{s:4:"type";s:7:"varchar";s:6:"length";i:32;s:6:"binary";b:0;s:8:"not null";b:0;}}}}',
  ))
  ->values(array(
    'collection' => 'entity.storage_schema.sql',
    'name' => 'content_moderation_state.field_schema_data.default_langcode',
    'value' => 'a:2:{s:35:"content_moderation_state_field_data";a:1:{s:6:"fields";a:1:{s:16:"default_langcode";a:3:{s:4:"type";s:3:"int";s:4:"size";s:4:"tiny";s:8:"not null";b:1;}}}s:39:"content_moderation_state_field_revision";a:1:{s:6:"fields";a:1:{s:16:"default_langcode";a:3:{s:4:"type";s:3:"int";s:4:"size";s:4:"tiny";s:8:"not null";b:1;}}}}',
  ))
  ->values(array(
    'collection' => 'entity.storage_schema.sql',
    'name' => 'content_moderation_state.field_schema_data.id',
    'value' => 'a:4:{s:24:"content_moderation_state";a:1:{s:6:"fields";a:1:{s:2:"id";a:4:{s:4:"type";s:6:"serial";s:8:"unsigned";b:1;s:4:"size";s:6:"normal";s:8:"not null";b:1;}}}s:35:"content_moderation_state_field_data";a:1:{s:6:"fields";a:1:{s:2:"id";a:4:{s:4:"type";s:3:"int";s:8:"unsigned";b:1;s:4:"size";s:6:"normal";s:8:"not null";b:1;}}}s:33:"content_moderation_state_revision";a:1:{s:6:"fields";a:1:{s:2:"id";a:4:{s:4:"type";s:3:"int";s:8:"unsigned";b:1;s:4:"size";s:6:"normal";s:8:"not null";b:1;}}}s:39:"content_moderation_state_field_revision";a:1:{s:6:"fields";a:1:{s:2:"id";a:4:{s:4:"type";s:3:"int";s:8:"unsigned";b:1;s:4:"size";s:6:"normal";s:8:"not null";b:1;}}}}',
  ))
  ->values(array(
    'collection' => 'entity.storage_schema.sql',
    'name' => 'content_moderation_state.field_schema_data.langcode',
    'value' => 'a:4:{s:24:"content_moderation_state";a:1:{s:6:"fields";a:1:{s:8:"langcode";a:3:{s:4:"type";s:13:"varchar_ascii";s:6:"length";i:12;s:8:"not null";b:1;}}}s:35:"content_moderation_state_field_data";a:1:{s:6:"fields";a:1:{s:8:"langcode";a:3:{s:4:"type";s:13:"varchar_ascii";s:6:"length";i:12;s:8:"not null";b:1;}}}s:33:"content_moderation_state_revision";a:1:{s:6:"fields";a:1:{s:8:"langcode";a:3:{s:4:"type";s:13:"varchar_ascii";s:6:"length";i:12;s:8:"not null";b:1;}}}s:39:"content_moderation_state_field_revision";a:1:{s:6:"fields";a:1:{s:8:"langcode";a:3:{s:4:"type";s:13:"varchar_ascii";s:6:"length";i:12;s:8:"not null";b:1;}}}}',
  ))
  ->values(array(
    'collection' => 'entity.storage_schema.sql',
    'name' => 'content_moderation_state.field_schema_data.moderation_state',
    'value' => 'a:2:{s:35:"content_moderation_state_field_data";a:1:{s:6:"fields";a:1:{s:16:"moderation_state";a:4:{s:4:"type";s:7:"varchar";s:6:"length";i:255;s:6:"binary";b:0;s:8:"not null";b:0;}}}s:39:"content_moderation_state_field_revision";a:1:{s:6:"fields";a:1:{s:16:"moderation_state";a:4:{s:4:"type";s:7:"varchar";s:6:"length";i:255;s:6:"binary";b:0;s:8:"not null";b:0;}}}}',
  ))
  ->values(array(
    'collection' => 'entity.storage_schema.sql',
    'name' => 'content_moderation_state.field_schema_data.revision_default',
    'value' => 'a:1:{s:33:"content_moderation_state_revision";a:1:{s:6:"fields";a:1:{s:16:"revision_default";a:3:{s:4:"type";s:3:"int";s:4:"size";s:4:"tiny";s:8:"not null";b:0;}}}}',
  ))
  ->values(array(
    'collection' => 'entity.storage_schema.sql',
    'name' => 'content_moderation_state.field_schema_data.revision_id',
    'value' => 'a:4:{s:24:"content_moderation_state";a:1:{s:6:"fields";a:1:{s:11:"revision_id";a:4:{s:4:"type";s:3:"int";s:8:"unsigned";b:1;s:4:"size";s:6:"normal";s:8:"not null";b:0;}}}s:35:"content_moderation_state_field_data";a:1:{s:6:"fields";a:1:{s:11:"revision_id";a:4:{s:4:"type";s:3:"int";s:8:"unsigned";b:1;s:4:"size";s:6:"normal";s:8:"not null";b:1;}}}s:33:"content_moderation_state_revision";a:1:{s:6:"fields";a:1:{s:11:"revision_id";a:4:{s:4:"type";s:6:"serial";s:8:"unsigned";b:1;s:4:"size";s:6:"normal";s:8:"not null";b:1;}}}s:39:"content_moderation_state_field_revision";a:1:{s:6:"fields";a:1:{s:11:"revision_id";a:4:{s:4:"type";s:3:"int";s:8:"unsigned";b:1;s:4:"size";s:6:"normal";s:8:"not null";b:1;}}}}',
  ))
  ->values(array(
    'collection' => 'entity.storage_schema.sql',
    'name' => 'content_moderation_state.field_schema_data.revision_translation_affected',
    'value' => 'a:2:{s:35:"content_moderation_state_field_data";a:1:{s:6:"fields";a:1:{s:29:"revision_translation_affected";a:3:{s:4:"type";s:3:"int";s:4:"size";s:4:"tiny";s:8:"not null";b:0;}}}s:39:"content_moderation_state_field_revision";a:1:{s:6:"fields";a:1:{s:29:"revision_translation_affected";a:3:{s:4:"type";s:3:"int";s:4:"size";s:4:"tiny";s:8:"not null";b:0;}}}}',
  ))
  ->values(array(
    'collection' => 'entity.storage_schema.sql',
    'name' => 'content_moderation_state.field_schema_data.uid',
    'value' => 'a:2:{s:35:"content_moderation_state_field_data";a:2:{s:6:"fields";a:1:{s:3:"uid";a:4:{s:11:"description";s:28:"The ID of the target entity.";s:4:"type";s:3:"int";s:8:"unsigned";b:1;s:8:"not null";b:1;}}s:7:"indexes";a:1:{s:46:"content_moderation_state_field__uid__target_id";a:1:{i:0;s:3:"uid";}}}s:39:"content_moderation_state_field_revision";a:2:{s:6:"fields";a:1:{s:3:"uid";a:4:{s:11:"description";s:28:"The ID of the target entity.";s:4:"type";s:3:"int";s:8:"unsigned";b:1;s:8:"not null";b:1;}}s:7:"indexes";a:1:{s:46:"content_moderation_state_field__uid__target_id";a:1:{i:0;s:3:"uid";}}}}',
  ))
  ->values(array(
    'collection' => 'entity.storage_schema.sql',
    'name' => 'content_moderation_state.field_schema_data.uuid',
    'value' => 'a:1:{s:24:"content_moderation_state";a:2:{s:6:"fields";a:1:{s:4:"uuid";a:4:{s:4:"type";s:13:"varchar_ascii";s:6:"length";i:128;s:6:"binary";b:0;s:8:"not null";b:1;}}s:11:"unique keys";a:1:{s:43:"content_moderation_state_field__uuid__value";a:1:{i:0;s:4:"uuid";}}}}',
  ))
  ->values(array(
    'collection' => 'entity.storage_schema.sql',
    'name' => 'content_moderation_state.field_schema_data.workflow',
    'value' => 'a:2:{s:35:"content_moderation_state_field_data";a:2:{s:6:"fields";a:1:{s:8:"workflow";a:4:{s:11:"description";s:28:"The ID of the target entity.";s:4:"type";s:13:"varchar_ascii";s:6:"length";i:255;s:8:"not null";b:0;}}s:7:"indexes";a:1:{s:36:"content_moderation_state__09628d8dbc";a:1:{i:0;s:8:"workflow";}}}s:39:"content_moderation_state_field_revision";a:2:{s:6:"fields";a:1:{s:8:"workflow";a:4:{s:11:"description";s:28:"The ID of the target entity.";s:4:"type";s:13:"varchar_ascii";s:6:"length";i:255;s:8:"not null";b:0;}}s:7:"indexes";a:1:{s:36:"content_moderation_state__09628d8dbc";a:1:{i:0;s:8:"workflow";}}}}',
  ))
  ->values(array(
    'collection' => 'system.schema',
    'name' => 'content_moderation',
    'value' => 'i:8700;',
  ))
  ->execute();

// Install the default configuration.
$connection->insert('config')
  ->fields([
    'collection',
    'name',
    'data',
  ])
  // Install the default 'Editorial' workflow.
  ->values([
    'collection' => '',
    'name' => 'workflows.workflow.editorial',
    'data' => 'a:9:{s:4:"uuid";s:36:"43e32143-fb54-4802-964a-09e324354e6b";s:8:"langcode";s:2:"en";s:6:"status";b:1;s:12:"dependencies";a:1:{s:6:"module";a:1:{i:0;s:18:"content_moderation";}}s:5:"_core";a:1:{s:19:"default_config_hash";s:43:"Ln7YAg2WXZ-5wn9ib-v9qOKFxF2YZLnwSKtX-V455hE";}s:2:"id";s:9:"editorial";s:5:"label";s:9:"Editorial";s:4:"type";s:18:"content_moderation";s:13:"type_settings";a:4:{s:6:"states";a:3:{s:8:"archived";a:4:{s:5:"label";s:8:"Archived";s:6:"weight";i:5;s:9:"published";b:0;s:16:"default_revision";b:1;}s:5:"draft";a:4:{s:5:"label";s:5:"Draft";s:9:"published";b:0;s:16:"default_revision";b:0;s:6:"weight";i:-5;}s:9:"published";a:4:{s:5:"label";s:9:"Published";s:9:"published";b:1;s:16:"default_revision";b:1;s:6:"weight";i:0;}}s:11:"transitions";a:5:{s:7:"archive";a:4:{s:5:"label";s:7:"Archive";s:4:"from";a:1:{i:0;s:9:"published";}s:2:"to";s:8:"archived";s:6:"weight";i:2;}s:14:"archived_draft";a:4:{s:5:"label";s:16:"Restore to Draft";s:4:"from";a:1:{i:0;s:8:"archived";}s:2:"to";s:5:"draft";s:6:"weight";i:3;}s:18:"archived_published";a:4:{s:5:"label";s:7:"Restore";s:4:"from";a:1:{i:0;s:8:"archived";}s:2:"to";s:9:"published";s:6:"weight";i:4;}s:16:"create_new_draft";a:4:{s:5:"label";s:16:"Create New Draft";s:2:"to";s:5:"draft";s:6:"weight";i:0;s:4:"from";a:2:{i:0;s:5:"draft";i:1;s:9:"published";}}s:7:"publish";a:4:{s:5:"label";s:7:"Publish";s:2:"to";s:9:"published";s:6:"weight";i:1;s:4:"from";a:2:{i:0;s:5:"draft";i:1;s:9:"published";}}}s:12:"entity_types";a:0:{}s:24:"default_moderation_state";s:5:"draft";}}',
  ])
  // Install the default 'Moderated content' view.
  ->values([
   'collection' => '',
   'name' => 'views.view.moderated_content',
   'data' => 'a:13:{s:4:"uuid";s:36:"56178a9d-e348-4733-86ce-c77533487974";s:8:"langcode";s:2:"en";s:6:"status";b:1;s:12:"dependencies";a:2:{s:8:"enforced";a:1:{s:6:"module";a:1:{i:0;s:18:"content_moderation";}}s:6:"module";a:2:{i:0;s:4:"node";i:1;s:4:"user";}}s:5:"_core";a:1:{s:19:"default_config_hash";s:43:"Kjqf5d4F134C3zua0tXV7LA3m5OEeS_Fowa2dP9kk2I";}s:2:"id";s:17:"moderated_content";s:5:"label";s:17:"Moderated content";s:6:"module";s:5:"views";s:11:"description";s:26:"Find and moderate content.";s:3:"tag";s:0:"";s:10:"base_table";s:19:"node_field_revision";s:10:"base_field";s:3:"vid";s:7:"display";a:2:{s:7:"default";a:6:{s:14:"display_plugin";s:7:"default";s:2:"id";s:7:"default";s:13:"display_title";s:6:"Master";s:8:"position";i:0;s:15:"display_options";a:18:{s:6:"access";a:2:{s:4:"type";s:4:"perm";s:7:"options";a:1:{s:4:"perm";s:28:"view any unpublished content";}}s:5:"cache";a:2:{s:4:"type";s:3:"tag";s:7:"options";a:0:{}}s:5:"query";a:2:{s:4:"type";s:11:"views_query";s:7:"options";a:5:{s:19:"disable_sql_rewrite";b:0;s:8:"distinct";b:0;s:7:"replica";b:0;s:13:"query_comment";s:0:"";s:10:"query_tags";a:0:{}}}s:12:"exposed_form";a:2:{s:4:"type";s:5:"basic";s:7:"options";a:7:{s:13:"submit_button";s:6:"Filter";s:12:"reset_button";b:1;s:18:"reset_button_label";s:5:"Reset";s:19:"exposed_sorts_label";s:7:"Sort by";s:17:"expose_sort_order";b:1;s:14:"sort_asc_label";s:3:"Asc";s:15:"sort_desc_label";s:4:"Desc";}}s:5:"pager";a:2:{s:4:"type";s:4:"full";s:7:"options";a:7:{s:14:"items_per_page";i:50;s:6:"offset";i:0;s:2:"id";i:0;s:11:"total_pages";N;s:4:"tags";a:4:{s:8:"previous";s:12:"â€¹ Previous";s:4:"next";s:8:"Next â€º";s:5:"first";s:8:"Â« First";s:4:"last";s:7:"Last Â»";}s:6:"expose";a:7:{s:14:"items_per_page";b:0;s:20:"items_per_page_label";s:14:"Items per page";s:22:"items_per_page_options";s:13:"5, 10, 25, 50";s:26:"items_per_page_options_all";b:0;s:32:"items_per_page_options_all_label";s:7:"- All -";s:6:"offset";b:0;s:12:"offset_label";s:6:"Offset";}s:8:"quantity";i:9;}}s:5:"style";a:2:{s:4:"type";s:5:"table";s:7:"options";a:12:{s:8:"grouping";a:0:{}s:9:"row_class";s:0:"";s:17:"default_row_class";b:1;s:8:"override";b:1;s:6:"sticky";b:1;s:7:"caption";s:0:"";s:7:"summary";s:0:"";s:11:"description";s:0:"";s:7:"columns";a:5:{s:5:"title";s:5:"title";s:4:"type";s:4:"type";s:4:"name";s:4:"name";s:16:"moderation_state";s:16:"moderation_state";s:7:"changed";s:7:"changed";}s:4:"info";a:5:{s:5:"title";a:6:{s:8:"sortable";b:1;s:18:"default_sort_order";s:3:"asc";s:5:"align";s:0:"";s:9:"separator";s:0:"";s:12:"empty_column";b:0;s:10:"responsive";s:0:"";}s:4:"type";a:6:{s:8:"sortable";b:1;s:18:"default_sort_order";s:3:"asc";s:5:"align";s:0:"";s:9:"separator";s:0:"";s:12:"empty_column";b:0;s:10:"responsive";s:0:"";}s:4:"name";a:6:{s:8:"sortable";b:0;s:18:"default_sort_order";s:3:"asc";s:5:"align";s:0:"";s:9:"separator";s:0:"";s:12:"empty_column";b:0;s:10:"responsive";s:0:"";}s:16:"moderation_state";a:6:{s:8:"sortable";b:1;s:18:"default_sort_order";s:3:"asc";s:5:"align";s:0:"";s:9:"separator";s:0:"";s:12:"empty_column";b:0;s:10:"responsive";s:0:"";}s:7:"changed";a:6:{s:8:"sortable";b:1;s:18:"default_sort_order";s:4:"desc";s:5:"align";s:0:"";s:9:"separator";s:0:"";s:12:"empty_column";b:0;s:10:"responsive";s:0:"";}}s:7:"default";s:7:"changed";s:11:"empty_table";b:1;}}s:3:"row";a:1:{s:4:"type";s:6:"fields";}s:6:"fields";a:6:{s:5:"title";a:37:{s:2:"id";s:5:"title";s:5:"table";s:19:"node_field_revision";s:5:"field";s:5:"title";s:12:"relationship";s:4:"none";s:10:"group_type";s:5:"group";s:11:"admin_label";s:0:"";s:5:"label";s:5:"Title";s:7:"exclude";b:0;s:5:"alter";a:26:{s:10:"alter_text";b:0;s:4:"text";s:0:"";s:9:"make_link";b:0;s:4:"path";s:0:"";s:8:"absolute";b:0;s:8:"external";b:0;s:14:"replace_spaces";b:0;s:9:"path_case";s:4:"none";s:15:"trim_whitespace";b:0;s:3:"alt";s:0:"";s:3:"rel";s:0:"";s:10:"link_class";s:0:"";s:6:"prefix";s:0:"";s:6:"suffix";s:0:"";s:6:"target";s:0:"";s:5:"nl2br";b:0;s:10:"max_length";i:0;s:13:"word_boundary";b:0;s:8:"ellipsis";b:0;s:9:"more_link";b:0;s:14:"more_link_text";s:0:"";s:14:"more_link_path";s:0:"";s:10:"strip_tags";b:0;s:4:"trim";b:0;s:13:"preserve_tags";s:0:"";s:4:"html";b:0;}s:12:"element_type";s:0:"";s:13:"element_class";s:0:"";s:18:"element_label_type";s:0:"";s:19:"element_label_class";s:0:"";s:19:"element_label_colon";b:1;s:20:"element_wrapper_type";s:0:"";s:21:"element_wrapper_class";s:0:"";s:23:"element_default_classes";b:1;s:5:"empty";s:0:"";s:10:"hide_empty";b:0;s:10:"empty_zero";b:0;s:16:"hide_alter_empty";b:1;s:17:"click_sort_column";s:5:"value";s:4:"type";s:6:"string";s:8:"settings";a:1:{s:14:"link_to_entity";b:1;}s:12:"group_column";s:5:"value";s:13:"group_columns";a:0:{}s:10:"group_rows";b:1;s:11:"delta_limit";i:0;s:12:"delta_offset";i:0;s:14:"delta_reversed";b:0;s:16:"delta_first_last";b:0;s:10:"multi_type";s:9:"separator";s:9:"separator";s:2:", ";s:17:"field_api_classes";b:0;s:11:"entity_type";s:4:"node";s:12:"entity_field";s:5:"title";s:9:"plugin_id";s:5:"field";}s:4:"type";a:37:{s:2:"id";s:4:"type";s:5:"table";s:15:"node_field_data";s:5:"field";s:4:"type";s:12:"relationship";s:3:"nid";s:10:"group_type";s:5:"group";s:11:"admin_label";s:0:"";s:5:"label";s:12:"Content type";s:7:"exclude";b:0;s:5:"alter";a:26:{s:10:"alter_text";b:0;s:4:"text";s:0:"";s:9:"make_link";b:0;s:4:"path";s:0:"";s:8:"absolute";b:0;s:8:"external";b:0;s:14:"replace_spaces";b:0;s:9:"path_case";s:4:"none";s:15:"trim_whitespace";b:0;s:3:"alt";s:0:"";s:3:"rel";s:0:"";s:10:"link_class";s:0:"";s:6:"prefix";s:0:"";s:6:"suffix";s:0:"";s:6:"target";s:0:"";s:5:"nl2br";b:0;s:10:"max_length";i:0;s:13:"word_boundary";b:1;s:8:"ellipsis";b:1;s:9:"more_link";b:0;s:14:"more_link_text";s:0:"";s:14:"more_link_path";s:0:"";s:10:"strip_tags";b:0;s:4:"trim";b:0;s:13:"preserve_tags";s:0:"";s:4:"html";b:0;}s:12:"element_type";s:0:"";s:13:"element_class";s:0:"";s:18:"element_label_type";s:0:"";s:19:"element_label_class";s:0:"";s:19:"element_label_colon";b:1;s:20:"element_wrapper_type";s:0:"";s:21:"element_wrapper_class";s:0:"";s:23:"element_default_classes";b:1;s:5:"empty";s:0:"";s:10:"hide_empty";b:0;s:10:"empty_zero";b:0;s:16:"hide_alter_empty";b:1;s:17:"click_sort_column";s:9:"target_id";s:4:"type";s:22:"entity_reference_label";s:8:"settings";a:1:{s:4:"link";b:0;}s:12:"group_column";s:9:"target_id";s:13:"group_columns";a:0:{}s:10:"group_rows";b:1;s:11:"delta_limit";i:0;s:12:"delta_offset";i:0;s:14:"delta_reversed";b:0;s:16:"delta_first_last";b:0;s:10:"multi_type";s:9:"separator";s:9:"separator";s:2:", ";s:17:"field_api_classes";b:0;s:11:"entity_type";s:4:"node";s:12:"entity_field";s:4:"type";s:9:"plugin_id";s:5:"field";}s:4:"name";a:37:{s:2:"id";s:4:"name";s:5:"table";s:16:"users_field_data";s:5:"field";s:4:"name";s:12:"relationship";s:3:"uid";s:10:"group_type";s:5:"group";s:11:"admin_label";s:0:"";s:5:"label";s:6:"Author";s:7:"exclude";b:0;s:5:"alter";a:26:{s:10:"alter_text";b:0;s:4:"text";s:0:"";s:9:"make_link";b:0;s:4:"path";s:0:"";s:8:"absolute";b:0;s:8:"external";b:0;s:14:"replace_spaces";b:0;s:9:"path_case";s:4:"none";s:15:"trim_whitespace";b:0;s:3:"alt";s:0:"";s:3:"rel";s:0:"";s:10:"link_class";s:0:"";s:6:"prefix";s:0:"";s:6:"suffix";s:0:"";s:6:"target";s:0:"";s:5:"nl2br";b:0;s:10:"max_length";i:0;s:13:"word_boundary";b:1;s:8:"ellipsis";b:1;s:9:"more_link";b:0;s:14:"more_link_text";s:0:"";s:14:"more_link_path";s:0:"";s:10:"strip_tags";b:0;s:4:"trim";b:0;s:13:"preserve_tags";s:0:"";s:4:"html";b:0;}s:12:"element_type";s:0:"";s:13:"element_class";s:0:"";s:18:"element_label_type";s:0:"";s:19:"element_label_class";s:0:"";s:19:"element_label_colon";b:1;s:20:"element_wrapper_type";s:0:"";s:21:"element_wrapper_class";s:0:"";s:23:"element_default_classes";b:1;s:5:"empty";s:0:"";s:10:"hide_empty";b:0;s:10:"empty_zero";b:0;s:16:"hide_alter_empty";b:1;s:17:"click_sort_column";s:5:"value";s:4:"type";s:9:"user_name";s:8:"settings";a:1:{s:14:"link_to_entity";b:1;}s:12:"group_column";s:5:"value";s:13:"group_columns";a:0:{}s:10:"group_rows";b:1;s:11:"delta_limit";i:0;s:12:"delta_offset";i:0;s:14:"delta_reversed";b:0;s:16:"delta_first_last";b:0;s:10:"multi_type";s:9:"separator";s:9:"separator";s:2:", ";s:17:"field_api_classes";b:0;s:11:"entity_type";s:4:"user";s:12:"entity_field";s:4:"name";s:9:"plugin_id";s:5:"field";}s:16:"moderation_state";a:36:{s:2:"id";s:16:"moderation_state";s:5:"table";s:19:"node_field_revision";s:5:"field";s:16:"moderation_state";s:12:"relationship";s:4:"none";s:10:"group_type";s:5:"group";s:11:"admin_label";s:0:"";s:5:"label";s:16:"Moderation state";s:7:"exclude";b:0;s:5:"alter";a:26:{s:10:"alter_text";b:0;s:4:"text";s:0:"";s:9:"make_link";b:0;s:4:"path";s:0:"";s:8:"absolute";b:0;s:8:"external";b:0;s:14:"replace_spaces";b:0;s:9:"path_case";s:4:"none";s:15:"trim_whitespace";b:0;s:3:"alt";s:0:"";s:3:"rel";s:0:"";s:10:"link_class";s:0:"";s:6:"prefix";s:0:"";s:6:"suffix";s:0:"";s:6:"target";s:0:"";s:5:"nl2br";b:0;s:10:"max_length";i:0;s:13:"word_boundary";b:1;s:8:"ellipsis";b:1;s:9:"more_link";b:0;s:14:"more_link_text";s:0:"";s:14:"more_link_path";s:0:"";s:10:"strip_tags";b:0;s:4:"trim";b:0;s:13:"preserve_tags";s:0:"";s:4:"html";b:0;}s:12:"element_type";s:0:"";s:13:"element_class";s:0:"";s:18:"element_label_type";s:0:"";s:19:"element_label_class";s:0:"";s:19:"element_label_colon";b:1;s:20:"element_wrapper_type";s:0:"";s:21:"element_wrapper_class";s:0:"";s:23:"element_default_classes";b:1;s:5:"empty";s:0:"";s:10:"hide_empty";b:0;s:10:"empty_zero";b:0;s:16:"hide_alter_empty";b:1;s:17:"click_sort_column";s:5:"value";s:4:"type";s:24:"content_moderation_state";s:8:"settings";a:0:{}s:12:"group_column";s:5:"value";s:13:"group_columns";a:0:{}s:10:"group_rows";b:1;s:11:"delta_limit";i:0;s:12:"delta_offset";i:0;s:14:"delta_reversed";b:0;s:16:"delta_first_last";b:0;s:10:"multi_type";s:9:"separator";s:9:"separator";s:2:", ";s:17:"field_api_classes";b:0;s:11:"entity_type";s:4:"node";s:9:"plugin_id";s:5:"field";}s:7:"changed";a:37:{s:2:"id";s:7:"changed";s:5:"table";s:19:"node_field_revision";s:5:"field";s:7:"changed";s:12:"relationship";s:4:"none";s:10:"group_type";s:5:"group";s:11:"admin_label";s:0:"";s:5:"label";s:7:"Updated";s:7:"exclude";b:0;s:5:"alter";a:26:{s:10:"alter_text";b:0;s:4:"text";s:0:"";s:9:"make_link";b:0;s:4:"path";s:0:"";s:8:"absolute";b:0;s:8:"external";b:0;s:14:"replace_spaces";b:0;s:9:"path_case";s:4:"none";s:15:"trim_whitespace";b:0;s:3:"alt";s:0:"";s:3:"rel";s:0:"";s:10:"link_class";s:0:"";s:6:"prefix";s:0:"";s:6:"suffix";s:0:"";s:6:"target";s:0:"";s:5:"nl2br";b:0;s:10:"max_length";i:0;s:13:"word_boundary";b:0;s:8:"ellipsis";b:0;s:9:"more_link";b:0;s:14:"more_link_text";s:0:"";s:14:"more_link_path";s:0:"";s:10:"strip_tags";b:0;s:4:"trim";b:0;s:13:"preserve_tags";s:0:"";s:4:"html";b:0;}s:12:"element_type";s:0:"";s:13:"element_class";s:0:"";s:18:"element_label_type";s:0:"";s:19:"element_label_class";s:0:"";s:19:"element_label_colon";b:1;s:20:"element_wrapper_type";s:0:"";s:21:"element_wrapper_class";s:0:"";s:23:"element_default_classes";b:1;s:5:"empty";s:0:"";s:10:"hide_empty";b:0;s:10:"empty_zero";b:0;s:16:"hide_alter_empty";b:1;s:17:"click_sort_column";s:5:"value";s:4:"type";s:9:"timestamp";s:8:"settings";a:3:{s:11:"date_format";s:5:"short";s:18:"custom_date_format";s:0:"";s:8:"timezone";s:0:"";}s:12:"group_column";s:5:"value";s:13:"group_columns";a:0:{}s:10:"group_rows";b:1;s:11:"delta_limit";i:0;s:12:"delta_offset";i:0;s:14:"delta_reversed";b:0;s:16:"delta_first_last";b:0;s:10:"multi_type";s:9:"separator";s:9:"separator";s:2:", ";s:17:"field_api_classes";b:0;s:11:"entity_type";s:4:"node";s:12:"entity_field";s:7:"changed";s:9:"plugin_id";s:5:"field";}s:10:"operations";a:24:{s:2:"id";s:10:"operations";s:5:"table";s:13:"node_revision";s:5:"field";s:10:"operations";s:12:"relationship";s:4:"none";s:10:"group_type";s:5:"group";s:11:"admin_label";s:0:"";s:5:"label";s:10:"Operations";s:7:"exclude";b:0;s:5:"alter";a:26:{s:10:"alter_text";b:0;s:4:"text";s:0:"";s:9:"make_link";b:0;s:4:"path";s:0:"";s:8:"absolute";b:0;s:8:"external";b:0;s:14:"replace_spaces";b:0;s:9:"path_case";s:4:"none";s:15:"trim_whitespace";b:0;s:3:"alt";s:0:"";s:3:"rel";s:0:"";s:10:"link_class";s:0:"";s:6:"prefix";s:0:"";s:6:"suffix";s:0:"";s:6:"target";s:0:"";s:5:"nl2br";b:0;s:10:"max_length";i:0;s:13:"word_boundary";b:1;s:8:"ellipsis";b:1;s:9:"more_link";b:0;s:14:"more_link_text";s:0:"";s:14:"more_link_path";s:0:"";s:10:"strip_tags";b:0;s:4:"trim";b:0;s:13:"preserve_tags";s:0:"";s:4:"html";b:0;}s:12:"element_type";s:0:"";s:13:"element_class";s:0:"";s:18:"element_label_type";s:0:"";s:19:"element_label_class";s:0:"";s:19:"element_label_colon";b:1;s:20:"element_wrapper_type";s:0:"";s:21:"element_wrapper_class";s:0:"";s:23:"element_default_classes";b:1;s:5:"empty";s:0:"";s:10:"hide_empty";b:0;s:10:"empty_zero";b:0;s:16:"hide_alter_empty";b:1;s:11:"destination";b:1;s:11:"entity_type";s:4:"node";s:9:"plugin_id";s:17:"entity_operations";}}s:7:"filters";a:6:{s:36:"latest_translation_affected_revision";a:15:{s:2:"id";s:36:"latest_translation_affected_revision";s:5:"table";s:13:"node_revision";s:5:"field";s:36:"latest_translation_affected_revision";s:12:"relationship";s:4:"none";s:10:"group_type";s:5:"group";s:11:"admin_label";s:0:"";s:8:"operator";s:1:"=";s:5:"value";s:0:"";s:5:"group";i:1;s:7:"exposed";b:0;s:6:"expose";a:12:{s:11:"operator_id";s:0:"";s:5:"label";s:0:"";s:11:"description";s:0:"";s:12:"use_operator";b:0;s:8:"operator";s:0:"";s:10:"identifier";s:0:"";s:8:"required";b:0;s:8:"remember";b:0;s:8:"multiple";b:0;s:14:"remember_roles";a:1:{s:13:"authenticated";s:13:"authenticated";}s:24:"operator_limit_selection";b:0;s:13:"operator_list";a:0:{}}s:10:"is_grouped";b:0;s:10:"group_info";a:10:{s:5:"label";s:0:"";s:11:"description";s:0:"";s:10:"identifier";s:0:"";s:8:"optional";b:1;s:6:"widget";s:6:"select";s:8:"multiple";b:0;s:8:"remember";b:0;s:13:"default_group";s:3:"All";s:22:"default_group_multiple";a:0:{}s:11:"group_items";a:0:{}}s:11:"entity_type";s:4:"node";s:9:"plugin_id";s:36:"latest_translation_affected_revision";}s:5:"title";a:16:{s:2:"id";s:5:"title";s:5:"table";s:19:"node_field_revision";s:5:"field";s:5:"title";s:12:"relationship";s:4:"none";s:10:"group_type";s:5:"group";s:11:"admin_label";s:0:"";s:8:"operator";s:8:"contains";s:5:"value";s:0:"";s:5:"group";i:1;s:7:"exposed";b:1;s:6:"expose";a:12:{s:11:"operator_id";s:8:"title_op";s:5:"label";s:5:"Title";s:11:"description";s:0:"";s:12:"use_operator";b:0;s:8:"operator";s:8:"title_op";s:10:"identifier";s:5:"title";s:8:"required";b:0;s:8:"remember";b:0;s:8:"multiple";b:0;s:14:"remember_roles";a:3:{s:13:"authenticated";s:13:"authenticated";s:9:"anonymous";s:1:"0";s:13:"administrator";s:1:"0";}s:24:"operator_limit_selection";b:0;s:13:"operator_list";a:0:{}}s:10:"is_grouped";b:0;s:10:"group_info";a:10:{s:5:"label";s:0:"";s:11:"description";s:0:"";s:10:"identifier";s:0:"";s:8:"optional";b:1;s:6:"widget";s:6:"select";s:8:"multiple";b:0;s:8:"remember";b:0;s:13:"default_group";s:3:"All";s:22:"default_group_multiple";a:0:{}s:11:"group_items";a:0:{}}s:11:"entity_type";s:4:"node";s:12:"entity_field";s:5:"title";s:9:"plugin_id";s:6:"string";}s:4:"type";a:16:{s:2:"id";s:4:"type";s:5:"table";s:15:"node_field_data";s:5:"field";s:4:"type";s:12:"relationship";s:3:"nid";s:10:"group_type";s:5:"group";s:11:"admin_label";s:0:"";s:8:"operator";s:2:"in";s:5:"value";a:0:{}s:5:"group";i:1;s:7:"exposed";b:1;s:6:"expose";a:13:{s:11:"operator_id";s:7:"type_op";s:5:"label";s:12:"Content type";s:11:"description";s:0:"";s:12:"use_operator";b:0;s:8:"operator";s:7:"type_op";s:10:"identifier";s:4:"type";s:8:"required";b:0;s:8:"remember";b:0;s:8:"multiple";b:0;s:14:"remember_roles";a:3:{s:13:"authenticated";s:13:"authenticated";s:9:"anonymous";s:1:"0";s:13:"administrator";s:1:"0";}s:6:"reduce";b:0;s:24:"operator_limit_selection";b:0;s:13:"operator_list";a:0:{}}s:10:"is_grouped";b:0;s:10:"group_info";a:10:{s:5:"label";s:0:"";s:11:"description";s:0:"";s:10:"identifier";s:0:"";s:8:"optional";b:1;s:6:"widget";s:6:"select";s:8:"multiple";b:0;s:8:"remember";b:0;s:13:"default_group";s:3:"All";s:22:"default_group_multiple";a:0:{}s:11:"group_items";a:0:{}}s:11:"entity_type";s:4:"node";s:12:"entity_field";s:4:"type";s:9:"plugin_id";s:6:"bundle";}s:16:"moderation_state";a:15:{s:2:"id";s:16:"moderation_state";s:5:"table";s:19:"node_field_revision";s:5:"field";s:16:"moderation_state";s:12:"relationship";s:4:"none";s:10:"group_type";s:5:"group";s:11:"admin_label";s:0:"";s:8:"operator";s:2:"in";s:5:"value";a:2:{s:15:"editorial-draft";s:15:"editorial-draft";s:18:"editorial-archived";s:18:"editorial-archived";}s:5:"group";i:1;s:7:"exposed";b:1;s:6:"expose";a:13:{s:11:"operator_id";s:19:"moderation_state_op";s:5:"label";s:16:"Moderation state";s:11:"description";s:0:"";s:12:"use_operator";b:0;s:8:"operator";s:19:"moderation_state_op";s:10:"identifier";s:16:"moderation_state";s:8:"required";b:0;s:8:"remember";b:0;s:8:"multiple";b:0;s:14:"remember_roles";a:3:{s:13:"authenticated";s:13:"authenticated";s:9:"anonymous";s:1:"0";s:13:"administrator";s:1:"0";}s:6:"reduce";b:1;s:24:"operator_limit_selection";b:0;s:13:"operator_list";a:0:{}}s:10:"is_grouped";b:0;s:10:"group_info";a:10:{s:5:"label";s:0:"";s:11:"description";s:0:"";s:10:"identifier";s:0:"";s:8:"optional";b:1;s:6:"widget";s:6:"select";s:8:"multiple";b:0;s:8:"remember";b:0;s:13:"default_group";s:3:"All";s:22:"default_group_multiple";a:0:{}s:11:"group_items";a:0:{}}s:11:"entity_type";s:4:"node";s:9:"plugin_id";s:23:"moderation_state_filter";}s:8:"langcode";a:16:{s:2:"id";s:8:"langcode";s:5:"table";s:19:"node_field_revision";s:5:"field";s:8:"langcode";s:12:"relationship";s:4:"none";s:10:"group_type";s:5:"group";s:11:"admin_label";s:0:"";s:8:"operator";s:2:"in";s:5:"value";a:0:{}s:5:"group";i:1;s:7:"exposed";b:1;s:6:"expose";a:13:{s:11:"operator_id";s:11:"langcode_op";s:5:"label";s:8:"Language";s:11:"description";s:0:"";s:12:"use_operator";b:0;s:8:"operator";s:11:"langcode_op";s:10:"identifier";s:8:"langcode";s:8:"required";b:0;s:8:"remember";b:0;s:8:"multiple";b:0;s:14:"remember_roles";a:3:{s:13:"authenticated";s:13:"authenticated";s:9:"anonymous";s:1:"0";s:13:"administrator";s:1:"0";}s:6:"reduce";b:0;s:24:"operator_limit_selection";b:0;s:13:"operator_list";a:0:{}}s:10:"is_grouped";b:0;s:10:"group_info";a:10:{s:5:"label";s:0:"";s:11:"description";s:0:"";s:10:"identifier";s:0:"";s:8:"optional";b:1;s:6:"widget";s:6:"select";s:8:"multiple";b:0;s:8:"remember";b:0;s:13:"default_group";s:3:"All";s:22:"default_group_multiple";a:0:{}s:11:"group_items";a:0:{}}s:11:"entity_type";s:4:"node";s:12:"entity_field";s:8:"langcode";s:9:"plugin_id";s:8:"language";}s:18:"moderation_state_1";a:15:{s:2:"id";s:18:"moderation_state_1";s:5:"table";s:19:"node_field_revision";s:5:"field";s:16:"moderation_state";s:12:"relationship";s:4:"none";s:10:"group_type";s:5:"group";s:11:"admin_label";s:0:"";s:8:"operator";s:6:"not in";s:5:"value";a:1:{s:19:"editorial-published";s:19:"editorial-published";}s:5:"group";i:1;s:7:"exposed";b:0;s:6:"expose";a:13:{s:11:"operator_id";s:0:"";s:5:"label";s:0:"";s:11:"description";s:0:"";s:12:"use_operator";b:0;s:8:"operator";s:0:"";s:10:"identifier";s:0:"";s:8:"required";b:0;s:8:"remember";b:0;s:8:"multiple";b:0;s:14:"remember_roles";a:1:{s:13:"authenticated";s:13:"authenticated";}s:6:"reduce";b:0;s:24:"operator_limit_selection";b:0;s:13:"operator_list";a:0:{}}s:10:"is_grouped";b:0;s:10:"group_info";a:10:{s:5:"label";s:0:"";s:11:"description";s:0:"";s:10:"identifier";s:0:"";s:8:"optional";b:1;s:6:"widget";s:6:"select";s:8:"multiple";b:0;s:8:"remember";b:0;s:13:"default_group";s:3:"All";s:22:"default_group_multiple";a:0:{}s:11:"group_items";a:0:{}}s:11:"entity_type";s:4:"node";s:9:"plugin_id";s:23:"moderation_state_filter";}}s:5:"sorts";a:0:{}s:5:"title";s:17:"Moderated content";s:6:"header";a:0:{}s:6:"footer";a:0:{}s:5:"empty";a:1:{s:16:"area_text_custom";a:10:{s:2:"id";s:16:"area_text_custom";s:5:"table";s:5:"views";s:5:"field";s:16:"area_text_custom";s:12:"relationship";s:4:"none";s:10:"group_type";s:5:"group";s:11:"admin_label";s:0:"";s:5:"empty";b:1;s:8:"tokenize";b:0;s:7:"content";s:98:"No moderated content available. Only pending versions of content, such as drafts, are listed here.";s:9:"plugin_id";s:11:"text_custom";}}s:13:"relationships";a:2:{s:3:"nid";a:10:{s:2:"id";s:3:"nid";s:5:"table";s:19:"node_field_revision";s:5:"field";s:3:"nid";s:12:"relationship";s:4:"none";s:10:"group_type";s:5:"group";s:11:"admin_label";s:47:"Get the actual content from a content revision.";s:8:"required";b:0;s:11:"entity_type";s:4:"node";s:12:"entity_field";s:3:"nid";s:9:"plugin_id";s:8:"standard";}s:3:"uid";a:10:{s:2:"id";s:3:"uid";s:5:"table";s:19:"node_field_revision";s:5:"field";s:3:"uid";s:12:"relationship";s:4:"none";s:10:"group_type";s:5:"group";s:11:"admin_label";s:4:"User";s:8:"required";b:0;s:11:"entity_type";s:4:"node";s:12:"entity_field";s:3:"uid";s:9:"plugin_id";s:8:"standard";}}s:9:"arguments";a:0:{}s:17:"display_extenders";a:0:{}s:13:"filter_groups";a:2:{s:8:"operator";s:3:"AND";s:6:"groups";a:1:{i:1;s:3:"AND";}}}s:14:"cache_metadata";a:3:{s:7:"max-age";i:-1;s:8:"contexts";a:6:{i:0;s:26:"languages:language_content";i:1;s:28:"languages:language_interface";i:2;s:3:"url";i:3;s:14:"url.query_args";i:4;s:21:"user.node_grants:view";i:5;s:16:"user.permissions";}s:4:"tags";a:0:{}}}s:17:"moderated_content";a:6:{s:14:"display_plugin";s:4:"page";s:2:"id";s:17:"moderated_content";s:13:"display_title";s:17:"Moderated content";s:8:"position";i:1;s:15:"display_options";a:3:{s:17:"display_extenders";a:0:{}s:4:"path";s:23:"admin/content/moderated";s:19:"display_description";s:0:"";}s:14:"cache_metadata";a:3:{s:7:"max-age";i:-1;s:8:"contexts";a:6:{i:0;s:26:"languages:language_content";i:1;s:28:"languages:language_interface";i:2;s:3:"url";i:3;s:14:"url.query_args";i:4;s:21:"user.node_grants:view";i:5;s:16:"user.permissions";}s:4:"tags";a:0:{}}}}}',
 ])
 ->execute();

 $connection->insert('menu_tree')
   ->fields([
     'menu_name',
     'id',
     'parent',
     'route_name',
     'route_param_key',
     'route_parameters',
     'url',
     'title',
     'description',
     'class',
     'options',
     'provider',
     'enabled',
     'discovered',
     'expanded',
     'weight',
     'metadata',
     'has_children',
     'depth',
     'p1',
     'p2',
     'p3',
     'p4',
     'p5',
     'p6',
     'p7',
     'p8',
     'p9',
     'form_class',
   ])
   ->values([
     'menu_name' => 'admin',
     'id' => 'entity.workflow.collection',
     'parent' => 'system.admin_config_workflow',
     'route_name' => 'entity.workflow.collection',
     'route_param_key' => '',
     'route_parameters' => 'a:0:{}',
     'url' => '',
     'title' => 'O:48:"Drupal\Core\StringTranslation\TranslatableMarkup":3:{s:9:"�*�string";s:9:"Workflows";s:12:"�*�arguments";a:0:{}s:10:"�*�options";a:0:{}}',
     'description' => 'O:48:"Drupal\Core\StringTranslation\TranslatableMarkup":3:{s:9:"�*�string";s:20:"Configure workflows.";s:12:"�*�arguments";a:0:{}s:10:"�*�options";a:0:{}}',
     'class' => 'Drupal\Core\Menu\MenuLinkDefault',
     'options' => 'a:0:{}',
     'provider' => 'workflows',
     'enabled' => '1',
     'discovered' => '1',
     'expanded' => '0',
     'weight' => '0',
     'metadata' => 'a:0:{}',
     'has_children' => '0',
     'depth' => '4',
     'p1' => '1',
     'p2' => '6',
     'p3' => '24',
     'p4' => '63',
     'p5' => '0',
     'p6' => '0',
     'p7' => '0',
     'p8' => '0',
     'p9' => '0',
     'form_class' => 'Drupal\Core\Menu\Form\MenuLinkDefaultForm',
   ])
   ->execute();

 // Insert the routes.
 $connection->insert('router')
   ->fields([
     'name',
     'path',
     'pattern_outline',
     'fit',
     'route',
     'number_parts',
   ])
   ->values([
     'name' => 'content_moderation.admin_moderated_content',
     'path' => '/admin/content/moderated',
     'pattern_outline' => '/admin/content/moderated',
     'fit' => '7',
     'route' => 'C:31:"Symfony\Component\Routing\Route":1333:{a:9:{s:4:"path";s:24:"/admin/content/moderated";s:4:"host";s:0:"";s:8:"defaults";a:5:{s:11:"_controller";s:47:"Drupal\views\Routing\ViewPageController::handle";s:6:"_title";s:17:"Moderated content";s:7:"view_id";s:17:"moderated_content";s:10:"display_id";s:17:"moderated_content";s:30:"_view_display_show_admin_links";b:1;}s:12:"requirements";a:2:{s:11:"_permission";s:28:"view any unpublished content";s:7:"_format";s:4:"html";}s:7:"options";a:9:{s:14:"compiler_class";s:33:"Drupal\Core\Routing\RouteCompiler";s:18:"_view_argument_map";a:0:{}s:23:"_view_display_plugin_id";s:4:"page";s:26:"_view_display_plugin_class";s:38:"Drupal\views\Plugin\views\display\Page";s:30:"_view_display_show_admin_links";b:1;s:16:"returns_response";b:0;s:4:"utf8";b:1;s:12:"_admin_route";b:1;s:14:"_access_checks";a:1:{i:0;s:23:"access_check.permission";}}s:7:"schemes";a:0:{}s:7:"methods";a:0:{}s:9:"condition";s:0:"";s:8:"compiled";C:33:"Drupal\Core\Routing\CompiledRoute":369:{a:11:{s:4:"vars";a:0:{}s:11:"path_prefix";s:0:"";s:10:"path_regex";s:31:"#^/admin/content/moderated$#sDu";s:11:"path_tokens";a:1:{i:0;a:2:{i:0;s:4:"text";i:1;s:24:"/admin/content/moderated";}}s:9:"path_vars";a:0:{}s:10:"host_regex";N;s:11:"host_tokens";a:0:{}s:9:"host_vars";a:0:{}s:3:"fit";i:7;s:14:"patternOutline";s:24:"/admin/content/moderated";s:8:"numParts";i:3;}}}}',
     'number_parts' => '3',
   ])
   ->values([
     'name' => 'content_moderation.workflow_type_edit_form',
     'path' => '/admin/config/workflow/workflows/manage/{workflow}/type/{entity_type_id}',
     'pattern_outline' => '/admin/config/workflow/workflows/manage/%/type/%',
     'fit' => '250',
     'route' => 'C:31:"Symfony\Component\Routing\Route":1707:{a:9:{s:4:"path";s:72:"/admin/config/workflow/workflows/manage/{workflow}/type/{entity_type_id}";s:4:"host";s:0:"";s:8:"defaults";a:2:{s:5:"_form";s:73:"\Drupal\content_moderation\Form\ContentModerationConfigureEntityTypesForm";s:15:"_title_callback";s:83:"\Drupal\content_moderation\Form\ContentModerationConfigureEntityTypesForm::getTitle";}s:12:"requirements";a:1:{s:11:"_permission";s:20:"administer workflows";}s:7:"options";a:5:{s:14:"compiler_class";s:33:"Drupal\Core\Routing\RouteCompiler";s:4:"utf8";b:1;s:10:"parameters";a:1:{s:8:"workflow";a:2:{s:4:"type";s:15:"entity:workflow";s:9:"converter";s:63:"drupal.proxy_original_service.paramconverter.configentity_admin";}}s:12:"_admin_route";b:1;s:14:"_access_checks";a:1:{i:0;s:23:"access_check.permission";}}s:7:"schemes";a:0:{}s:7:"methods";a:2:{i:0;s:3:"GET";i:1;s:4:"POST";}s:9:"condition";s:0:"";s:8:"compiled";C:33:"Drupal\Core\Routing\CompiledRoute":786:{a:11:{s:4:"vars";a:2:{i:0;s:8:"workflow";i:1;s:14:"entity_type_id";}s:11:"path_prefix";s:0:"";s:10:"path_regex";s:99:"#^/admin/config/workflow/workflows/manage/(?P<workflow>[^/]++)/type/(?P<entity_type_id>[^/]++)$#sDu";s:11:"path_tokens";a:4:{i:0;a:5:{i:0;s:8:"variable";i:1;s:1:"/";i:2;s:6:"[^/]++";i:3;s:14:"entity_type_id";i:4;b:1;}i:1;a:2:{i:0;s:4:"text";i:1;s:5:"/type";}i:2;a:5:{i:0;s:8:"variable";i:1;s:1:"/";i:2;s:6:"[^/]++";i:3;s:8:"workflow";i:4;b:1;}i:3;a:2:{i:0;s:4:"text";i:1;s:39:"/admin/config/workflow/workflows/manage";}}s:9:"path_vars";a:2:{i:0;s:8:"workflow";i:1;s:14:"entity_type_id";}s:10:"host_regex";N;s:11:"host_tokens";a:0:{}s:9:"host_vars";a:0:{}s:3:"fit";i:250;s:14:"patternOutline";s:48:"/admin/config/workflow/workflows/manage/%/type/%";s:8:"numParts";i:8;}}}}',
     'number_parts' => '8',
   ])
   ->values([
     'name' => 'entity.block_content.latest_version',
     'path' => '/block/{block_content}/latest',
     'pattern_outline' => '/block/%/latest',
     'fit' => '5',
     'route' => 'C:31:"Symfony\Component\Routing\Route":1494:{a:9:{s:4:"path";s:29:"/block/{block_content}/latest";s:4:"host";s:0:"";s:8:"defaults";a:2:{s:12:"_entity_view";s:18:"block_content.full";s:15:"_title_callback";s:54:"\Drupal\Core\Entity\Controller\EntityController::title";}s:12:"requirements";a:3:{s:14:"_entity_access";s:18:"block_content.view";s:34:"_content_moderation_latest_version";s:4:"TRUE";s:13:"block_content";s:3:"\d+";}s:7:"options";a:5:{s:14:"compiler_class";s:33:"Drupal\Core\Routing\RouteCompiler";s:31:"_content_moderation_entity_type";s:13:"block_content";s:10:"parameters";a:1:{s:13:"block_content";a:3:{s:4:"type";s:20:"entity:block_content";s:20:"load_latest_revision";b:1;s:9:"converter";s:21:"paramconverter.entity";}}s:14:"_access_checks";a:2:{i:0;s:19:"access_check.entity";i:1;s:28:"access_check.latest_revision";}s:4:"utf8";b:1;}s:7:"schemes";a:0:{}s:7:"methods";a:2:{i:0;s:3:"GET";i:1;s:4:"POST";}s:9:"condition";s:0:"";s:8:"compiled";C:33:"Drupal\Core\Routing\CompiledRoute":534:{a:11:{s:4:"vars";a:1:{i:0;s:13:"block_content";}s:11:"path_prefix";s:0:"";s:10:"path_regex";s:43:"#^/block/(?P<block_content>\d+)/latest$#sDu";s:11:"path_tokens";a:3:{i:0;a:2:{i:0;s:4:"text";i:1;s:7:"/latest";}i:1;a:5:{i:0;s:8:"variable";i:1;s:1:"/";i:2;s:3:"\d+";i:3;s:13:"block_content";i:4;b:1;}i:2;a:2:{i:0;s:4:"text";i:1;s:6:"/block";}}s:9:"path_vars";a:1:{i:0;s:13:"block_content";}s:10:"host_regex";N;s:11:"host_tokens";a:0:{}s:9:"host_vars";a:0:{}s:3:"fit";i:5;s:14:"patternOutline";s:15:"/block/%/latest";s:8:"numParts";i:3;}}}}',
     'number_parts' => '3',
   ])
   ->values([
     'name' => 'entity.menu_link_content.latest_version',
     'path' => '/admin/structure/menu/item/{menu_link_content}/edit/latest',
     'pattern_outline' => '/admin/structure/menu/item/%/edit/latest',
     'fit' => '123',
     'route' => 'C:31:"Symfony\Component\Routing\Route":1666:{a:9:{s:4:"path";s:58:"/admin/structure/menu/item/{menu_link_content}/edit/latest";s:4:"host";s:0:"";s:8:"defaults";a:2:{s:12:"_entity_view";s:22:"menu_link_content.full";s:15:"_title_callback";s:54:"\Drupal\Core\Entity\Controller\EntityController::title";}s:12:"requirements";a:3:{s:14:"_entity_access";s:22:"menu_link_content.view";s:34:"_content_moderation_latest_version";s:4:"TRUE";s:17:"menu_link_content";s:3:"\d+";}s:7:"options";a:6:{s:14:"compiler_class";s:33:"Drupal\Core\Routing\RouteCompiler";s:31:"_content_moderation_entity_type";s:17:"menu_link_content";s:10:"parameters";a:1:{s:17:"menu_link_content";a:3:{s:4:"type";s:24:"entity:menu_link_content";s:20:"load_latest_revision";b:1;s:9:"converter";s:21:"paramconverter.entity";}}s:12:"_admin_route";b:1;s:14:"_access_checks";a:2:{i:0;s:19:"access_check.entity";i:1;s:28:"access_check.latest_revision";}s:4:"utf8";b:1;}s:7:"schemes";a:0:{}s:7:"methods";a:2:{i:0;s:3:"GET";i:1;s:4:"POST";}s:9:"condition";s:0:"";s:8:"compiled";C:33:"Drupal\Core\Routing\CompiledRoute":629:{a:11:{s:4:"vars";a:1:{i:0;s:17:"menu_link_content";}s:11:"path_prefix";s:0:"";s:10:"path_regex";s:72:"#^/admin/structure/menu/item/(?P<menu_link_content>\d+)/edit/latest$#sDu";s:11:"path_tokens";a:3:{i:0;a:2:{i:0;s:4:"text";i:1;s:12:"/edit/latest";}i:1;a:5:{i:0;s:8:"variable";i:1;s:1:"/";i:2;s:3:"\d+";i:3;s:17:"menu_link_content";i:4;b:1;}i:2;a:2:{i:0;s:4:"text";i:1;s:26:"/admin/structure/menu/item";}}s:9:"path_vars";a:1:{i:0;s:17:"menu_link_content";}s:10:"host_regex";N;s:11:"host_tokens";a:0:{}s:9:"host_vars";a:0:{}s:3:"fit";i:123;s:14:"patternOutline";s:40:"/admin/structure/menu/item/%/edit/latest";s:8:"numParts";i:7;}}}}',
     'number_parts' => '7',
   ])
   ->values([
     'name' => 'entity.node.latest_version',
     'path' => '/node/{node}/latest',
     'pattern_outline' => '/node/%/latest',
     'fit' => '5',
     'route' => 'C:31:"Symfony\Component\Routing\Route":1383:{a:9:{s:4:"path";s:19:"/node/{node}/latest";s:4:"host";s:0:"";s:8:"defaults";a:2:{s:12:"_entity_view";s:9:"node.full";s:15:"_title_callback";s:54:"\Drupal\Core\Entity\Controller\EntityController::title";}s:12:"requirements";a:3:{s:14:"_entity_access";s:9:"node.view";s:34:"_content_moderation_latest_version";s:4:"TRUE";s:4:"node";s:3:"\d+";}s:7:"options";a:5:{s:14:"compiler_class";s:33:"Drupal\Core\Routing\RouteCompiler";s:31:"_content_moderation_entity_type";s:4:"node";s:10:"parameters";a:1:{s:4:"node";a:3:{s:4:"type";s:11:"entity:node";s:20:"load_latest_revision";b:1;s:9:"converter";s:21:"paramconverter.entity";}}s:14:"_access_checks";a:2:{i:0;s:19:"access_check.entity";i:1;s:28:"access_check.latest_revision";}s:4:"utf8";b:1;}s:7:"schemes";a:0:{}s:7:"methods";a:2:{i:0;s:3:"GET";i:1;s:4:"POST";}s:9:"condition";s:0:"";s:8:"compiled";C:33:"Drupal\Core\Routing\CompiledRoute":492:{a:11:{s:4:"vars";a:1:{i:0;s:4:"node";}s:11:"path_prefix";s:0:"";s:10:"path_regex";s:33:"#^/node/(?P<node>\d+)/latest$#sDu";s:11:"path_tokens";a:3:{i:0;a:2:{i:0;s:4:"text";i:1;s:7:"/latest";}i:1;a:5:{i:0;s:8:"variable";i:1;s:1:"/";i:2;s:3:"\d+";i:3;s:4:"node";i:4;b:1;}i:2;a:2:{i:0;s:4:"text";i:1;s:5:"/node";}}s:9:"path_vars";a:1:{i:0;s:4:"node";}s:10:"host_regex";N;s:11:"host_tokens";a:0:{}s:9:"host_vars";a:0:{}s:3:"fit";i:5;s:14:"patternOutline";s:14:"/node/%/latest";s:8:"numParts";i:3;}}}}',
     'number_parts' => '3',
   ])
   ->values([
     'name' => 'entity.taxonomy_term.latest_version',
     'path' => '/taxonomy/term/{taxonomy_term}/latest',
     'pattern_outline' => '/taxonomy/term/%/latest',
     'fit' => '13',
     'route' => 'C:31:"Symfony\Component\Routing\Route":1528:{a:9:{s:4:"path";s:37:"/taxonomy/term/{taxonomy_term}/latest";s:4:"host";s:0:"";s:8:"defaults";a:2:{s:12:"_entity_view";s:18:"taxonomy_term.full";s:15:"_title_callback";s:54:"\Drupal\Core\Entity\Controller\EntityController::title";}s:12:"requirements";a:3:{s:14:"_entity_access";s:18:"taxonomy_term.view";s:34:"_content_moderation_latest_version";s:4:"TRUE";s:13:"taxonomy_term";s:3:"\d+";}s:7:"options";a:5:{s:14:"compiler_class";s:33:"Drupal\Core\Routing\RouteCompiler";s:31:"_content_moderation_entity_type";s:13:"taxonomy_term";s:10:"parameters";a:1:{s:13:"taxonomy_term";a:3:{s:4:"type";s:20:"entity:taxonomy_term";s:20:"load_latest_revision";b:1;s:9:"converter";s:21:"paramconverter.entity";}}s:14:"_access_checks";a:2:{i:0;s:19:"access_check.entity";i:1;s:28:"access_check.latest_revision";}s:4:"utf8";b:1;}s:7:"schemes";a:0:{}s:7:"methods";a:2:{i:0;s:3:"GET";i:1;s:4:"POST";}s:9:"condition";s:0:"";s:8:"compiled";C:33:"Drupal\Core\Routing\CompiledRoute":560:{a:11:{s:4:"vars";a:1:{i:0;s:13:"taxonomy_term";}s:11:"path_prefix";s:0:"";s:10:"path_regex";s:51:"#^/taxonomy/term/(?P<taxonomy_term>\d+)/latest$#sDu";s:11:"path_tokens";a:3:{i:0;a:2:{i:0;s:4:"text";i:1;s:7:"/latest";}i:1;a:5:{i:0;s:8:"variable";i:1;s:1:"/";i:2;s:3:"\d+";i:3;s:13:"taxonomy_term";i:4;b:1;}i:2;a:2:{i:0;s:4:"text";i:1;s:14:"/taxonomy/term";}}s:9:"path_vars";a:1:{i:0;s:13:"taxonomy_term";}s:10:"host_regex";N;s:11:"host_tokens";a:0:{}s:9:"host_vars";a:0:{}s:3:"fit";i:13;s:14:"patternOutline";s:23:"/taxonomy/term/%/latest";s:8:"numParts";i:4;}}}}',
     'number_parts' => '4',
   ])
   ->values([
     'name' => 'entity.workflow.add_form',
     'path' => '/admin/config/workflow/workflows/add',
     'pattern_outline' => '/admin/config/workflow/workflows/add',
     'fit' => '31',
     'route' => 'C:31:"Symfony\Component\Routing\Route":1082:{a:9:{s:4:"path";s:36:"/admin/config/workflow/workflows/add";s:4:"host";s:0:"";s:8:"defaults";a:3:{s:12:"_entity_form";s:12:"workflow.add";s:14:"entity_type_id";s:8:"workflow";s:15:"_title_callback";s:56:"Drupal\Core\Entity\Controller\EntityController::addTitle";}s:12:"requirements";a:1:{s:21:"_entity_create_access";s:8:"workflow";}s:7:"options";a:4:{s:14:"compiler_class";s:33:"Drupal\Core\Routing\RouteCompiler";s:12:"_admin_route";b:1;s:14:"_access_checks";a:1:{i:0;s:26:"access_check.entity_create";}s:4:"utf8";b:1;}s:7:"schemes";a:0:{}s:7:"methods";a:2:{i:0;s:3:"GET";i:1;s:4:"POST";}s:9:"condition";s:0:"";s:8:"compiled";C:33:"Drupal\Core\Routing\CompiledRoute":406:{a:11:{s:4:"vars";a:0:{}s:11:"path_prefix";s:0:"";s:10:"path_regex";s:43:"#^/admin/config/workflow/workflows/add$#sDu";s:11:"path_tokens";a:1:{i:0;a:2:{i:0;s:4:"text";i:1;s:36:"/admin/config/workflow/workflows/add";}}s:9:"path_vars";a:0:{}s:10:"host_regex";N;s:11:"host_tokens";a:0:{}s:9:"host_vars";a:0:{}s:3:"fit";i:31;s:14:"patternOutline";s:36:"/admin/config/workflow/workflows/add";s:8:"numParts";i:5;}}}}',
     'number_parts' => '5',
   ])
   ->values([
     'name' => 'entity.workflow.add_state_form',
     'path' => '/admin/config/workflow/workflows/manage/{workflow}/add_state',
     'pattern_outline' => '/admin/config/workflow/workflows/manage/%/add_state',
     'fit' => '125',
     'route' => 'C:31:"Symfony\Component\Routing\Route":1419:{a:9:{s:4:"path";s:60:"/admin/config/workflow/workflows/manage/{workflow}/add_state";s:4:"host";s:0:"";s:8:"defaults";a:2:{s:12:"_entity_form";s:18:"workflow.add-state";s:6:"_title";s:9:"Add state";}s:12:"requirements";a:1:{s:16:"_workflow_access";s:9:"add-state";}s:7:"options";a:5:{s:14:"compiler_class";s:33:"Drupal\Core\Routing\RouteCompiler";s:4:"utf8";b:1;s:10:"parameters";a:1:{s:8:"workflow";a:2:{s:4:"type";s:15:"entity:workflow";s:9:"converter";s:63:"drupal.proxy_original_service.paramconverter.configentity_admin";}}s:12:"_admin_route";b:1;s:14:"_access_checks";a:1:{i:0;s:43:"workflows.access_check.extended_permissions";}}s:7:"schemes";a:0:{}s:7:"methods";a:2:{i:0;s:3:"GET";i:1;s:4:"POST";}s:9:"condition";s:0:"";s:8:"compiled";C:33:"Drupal\Core\Routing\CompiledRoute":629:{a:11:{s:4:"vars";a:1:{i:0;s:8:"workflow";}s:11:"path_prefix";s:0:"";s:10:"path_regex";s:77:"#^/admin/config/workflow/workflows/manage/(?P<workflow>[^/]++)/add_state$#sDu";s:11:"path_tokens";a:3:{i:0;a:2:{i:0;s:4:"text";i:1;s:10:"/add_state";}i:1;a:5:{i:0;s:8:"variable";i:1;s:1:"/";i:2;s:6:"[^/]++";i:3;s:8:"workflow";i:4;b:1;}i:2;a:2:{i:0;s:4:"text";i:1;s:39:"/admin/config/workflow/workflows/manage";}}s:9:"path_vars";a:1:{i:0;s:8:"workflow";}s:10:"host_regex";N;s:11:"host_tokens";a:0:{}s:9:"host_vars";a:0:{}s:3:"fit";i:125;s:14:"patternOutline";s:51:"/admin/config/workflow/workflows/manage/%/add_state";s:8:"numParts";i:7;}}}}',
     'number_parts' => '7',
   ])
   ->values([
     'name' => 'entity.workflow.add_transition_form',
     'path' => '/admin/config/workflow/workflows/manage/{workflow}/add_transition',
     'pattern_outline' => '/admin/config/workflow/workflows/manage/%/add_transition',
     'fit' => '125',
     'route' => 'C:31:"Symfony\Component\Routing\Route":1456:{a:9:{s:4:"path";s:65:"/admin/config/workflow/workflows/manage/{workflow}/add_transition";s:4:"host";s:0:"";s:8:"defaults";a:2:{s:12:"_entity_form";s:23:"workflow.add-transition";s:6:"_title";s:14:"Add transition";}s:12:"requirements";a:1:{s:16:"_workflow_access";s:14:"add-transition";}s:7:"options";a:5:{s:14:"compiler_class";s:33:"Drupal\Core\Routing\RouteCompiler";s:4:"utf8";b:1;s:10:"parameters";a:1:{s:8:"workflow";a:2:{s:4:"type";s:15:"entity:workflow";s:9:"converter";s:63:"drupal.proxy_original_service.paramconverter.configentity_admin";}}s:12:"_admin_route";b:1;s:14:"_access_checks";a:1:{i:0;s:43:"workflows.access_check.extended_permissions";}}s:7:"schemes";a:0:{}s:7:"methods";a:2:{i:0;s:3:"GET";i:1;s:4:"POST";}s:9:"condition";s:0:"";s:8:"compiled";C:33:"Drupal\Core\Routing\CompiledRoute":644:{a:11:{s:4:"vars";a:1:{i:0;s:8:"workflow";}s:11:"path_prefix";s:0:"";s:10:"path_regex";s:82:"#^/admin/config/workflow/workflows/manage/(?P<workflow>[^/]++)/add_transition$#sDu";s:11:"path_tokens";a:3:{i:0;a:2:{i:0;s:4:"text";i:1;s:15:"/add_transition";}i:1;a:5:{i:0;s:8:"variable";i:1;s:1:"/";i:2;s:6:"[^/]++";i:3;s:8:"workflow";i:4;b:1;}i:2;a:2:{i:0;s:4:"text";i:1;s:39:"/admin/config/workflow/workflows/manage";}}s:9:"path_vars";a:1:{i:0;s:8:"workflow";}s:10:"host_regex";N;s:11:"host_tokens";a:0:{}s:9:"host_vars";a:0:{}s:3:"fit";i:125;s:14:"patternOutline";s:56:"/admin/config/workflow/workflows/manage/%/add_transition";s:8:"numParts";i:7;}}}}',
     'number_parts' => '7',
   ])
   ->values([
     'name' => 'entity.workflow.collection',
     'path' => '/admin/config/workflow/workflows',
     'pattern_outline' => '/admin/config/workflow/workflows',
     'fit' => '15',
     'route' => 'C:31:"Symfony\Component\Routing\Route":1025:{a:9:{s:4:"path";s:32:"/admin/config/workflow/workflows";s:4:"host";s:0:"";s:8:"defaults";a:4:{s:12:"_entity_list";s:8:"workflow";s:6:"_title";s:9:"Workflows";s:16:"_title_arguments";a:0:{}s:14:"_title_context";s:0:"";}s:12:"requirements";a:1:{s:11:"_permission";s:20:"administer workflows";}s:7:"options";a:4:{s:14:"compiler_class";s:33:"Drupal\Core\Routing\RouteCompiler";s:12:"_admin_route";b:1;s:14:"_access_checks";a:1:{i:0;s:23:"access_check.permission";}s:4:"utf8";b:1;}s:7:"schemes";a:0:{}s:7:"methods";a:2:{i:0;s:3:"GET";i:1;s:4:"POST";}s:9:"condition";s:0:"";s:8:"compiled";C:33:"Drupal\Core\Routing\CompiledRoute":394:{a:11:{s:4:"vars";a:0:{}s:11:"path_prefix";s:0:"";s:10:"path_regex";s:39:"#^/admin/config/workflow/workflows$#sDu";s:11:"path_tokens";a:1:{i:0;a:2:{i:0;s:4:"text";i:1;s:32:"/admin/config/workflow/workflows";}}s:9:"path_vars";a:0:{}s:10:"host_regex";N;s:11:"host_tokens";a:0:{}s:9:"host_vars";a:0:{}s:3:"fit";i:15;s:14:"patternOutline";s:32:"/admin/config/workflow/workflows";s:8:"numParts";i:4;}}}}',
     'number_parts' => '4',
   ])
   ->values([
     'name' => 'entity.workflow.delete_form',
     'path' => '/admin/config/workflow/workflows/manage/{workflow}/delete',
     'pattern_outline' => '/admin/config/workflow/workflows/manage/%/delete',
     'fit' => '125',
     'route' => 'C:31:"Symfony\Component\Routing\Route":1446:{a:9:{s:4:"path";s:57:"/admin/config/workflow/workflows/manage/{workflow}/delete";s:4:"host";s:0:"";s:8:"defaults";a:2:{s:12:"_entity_form";s:15:"workflow.delete";s:15:"_title_callback";s:60:"\Drupal\Core\Entity\Controller\EntityController::deleteTitle";}s:12:"requirements";a:1:{s:14:"_entity_access";s:15:"workflow.delete";}s:7:"options";a:5:{s:14:"compiler_class";s:33:"Drupal\Core\Routing\RouteCompiler";s:10:"parameters";a:1:{s:8:"workflow";a:2:{s:4:"type";s:15:"entity:workflow";s:9:"converter";s:63:"drupal.proxy_original_service.paramconverter.configentity_admin";}}s:12:"_admin_route";b:1;s:14:"_access_checks";a:1:{i:0;s:19:"access_check.entity";}s:4:"utf8";b:1;}s:7:"schemes";a:0:{}s:7:"methods";a:2:{i:0;s:3:"GET";i:1;s:4:"POST";}s:9:"condition";s:0:"";s:8:"compiled";C:33:"Drupal\Core\Routing\CompiledRoute":619:{a:11:{s:4:"vars";a:1:{i:0;s:8:"workflow";}s:11:"path_prefix";s:0:"";s:10:"path_regex";s:74:"#^/admin/config/workflow/workflows/manage/(?P<workflow>[^/]++)/delete$#sDu";s:11:"path_tokens";a:3:{i:0;a:2:{i:0;s:4:"text";i:1;s:7:"/delete";}i:1;a:5:{i:0;s:8:"variable";i:1;s:1:"/";i:2;s:6:"[^/]++";i:3;s:8:"workflow";i:4;b:1;}i:2;a:2:{i:0;s:4:"text";i:1;s:39:"/admin/config/workflow/workflows/manage";}}s:9:"path_vars";a:1:{i:0;s:8:"workflow";}s:10:"host_regex";N;s:11:"host_tokens";a:0:{}s:9:"host_vars";a:0:{}s:3:"fit";i:125;s:14:"patternOutline";s:48:"/admin/config/workflow/workflows/manage/%/delete";s:8:"numParts";i:7;}}}}',
     'number_parts' => '7',
   ])
   ->values([
     'name' => 'entity.workflow.delete_state_form',
     'path' => '/admin/config/workflow/workflows/manage/{workflow}/state/{workflow_state}/delete',
     'pattern_outline' => '/admin/config/workflow/workflows/manage/%/state/%/delete',
     'fit' => '501',
     'route' => 'C:31:"Symfony\Component\Routing\Route":1685:{a:9:{s:4:"path";s:80:"/admin/config/workflow/workflows/manage/{workflow}/state/{workflow_state}/delete";s:4:"host";s:0:"";s:8:"defaults";a:2:{s:5:"_form";s:46:"\Drupal\workflows\Form\WorkflowStateDeleteForm";s:6:"_title";s:12:"Delete state";}s:12:"requirements";a:1:{s:16:"_workflow_access";s:12:"delete-state";}s:7:"options";a:5:{s:14:"compiler_class";s:33:"Drupal\Core\Routing\RouteCompiler";s:4:"utf8";b:1;s:10:"parameters";a:1:{s:8:"workflow";a:2:{s:4:"type";s:15:"entity:workflow";s:9:"converter";s:63:"drupal.proxy_original_service.paramconverter.configentity_admin";}}s:12:"_admin_route";b:1;s:14:"_access_checks";a:1:{i:0;s:43:"workflows.access_check.extended_permissions";}}s:7:"schemes";a:0:{}s:7:"methods";a:2:{i:0;s:3:"GET";i:1;s:4:"POST";}s:9:"condition";s:0:"";s:8:"compiled";C:33:"Drupal\Core\Routing\CompiledRoute":847:{a:11:{s:4:"vars";a:2:{i:0;s:8:"workflow";i:1;s:14:"workflow_state";}s:11:"path_prefix";s:0:"";s:10:"path_regex";s:107:"#^/admin/config/workflow/workflows/manage/(?P<workflow>[^/]++)/state/(?P<workflow_state>[^/]++)/delete$#sDu";s:11:"path_tokens";a:5:{i:0;a:2:{i:0;s:4:"text";i:1;s:7:"/delete";}i:1;a:5:{i:0;s:8:"variable";i:1;s:1:"/";i:2;s:6:"[^/]++";i:3;s:14:"workflow_state";i:4;b:1;}i:2;a:2:{i:0;s:4:"text";i:1;s:6:"/state";}i:3;a:5:{i:0;s:8:"variable";i:1;s:1:"/";i:2;s:6:"[^/]++";i:3;s:8:"workflow";i:4;b:1;}i:4;a:2:{i:0;s:4:"text";i:1;s:39:"/admin/config/workflow/workflows/manage";}}s:9:"path_vars";a:2:{i:0;s:8:"workflow";i:1;s:14:"workflow_state";}s:10:"host_regex";N;s:11:"host_tokens";a:0:{}s:9:"host_vars";a:0:{}s:3:"fit";i:501;s:14:"patternOutline";s:56:"/admin/config/workflow/workflows/manage/%/state/%/delete";s:8:"numParts";i:9;}}}}',
     'number_parts' => '9',
   ])
   ->values([
     'name' => 'entity.workflow.delete_transition_form',
     'path' => '/admin/config/workflow/workflows/manage/{workflow}/transition/{workflow_transition}/delete',
     'pattern_outline' => '/admin/config/workflow/workflows/manage/%/transition/%/delete',
     'fit' => '501',
     'route' => 'C:31:"Symfony\Component\Routing\Route":1746:{a:9:{s:4:"path";s:90:"/admin/config/workflow/workflows/manage/{workflow}/transition/{workflow_transition}/delete";s:4:"host";s:0:"";s:8:"defaults";a:2:{s:5:"_form";s:51:"\Drupal\workflows\Form\WorkflowTransitionDeleteForm";s:6:"_title";s:17:"Delete transition";}s:12:"requirements";a:1:{s:16:"_workflow_access";s:17:"delete-transition";}s:7:"options";a:5:{s:14:"compiler_class";s:33:"Drupal\Core\Routing\RouteCompiler";s:4:"utf8";b:1;s:10:"parameters";a:1:{s:8:"workflow";a:2:{s:4:"type";s:15:"entity:workflow";s:9:"converter";s:63:"drupal.proxy_original_service.paramconverter.configentity_admin";}}s:12:"_admin_route";b:1;s:14:"_access_checks";a:1:{i:0;s:43:"workflows.access_check.extended_permissions";}}s:7:"schemes";a:0:{}s:7:"methods";a:2:{i:0;s:3:"GET";i:1;s:4:"POST";}s:9:"condition";s:0:"";s:8:"compiled";C:33:"Drupal\Core\Routing\CompiledRoute":883:{a:11:{s:4:"vars";a:2:{i:0;s:8:"workflow";i:1;s:19:"workflow_transition";}s:11:"path_prefix";s:0:"";s:10:"path_regex";s:117:"#^/admin/config/workflow/workflows/manage/(?P<workflow>[^/]++)/transition/(?P<workflow_transition>[^/]++)/delete$#sDu";s:11:"path_tokens";a:5:{i:0;a:2:{i:0;s:4:"text";i:1;s:7:"/delete";}i:1;a:5:{i:0;s:8:"variable";i:1;s:1:"/";i:2;s:6:"[^/]++";i:3;s:19:"workflow_transition";i:4;b:1;}i:2;a:2:{i:0;s:4:"text";i:1;s:11:"/transition";}i:3;a:5:{i:0;s:8:"variable";i:1;s:1:"/";i:2;s:6:"[^/]++";i:3;s:8:"workflow";i:4;b:1;}i:4;a:2:{i:0;s:4:"text";i:1;s:39:"/admin/config/workflow/workflows/manage";}}s:9:"path_vars";a:2:{i:0;s:8:"workflow";i:1;s:19:"workflow_transition";}s:10:"host_regex";N;s:11:"host_tokens";a:0:{}s:9:"host_vars";a:0:{}s:3:"fit";i:501;s:14:"patternOutline";s:61:"/admin/config/workflow/workflows/manage/%/transition/%/delete";s:8:"numParts";i:9;}}}}',
     'number_parts' => '9',
   ])
   ->values([
     'name' => 'entity.workflow.edit_form',
     'path' => '/admin/config/workflow/workflows/manage/{workflow}',
     'pattern_outline' => '/admin/config/workflow/workflows/manage/%',
     'fit' => '62',
     'route' => 'C:31:"Symfony\Component\Routing\Route":1377:{a:9:{s:4:"path";s:50:"/admin/config/workflow/workflows/manage/{workflow}";s:4:"host";s:0:"";s:8:"defaults";a:2:{s:12:"_entity_form";s:13:"workflow.edit";s:15:"_title_callback";s:58:"\Drupal\Core\Entity\Controller\EntityController::editTitle";}s:12:"requirements";a:1:{s:14:"_entity_access";s:15:"workflow.update";}s:7:"options";a:5:{s:14:"compiler_class";s:33:"Drupal\Core\Routing\RouteCompiler";s:10:"parameters";a:1:{s:8:"workflow";a:2:{s:4:"type";s:15:"entity:workflow";s:9:"converter";s:63:"drupal.proxy_original_service.paramconverter.configentity_admin";}}s:12:"_admin_route";b:1;s:14:"_access_checks";a:1:{i:0;s:19:"access_check.entity";}s:4:"utf8";b:1;}s:7:"schemes";a:0:{}s:7:"methods";a:2:{i:0;s:3:"GET";i:1;s:4:"POST";}s:9:"condition";s:0:"";s:8:"compiled";C:33:"Drupal\Core\Routing\CompiledRoute":561:{a:11:{s:4:"vars";a:1:{i:0;s:8:"workflow";}s:11:"path_prefix";s:0:"";s:10:"path_regex";s:67:"#^/admin/config/workflow/workflows/manage/(?P<workflow>[^/]++)$#sDu";s:11:"path_tokens";a:2:{i:0;a:5:{i:0;s:8:"variable";i:1;s:1:"/";i:2;s:6:"[^/]++";i:3;s:8:"workflow";i:4;b:1;}i:1;a:2:{i:0;s:4:"text";i:1;s:39:"/admin/config/workflow/workflows/manage";}}s:9:"path_vars";a:1:{i:0;s:8:"workflow";}s:10:"host_regex";N;s:11:"host_tokens";a:0:{}s:9:"host_vars";a:0:{}s:3:"fit";i:62;s:14:"patternOutline";s:41:"/admin/config/workflow/workflows/manage/%";s:8:"numParts";i:6;}}}}',
     'number_parts' => '6',
   ])
   ->values([
     'name' => 'entity.workflow.edit_state_form',
     'path' => '/admin/config/workflow/workflows/manage/{workflow}/state/{workflow_state}',
     'pattern_outline' => '/admin/config/workflow/workflows/manage/%/state/%',
     'fit' => '250',
     'route' => 'C:31:"Symfony\Component\Routing\Route":1600:{a:9:{s:4:"path";s:73:"/admin/config/workflow/workflows/manage/{workflow}/state/{workflow_state}";s:4:"host";s:0:"";s:8:"defaults";a:2:{s:12:"_entity_form";s:19:"workflow.edit-state";s:6:"_title";s:10:"Edit state";}s:12:"requirements";a:1:{s:16:"_workflow_access";s:12:"update-state";}s:7:"options";a:5:{s:14:"compiler_class";s:33:"Drupal\Core\Routing\RouteCompiler";s:4:"utf8";b:1;s:10:"parameters";a:1:{s:8:"workflow";a:2:{s:4:"type";s:15:"entity:workflow";s:9:"converter";s:63:"drupal.proxy_original_service.paramconverter.configentity_admin";}}s:12:"_admin_route";b:1;s:14:"_access_checks";a:1:{i:0;s:43:"workflows.access_check.extended_permissions";}}s:7:"schemes";a:0:{}s:7:"methods";a:2:{i:0;s:3:"GET";i:1;s:4:"POST";}s:9:"condition";s:0:"";s:8:"compiled";C:33:"Drupal\Core\Routing\CompiledRoute":790:{a:11:{s:4:"vars";a:2:{i:0;s:8:"workflow";i:1;s:14:"workflow_state";}s:11:"path_prefix";s:0:"";s:10:"path_regex";s:100:"#^/admin/config/workflow/workflows/manage/(?P<workflow>[^/]++)/state/(?P<workflow_state>[^/]++)$#sDu";s:11:"path_tokens";a:4:{i:0;a:5:{i:0;s:8:"variable";i:1;s:1:"/";i:2;s:6:"[^/]++";i:3;s:14:"workflow_state";i:4;b:1;}i:1;a:2:{i:0;s:4:"text";i:1;s:6:"/state";}i:2;a:5:{i:0;s:8:"variable";i:1;s:1:"/";i:2;s:6:"[^/]++";i:3;s:8:"workflow";i:4;b:1;}i:3;a:2:{i:0;s:4:"text";i:1;s:39:"/admin/config/workflow/workflows/manage";}}s:9:"path_vars";a:2:{i:0;s:8:"workflow";i:1;s:14:"workflow_state";}s:10:"host_regex";N;s:11:"host_tokens";a:0:{}s:9:"host_vars";a:0:{}s:3:"fit";i:250;s:14:"patternOutline";s:49:"/admin/config/workflow/workflows/manage/%/state/%";s:8:"numParts";i:8;}}}}',
     'number_parts' => '8',
   ])
   ->values([
     'name' => 'entity.workflow.edit_transition_form',
     'path' => '/admin/config/workflow/workflows/manage/{workflow}/transition/{workflow_transition}',
     'pattern_outline' => '/admin/config/workflow/workflows/manage/%/transition/%',
     'fit' => '250',
     'route' => 'C:31:"Symfony\Component\Routing\Route":1661:{a:9:{s:4:"path";s:83:"/admin/config/workflow/workflows/manage/{workflow}/transition/{workflow_transition}";s:4:"host";s:0:"";s:8:"defaults";a:2:{s:12:"_entity_form";s:24:"workflow.edit-transition";s:6:"_title";s:15:"Edit transition";}s:12:"requirements";a:1:{s:16:"_workflow_access";s:17:"update-transition";}s:7:"options";a:5:{s:14:"compiler_class";s:33:"Drupal\Core\Routing\RouteCompiler";s:4:"utf8";b:1;s:10:"parameters";a:1:{s:8:"workflow";a:2:{s:4:"type";s:15:"entity:workflow";s:9:"converter";s:63:"drupal.proxy_original_service.paramconverter.configentity_admin";}}s:12:"_admin_route";b:1;s:14:"_access_checks";a:1:{i:0;s:43:"workflows.access_check.extended_permissions";}}s:7:"schemes";a:0:{}s:7:"methods";a:2:{i:0;s:3:"GET";i:1;s:4:"POST";}s:9:"condition";s:0:"";s:8:"compiled";C:33:"Drupal\Core\Routing\CompiledRoute":826:{a:11:{s:4:"vars";a:2:{i:0;s:8:"workflow";i:1;s:19:"workflow_transition";}s:11:"path_prefix";s:0:"";s:10:"path_regex";s:110:"#^/admin/config/workflow/workflows/manage/(?P<workflow>[^/]++)/transition/(?P<workflow_transition>[^/]++)$#sDu";s:11:"path_tokens";a:4:{i:0;a:5:{i:0;s:8:"variable";i:1;s:1:"/";i:2;s:6:"[^/]++";i:3;s:19:"workflow_transition";i:4;b:1;}i:1;a:2:{i:0;s:4:"text";i:1;s:11:"/transition";}i:2;a:5:{i:0;s:8:"variable";i:1;s:1:"/";i:2;s:6:"[^/]++";i:3;s:8:"workflow";i:4;b:1;}i:3;a:2:{i:0;s:4:"text";i:1;s:39:"/admin/config/workflow/workflows/manage";}}s:9:"path_vars";a:2:{i:0;s:8:"workflow";i:1;s:19:"workflow_transition";}s:10:"host_regex";N;s:11:"host_tokens";a:0:{}s:9:"host_vars";a:0:{}s:3:"fit";i:250;s:14:"patternOutline";s:54:"/admin/config/workflow/workflows/manage/%/transition/%";s:8:"numParts";i:8;}}}}',
     'number_parts' => '8',
   ])
   ->values([
     'name' => 'view.moderated_content.moderated_content',
     'path' => '/admin/content/moderated',
     'pattern_outline' => '/admin/content/moderated',
     'fit' => '7',
     'route' => 'C:31:"Symfony\Component\Routing\Route":1362:{a:9:{s:4:"path";s:24:"/admin/content/moderated";s:4:"host";s:0:"";s:8:"defaults";a:5:{s:11:"_controller";s:47:"Drupal\views\Routing\ViewPageController::handle";s:6:"_title";s:17:"Moderated content";s:7:"view_id";s:17:"moderated_content";s:10:"display_id";s:17:"moderated_content";s:30:"_view_display_show_admin_links";b:1;}s:12:"requirements";a:2:{s:11:"_permission";s:28:"view any unpublished content";s:7:"_format";s:4:"html";}s:7:"options";a:9:{s:14:"compiler_class";s:33:"Drupal\Core\Routing\RouteCompiler";s:18:"_view_argument_map";a:0:{}s:23:"_view_display_plugin_id";s:4:"page";s:26:"_view_display_plugin_class";s:38:"Drupal\views\Plugin\views\display\Page";s:30:"_view_display_show_admin_links";b:1;s:16:"returns_response";b:0;s:12:"_admin_route";b:1;s:14:"_access_checks";a:1:{i:0;s:23:"access_check.permission";}s:4:"utf8";b:1;}s:7:"schemes";a:0:{}s:7:"methods";a:2:{i:0;s:3:"GET";i:1;s:4:"POST";}s:9:"condition";s:0:"";s:8:"compiled";C:33:"Drupal\Core\Routing\CompiledRoute":369:{a:11:{s:4:"vars";a:0:{}s:11:"path_prefix";s:0:"";s:10:"path_regex";s:31:"#^/admin/content/moderated$#sDu";s:11:"path_tokens";a:1:{i:0;a:2:{i:0;s:4:"text";i:1;s:24:"/admin/content/moderated";}}s:9:"path_vars";a:0:{}s:10:"host_regex";N;s:11:"host_tokens";a:0:{}s:9:"host_vars";a:0:{}s:3:"fit";i:7;s:14:"patternOutline";s:24:"/admin/content/moderated";s:8:"numParts";i:3;}}}}',
     'number_parts' => '3',
   ])
   ->execute();

 // Update routing.non_admin_routes state to include the new non-admin routes.
 $non_admin_routes = $connection->select('key_value')
   ->fields('key_value', ['value'])
   ->condition('collection', 'state')
   ->condition('name', 'routing.non_admin_routes')
   ->execute()
   ->fetchField();
 $non_admin_routes = unserialize($non_admin_routes);
 $non_admin_routes[] = 'entity.node.latest_version';
 $connection->update('key_value')
   ->fields(['value' => serialize($non_admin_routes)])
   ->condition('collection', 'state')
   ->condition('name', 'routing.non_admin_routes')
   ->execute();
