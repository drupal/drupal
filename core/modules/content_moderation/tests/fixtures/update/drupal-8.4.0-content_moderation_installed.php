<?php
// @codingStandardsIgnoreFile
/**
 * @file
 * Content moderation installed in the standard profile at 8.4.0.
 *
 * This file applies on top of drupal-8.4.0.bare.standard.php.gz.
 */

use Drupal\Core\Database\Database;

$connection = Database::getConnection();

$connection->delete('config')
  ->condition('name', ['core.extension'], 'IN')
  ->execute();

$connection->insert('config')
  ->fields(array(
    'collection',
    'name',
    'data',
  ))
  ->values(array(
    'collection' => '',
    'name' => 'core.extension',
    'data' => 'a:4:{s:6:"module";a:44:{s:14:"automated_cron";i:0;s:5:"block";i:0;s:13:"block_content";i:0;s:10:"breakpoint";i:0;s:8:"ckeditor";i:0;s:5:"color";i:0;s:7:"comment";i:0;s:6:"config";i:0;s:7:"contact";i:0;s:18:"content_moderation";i:0;s:10:"contextual";i:0;s:8:"datetime";i:0;s:5:"dblog";i:0;s:18:"dynamic_page_cache";i:0;s:6:"editor";i:0;s:5:"field";i:0;s:8:"field_ui";i:0;s:4:"file";i:0;s:6:"filter";i:0;s:4:"help";i:0;s:7:"history";i:0;s:5:"image";i:0;s:4:"link";i:0;s:7:"menu_ui";i:0;s:4:"node";i:0;s:7:"options";i:0;s:10:"page_cache";i:0;s:4:"path";i:0;s:9:"quickedit";i:0;s:3:"rdf";i:0;s:6:"search";i:0;s:8:"shortcut";i:0;s:6:"system";i:0;s:8:"taxonomy";i:0;s:4:"text";i:0;s:7:"toolbar";i:0;s:4:"tour";i:0;s:6:"update";i:0;s:4:"user";i:0;s:8:"views_ui";i:0;s:9:"workflows";i:0;s:17:"menu_link_content";i:1;s:5:"views";i:10;s:8:"standard";i:1000;}s:5:"theme";a:4:{s:6:"stable";i:0;s:6:"classy";i:0;s:6:"bartik";i:0;s:5:"seven";i:0;}s:7:"profile";s:8:"standard";s:5:"_core";a:1:{s:19:"default_config_hash";s:43:"R4IF-ClDHXxblLcG0L7MgsLvfBIMAvi_skumNFQwkDc";}}',
  ))
  ->values(array(
    'collection' => '',
    'name' => 'workflows.workflow.editorial',
    'data' => 'a:9:{s:4:"uuid";s:36:"08b548c7-ff59-468b-9347-7d697680d035";s:8:"langcode";s:2:"en";s:6:"status";b:1;s:12:"dependencies";a:1:{s:6:"module";a:1:{i:0;s:18:"content_moderation";}}s:5:"_core";a:1:{s:19:"default_config_hash";s:43:"T_JxNjYlfoRBi7Bj1zs5Xv9xv1btuBkKp5C1tNrjMhI";}s:2:"id";s:9:"editorial";s:5:"label";s:9:"Editorial";s:4:"type";s:18:"content_moderation";s:13:"type_settings";a:3:{s:6:"states";a:3:{s:8:"archived";a:4:{s:5:"label";s:8:"Archived";s:6:"weight";i:5;s:9:"published";b:0;s:16:"default_revision";b:1;}s:5:"draft";a:4:{s:5:"label";s:5:"Draft";s:9:"published";b:0;s:16:"default_revision";b:0;s:6:"weight";i:-5;}s:9:"published";a:4:{s:5:"label";s:9:"Published";s:9:"published";b:1;s:16:"default_revision";b:1;s:6:"weight";i:0;}}s:11:"transitions";a:5:{s:7:"archive";a:4:{s:5:"label";s:7:"Archive";s:4:"from";a:1:{i:0;s:9:"published";}s:2:"to";s:8:"archived";s:6:"weight";i:2;}s:14:"archived_draft";a:4:{s:5:"label";s:16:"Restore to Draft";s:4:"from";a:1:{i:0;s:8:"archived";}s:2:"to";s:5:"draft";s:6:"weight";i:3;}s:18:"archived_published";a:4:{s:5:"label";s:7:"Restore";s:4:"from";a:1:{i:0;s:8:"archived";}s:2:"to";s:9:"published";s:6:"weight";i:4;}s:16:"create_new_draft";a:4:{s:5:"label";s:16:"Create New Draft";s:2:"to";s:5:"draft";s:6:"weight";i:0;s:4:"from";a:2:{i:0;s:5:"draft";i:1;s:9:"published";}}s:7:"publish";a:4:{s:5:"label";s:7:"Publish";s:2:"to";s:9:"published";s:6:"weight";i:1;s:4:"from";a:2:{i:0;s:5:"draft";i:1;s:9:"published";}}}s:12:"entity_types";a:0:{}}}',
  ))
  ->execute();

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

$connection->delete('key_value')
  ->condition('name', [
    'routing.non_admin_routes',
    'system.js_cache_files',
    'system.theme.files',
  ], 'IN')
  ->execute();

$connection->insert('key_value')
  ->fields(array(
    'collection',
    'name',
    'value',
  ))
  ->values(array(
    'collection' => 'config.entity.key_store.workflow',
    'name' => 'uuid:08b548c7-ff59-468b-9347-7d697680d035',
    'value' => 'a:1:{i:0;s:28:"workflows.workflow.editorial";}',
  ))
  ->values(array(
    'collection' => 'entity.definitions.installed',
    'name' => 'content_moderation_state.entity_type',
    'value' => 'O:36:"Drupal\Core\Entity\ContentEntityType":38:{s:25:" * revision_metadata_keys";a:0:{}s:15:" * static_cache";b:1;s:15:" * render_cache";b:1;s:19:" * persistent_cache";b:1;s:14:" * entity_keys";a:8:{s:2:"id";s:2:"id";s:8:"revision";s:11:"revision_id";s:4:"uuid";s:4:"uuid";s:3:"uid";s:3:"uid";s:8:"langcode";s:8:"langcode";s:6:"bundle";s:0:"";s:16:"default_langcode";s:16:"default_langcode";s:29:"revision_translation_affected";s:29:"revision_translation_affected";}s:5:" * id";s:24:"content_moderation_state";s:16:" * originalClass";s:55:"Drupal\content_moderation\Entity\ContentModerationState";s:11:" * handlers";a:5:{s:14:"storage_schema";s:61:"Drupal\content_moderation\ContentModerationStateStorageSchema";s:10:"views_data";s:29:"\Drupal\views\EntityViewsData";s:6:"access";s:68:"Drupal\content_moderation\ContentModerationStateAccessControlHandler";s:7:"storage";s:46:"Drupal\Core\Entity\Sql\SqlContentEntityStorage";s:12:"view_builder";s:36:"Drupal\Core\Entity\EntityViewBuilder";}s:19:" * admin_permission";N;s:25:" * permission_granularity";s:11:"entity_type";s:8:" * links";a:0:{}s:17:" * label_callback";N;s:21:" * bundle_entity_type";N;s:12:" * bundle_of";N;s:15:" * bundle_label";N;s:13:" * base_table";s:24:"content_moderation_state";s:22:" * revision_data_table";s:39:"content_moderation_state_field_revision";s:17:" * revision_table";s:33:"content_moderation_state_revision";s:13:" * data_table";s:35:"content_moderation_state_field_data";s:15:" * translatable";b:1;s:19:" * show_revision_ui";b:0;s:8:" * label";O:48:"Drupal\Core\StringTranslation\TranslatableMarkup":3:{s:9:" * string";s:24:"Content moderation state";s:12:" * arguments";a:0:{}s:10:" * options";a:0:{}}s:19:" * label_collection";s:0:"";s:17:" * label_singular";O:48:"Drupal\Core\StringTranslation\TranslatableMarkup":3:{s:9:" * string";s:24:"content moderation state";s:12:" * arguments";a:0:{}s:10:" * options";a:0:{}}s:15:" * label_plural";O:48:"Drupal\Core\StringTranslation\TranslatableMarkup":3:{s:9:" * string";s:25:"content moderation states";s:12:" * arguments";a:0:{}s:10:" * options";a:0:{}}s:14:" * label_count";a:3:{s:8:"singular";s:31:"@count content moderation state";s:6:"plural";s:32:"@count content moderation states";s:7:"context";N;}s:15:" * uri_callback";N;s:8:" * group";s:7:"content";s:14:" * group_label";O:48:"Drupal\Core\StringTranslation\TranslatableMarkup":3:{s:9:" * string";s:7:"Content";s:12:" * arguments";a:0:{}s:10:" * options";a:1:{s:7:"context";s:17:"Entity type group";}}s:22:" * field_ui_base_route";N;s:26:" * common_reference_target";b:0;s:22:" * list_cache_contexts";a:0:{}s:18:" * list_cache_tags";a:1:{i:0;s:29:"content_moderation_state_list";}s:14:" * constraints";a:0:{}s:13:" * additional";a:0:{}s:8:" * class";s:55:"Drupal\content_moderation\Entity\ContentModerationState";s:11:" * provider";s:18:"content_moderation";s:20:" * stringTranslation";N;}',
  ))
  ->values(array(
    'collection' => 'entity.definitions.installed',
    'name' => 'content_moderation_state.field_storage_definitions',
    'value' => 'a:12:{s:2:"id";O:37:"Drupal\Core\Field\BaseFieldDefinition":5:{s:7:" * type";s:7:"integer";s:9:" * schema";a:4:{s:7:"columns";a:1:{s:5:"value";a:3:{s:4:"type";s:3:"int";s:8:"unsigned";b:1;s:4:"size";s:6:"normal";}}s:11:"unique keys";a:0:{}s:7:"indexes";a:0:{}s:12:"foreign keys";a:0:{}}s:10:" * indexes";a:0:{}s:17:" * itemDefinition";O:51:"Drupal\Core\Field\TypedData\FieldItemDataDefinition":2:{s:18:" * fieldDefinition";r:2;s:13:" * definition";a:2:{s:4:"type";s:18:"field_item:integer";s:8:"settings";a:6:{s:8:"unsigned";b:1;s:4:"size";s:6:"normal";s:3:"min";s:0:"";s:3:"max";s:0:"";s:6:"prefix";s:0:"";s:6:"suffix";s:0:"";}}}s:13:" * definition";a:6:{s:5:"label";O:48:"Drupal\Core\StringTranslation\TranslatableMarkup":3:{s:9:" * string";s:2:"ID";s:12:" * arguments";a:0:{}s:10:" * options";a:0:{}}s:9:"read-only";b:1;s:8:"provider";s:18:"content_moderation";s:10:"field_name";s:2:"id";s:11:"entity_type";s:24:"content_moderation_state";s:6:"bundle";N;}}s:4:"uuid";O:37:"Drupal\Core\Field\BaseFieldDefinition":5:{s:7:" * type";s:4:"uuid";s:9:" * schema";a:4:{s:7:"columns";a:1:{s:5:"value";a:3:{s:4:"type";s:13:"varchar_ascii";s:6:"length";i:128;s:6:"binary";b:0;}}s:11:"unique keys";a:1:{s:5:"value";a:1:{i:0;s:5:"value";}}s:7:"indexes";a:0:{}s:12:"foreign keys";a:0:{}}s:10:" * indexes";a:0:{}s:17:" * itemDefinition";O:51:"Drupal\Core\Field\TypedData\FieldItemDataDefinition":2:{s:18:" * fieldDefinition";r:35;s:13:" * definition";a:2:{s:4:"type";s:15:"field_item:uuid";s:8:"settings";a:3:{s:10:"max_length";i:128;s:8:"is_ascii";b:1;s:14:"case_sensitive";b:0;}}}s:13:" * definition";a:6:{s:5:"label";O:48:"Drupal\Core\StringTranslation\TranslatableMarkup":3:{s:9:" * string";s:4:"UUID";s:12:" * arguments";a:0:{}s:10:" * options";a:0:{}}s:9:"read-only";b:1;s:8:"provider";s:18:"content_moderation";s:10:"field_name";s:4:"uuid";s:11:"entity_type";s:24:"content_moderation_state";s:6:"bundle";N;}}s:11:"revision_id";O:37:"Drupal\Core\Field\BaseFieldDefinition":5:{s:7:" * type";s:7:"integer";s:9:" * schema";a:4:{s:7:"columns";a:1:{s:5:"value";a:3:{s:4:"type";s:3:"int";s:8:"unsigned";b:1;s:4:"size";s:6:"normal";}}s:11:"unique keys";a:0:{}s:7:"indexes";a:0:{}s:12:"foreign keys";a:0:{}}s:10:" * indexes";a:0:{}s:17:" * itemDefinition";O:51:"Drupal\Core\Field\TypedData\FieldItemDataDefinition":2:{s:18:" * fieldDefinition";r:67;s:13:" * definition";a:2:{s:4:"type";s:18:"field_item:integer";s:8:"settings";a:6:{s:8:"unsigned";b:1;s:4:"size";s:6:"normal";s:3:"min";s:0:"";s:3:"max";s:0:"";s:6:"prefix";s:0:"";s:6:"suffix";s:0:"";}}}s:13:" * definition";a:6:{s:5:"label";O:48:"Drupal\Core\StringTranslation\TranslatableMarkup":3:{s:9:" * string";s:11:"Revision ID";s:12:" * arguments";a:0:{}s:10:" * options";a:0:{}}s:9:"read-only";b:1;s:8:"provider";s:18:"content_moderation";s:10:"field_name";s:11:"revision_id";s:11:"entity_type";s:24:"content_moderation_state";s:6:"bundle";N;}}s:8:"langcode";O:37:"Drupal\Core\Field\BaseFieldDefinition":5:{s:7:" * type";s:8:"language";s:9:" * schema";a:4:{s:7:"columns";a:1:{s:5:"value";a:2:{s:4:"type";s:13:"varchar_ascii";s:6:"length";i:12;}}s:11:"unique keys";a:0:{}s:7:"indexes";a:0:{}s:12:"foreign keys";a:0:{}}s:10:" * indexes";a:0:{}s:17:" * itemDefinition";O:51:"Drupal\Core\Field\TypedData\FieldItemDataDefinition":2:{s:18:" * fieldDefinition";r:100;s:13:" * definition";a:2:{s:4:"type";s:19:"field_item:language";s:8:"settings";a:0:{}}}s:13:" * definition";a:8:{s:5:"label";O:48:"Drupal\Core\StringTranslation\TranslatableMarkup":3:{s:9:" * string";s:8:"Language";s:12:" * arguments";a:0:{}s:10:" * options";a:0:{}}s:7:"display";a:2:{s:4:"view";a:1:{s:7:"options";a:1:{s:6:"region";s:6:"hidden";}}s:4:"form";a:1:{s:7:"options";a:2:{s:4:"type";s:15:"language_select";s:6:"weight";i:2;}}}s:12:"revisionable";b:1;s:12:"translatable";b:1;s:8:"provider";s:18:"content_moderation";s:10:"field_name";s:8:"langcode";s:11:"entity_type";s:24:"content_moderation_state";s:6:"bundle";N;}}s:3:"uid";O:37:"Drupal\Core\Field\BaseFieldDefinition":5:{s:7:" * type";s:16:"entity_reference";s:9:" * schema";a:4:{s:7:"columns";a:1:{s:9:"target_id";a:3:{s:11:"description";s:28:"The ID of the target entity.";s:4:"type";s:3:"int";s:8:"unsigned";b:1;}}s:7:"indexes";a:1:{s:9:"target_id";a:1:{i:0;s:9:"target_id";}}s:11:"unique keys";a:0:{}s:12:"foreign keys";a:0:{}}s:10:" * indexes";a:0:{}s:17:" * itemDefinition";O:51:"Drupal\Core\Field\TypedData\FieldItemDataDefinition":2:{s:18:" * fieldDefinition";r:135;s:13:" * definition";a:2:{s:4:"type";s:27:"field_item:entity_reference";s:8:"settings";a:3:{s:11:"target_type";s:4:"user";s:7:"handler";s:7:"default";s:16:"handler_settings";a:0:{}}}}s:13:" * definition";a:9:{s:5:"label";O:48:"Drupal\Core\StringTranslation\TranslatableMarkup":3:{s:9:" * string";s:4:"User";s:12:" * arguments";a:0:{}s:10:" * options";a:0:{}}s:11:"description";O:48:"Drupal\Core\StringTranslation\TranslatableMarkup":3:{s:9:" * string";s:35:"The username of the entity creator.";s:12:" * arguments";a:0:{}s:10:" * options";a:0:{}}s:22:"default_value_callback";s:73:"Drupal\content_moderation\Entity\ContentModerationState::getCurrentUserId";s:12:"translatable";b:1;s:12:"revisionable";b:1;s:8:"provider";s:18:"content_moderation";s:10:"field_name";s:3:"uid";s:11:"entity_type";s:24:"content_moderation_state";s:6:"bundle";N;}}s:8:"workflow";O:37:"Drupal\Core\Field\BaseFieldDefinition":5:{s:7:" * type";s:16:"entity_reference";s:9:" * schema";a:4:{s:7:"columns";a:1:{s:9:"target_id";a:3:{s:11:"description";s:28:"The ID of the target entity.";s:4:"type";s:13:"varchar_ascii";s:6:"length";i:255;}}s:7:"indexes";a:1:{s:9:"target_id";a:1:{i:0;s:9:"target_id";}}s:11:"unique keys";a:0:{}s:12:"foreign keys";a:0:{}}s:10:" * indexes";a:0:{}s:17:" * itemDefinition";O:51:"Drupal\Core\Field\TypedData\FieldItemDataDefinition":2:{s:18:" * fieldDefinition";r:173;s:13:" * definition";a:2:{s:4:"type";s:27:"field_item:entity_reference";s:8:"settings";a:3:{s:11:"target_type";s:8:"workflow";s:7:"handler";s:7:"default";s:16:"handler_settings";a:0:{}}}}s:13:" * definition";a:8:{s:5:"label";O:48:"Drupal\Core\StringTranslation\TranslatableMarkup":3:{s:9:" * string";s:8:"Workflow";s:12:" * arguments";a:0:{}s:10:" * options";a:0:{}}s:11:"description";O:48:"Drupal\Core\StringTranslation\TranslatableMarkup":3:{s:9:" * string";s:40:"The workflow the moderation state is in.";s:12:" * arguments";a:0:{}s:10:" * options";a:0:{}}s:8:"required";b:1;s:12:"revisionable";b:1;s:8:"provider";s:18:"content_moderation";s:10:"field_name";s:8:"workflow";s:11:"entity_type";s:24:"content_moderation_state";s:6:"bundle";N;}}s:16:"moderation_state";O:37:"Drupal\Core\Field\BaseFieldDefinition":5:{s:7:" * type";s:6:"string";s:9:" * schema";a:4:{s:7:"columns";a:1:{s:5:"value";a:3:{s:4:"type";s:7:"varchar";s:6:"length";i:255;s:6:"binary";b:0;}}s:11:"unique keys";a:0:{}s:7:"indexes";a:0:{}s:12:"foreign keys";a:0:{}}s:10:" * indexes";a:0:{}s:17:" * itemDefinition";O:51:"Drupal\Core\Field\TypedData\FieldItemDataDefinition":2:{s:18:" * fieldDefinition";r:210;s:13:" * definition";a:2:{s:4:"type";s:17:"field_item:string";s:8:"settings";a:3:{s:10:"max_length";i:255;s:8:"is_ascii";b:0;s:14:"case_sensitive";b:0;}}}s:13:" * definition";a:9:{s:5:"label";O:48:"Drupal\Core\StringTranslation\TranslatableMarkup":3:{s:9:" * string";s:16:"Moderation state";s:12:" * arguments";a:0:{}s:10:" * options";a:0:{}}s:11:"description";O:48:"Drupal\Core\StringTranslation\TranslatableMarkup":3:{s:9:" * string";s:47:"The moderation state of the referenced content.";s:12:" * arguments";a:0:{}s:10:" * options";a:0:{}}s:8:"required";b:1;s:12:"translatable";b:1;s:12:"revisionable";b:1;s:8:"provider";s:18:"content_moderation";s:10:"field_name";s:16:"moderation_state";s:11:"entity_type";s:24:"content_moderation_state";s:6:"bundle";N;}}s:22:"content_entity_type_id";O:37:"Drupal\Core\Field\BaseFieldDefinition":5:{s:7:" * type";s:6:"string";s:9:" * schema";a:4:{s:7:"columns";a:1:{s:5:"value";a:3:{s:4:"type";s:7:"varchar";s:6:"length";i:32;s:6:"binary";b:0;}}s:11:"unique keys";a:0:{}s:7:"indexes";a:0:{}s:12:"foreign keys";a:0:{}}s:10:" * indexes";a:0:{}s:17:" * itemDefinition";O:51:"Drupal\Core\Field\TypedData\FieldItemDataDefinition":2:{s:18:" * fieldDefinition";r:246;s:13:" * definition";a:2:{s:4:"type";s:17:"field_item:string";s:8:"settings";a:3:{s:10:"max_length";i:32;s:8:"is_ascii";b:0;s:14:"case_sensitive";b:0;}}}s:13:" * definition";a:8:{s:5:"label";O:48:"Drupal\Core\StringTranslation\TranslatableMarkup":3:{s:9:" * string";s:22:"Content entity type ID";s:12:" * arguments";a:0:{}s:10:" * options";a:0:{}}s:11:"description";O:48:"Drupal\Core\StringTranslation\TranslatableMarkup":3:{s:9:" * string";s:63:"The ID of the content entity type this moderation state is for.";s:12:" * arguments";a:0:{}s:10:" * options";a:0:{}}s:8:"required";b:1;s:12:"revisionable";b:1;s:8:"provider";s:18:"content_moderation";s:10:"field_name";s:22:"content_entity_type_id";s:11:"entity_type";s:24:"content_moderation_state";s:6:"bundle";N;}}s:17:"content_entity_id";O:37:"Drupal\Core\Field\BaseFieldDefinition":5:{s:7:" * type";s:7:"integer";s:9:" * schema";a:4:{s:7:"columns";a:1:{s:5:"value";a:3:{s:4:"type";s:3:"int";s:8:"unsigned";b:0;s:4:"size";s:6:"normal";}}s:11:"unique keys";a:0:{}s:7:"indexes";a:0:{}s:12:"foreign keys";a:0:{}}s:10:" * indexes";a:0:{}s:17:" * itemDefinition";O:51:"Drupal\Core\Field\TypedData\FieldItemDataDefinition":2:{s:18:" * fieldDefinition";r:281;s:13:" * definition";a:2:{s:4:"type";s:18:"field_item:integer";s:8:"settings";a:6:{s:8:"unsigned";b:0;s:4:"size";s:6:"normal";s:3:"min";s:0:"";s:3:"max";s:0:"";s:6:"prefix";s:0:"";s:6:"suffix";s:0:"";}}}s:13:" * definition";a:8:{s:5:"label";O:48:"Drupal\Core\StringTranslation\TranslatableMarkup":3:{s:9:" * string";s:17:"Content entity ID";s:12:" * arguments";a:0:{}s:10:" * options";a:0:{}}s:11:"description";O:48:"Drupal\Core\StringTranslation\TranslatableMarkup":3:{s:9:" * string";s:58:"The ID of the content entity this moderation state is for.";s:12:" * arguments";a:0:{}s:10:" * options";a:0:{}}s:8:"required";b:1;s:12:"revisionable";b:1;s:8:"provider";s:18:"content_moderation";s:10:"field_name";s:17:"content_entity_id";s:11:"entity_type";s:24:"content_moderation_state";s:6:"bundle";N;}}s:26:"content_entity_revision_id";O:37:"Drupal\Core\Field\BaseFieldDefinition":5:{s:7:" * type";s:7:"integer";s:9:" * schema";a:4:{s:7:"columns";a:1:{s:5:"value";a:3:{s:4:"type";s:3:"int";s:8:"unsigned";b:0;s:4:"size";s:6:"normal";}}s:11:"unique keys";a:0:{}s:7:"indexes";a:0:{}s:12:"foreign keys";a:0:{}}s:10:" * indexes";a:0:{}s:17:" * itemDefinition";O:51:"Drupal\Core\Field\TypedData\FieldItemDataDefinition":2:{s:18:" * fieldDefinition";r:319;s:13:" * definition";a:2:{s:4:"type";s:18:"field_item:integer";s:8:"settings";a:6:{s:8:"unsigned";b:0;s:4:"size";s:6:"normal";s:3:"min";s:0:"";s:3:"max";s:0:"";s:6:"prefix";s:0:"";s:6:"suffix";s:0:"";}}}s:13:" * definition";a:8:{s:5:"label";O:48:"Drupal\Core\StringTranslation\TranslatableMarkup":3:{s:9:" * string";s:26:"Content entity revision ID";s:12:" * arguments";a:0:{}s:10:" * options";a:0:{}}s:11:"description";O:48:"Drupal\Core\StringTranslation\TranslatableMarkup":3:{s:9:" * string";s:67:"The revision ID of the content entity this moderation state is for.";s:12:" * arguments";a:0:{}s:10:" * options";a:0:{}}s:8:"required";b:1;s:12:"revisionable";b:1;s:8:"provider";s:18:"content_moderation";s:10:"field_name";s:26:"content_entity_revision_id";s:11:"entity_type";s:24:"content_moderation_state";s:6:"bundle";N;}}s:16:"default_langcode";O:37:"Drupal\Core\Field\BaseFieldDefinition":5:{s:7:" * type";s:7:"boolean";s:9:" * schema";a:4:{s:7:"columns";a:1:{s:5:"value";a:2:{s:4:"type";s:3:"int";s:4:"size";s:4:"tiny";}}s:11:"unique keys";a:0:{}s:7:"indexes";a:0:{}s:12:"foreign keys";a:0:{}}s:10:" * indexes";a:0:{}s:17:" * itemDefinition";O:51:"Drupal\Core\Field\TypedData\FieldItemDataDefinition":2:{s:18:" * fieldDefinition";r:357;s:13:" * definition";a:2:{s:4:"type";s:18:"field_item:boolean";s:8:"settings";a:2:{s:8:"on_label";O:48:"Drupal\Core\StringTranslation\TranslatableMarkup":3:{s:9:" * string";s:2:"On";s:12:" * arguments";a:0:{}s:10:" * options";a:0:{}}s:9:"off_label";O:48:"Drupal\Core\StringTranslation\TranslatableMarkup":3:{s:9:" * string";s:3:"Off";s:12:" * arguments";a:0:{}s:10:" * options";a:0:{}}}}}s:13:" * definition";a:9:{s:5:"label";O:48:"Drupal\Core\StringTranslation\TranslatableMarkup":3:{s:9:" * string";s:19:"Default translation";s:12:" * arguments";a:0:{}s:10:" * options";a:0:{}}s:11:"description";O:48:"Drupal\Core\StringTranslation\TranslatableMarkup":3:{s:9:" * string";s:58:"A flag indicating whether this is the default translation.";s:12:" * arguments";a:0:{}s:10:" * options";a:0:{}}s:12:"translatable";b:1;s:12:"revisionable";b:1;s:13:"default_value";a:1:{i:0;a:1:{s:5:"value";b:1;}}s:8:"provider";s:18:"content_moderation";s:10:"field_name";s:16:"default_langcode";s:11:"entity_type";s:24:"content_moderation_state";s:6:"bundle";N;}}s:29:"revision_translation_affected";O:37:"Drupal\Core\Field\BaseFieldDefinition":5:{s:7:" * type";s:7:"boolean";s:9:" * schema";a:4:{s:7:"columns";a:1:{s:5:"value";a:2:{s:4:"type";s:3:"int";s:4:"size";s:4:"tiny";}}s:11:"unique keys";a:0:{}s:7:"indexes";a:0:{}s:12:"foreign keys";a:0:{}}s:10:" * indexes";a:0:{}s:17:" * itemDefinition";O:51:"Drupal\Core\Field\TypedData\FieldItemDataDefinition":2:{s:18:" * fieldDefinition";r:399;s:13:" * definition";a:2:{s:4:"type";s:18:"field_item:boolean";s:8:"settings";a:2:{s:8:"on_label";O:48:"Drupal\Core\StringTranslation\TranslatableMarkup":3:{s:9:" * string";s:2:"On";s:12:" * arguments";a:0:{}s:10:" * options";a:0:{}}s:9:"off_label";O:48:"Drupal\Core\StringTranslation\TranslatableMarkup":3:{s:9:" * string";s:3:"Off";s:12:" * arguments";a:0:{}s:10:" * options";a:0:{}}}}}s:13:" * definition";a:9:{s:5:"label";O:48:"Drupal\Core\StringTranslation\TranslatableMarkup":3:{s:9:" * string";s:29:"Revision translation affected";s:12:" * arguments";a:0:{}s:10:" * options";a:0:{}}s:11:"description";O:48:"Drupal\Core\StringTranslation\TranslatableMarkup":3:{s:9:" * string";s:72:"Indicates if the last edit of a translation belongs to current revision.";s:12:" * arguments";a:0:{}s:10:" * options";a:0:{}}s:9:"read-only";b:1;s:12:"revisionable";b:1;s:12:"translatable";b:1;s:8:"provider";s:18:"content_moderation";s:10:"field_name";s:29:"revision_translation_affected";s:11:"entity_type";s:24:"content_moderation_state";s:6:"bundle";N;}}}',
  ))
  ->values(array(
    'collection' => 'entity.definitions.installed',
    'name' => 'workflow.entity_type',
    'value' => 'O:42:"Drupal\Core\Config\Entity\ConfigEntityType":41:{s:16:" * config_prefix";s:8:"workflow";s:15:" * static_cache";b:0;s:14:" * lookup_keys";a:1:{i:0;s:4:"uuid";}s:16:" * config_export";a:4:{i:0;s:2:"id";i:1;s:5:"label";i:2;s:4:"type";i:3;s:13:"type_settings";}s:21:" * mergedConfigExport";a:0:{}s:15:" * render_cache";b:1;s:19:" * persistent_cache";b:1;s:14:" * entity_keys";a:8:{s:2:"id";s:2:"id";s:5:"label";s:5:"label";s:4:"uuid";s:4:"uuid";s:8:"revision";s:0:"";s:6:"bundle";s:0:"";s:8:"langcode";s:8:"langcode";s:16:"default_langcode";s:16:"default_langcode";s:29:"revision_translation_affected";s:29:"revision_translation_affected";}s:5:" * id";s:8:"workflow";s:16:" * originalClass";s:32:"Drupal\workflows\Entity\Workflow";s:11:" * handlers";a:5:{s:6:"access";s:45:"Drupal\workflows\WorkflowAccessControlHandler";s:12:"list_builder";s:36:"Drupal\workflows\WorkflowListBuilder";s:4:"form";a:9:{s:3:"add";s:37:"Drupal\workflows\Form\WorkflowAddForm";s:4:"edit";s:38:"Drupal\workflows\Form\WorkflowEditForm";s:6:"delete";s:40:"Drupal\workflows\Form\WorkflowDeleteForm";s:9:"add-state";s:42:"Drupal\workflows\Form\WorkflowStateAddForm";s:10:"edit-state";s:43:"Drupal\workflows\Form\WorkflowStateEditForm";s:12:"delete-state";s:45:"Drupal\workflows\Form\WorkflowStateDeleteForm";s:14:"add-transition";s:47:"Drupal\workflows\Form\WorkflowTransitionAddForm";s:15:"edit-transition";s:48:"Drupal\workflows\Form\WorkflowTransitionEditForm";s:17:"delete-transition";s:50:"Drupal\workflows\Form\WorkflowTransitionDeleteForm";}s:14:"route_provider";a:1:{s:4:"html";s:49:"Drupal\Core\Entity\Routing\AdminHtmlRouteProvider";}s:7:"storage";s:45:"Drupal\Core\Config\Entity\ConfigEntityStorage";}s:19:" * admin_permission";s:20:"administer workflows";s:25:" * permission_granularity";s:11:"entity_type";s:8:" * links";a:6:{s:8:"add-form";s:36:"/admin/config/workflow/workflows/add";s:9:"edit-form";s:50:"/admin/config/workflow/workflows/manage/{workflow}";s:11:"delete-form";s:57:"/admin/config/workflow/workflows/manage/{workflow}/delete";s:14:"add-state-form";s:60:"/admin/config/workflow/workflows/manage/{workflow}/add_state";s:19:"add-transition-form";s:65:"/admin/config/workflow/workflows/manage/{workflow}/add_transition";s:10:"collection";s:32:"/admin/config/workflow/workflows";}s:17:" * label_callback";N;s:21:" * bundle_entity_type";N;s:12:" * bundle_of";N;s:15:" * bundle_label";N;s:13:" * base_table";N;s:22:" * revision_data_table";N;s:17:" * revision_table";N;s:13:" * data_table";N;s:15:" * translatable";b:0;s:19:" * show_revision_ui";b:0;s:8:" * label";O:48:"Drupal\Core\StringTranslation\TranslatableMarkup":3:{s:9:" * string";s:8:"Workflow";s:12:" * arguments";a:0:{}s:10:" * options";a:0:{}}s:19:" * label_collection";O:48:"Drupal\Core\StringTranslation\TranslatableMarkup":3:{s:9:" * string";s:9:"Workflows";s:12:" * arguments";a:0:{}s:10:" * options";a:0:{}}s:17:" * label_singular";s:0:"";s:15:" * label_plural";s:0:"";s:14:" * label_count";a:0:{}s:15:" * uri_callback";N;s:8:" * group";s:13:"configuration";s:14:" * group_label";O:48:"Drupal\Core\StringTranslation\TranslatableMarkup":3:{s:9:" * string";s:13:"Configuration";s:12:" * arguments";a:0:{}s:10:" * options";a:1:{s:7:"context";s:17:"Entity type group";}}s:22:" * field_ui_base_route";N;s:26:" * common_reference_target";b:0;s:22:" * list_cache_contexts";a:0:{}s:18:" * list_cache_tags";a:1:{i:0;s:20:"config:workflow_list";}s:14:" * constraints";a:0:{}s:13:" * additional";a:0:{}s:8:" * class";s:32:"Drupal\workflows\Entity\Workflow";s:11:" * provider";s:9:"workflows";s:20:" * stringTranslation";N;}',
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
    'value' => 'a:4:{s:24:"content_moderation_state";a:1:{s:6:"fields";a:1:{s:2:"id";a:4:{s:4:"type";s:3:"int";s:8:"unsigned";b:1;s:4:"size";s:6:"normal";s:8:"not null";b:1;}}}s:35:"content_moderation_state_field_data";a:1:{s:6:"fields";a:1:{s:2:"id";a:4:{s:4:"type";s:3:"int";s:8:"unsigned";b:1;s:4:"size";s:6:"normal";s:8:"not null";b:1;}}}s:33:"content_moderation_state_revision";a:1:{s:6:"fields";a:1:{s:2:"id";a:4:{s:4:"type";s:3:"int";s:8:"unsigned";b:1;s:4:"size";s:6:"normal";s:8:"not null";b:1;}}}s:39:"content_moderation_state_field_revision";a:1:{s:6:"fields";a:1:{s:2:"id";a:4:{s:4:"type";s:3:"int";s:8:"unsigned";b:1;s:4:"size";s:6:"normal";s:8:"not null";b:1;}}}}',
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
    'name' => 'content_moderation_state.field_schema_data.revision_id',
    'value' => 'a:4:{s:24:"content_moderation_state";a:1:{s:6:"fields";a:1:{s:11:"revision_id";a:4:{s:4:"type";s:3:"int";s:8:"unsigned";b:1;s:4:"size";s:6:"normal";s:8:"not null";b:0;}}}s:35:"content_moderation_state_field_data";a:1:{s:6:"fields";a:1:{s:11:"revision_id";a:4:{s:4:"type";s:3:"int";s:8:"unsigned";b:1;s:4:"size";s:6:"normal";s:8:"not null";b:1;}}}s:33:"content_moderation_state_revision";a:1:{s:6:"fields";a:1:{s:11:"revision_id";a:4:{s:4:"type";s:3:"int";s:8:"unsigned";b:1;s:4:"size";s:6:"normal";s:8:"not null";b:1;}}}s:39:"content_moderation_state_field_revision";a:1:{s:6:"fields";a:1:{s:11:"revision_id";a:4:{s:4:"type";s:3:"int";s:8:"unsigned";b:1;s:4:"size";s:6:"normal";s:8:"not null";b:1;}}}}',
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
    'collection' => 'state',
    'name' => 'routing.non_admin_routes',
    'value' => 'a:97:{i:0;s:27:"block.category_autocomplete";i:1;s:22:"block_content.add_page";i:2;s:22:"block_content.add_form";i:3;s:30:"entity.block_content.canonical";i:4;s:30:"entity.block_content.edit_form";i:5;s:32:"entity.block_content.delete_form";i:6;s:24:"entity.comment.edit_form";i:7;s:15:"comment.approve";i:8;s:24:"entity.comment.canonical";i:9;s:26:"entity.comment.delete_form";i:10;s:13:"comment.reply";i:11;s:31:"comment.new_comments_node_links";i:12;s:21:"comment.node_redirect";i:13;s:17:"contact.site_page";i:14;s:29:"entity.contact_form.canonical";i:15;s:24:"entity.user.contact_form";i:16;s:17:"contextual.render";i:17;s:17:"editor.filter_xss";i:18;s:31:"editor.field_untransformed_text";i:19;s:19:"editor.image_dialog";i:20;s:18:"editor.link_dialog";i:21;s:18:"file.ajax_progress";i:22;s:15:"filter.tips_all";i:23;s:11:"filter.tips";i:24;s:26:"history.get_last_node_view";i:25;s:17:"history.read_node";i:26;s:18:"image.style_public";i:27;s:19:"image.style_private";i:28;s:12:"image.upload";i:29;s:10:"image.info";i:30;s:13:"node.add_page";i:31;s:8:"node.add";i:32;s:19:"entity.node.preview";i:33;s:27:"entity.node.version_history";i:34;s:20:"entity.node.revision";i:35;s:28:"node.revision_revert_confirm";i:36;s:40:"node.revision_revert_translation_confirm";i:37;s:28:"node.revision_delete_confirm";i:38;s:18:"quickedit.metadata";i:39;s:21:"quickedit.attachments";i:40;s:20:"quickedit.field_form";i:41;s:21:"quickedit.entity_save";i:42;s:11:"search.view";i:43;s:23:"search.view_node_search";i:44;s:23:"search.help_node_search";i:45;s:23:"search.view_user_search";i:46;s:23:"search.help_user_search";i:47;s:19:"shortcut.set_switch";i:48;s:10:"system.401";i:49;s:10:"system.403";i:50;s:10:"system.404";i:51;s:10:"system.4xx";i:52;s:11:"system.cron";i:53;s:33:"system.machine_name_transliterate";i:54;s:12:"system.files";i:55;s:28:"system.private_file_download";i:56;s:16:"system.temporary";i:57;s:7:"<front>";i:58;s:6:"<none>";i:59;s:8:"<nolink>";i:60;s:9:"<current>";i:61;s:15:"system.timezone";i:62;s:22:"system.batch_page.html";i:63;s:22:"system.batch_page.json";i:64;s:16:"system.db_update";i:65;s:26:"system.entity_autocomplete";i:66;s:16:"system.csrftoken";i:67;s:30:"entity.taxonomy_term.edit_form";i:68;s:32:"entity.taxonomy_term.delete_form";i:69;s:16:"toolbar.subtrees";i:70;s:13:"user.register";i:71;s:11:"user.logout";i:72;s:9:"user.pass";i:73;s:14:"user.pass.http";i:74;s:9:"user.page";i:75;s:10:"user.login";i:76;s:15:"user.login.http";i:77;s:22:"user.login_status.http";i:78;s:16:"user.logout.http";i:79;s:19:"user.cancel_confirm";i:80;s:16:"user.reset.login";i:81;s:10:"user.reset";i:82;s:15:"user.reset.form";i:83;s:21:"view.frontpage.feed_1";i:84;s:21:"view.frontpage.page_1";i:85;s:25:"view.taxonomy_term.feed_1";i:86;s:25:"view.taxonomy_term.page_1";i:87;s:10:"views.ajax";i:88;s:35:"entity.block_content.latest_version";i:89;s:21:"entity.node.canonical";i:90;s:23:"entity.node.delete_form";i:91;s:21:"entity.node.edit_form";i:92;s:26:"entity.node.latest_version";i:93;s:21:"entity.user.canonical";i:94;s:21:"entity.user.edit_form";i:95;s:23:"entity.user.cancel_form";i:96;s:30:"entity.taxonomy_term.canonical";}',
  ))
  ->values(array(
    'collection' => 'state',
    'name' => 'system.js_cache_files',
    'value' => 'a:10:{s:64:"ef5219d33ebedcd4b9b0ccc64f741d50bebb463122945dd3b12519b97e268ab4";s:61:"public://js/js_VtafjXmRvoUgAzqzYTA3Wrjkx9wcWhjP0G4ZnnqRamA.js";s:64:"22b57c12b5f7dfa20d16a8fb27842e2c48a55df949019086a2e14bfa9b53ed21";s:61:"public://js/js_BKcMdIbOMdbTdLn9dkUq3KCJfIKKo2SvKoQ1AnB8D-g.js";s:64:"c839df7c4fcaff2cb7890a0c2e9316f456b4c990c363fb4eb87a2a601c594055";s:61:"public://js/js_VhqXmo4azheUjYC30rijnR_Dddo0WjWkF27k5gTL8S4.js";s:64:"4290e1da549b525e5a284c0b6932deb2925f10d69b9e9df47ab9cf9be6f908c3";s:61:"public://js/js_bXOpMT4zIssDSNf-hJCfDU-GMYjogKxosCScYEEjggE.js";s:64:"ffc78e60c19e191320a1b742a777ad5b93976fce4b274faf2332dea1c3cf2393";s:61:"public://js/js_lZ_KgpFfmlx3GgVnM7BsJsa7fCjkkusU9keGexj0zRU.js";s:64:"a3979c3d25cb559722f7d2706c5d35e45bee24623da43b716fd806beea460ea4";s:61:"public://js/js_jeYE5w7CHcwrxNQJfqi7dVmAaL_TOwRxNmRmq7vLsUQ.js";s:64:"79ab52de68ab5af51160a0ef90f0c3b81977061cd1b4ec411ace995fb97ed34f";s:61:"public://js/js_PSJbtOVCvisdPwajJGvk9V8i7H6XPQfSy9LE1sAkneE.js";s:64:"07fd78d9ba4d77f63cb7a40bfaf66bb5d6232e46a5822207e8dd0d9252810971";s:61:"public://js/js_yFV18P6CACJDKa_0KFPQJwI-GGWxK6FqfSt1jdGZzDo.js";s:64:"f7a654d4d83e97e639b9855ec7593433aa08380ffd163ea2860c4d17f53f0f1b";s:61:"public://js/js_a-XEqg_PQIgAR7_4F2EScN6QKaClD_F43n2X6kQJwu4.js";s:64:"ddea937b5008530524945e74d82ce7ad1660346c4d44396941f743f3a0440973";s:61:"public://js/js_8BEUTcp1kBATjLlIGkgkfV9MI1FiKvn5V0c3C89wHSI.js";}',
  ))
  ->values(array(
    'collection' => 'state',
    'name' => 'system.theme.files',
    'value' => 'a:47:{s:19:"test_invalid_engine";s:81:"core/modules/system/tests/themes/test_invalid_engine/test_invalid_engine.info.yml";s:34:"test_ckeditor_stylesheets_external";s:111:"core/modules/system/tests/themes/test_ckeditor_stylesheets_external/test_ckeditor_stylesheets_external.info.yml";s:43:"test_ckeditor_stylesheets_protocol_relative";s:129:"core/modules/system/tests/themes/test_ckeditor_stylesheets_protocol_relative/test_ckeditor_stylesheets_protocol_relative.info.yml";s:34:"test_ckeditor_stylesheets_relative";s:111:"core/modules/system/tests/themes/test_ckeditor_stylesheets_relative/test_ckeditor_stylesheets_relative.info.yml";s:26:"test_theme_nyan_cat_engine";s:95:"core/modules/system/tests/themes/test_theme_nyan_cat_engine/test_theme_nyan_cat_engine.info.yml";s:19:"test_theme_settings";s:81:"core/modules/system/tests/themes/test_theme_settings/test_theme_settings.info.yml";s:16:"test_theme_theme";s:75:"core/modules/system/tests/themes/test_theme_theme/test_theme_theme.info.yml";s:14:"test_wild_west";s:71:"core/modules/system/tests/themes/test_wild_west/test_wild_west.info.yml";s:5:"stark";s:32:"core/themes/stark/stark.info.yml";s:19:"big_pipe_test_theme";s:83:"core/modules/big_pipe/tests/themes/big_pipe_test_theme/big_pipe_test_theme.info.yml";s:29:"block_test_specialchars_theme";s:119:"core/modules/block/tests/modules/block_test/themes/block_test_specialchars_theme/block_test_specialchars_theme.info.yml";s:16:"block_test_theme";s:93:"core/modules/block/tests/modules/block_test/themes/block_test_theme/block_test_theme.info.yml";s:21:"breakpoint_theme_test";s:89:"core/modules/breakpoint/tests/themes/breakpoint_theme_test/breakpoint_theme_test.info.yml";s:16:"color_test_theme";s:93:"core/modules/color/tests/modules/color_test/themes/color_test_theme/color_test_theme.info.yml";s:23:"config_clash_test_theme";s:82:"core/modules/config/tests/config_clash_test_theme/config_clash_test_theme.info.yml";s:29:"config_translation_test_theme";s:113:"core/modules/config_translation/tests/themes/config_translation_test_theme/config_translation_test_theme.info.yml";s:24:"statistics_test_attached";s:95:"core/modules/statistics/tests/themes/statistics_test_attached/statistics_test_attached.info.yml";s:14:"test_basetheme";s:71:"core/modules/system/tests/themes/test_basetheme/test_basetheme.info.yml";s:22:"test_invalid_basetheme";s:87:"core/modules/system/tests/themes/test_invalid_basetheme/test_invalid_basetheme.info.yml";s:26:"test_invalid_basetheme_sub";s:95:"core/modules/system/tests/themes/test_invalid_basetheme_sub/test_invalid_basetheme_sub.info.yml";s:17:"test_invalid_core";s:77:"core/modules/system/tests/themes/test_invalid_core/test_invalid_core.info.yml";s:19:"test_invalid_region";s:81:"core/modules/system/tests/themes/test_invalid_region/test_invalid_region.info.yml";s:11:"test_stable";s:65:"core/modules/system/tests/themes/test_stable/test_stable.info.yml";s:16:"test_subsubtheme";s:75:"core/modules/system/tests/themes/test_subsubtheme/test_subsubtheme.info.yml";s:10:"test_theme";s:63:"core/modules/system/tests/themes/test_theme/test_theme.info.yml";s:51:"test_theme_having_veery_long_name_which_is_too_long";s:145:"core/modules/system/tests/themes/test_theme_having_veery_long_name_which_is_too_long/test_theme_having_veery_long_name_which_is_too_long.info.yml";s:26:"test_theme_libraries_empty";s:95:"core/modules/system/tests/themes/test_theme_libraries_empty/test_theme_libraries_empty.info.yml";s:27:"test_theme_libraries_extend";s:97:"core/modules/system/tests/themes/test_theme_libraries_extend/test_theme_libraries_extend.info.yml";s:50:"test_theme_libraries_override_with_drupal_settings";s:143:"core/modules/system/tests/themes/test_theme_libraries_override_with_drupal_settings/test_theme_libraries_override_with_drupal_settings.info.yml";s:48:"test_theme_libraries_override_with_invalid_asset";s:139:"core/modules/system/tests/themes/test_theme_libraries_override_with_invalid_asset/test_theme_libraries_override_with_invalid_asset.info.yml";s:40:"test_theme_twig_registry_loader_subtheme";s:123:"core/modules/system/tests/themes/test_theme_twig_registry_loader_subtheme/test_theme_twig_registry_loader_subtheme.info.yml";s:20:"update_test_subtheme";s:83:"core/modules/update/tests/themes/update_test_subtheme/update_test_subtheme.info.yml";s:15:"user_test_theme";s:71:"core/modules/user/tests/themes/user_test_theme/user_test_theme.info.yml";s:27:"views_test_checkboxes_theme";s:96:"core/modules/views/tests/themes/views_test_checkboxes_theme/views_test_checkboxes_theme.info.yml";s:16:"views_test_theme";s:74:"core/modules/views/tests/themes/views_test_theme/views_test_theme.info.yml";s:13:"test_subtheme";s:69:"core/modules/system/tests/themes/test_subtheme/test_subtheme.info.yml";s:31:"test_theme_twig_registry_loader";s:105:"core/modules/system/tests/themes/test_theme_twig_registry_loader/test_theme_twig_registry_loader.info.yml";s:37:"test_theme_twig_registry_loader_theme";s:117:"core/modules/system/tests/themes/test_theme_twig_registry_loader_theme/test_theme_twig_registry_loader_theme.info.yml";s:21:"update_test_basetheme";s:85:"core/modules/update/tests/themes/update_test_basetheme/update_test_basetheme.info.yml";s:6:"stable";s:34:"core/themes/stable/stable.info.yml";s:5:"seven";s:32:"core/themes/seven/seven.info.yml";s:6:"bartik";s:34:"core/themes/bartik/bartik.info.yml";s:6:"classy";s:34:"core/themes/classy/classy.info.yml";s:23:"entity_print_test_theme";s:90:"modules/entity_print/tests/themes/entity_print_test_theme/entity_print_test_theme.info.yml";s:28:"webform_bootstrap_test_theme";s:121:"modules/webform/modules/webform_bootstrap/tests/themes/webform_bootstrap_test_theme/webform_bootstrap_test_theme.info.yml";s:19:"webform_test_bartik";s:77:"modules/webform/tests/themes/webform_test_bartik/webform_test_bartik.info.yml";s:4:"mayo";s:25:"themes/mayo/mayo.info.yml";}',
  ))
  ->values(array(
    'collection' => 'system.schema',
    'name' => 'content_moderation',
    'value' => 's:4:"8401";',
  ))
  ->values(array(
    'collection' => 'system.schema',
    'name' => 'workflows',
    'value' => 'i:8000;',
  ))
  ->execute();

$connection->delete('menu_tree')
  ->condition('mlid', [
    '24',
  ], 'IN')
  ->execute();

$connection->insert('menu_tree')
  ->fields(array(
    'menu_name',
    'mlid',
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
  ))
  ->values(array(
    'menu_name' => 'admin',
    'mlid' => '24',
    'id' => 'system.admin_config_workflow',
    'parent' => 'system.admin_config',
    'route_name' => 'system.admin_config_workflow',
    'route_param_key' => '',
    'route_parameters' => 'a:0:{}',
    'url' => '',
    'title' => 'O:48:"Drupal\Core\StringTranslation\TranslatableMarkup":3:{s:9:" * string";s:8:"Workflow";s:12:" * arguments";a:0:{}s:10:" * options";a:0:{}}',
    'description' => 'O:48:"Drupal\Core\StringTranslation\TranslatableMarkup":3:{s:9:" * string";s:28:"Manage the content workflow.";s:12:" * arguments";a:0:{}s:10:" * options";a:0:{}}',
    'class' => 'Drupal\Core\Menu\MenuLinkDefault',
    'options' => 'a:0:{}',
    'provider' => 'system',
    'enabled' => '1',
    'discovered' => '1',
    'expanded' => '0',
    'weight' => '5',
    'metadata' => 'a:0:{}',
    'has_children' => '1',
    'depth' => '3',
    'p1' => '1',
    'p2' => '6',
    'p3' => '24',
    'p4' => '0',
    'p5' => '0',
    'p6' => '0',
    'p7' => '0',
    'p8' => '0',
    'p9' => '0',
    'form_class' => 'Drupal\Core\Menu\Form\MenuLinkDefaultForm',
  ))
  ->values(array(
    'menu_name' => 'admin',
    'mlid' => '63',
    'id' => 'entity.workflow.collection',
    'parent' => 'system.admin_config_workflow',
    'route_name' => 'entity.workflow.collection',
    'route_param_key' => '',
    'route_parameters' => 'a:0:{}',
    'url' => '',
    'title' => 'O:48:"Drupal\Core\StringTranslation\TranslatableMarkup":3:{s:9:" * string";s:9:"Workflows";s:12:" * arguments";a:0:{}s:10:" * options";a:0:{}}',
    'description' => 'O:48:"Drupal\Core\StringTranslation\TranslatableMarkup":3:{s:9:" * string";s:20:"Configure workflows.";s:12:" * arguments";a:0:{}s:10:" * options";a:0:{}}',
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
  ))
  ->execute();

$connection->delete('router')
  ->condition('name', [
    'entity.block_content.canonical',
    'entity.block_content.edit_form',
    'entity.node.edit_form',
  ], 'IN')
  ->execute();

$connection->insert('router')
  ->fields(array(
    'name',
    'path',
    'pattern_outline',
    'fit',
    'route',
    'number_parts',
  ))
  ->values(array(
    'name' => 'content_moderation.workflow_type_edit_form',
    'path' => '/admin/config/workflow/workflows/manage/{workflow}/type/{entity_type_id}',
    'pattern_outline' => '/admin/config/workflow/workflows/manage/%/type/%',
    'fit' => '250',
    'route' => 'C:31:"Symfony\Component\Routing\Route":1871:{a:9:{s:4:"path";s:72:"/admin/config/workflow/workflows/manage/{workflow}/type/{entity_type_id}";s:4:"host";s:0:"";s:8:"defaults";a:2:{s:5:"_form";s:73:"\Drupal\content_moderation\Form\ContentModerationConfigureEntityTypesForm";s:15:"_title_callback";s:83:"\Drupal\content_moderation\Form\ContentModerationConfigureEntityTypesForm::getTitle";}s:12:"requirements";a:1:{s:11:"_permission";s:20:"administer workflows";}s:7:"options";a:6:{s:14:"compiler_class";s:34:"\Drupal\Core\Routing\RouteCompiler";s:10:"parameters";a:1:{s:8:"workflow";a:2:{s:4:"type";s:15:"entity:workflow";s:9:"converter";s:63:"drupal.proxy_original_service.paramconverter.configentity_admin";}}s:12:"_admin_route";b:1;s:14:"_route_filters";a:2:{i:0;s:13:"method_filter";i:1;s:27:"content_type_header_matcher";}s:16:"_route_enhancers";a:2:{i:0;s:31:"route_enhancer.param_conversion";i:1;s:19:"route_enhancer.form";}s:14:"_access_checks";a:1:{i:0;s:23:"access_check.permission";}}s:7:"schemes";a:0:{}s:7:"methods";a:2:{i:0;s:3:"GET";i:1;s:4:"POST";}s:9:"condition";s:0:"";s:8:"compiled";C:33:"Drupal\Core\Routing\CompiledRoute":768:{a:11:{s:4:"vars";a:2:{i:0;s:8:"workflow";i:1;s:14:"entity_type_id";}s:11:"path_prefix";s:0:"";s:10:"path_regex";s:97:"#^/admin/config/workflow/workflows/manage/(?P<workflow>[^/]++)/type/(?P<entity_type_id>[^/]++)$#s";s:11:"path_tokens";a:4:{i:0;a:4:{i:0;s:8:"variable";i:1;s:1:"/";i:2;s:6:"[^/]++";i:3;s:14:"entity_type_id";}i:1;a:2:{i:0;s:4:"text";i:1;s:5:"/type";}i:2;a:4:{i:0;s:8:"variable";i:1;s:1:"/";i:2;s:6:"[^/]++";i:3;s:8:"workflow";}i:3;a:2:{i:0;s:4:"text";i:1;s:39:"/admin/config/workflow/workflows/manage";}}s:9:"path_vars";a:2:{i:0;s:8:"workflow";i:1;s:14:"entity_type_id";}s:10:"host_regex";N;s:11:"host_tokens";a:0:{}s:9:"host_vars";a:0:{}s:3:"fit";i:250;s:14:"patternOutline";s:48:"/admin/config/workflow/workflows/manage/%/type/%";s:8:"numParts";i:8;}}}}',
    'number_parts' => '8',
  ))
  ->values(array(
    'name' => 'entity.block_content.canonical',
    'path' => '/block/{block_content}',
    'pattern_outline' => '/block/%',
    'fit' => '2',
    'route' => 'C:31:"Symfony\Component\Routing\Route":1368:{a:9:{s:4:"path";s:22:"/block/{block_content}";s:4:"host";s:0:"";s:8:"defaults";a:1:{s:12:"_entity_form";s:18:"block_content.edit";}s:12:"requirements";a:2:{s:14:"_entity_access";s:20:"block_content.update";s:13:"block_content";s:3:"\d+";}s:7:"options";a:6:{s:14:"compiler_class";s:34:"\Drupal\Core\Routing\RouteCompiler";s:12:"_admin_route";b:1;s:10:"parameters";a:1:{s:13:"block_content";a:2:{s:4:"type";s:20:"entity:block_content";s:9:"converter";s:30:"paramconverter.latest_revision";}}s:14:"_route_filters";a:2:{i:0;s:13:"method_filter";i:1;s:27:"content_type_header_matcher";}s:16:"_route_enhancers";a:2:{i:0;s:31:"route_enhancer.param_conversion";i:1;s:21:"route_enhancer.entity";}s:14:"_access_checks";a:1:{i:0;s:19:"access_check.entity";}}s:7:"schemes";a:0:{}s:7:"methods";a:2:{i:0;s:3:"GET";i:1;s:4:"POST";}s:9:"condition";s:0:"";s:8:"compiled";C:33:"Drupal\Core\Routing\CompiledRoute":466:{a:11:{s:4:"vars";a:1:{i:0;s:13:"block_content";}s:11:"path_prefix";s:0:"";s:10:"path_regex";s:34:"#^/block/(?P<block_content>\d+)$#s";s:11:"path_tokens";a:2:{i:0;a:4:{i:0;s:8:"variable";i:1;s:1:"/";i:2;s:3:"\d+";i:3;s:13:"block_content";}i:1;a:2:{i:0;s:4:"text";i:1;s:6:"/block";}}s:9:"path_vars";a:1:{i:0;s:13:"block_content";}s:10:"host_regex";N;s:11:"host_tokens";a:0:{}s:9:"host_vars";a:0:{}s:3:"fit";i:2;s:14:"patternOutline";s:8:"/block/%";s:8:"numParts";i:2;}}}}',
    'number_parts' => '2',
  ))
  ->values(array(
    'name' => 'entity.block_content.edit_form',
    'path' => '/block/{block_content}',
    'pattern_outline' => '/block/%',
    'fit' => '2',
    'route' => 'C:31:"Symfony\Component\Routing\Route":1368:{a:9:{s:4:"path";s:22:"/block/{block_content}";s:4:"host";s:0:"";s:8:"defaults";a:1:{s:12:"_entity_form";s:18:"block_content.edit";}s:12:"requirements";a:2:{s:14:"_entity_access";s:20:"block_content.update";s:13:"block_content";s:3:"\d+";}s:7:"options";a:6:{s:14:"compiler_class";s:34:"\Drupal\Core\Routing\RouteCompiler";s:12:"_admin_route";b:1;s:10:"parameters";a:1:{s:13:"block_content";a:2:{s:4:"type";s:20:"entity:block_content";s:9:"converter";s:30:"paramconverter.latest_revision";}}s:14:"_route_filters";a:2:{i:0;s:13:"method_filter";i:1;s:27:"content_type_header_matcher";}s:16:"_route_enhancers";a:2:{i:0;s:31:"route_enhancer.param_conversion";i:1;s:21:"route_enhancer.entity";}s:14:"_access_checks";a:1:{i:0;s:19:"access_check.entity";}}s:7:"schemes";a:0:{}s:7:"methods";a:2:{i:0;s:3:"GET";i:1;s:4:"POST";}s:9:"condition";s:0:"";s:8:"compiled";C:33:"Drupal\Core\Routing\CompiledRoute":466:{a:11:{s:4:"vars";a:1:{i:0;s:13:"block_content";}s:11:"path_prefix";s:0:"";s:10:"path_regex";s:34:"#^/block/(?P<block_content>\d+)$#s";s:11:"path_tokens";a:2:{i:0;a:4:{i:0;s:8:"variable";i:1;s:1:"/";i:2;s:3:"\d+";i:3;s:13:"block_content";}i:1;a:2:{i:0;s:4:"text";i:1;s:6:"/block";}}s:9:"path_vars";a:1:{i:0;s:13:"block_content";}s:10:"host_regex";N;s:11:"host_tokens";a:0:{}s:9:"host_vars";a:0:{}s:3:"fit";i:2;s:14:"patternOutline";s:8:"/block/%";s:8:"numParts";i:2;}}}}',
    'number_parts' => '2',
  ))
  ->values(array(
    'name' => 'entity.block_content.latest_version',
    'path' => '/block/{block_content}/latest',
    'pattern_outline' => '/block/%/latest',
    'fit' => '5',
    'route' => 'C:31:"Symfony\Component\Routing\Route":1678:{a:9:{s:4:"path";s:29:"/block/{block_content}/latest";s:4:"host";s:0:"";s:8:"defaults";a:2:{s:12:"_entity_view";s:18:"block_content.full";s:15:"_title_callback";s:54:"\Drupal\Core\Entity\Controller\EntityController::title";}s:12:"requirements";a:3:{s:14:"_entity_access";s:18:"block_content.view";s:34:"_content_moderation_latest_version";s:4:"TRUE";s:13:"block_content";s:3:"\d+";}s:7:"options";a:6:{s:14:"compiler_class";s:34:"\Drupal\Core\Routing\RouteCompiler";s:31:"_content_moderation_entity_type";s:13:"block_content";s:10:"parameters";a:1:{s:13:"block_content";a:3:{s:4:"type";s:20:"entity:block_content";s:21:"load_pending_revision";i:1;s:9:"converter";s:30:"paramconverter.latest_revision";}}s:14:"_route_filters";a:2:{i:0;s:13:"method_filter";i:1;s:27:"content_type_header_matcher";}s:16:"_route_enhancers";a:2:{i:0;s:31:"route_enhancer.param_conversion";i:1;s:21:"route_enhancer.entity";}s:14:"_access_checks";a:2:{i:0;s:19:"access_check.entity";i:1;s:28:"access_check.latest_revision";}}s:7:"schemes";a:0:{}s:7:"methods";a:2:{i:0;s:3:"GET";i:1;s:4:"POST";}s:9:"condition";s:0:"";s:8:"compiled";C:33:"Drupal\Core\Routing\CompiledRoute":524:{a:11:{s:4:"vars";a:1:{i:0;s:13:"block_content";}s:11:"path_prefix";s:0:"";s:10:"path_regex";s:41:"#^/block/(?P<block_content>\d+)/latest$#s";s:11:"path_tokens";a:3:{i:0;a:2:{i:0;s:4:"text";i:1;s:7:"/latest";}i:1;a:4:{i:0;s:8:"variable";i:1;s:1:"/";i:2;s:3:"\d+";i:3;s:13:"block_content";}i:2;a:2:{i:0;s:4:"text";i:1;s:6:"/block";}}s:9:"path_vars";a:1:{i:0;s:13:"block_content";}s:10:"host_regex";N;s:11:"host_tokens";a:0:{}s:9:"host_vars";a:0:{}s:3:"fit";i:5;s:14:"patternOutline";s:15:"/block/%/latest";s:8:"numParts";i:3;}}}}',
    'number_parts' => '3',
  ))
  ->values(array(
    'name' => 'entity.node.edit_form',
    'path' => '/node/{node}/edit',
    'pattern_outline' => '/node/%/edit',
    'fit' => '5',
    'route' => 'C:31:"Symfony\Component\Routing\Route":1358:{a:9:{s:4:"path";s:17:"/node/{node}/edit";s:4:"host";s:0:"";s:8:"defaults";a:1:{s:12:"_entity_form";s:9:"node.edit";}s:12:"requirements";a:2:{s:14:"_entity_access";s:11:"node.update";s:4:"node";s:3:"\d+";}s:7:"options";a:7:{s:14:"compiler_class";s:34:"\Drupal\Core\Routing\RouteCompiler";s:21:"_node_operation_route";b:1;s:12:"_admin_route";b:1;s:10:"parameters";a:1:{s:4:"node";a:2:{s:4:"type";s:11:"entity:node";s:9:"converter";s:30:"paramconverter.latest_revision";}}s:14:"_route_filters";a:2:{i:0;s:13:"method_filter";i:1;s:27:"content_type_header_matcher";}s:16:"_route_enhancers";a:2:{i:0;s:31:"route_enhancer.param_conversion";i:1;s:21:"route_enhancer.entity";}s:14:"_access_checks";a:1:{i:0;s:19:"access_check.entity";}}s:7:"schemes";a:0:{}s:7:"methods";a:2:{i:0;s:3:"GET";i:1;s:4:"POST";}s:9:"condition";s:0:"";s:8:"compiled";C:33:"Drupal\Core\Routing\CompiledRoute":476:{a:11:{s:4:"vars";a:1:{i:0;s:4:"node";}s:11:"path_prefix";s:0:"";s:10:"path_regex";s:29:"#^/node/(?P<node>\d+)/edit$#s";s:11:"path_tokens";a:3:{i:0;a:2:{i:0;s:4:"text";i:1;s:5:"/edit";}i:1;a:4:{i:0;s:8:"variable";i:1;s:1:"/";i:2;s:3:"\d+";i:3;s:4:"node";}i:2;a:2:{i:0;s:4:"text";i:1;s:5:"/node";}}s:9:"path_vars";a:1:{i:0;s:4:"node";}s:10:"host_regex";N;s:11:"host_tokens";a:0:{}s:9:"host_vars";a:0:{}s:3:"fit";i:5;s:14:"patternOutline";s:12:"/node/%/edit";s:8:"numParts";i:3;}}}}',
    'number_parts' => '3',
  ))
  ->values(array(
    'name' => 'entity.node.latest_version',
    'path' => '/node/{node}/latest',
    'pattern_outline' => '/node/%/latest',
    'fit' => '5',
    'route' => 'C:31:"Symfony\Component\Routing\Route":1567:{a:9:{s:4:"path";s:19:"/node/{node}/latest";s:4:"host";s:0:"";s:8:"defaults";a:2:{s:12:"_entity_view";s:9:"node.full";s:15:"_title_callback";s:54:"\Drupal\Core\Entity\Controller\EntityController::title";}s:12:"requirements";a:3:{s:14:"_entity_access";s:9:"node.view";s:34:"_content_moderation_latest_version";s:4:"TRUE";s:4:"node";s:3:"\d+";}s:7:"options";a:6:{s:14:"compiler_class";s:34:"\Drupal\Core\Routing\RouteCompiler";s:31:"_content_moderation_entity_type";s:4:"node";s:10:"parameters";a:1:{s:4:"node";a:3:{s:4:"type";s:11:"entity:node";s:21:"load_pending_revision";i:1;s:9:"converter";s:30:"paramconverter.latest_revision";}}s:14:"_route_filters";a:2:{i:0;s:13:"method_filter";i:1;s:27:"content_type_header_matcher";}s:16:"_route_enhancers";a:2:{i:0;s:31:"route_enhancer.param_conversion";i:1;s:21:"route_enhancer.entity";}s:14:"_access_checks";a:2:{i:0;s:19:"access_check.entity";i:1;s:28:"access_check.latest_revision";}}s:7:"schemes";a:0:{}s:7:"methods";a:2:{i:0;s:3:"GET";i:1;s:4:"POST";}s:9:"condition";s:0:"";s:8:"compiled";C:33:"Drupal\Core\Routing\CompiledRoute":482:{a:11:{s:4:"vars";a:1:{i:0;s:4:"node";}s:11:"path_prefix";s:0:"";s:10:"path_regex";s:31:"#^/node/(?P<node>\d+)/latest$#s";s:11:"path_tokens";a:3:{i:0;a:2:{i:0;s:4:"text";i:1;s:7:"/latest";}i:1;a:4:{i:0;s:8:"variable";i:1;s:1:"/";i:2;s:3:"\d+";i:3;s:4:"node";}i:2;a:2:{i:0;s:4:"text";i:1;s:5:"/node";}}s:9:"path_vars";a:1:{i:0;s:4:"node";}s:10:"host_regex";N;s:11:"host_tokens";a:0:{}s:9:"host_vars";a:0:{}s:3:"fit";i:5;s:14:"patternOutline";s:14:"/node/%/latest";s:8:"numParts";i:3;}}}}',
    'number_parts' => '3',
  ))
  ->values(array(
    'name' => 'entity.workflow.add_form',
    'path' => '/admin/config/workflow/workflows/add',
    'pattern_outline' => '/admin/config/workflow/workflows/add',
    'fit' => '31',
    'route' => 'C:31:"Symfony\Component\Routing\Route":1264:{a:9:{s:4:"path";s:36:"/admin/config/workflow/workflows/add";s:4:"host";s:0:"";s:8:"defaults";a:3:{s:12:"_entity_form";s:12:"workflow.add";s:14:"entity_type_id";s:8:"workflow";s:15:"_title_callback";s:56:"Drupal\Core\Entity\Controller\EntityController::addTitle";}s:12:"requirements";a:1:{s:21:"_entity_create_access";s:8:"workflow";}s:7:"options";a:5:{s:14:"compiler_class";s:34:"\Drupal\Core\Routing\RouteCompiler";s:12:"_admin_route";b:1;s:14:"_route_filters";a:2:{i:0;s:13:"method_filter";i:1;s:27:"content_type_header_matcher";}s:16:"_route_enhancers";a:2:{i:0;s:31:"route_enhancer.param_conversion";i:1;s:21:"route_enhancer.entity";}s:14:"_access_checks";a:1:{i:0;s:26:"access_check.entity_create";}}s:7:"schemes";a:0:{}s:7:"methods";a:2:{i:0;s:3:"GET";i:1;s:4:"POST";}s:9:"condition";s:0:"";s:8:"compiled";C:33:"Drupal\Core\Routing\CompiledRoute":404:{a:11:{s:4:"vars";a:0:{}s:11:"path_prefix";s:0:"";s:10:"path_regex";s:41:"#^/admin/config/workflow/workflows/add$#s";s:11:"path_tokens";a:1:{i:0;a:2:{i:0;s:4:"text";i:1;s:36:"/admin/config/workflow/workflows/add";}}s:9:"path_vars";a:0:{}s:10:"host_regex";N;s:11:"host_tokens";a:0:{}s:9:"host_vars";a:0:{}s:3:"fit";i:31;s:14:"patternOutline";s:36:"/admin/config/workflow/workflows/add";s:8:"numParts";i:5;}}}}',
    'number_parts' => '5',
  ))
  ->values(array(
    'name' => 'entity.workflow.add_state_form',
    'path' => '/admin/config/workflow/workflows/manage/{workflow}/add_state',
    'pattern_outline' => '/admin/config/workflow/workflows/manage/%/add_state',
    'fit' => '125',
    'route' => 'C:31:"Symfony\Component\Routing\Route":1572:{a:9:{s:4:"path";s:60:"/admin/config/workflow/workflows/manage/{workflow}/add_state";s:4:"host";s:0:"";s:8:"defaults";a:2:{s:12:"_entity_form";s:18:"workflow.add-state";s:6:"_title";s:9:"Add state";}s:12:"requirements";a:1:{s:14:"_entity_access";s:13:"workflow.edit";}s:7:"options";a:6:{s:14:"compiler_class";s:34:"\Drupal\Core\Routing\RouteCompiler";s:10:"parameters";a:1:{s:8:"workflow";a:2:{s:4:"type";s:15:"entity:workflow";s:9:"converter";s:63:"drupal.proxy_original_service.paramconverter.configentity_admin";}}s:12:"_admin_route";b:1;s:14:"_route_filters";a:2:{i:0;s:13:"method_filter";i:1;s:27:"content_type_header_matcher";}s:16:"_route_enhancers";a:2:{i:0;s:31:"route_enhancer.param_conversion";i:1;s:21:"route_enhancer.entity";}s:14:"_access_checks";a:1:{i:0;s:19:"access_check.entity";}}s:7:"schemes";a:0:{}s:7:"methods";a:2:{i:0;s:3:"GET";i:1;s:4:"POST";}s:9:"condition";s:0:"";s:8:"compiled";C:33:"Drupal\Core\Routing\CompiledRoute":619:{a:11:{s:4:"vars";a:1:{i:0;s:8:"workflow";}s:11:"path_prefix";s:0:"";s:10:"path_regex";s:75:"#^/admin/config/workflow/workflows/manage/(?P<workflow>[^/]++)/add_state$#s";s:11:"path_tokens";a:3:{i:0;a:2:{i:0;s:4:"text";i:1;s:10:"/add_state";}i:1;a:4:{i:0;s:8:"variable";i:1;s:1:"/";i:2;s:6:"[^/]++";i:3;s:8:"workflow";}i:2;a:2:{i:0;s:4:"text";i:1;s:39:"/admin/config/workflow/workflows/manage";}}s:9:"path_vars";a:1:{i:0;s:8:"workflow";}s:10:"host_regex";N;s:11:"host_tokens";a:0:{}s:9:"host_vars";a:0:{}s:3:"fit";i:125;s:14:"patternOutline";s:51:"/admin/config/workflow/workflows/manage/%/add_state";s:8:"numParts";i:7;}}}}',
    'number_parts' => '7',
  ))
  ->values(array(
    'name' => 'entity.workflow.add_transition_form',
    'path' => '/admin/config/workflow/workflows/manage/{workflow}/add_transition',
    'pattern_outline' => '/admin/config/workflow/workflows/manage/%/add_transition',
    'fit' => '125',
    'route' => 'C:31:"Symfony\Component\Routing\Route":1603:{a:9:{s:4:"path";s:65:"/admin/config/workflow/workflows/manage/{workflow}/add_transition";s:4:"host";s:0:"";s:8:"defaults";a:2:{s:12:"_entity_form";s:23:"workflow.add-transition";s:6:"_title";s:14:"Add transition";}s:12:"requirements";a:1:{s:14:"_entity_access";s:13:"workflow.edit";}s:7:"options";a:6:{s:14:"compiler_class";s:34:"\Drupal\Core\Routing\RouteCompiler";s:10:"parameters";a:1:{s:8:"workflow";a:2:{s:4:"type";s:15:"entity:workflow";s:9:"converter";s:63:"drupal.proxy_original_service.paramconverter.configentity_admin";}}s:12:"_admin_route";b:1;s:14:"_route_filters";a:2:{i:0;s:13:"method_filter";i:1;s:27:"content_type_header_matcher";}s:16:"_route_enhancers";a:2:{i:0;s:31:"route_enhancer.param_conversion";i:1;s:21:"route_enhancer.entity";}s:14:"_access_checks";a:1:{i:0;s:19:"access_check.entity";}}s:7:"schemes";a:0:{}s:7:"methods";a:2:{i:0;s:3:"GET";i:1;s:4:"POST";}s:9:"condition";s:0:"";s:8:"compiled";C:33:"Drupal\Core\Routing\CompiledRoute":634:{a:11:{s:4:"vars";a:1:{i:0;s:8:"workflow";}s:11:"path_prefix";s:0:"";s:10:"path_regex";s:80:"#^/admin/config/workflow/workflows/manage/(?P<workflow>[^/]++)/add_transition$#s";s:11:"path_tokens";a:3:{i:0;a:2:{i:0;s:4:"text";i:1;s:15:"/add_transition";}i:1;a:4:{i:0;s:8:"variable";i:1;s:1:"/";i:2;s:6:"[^/]++";i:3;s:8:"workflow";}i:2;a:2:{i:0;s:4:"text";i:1;s:39:"/admin/config/workflow/workflows/manage";}}s:9:"path_vars";a:1:{i:0;s:8:"workflow";}s:10:"host_regex";N;s:11:"host_tokens";a:0:{}s:9:"host_vars";a:0:{}s:3:"fit";i:125;s:14:"patternOutline";s:56:"/admin/config/workflow/workflows/manage/%/add_transition";s:8:"numParts";i:7;}}}}',
    'number_parts' => '7',
  ))
  ->values(array(
    'name' => 'entity.workflow.collection',
    'path' => '/admin/config/workflow/workflows',
    'pattern_outline' => '/admin/config/workflow/workflows',
    'fit' => '15',
    'route' => 'C:31:"Symfony\Component\Routing\Route":1207:{a:9:{s:4:"path";s:32:"/admin/config/workflow/workflows";s:4:"host";s:0:"";s:8:"defaults";a:4:{s:12:"_entity_list";s:8:"workflow";s:6:"_title";s:9:"Workflows";s:16:"_title_arguments";a:0:{}s:14:"_title_context";s:0:"";}s:12:"requirements";a:1:{s:11:"_permission";s:20:"administer workflows";}s:7:"options";a:5:{s:14:"compiler_class";s:34:"\Drupal\Core\Routing\RouteCompiler";s:12:"_admin_route";b:1;s:14:"_route_filters";a:2:{i:0;s:13:"method_filter";i:1;s:27:"content_type_header_matcher";}s:16:"_route_enhancers";a:2:{i:0;s:31:"route_enhancer.param_conversion";i:1;s:21:"route_enhancer.entity";}s:14:"_access_checks";a:1:{i:0;s:23:"access_check.permission";}}s:7:"schemes";a:0:{}s:7:"methods";a:2:{i:0;s:3:"GET";i:1;s:4:"POST";}s:9:"condition";s:0:"";s:8:"compiled";C:33:"Drupal\Core\Routing\CompiledRoute":392:{a:11:{s:4:"vars";a:0:{}s:11:"path_prefix";s:0:"";s:10:"path_regex";s:37:"#^/admin/config/workflow/workflows$#s";s:11:"path_tokens";a:1:{i:0;a:2:{i:0;s:4:"text";i:1;s:32:"/admin/config/workflow/workflows";}}s:9:"path_vars";a:0:{}s:10:"host_regex";N;s:11:"host_tokens";a:0:{}s:9:"host_vars";a:0:{}s:3:"fit";i:15;s:14:"patternOutline";s:32:"/admin/config/workflow/workflows";s:8:"numParts";i:4;}}}}',
    'number_parts' => '4',
  ))
  ->values(array(
    'name' => 'entity.workflow.delete_form',
    'path' => '/admin/config/workflow/workflows/manage/{workflow}/delete',
    'pattern_outline' => '/admin/config/workflow/workflows/manage/%/delete',
    'fit' => '125',
    'route' => 'C:31:"Symfony\Component\Routing\Route":1620:{a:9:{s:4:"path";s:57:"/admin/config/workflow/workflows/manage/{workflow}/delete";s:4:"host";s:0:"";s:8:"defaults";a:2:{s:12:"_entity_form";s:15:"workflow.delete";s:15:"_title_callback";s:60:"\Drupal\Core\Entity\Controller\EntityController::deleteTitle";}s:12:"requirements";a:1:{s:14:"_entity_access";s:15:"workflow.delete";}s:7:"options";a:6:{s:14:"compiler_class";s:34:"\Drupal\Core\Routing\RouteCompiler";s:10:"parameters";a:1:{s:8:"workflow";a:2:{s:4:"type";s:15:"entity:workflow";s:9:"converter";s:63:"drupal.proxy_original_service.paramconverter.configentity_admin";}}s:12:"_admin_route";b:1;s:14:"_route_filters";a:2:{i:0;s:13:"method_filter";i:1;s:27:"content_type_header_matcher";}s:16:"_route_enhancers";a:2:{i:0;s:31:"route_enhancer.param_conversion";i:1;s:21:"route_enhancer.entity";}s:14:"_access_checks";a:1:{i:0;s:19:"access_check.entity";}}s:7:"schemes";a:0:{}s:7:"methods";a:2:{i:0;s:3:"GET";i:1;s:4:"POST";}s:9:"condition";s:0:"";s:8:"compiled";C:33:"Drupal\Core\Routing\CompiledRoute":609:{a:11:{s:4:"vars";a:1:{i:0;s:8:"workflow";}s:11:"path_prefix";s:0:"";s:10:"path_regex";s:72:"#^/admin/config/workflow/workflows/manage/(?P<workflow>[^/]++)/delete$#s";s:11:"path_tokens";a:3:{i:0;a:2:{i:0;s:4:"text";i:1;s:7:"/delete";}i:1;a:4:{i:0;s:8:"variable";i:1;s:1:"/";i:2;s:6:"[^/]++";i:3;s:8:"workflow";}i:2;a:2:{i:0;s:4:"text";i:1;s:39:"/admin/config/workflow/workflows/manage";}}s:9:"path_vars";a:1:{i:0;s:8:"workflow";}s:10:"host_regex";N;s:11:"host_tokens";a:0:{}s:9:"host_vars";a:0:{}s:3:"fit";i:125;s:14:"patternOutline";s:48:"/admin/config/workflow/workflows/manage/%/delete";s:8:"numParts";i:7;}}}}',
    'number_parts' => '7',
  ))
  ->values(array(
    'name' => 'entity.workflow.delete_state_form',
    'path' => '/admin/config/workflow/workflows/manage/{workflow}/state/{workflow_state}/delete',
    'pattern_outline' => '/admin/config/workflow/workflows/manage/%/state/%/delete',
    'fit' => '501',
    'route' => 'C:31:"Symfony\Component\Routing\Route":1845:{a:9:{s:4:"path";s:80:"/admin/config/workflow/workflows/manage/{workflow}/state/{workflow_state}/delete";s:4:"host";s:0:"";s:8:"defaults";a:2:{s:5:"_form";s:46:"\Drupal\workflows\Form\WorkflowStateDeleteForm";s:6:"_title";s:12:"Delete state";}s:12:"requirements";a:1:{s:29:"_workflow_state_delete_access";s:4:"true";}s:7:"options";a:6:{s:14:"compiler_class";s:34:"\Drupal\Core\Routing\RouteCompiler";s:10:"parameters";a:1:{s:8:"workflow";a:2:{s:4:"type";s:15:"entity:workflow";s:9:"converter";s:63:"drupal.proxy_original_service.paramconverter.configentity_admin";}}s:12:"_admin_route";b:1;s:14:"_route_filters";a:2:{i:0;s:13:"method_filter";i:1;s:27:"content_type_header_matcher";}s:16:"_route_enhancers";a:2:{i:0;s:31:"route_enhancer.param_conversion";i:1;s:19:"route_enhancer.form";}s:14:"_access_checks";a:1:{i:0;s:35:"workflows.access_check.delete_state";}}s:7:"schemes";a:0:{}s:7:"methods";a:2:{i:0;s:3:"GET";i:1;s:4:"POST";}s:9:"condition";s:0:"";s:8:"compiled";C:33:"Drupal\Core\Routing\CompiledRoute":829:{a:11:{s:4:"vars";a:2:{i:0;s:8:"workflow";i:1;s:14:"workflow_state";}s:11:"path_prefix";s:0:"";s:10:"path_regex";s:105:"#^/admin/config/workflow/workflows/manage/(?P<workflow>[^/]++)/state/(?P<workflow_state>[^/]++)/delete$#s";s:11:"path_tokens";a:5:{i:0;a:2:{i:0;s:4:"text";i:1;s:7:"/delete";}i:1;a:4:{i:0;s:8:"variable";i:1;s:1:"/";i:2;s:6:"[^/]++";i:3;s:14:"workflow_state";}i:2;a:2:{i:0;s:4:"text";i:1;s:6:"/state";}i:3;a:4:{i:0;s:8:"variable";i:1;s:1:"/";i:2;s:6:"[^/]++";i:3;s:8:"workflow";}i:4;a:2:{i:0;s:4:"text";i:1;s:39:"/admin/config/workflow/workflows/manage";}}s:9:"path_vars";a:2:{i:0;s:8:"workflow";i:1;s:14:"workflow_state";}s:10:"host_regex";N;s:11:"host_tokens";a:0:{}s:9:"host_vars";a:0:{}s:3:"fit";i:501;s:14:"patternOutline";s:56:"/admin/config/workflow/workflows/manage/%/state/%/delete";s:8:"numParts";i:9;}}}}',
    'number_parts' => '9',
  ))
  ->values(array(
    'name' => 'entity.workflow.delete_transition_form',
    'path' => '/admin/config/workflow/workflows/manage/{workflow}/transition/{workflow_transition}/delete',
    'pattern_outline' => '/admin/config/workflow/workflows/manage/%/transition/%/delete',
    'fit' => '501',
    'route' => 'C:31:"Symfony\Component\Routing\Route":1880:{a:9:{s:4:"path";s:90:"/admin/config/workflow/workflows/manage/{workflow}/transition/{workflow_transition}/delete";s:4:"host";s:0:"";s:8:"defaults";a:2:{s:5:"_form";s:51:"\Drupal\workflows\Form\WorkflowTransitionDeleteForm";s:6:"_title";s:17:"Delete transition";}s:12:"requirements";a:1:{s:14:"_entity_access";s:13:"workflow.edit";}s:7:"options";a:6:{s:14:"compiler_class";s:34:"\Drupal\Core\Routing\RouteCompiler";s:10:"parameters";a:1:{s:8:"workflow";a:2:{s:4:"type";s:15:"entity:workflow";s:9:"converter";s:63:"drupal.proxy_original_service.paramconverter.configentity_admin";}}s:12:"_admin_route";b:1;s:14:"_route_filters";a:2:{i:0;s:13:"method_filter";i:1;s:27:"content_type_header_matcher";}s:16:"_route_enhancers";a:2:{i:0;s:31:"route_enhancer.param_conversion";i:1;s:19:"route_enhancer.form";}s:14:"_access_checks";a:1:{i:0;s:19:"access_check.entity";}}s:7:"schemes";a:0:{}s:7:"methods";a:2:{i:0;s:3:"GET";i:1;s:4:"POST";}s:9:"condition";s:0:"";s:8:"compiled";C:33:"Drupal\Core\Routing\CompiledRoute":865:{a:11:{s:4:"vars";a:2:{i:0;s:8:"workflow";i:1;s:19:"workflow_transition";}s:11:"path_prefix";s:0:"";s:10:"path_regex";s:115:"#^/admin/config/workflow/workflows/manage/(?P<workflow>[^/]++)/transition/(?P<workflow_transition>[^/]++)/delete$#s";s:11:"path_tokens";a:5:{i:0;a:2:{i:0;s:4:"text";i:1;s:7:"/delete";}i:1;a:4:{i:0;s:8:"variable";i:1;s:1:"/";i:2;s:6:"[^/]++";i:3;s:19:"workflow_transition";}i:2;a:2:{i:0;s:4:"text";i:1;s:11:"/transition";}i:3;a:4:{i:0;s:8:"variable";i:1;s:1:"/";i:2;s:6:"[^/]++";i:3;s:8:"workflow";}i:4;a:2:{i:0;s:4:"text";i:1;s:39:"/admin/config/workflow/workflows/manage";}}s:9:"path_vars";a:2:{i:0;s:8:"workflow";i:1;s:19:"workflow_transition";}s:10:"host_regex";N;s:11:"host_tokens";a:0:{}s:9:"host_vars";a:0:{}s:3:"fit";i:501;s:14:"patternOutline";s:61:"/admin/config/workflow/workflows/manage/%/transition/%/delete";s:8:"numParts";i:9;}}}}',
    'number_parts' => '9',
  ))
  ->values(array(
    'name' => 'entity.workflow.edit_form',
    'path' => '/admin/config/workflow/workflows/manage/{workflow}',
    'pattern_outline' => '/admin/config/workflow/workflows/manage/%',
    'fit' => '62',
    'route' => 'C:31:"Symfony\Component\Routing\Route":1551:{a:9:{s:4:"path";s:50:"/admin/config/workflow/workflows/manage/{workflow}";s:4:"host";s:0:"";s:8:"defaults";a:2:{s:12:"_entity_form";s:13:"workflow.edit";s:15:"_title_callback";s:58:"\Drupal\Core\Entity\Controller\EntityController::editTitle";}s:12:"requirements";a:1:{s:14:"_entity_access";s:15:"workflow.update";}s:7:"options";a:6:{s:14:"compiler_class";s:34:"\Drupal\Core\Routing\RouteCompiler";s:10:"parameters";a:1:{s:8:"workflow";a:2:{s:4:"type";s:15:"entity:workflow";s:9:"converter";s:63:"drupal.proxy_original_service.paramconverter.configentity_admin";}}s:12:"_admin_route";b:1;s:14:"_route_filters";a:2:{i:0;s:13:"method_filter";i:1;s:27:"content_type_header_matcher";}s:16:"_route_enhancers";a:2:{i:0;s:31:"route_enhancer.param_conversion";i:1;s:21:"route_enhancer.entity";}s:14:"_access_checks";a:1:{i:0;s:19:"access_check.entity";}}s:7:"schemes";a:0:{}s:7:"methods";a:2:{i:0;s:3:"GET";i:1;s:4:"POST";}s:9:"condition";s:0:"";s:8:"compiled";C:33:"Drupal\Core\Routing\CompiledRoute":551:{a:11:{s:4:"vars";a:1:{i:0;s:8:"workflow";}s:11:"path_prefix";s:0:"";s:10:"path_regex";s:65:"#^/admin/config/workflow/workflows/manage/(?P<workflow>[^/]++)$#s";s:11:"path_tokens";a:2:{i:0;a:4:{i:0;s:8:"variable";i:1;s:1:"/";i:2;s:6:"[^/]++";i:3;s:8:"workflow";}i:1;a:2:{i:0;s:4:"text";i:1;s:39:"/admin/config/workflow/workflows/manage";}}s:9:"path_vars";a:1:{i:0;s:8:"workflow";}s:10:"host_regex";N;s:11:"host_tokens";a:0:{}s:9:"host_vars";a:0:{}s:3:"fit";i:62;s:14:"patternOutline";s:41:"/admin/config/workflow/workflows/manage/%";s:8:"numParts";i:6;}}}}',
    'number_parts' => '6',
  ))
  ->values(array(
    'name' => 'entity.workflow.edit_state_form',
    'path' => '/admin/config/workflow/workflows/manage/{workflow}/state/{workflow_state}',
    'pattern_outline' => '/admin/config/workflow/workflows/manage/%/state/%',
    'fit' => '250',
    'route' => 'C:31:"Symfony\Component\Routing\Route":1740:{a:9:{s:4:"path";s:73:"/admin/config/workflow/workflows/manage/{workflow}/state/{workflow_state}";s:4:"host";s:0:"";s:8:"defaults";a:2:{s:12:"_entity_form";s:19:"workflow.edit-state";s:6:"_title";s:10:"Edit state";}s:12:"requirements";a:1:{s:14:"_entity_access";s:13:"workflow.edit";}s:7:"options";a:6:{s:14:"compiler_class";s:34:"\Drupal\Core\Routing\RouteCompiler";s:10:"parameters";a:1:{s:8:"workflow";a:2:{s:4:"type";s:15:"entity:workflow";s:9:"converter";s:63:"drupal.proxy_original_service.paramconverter.configentity_admin";}}s:12:"_admin_route";b:1;s:14:"_route_filters";a:2:{i:0;s:13:"method_filter";i:1;s:27:"content_type_header_matcher";}s:16:"_route_enhancers";a:2:{i:0;s:31:"route_enhancer.param_conversion";i:1;s:21:"route_enhancer.entity";}s:14:"_access_checks";a:1:{i:0;s:19:"access_check.entity";}}s:7:"schemes";a:0:{}s:7:"methods";a:2:{i:0;s:3:"GET";i:1;s:4:"POST";}s:9:"condition";s:0:"";s:8:"compiled";C:33:"Drupal\Core\Routing\CompiledRoute":771:{a:11:{s:4:"vars";a:2:{i:0;s:8:"workflow";i:1;s:14:"workflow_state";}s:11:"path_prefix";s:0:"";s:10:"path_regex";s:98:"#^/admin/config/workflow/workflows/manage/(?P<workflow>[^/]++)/state/(?P<workflow_state>[^/]++)$#s";s:11:"path_tokens";a:4:{i:0;a:4:{i:0;s:8:"variable";i:1;s:1:"/";i:2;s:6:"[^/]++";i:3;s:14:"workflow_state";}i:1;a:2:{i:0;s:4:"text";i:1;s:6:"/state";}i:2;a:4:{i:0;s:8:"variable";i:1;s:1:"/";i:2;s:6:"[^/]++";i:3;s:8:"workflow";}i:3;a:2:{i:0;s:4:"text";i:1;s:39:"/admin/config/workflow/workflows/manage";}}s:9:"path_vars";a:2:{i:0;s:8:"workflow";i:1;s:14:"workflow_state";}s:10:"host_regex";N;s:11:"host_tokens";a:0:{}s:9:"host_vars";a:0:{}s:3:"fit";i:250;s:14:"patternOutline";s:49:"/admin/config/workflow/workflows/manage/%/state/%";s:8:"numParts";i:8;}}}}',
    'number_parts' => '8',
  ))
  ->values(array(
    'name' => 'entity.workflow.edit_transition_form',
    'path' => '/admin/config/workflow/workflows/manage/{workflow}/transition/{workflow_transition}',
    'pattern_outline' => '/admin/config/workflow/workflows/manage/%/transition/%',
    'fit' => '250',
    'route' => 'C:31:"Symfony\Component\Routing\Route":1797:{a:9:{s:4:"path";s:83:"/admin/config/workflow/workflows/manage/{workflow}/transition/{workflow_transition}";s:4:"host";s:0:"";s:8:"defaults";a:2:{s:12:"_entity_form";s:24:"workflow.edit-transition";s:6:"_title";s:15:"Edit transition";}s:12:"requirements";a:1:{s:14:"_entity_access";s:13:"workflow.edit";}s:7:"options";a:6:{s:14:"compiler_class";s:34:"\Drupal\Core\Routing\RouteCompiler";s:10:"parameters";a:1:{s:8:"workflow";a:2:{s:4:"type";s:15:"entity:workflow";s:9:"converter";s:63:"drupal.proxy_original_service.paramconverter.configentity_admin";}}s:12:"_admin_route";b:1;s:14:"_route_filters";a:2:{i:0;s:13:"method_filter";i:1;s:27:"content_type_header_matcher";}s:16:"_route_enhancers";a:2:{i:0;s:31:"route_enhancer.param_conversion";i:1;s:21:"route_enhancer.entity";}s:14:"_access_checks";a:1:{i:0;s:19:"access_check.entity";}}s:7:"schemes";a:0:{}s:7:"methods";a:2:{i:0;s:3:"GET";i:1;s:4:"POST";}s:9:"condition";s:0:"";s:8:"compiled";C:33:"Drupal\Core\Routing\CompiledRoute":808:{a:11:{s:4:"vars";a:2:{i:0;s:8:"workflow";i:1;s:19:"workflow_transition";}s:11:"path_prefix";s:0:"";s:10:"path_regex";s:108:"#^/admin/config/workflow/workflows/manage/(?P<workflow>[^/]++)/transition/(?P<workflow_transition>[^/]++)$#s";s:11:"path_tokens";a:4:{i:0;a:4:{i:0;s:8:"variable";i:1;s:1:"/";i:2;s:6:"[^/]++";i:3;s:19:"workflow_transition";}i:1;a:2:{i:0;s:4:"text";i:1;s:11:"/transition";}i:2;a:4:{i:0;s:8:"variable";i:1;s:1:"/";i:2;s:6:"[^/]++";i:3;s:8:"workflow";}i:3;a:2:{i:0;s:4:"text";i:1;s:39:"/admin/config/workflow/workflows/manage";}}s:9:"path_vars";a:2:{i:0;s:8:"workflow";i:1;s:19:"workflow_transition";}s:10:"host_regex";N;s:11:"host_tokens";a:0:{}s:9:"host_vars";a:0:{}s:3:"fit";i:250;s:14:"patternOutline";s:54:"/admin/config/workflow/workflows/manage/%/transition/%";s:8:"numParts";i:8;}}}}',
    'number_parts' => '8',
  ))
  ->execute();
