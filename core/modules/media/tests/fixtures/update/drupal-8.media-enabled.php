<?php

/**
 * @file
 * Contains database additions to drupal-8.bare.standard.php.gz for testing the
 * upgrade paths of media module.
 */

use Drupal\Core\Database\Database;
use Symfony\Component\Yaml\Yaml;

$connection = Database::getConnection();

// Set the schema version.
$connection->merge('key_value')
  ->fields([
    'value' => 'i:8000;',
    'name' => 'media',
    'collection' => 'system.schema',
  ])
  ->condition('collection', 'system.schema')
  ->condition('name', 'media')
  ->execute();

// Update core.extension.
$extensions = $connection->select('config')
  ->fields('config', ['data'])
  ->condition('collection', '')
  ->condition('name', 'core.extension')
  ->execute()
  ->fetchField();
$extensions = unserialize($extensions);
$extensions['module']['media'] = 8000;
$connection->update('config')
  ->fields([
    'data' => serialize($extensions),
    'collection' => '',
    'name' => 'core.extension',
  ])
  ->condition('collection', '')
  ->condition('name', 'core.extension')
  ->execute();

// Find media configs.
$config_directory = new RecursiveDirectoryIterator(__DIR__ . '/../../../../media/config/install');

// Find standard profile configs.
$profile_directory = new RecursiveDirectoryIterator(__DIR__ . '/../../../../../profiles/standard/config/optional');
$iterator = new RecursiveIteratorIterator($profile_directory);
$regex_iterator = new RegexIterator($iterator, '/.*media\..*/i');

$append_iterator = new \AppendIterator();
$append_iterator->append($config_directory);
$append_iterator->append($regex_iterator);

// Install media configs.
foreach ($append_iterator as $file_info) {
  if ($file_info->getExtension() == 'yml') {
    $config = Yaml::parse(file_get_contents($file_info->getRealPath()));
    $connection->merge('config')
      ->condition('name', $file_info->getBasename('.yml'))
      ->condition('collection', '')
      ->fields([
        'data' => serialize($config),
        'name' => $file_info->getBasename('.yml'),
        'collection' => '',
      ])
      ->execute();
  }
}

// Create the tables.
$connection->schema()->createTable('media', [
  'fields' => [
    'mid' => [
      'type' => 'serial',
      'not null' => TRUE,
      'size' => 'normal',
      'unsigned' => TRUE,
    ],
    'vid' => [
      'type' => 'int',
      'not null' => FALSE,
      'size' => 'normal',
      'unsigned' => TRUE,
    ],
    'bundle' => [
      'type' => 'varchar_ascii',
      'not null' => TRUE,
      'length' => '32',
    ],
    'uuid' => [
      'type' => 'varchar_ascii',
      'not null' => TRUE,
      'length' => '128',
    ],
    'langcode' => [
      'type' => 'varchar_ascii',
      'not null' => TRUE,
      'length' => '12',
    ],
  ],
  'primary key' => [
    'mid',
  ],
  'unique keys' => [
    'media_field__uuid__value' => [
      'uuid',
    ],
    'media__vid' => [
      'vid',
    ],
  ],
  'indexes' => [
    'media_field__bundle__target_id' => [
      'bundle',
    ],
  ],
  'mysql_character_set' => 'utf8mb4',
]);

$connection->schema()->createTable('media__field_media_file', [
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
    'field_media_file_target_id' => [
      'type' => 'int',
      'not null' => TRUE,
      'size' => 'normal',
      'unsigned' => TRUE,
    ],
    'field_media_file_display' => [
      'type' => 'int',
      'not null' => FALSE,
      'size' => 'tiny',
      'default' => '1',
      'unsigned' => TRUE,
    ],
    'field_media_file_description' => [
      'type' => 'text',
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
    'field_media_file_target_id' => [
      'field_media_file_target_id',
    ],
  ],
  'mysql_character_set' => 'utf8mb4',
]);

$connection->schema()->createTable('media__field_media_image', [
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
    'field_media_image_target_id' => [
      'type' => 'int',
      'not null' => TRUE,
      'size' => 'normal',
      'unsigned' => TRUE,
    ],
    'field_media_image_alt' => [
      'type' => 'varchar',
      'not null' => FALSE,
      'length' => '512',
    ],
    'field_media_image_title' => [
      'type' => 'varchar',
      'not null' => FALSE,
      'length' => '1024',
    ],
    'field_media_image_width' => [
      'type' => 'int',
      'not null' => FALSE,
      'size' => 'normal',
      'unsigned' => TRUE,
    ],
    'field_media_image_height' => [
      'type' => 'int',
      'not null' => FALSE,
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
    'field_media_image_target_id' => [
      'field_media_image_target_id',
    ],
  ],
  'mysql_character_set' => 'utf8mb4',
]);

$connection->schema()->createTable('media_field_data', [
  'fields' => [
    'mid' => [
      'type' => 'int',
      'not null' => TRUE,
      'size' => 'normal',
      'unsigned' => TRUE,
    ],
    'vid' => [
      'type' => 'int',
      'not null' => TRUE,
      'size' => 'normal',
      'unsigned' => TRUE,
    ],
    'bundle' => [
      'type' => 'varchar_ascii',
      'not null' => TRUE,
      'length' => '32',
    ],
    'langcode' => [
      'type' => 'varchar_ascii',
      'not null' => TRUE,
      'length' => '12',
    ],
    'status' => [
      'type' => 'int',
      'not null' => TRUE,
      'size' => 'tiny',
    ],
    'name' => [
      'type' => 'varchar',
      'not null' => FALSE,
      'length' => '255',
    ],
    'thumbnail__target_id' => [
      'type' => 'int',
      'not null' => FALSE,
      'size' => 'normal',
      'unsigned' => TRUE,
    ],
    'thumbnail__alt' => [
      'type' => 'varchar',
      'not null' => FALSE,
      'length' => '512',
    ],
    'thumbnail__title' => [
      'type' => 'varchar',
      'not null' => FALSE,
      'length' => '1024',
    ],
    'thumbnail__width' => [
      'type' => 'int',
      'not null' => FALSE,
      'size' => 'normal',
      'unsigned' => TRUE,
    ],
    'thumbnail__height' => [
      'type' => 'int',
      'not null' => FALSE,
      'size' => 'normal',
      'unsigned' => TRUE,
    ],
    'uid' => [
      'type' => 'int',
      'not null' => FALSE,
      'size' => 'normal',
      'unsigned' => TRUE,
    ],
    'created' => [
      'type' => 'int',
      'not null' => FALSE,
      'size' => 'normal',
    ],
    'changed' => [
      'type' => 'int',
      'not null' => FALSE,
      'size' => 'normal',
    ],
    'default_langcode' => [
      'type' => 'int',
      'not null' => TRUE,
      'size' => 'tiny',
    ],
    'revision_translation_affected' => [
      'type' => 'int',
      'not null' => FALSE,
      'size' => 'tiny',
    ],
  ],
  'primary key' => [
    'mid',
    'langcode',
  ],
  'indexes' => [
    'media__id__default_langcode__langcode' => [
      'mid',
      'default_langcode',
      'langcode',
    ],
    'media__vid' => [
      'vid',
    ],
    'media_field__bundle__target_id' => [
      'bundle',
    ],
    'media_field__thumbnail__target_id' => [
      'thumbnail__target_id',
    ],
    'media_field__uid__target_id' => [
      'uid',
    ],
    'media__status_bundle' => [
      'status',
      'bundle',
      'mid',
    ],
  ],
  'mysql_character_set' => 'utf8mb4',
]);

$connection->schema()->createTable('media_field_revision', [
  'fields' => [
    'mid' => [
      'type' => 'int',
      'not null' => TRUE,
      'size' => 'normal',
      'unsigned' => TRUE,
    ],
    'vid' => [
      'type' => 'int',
      'not null' => TRUE,
      'size' => 'normal',
      'unsigned' => TRUE,
    ],
    'langcode' => [
      'type' => 'varchar_ascii',
      'not null' => TRUE,
      'length' => '12',
    ],
    'status' => [
      'type' => 'int',
      'not null' => TRUE,
      'size' => 'tiny',
    ],
    'name' => [
      'type' => 'varchar',
      'not null' => FALSE,
      'length' => '255',
    ],
    'thumbnail__target_id' => [
      'type' => 'int',
      'not null' => FALSE,
      'size' => 'normal',
      'unsigned' => TRUE,
    ],
    'thumbnail__alt' => [
      'type' => 'varchar',
      'not null' => FALSE,
      'length' => '512',
    ],
    'thumbnail__title' => [
      'type' => 'varchar',
      'not null' => FALSE,
      'length' => '1024',
    ],
    'thumbnail__width' => [
      'type' => 'int',
      'not null' => FALSE,
      'size' => 'normal',
      'unsigned' => TRUE,
    ],
    'thumbnail__height' => [
      'type' => 'int',
      'not null' => FALSE,
      'size' => 'normal',
      'unsigned' => TRUE,
    ],
    'uid' => [
      'type' => 'int',
      'not null' => FALSE,
      'size' => 'normal',
      'unsigned' => TRUE,
    ],
    'created' => [
      'type' => 'int',
      'not null' => FALSE,
      'size' => 'normal',
    ],
    'changed' => [
      'type' => 'int',
      'not null' => FALSE,
      'size' => 'normal',
    ],
    'default_langcode' => [
      'type' => 'int',
      'not null' => TRUE,
      'size' => 'tiny',
    ],
    'revision_translation_affected' => [
      'type' => 'int',
      'not null' => FALSE,
      'size' => 'tiny',
    ],
  ],
  'primary key' => [
    'vid',
    'langcode',
  ],
  'indexes' => [
    'media__id__default_langcode__langcode' => [
      'mid',
      'default_langcode',
      'langcode',
    ],
    'media_field__thumbnail__target_id' => [
      'thumbnail__target_id',
    ],
    'media_field__uid__target_id' => [
      'uid',
    ],
  ],
  'mysql_character_set' => 'utf8mb4',
]);

$connection->schema()->createTable('media_revision', [
  'fields' => [
    'mid' => [
      'type' => 'int',
      'not null' => TRUE,
      'size' => 'normal',
      'unsigned' => TRUE,
    ],
    'vid' => [
      'type' => 'serial',
      'not null' => TRUE,
      'size' => 'normal',
      'unsigned' => TRUE,
    ],
    'langcode' => [
      'type' => 'varchar_ascii',
      'not null' => TRUE,
      'length' => '12',
    ],
    'revision_user' => [
      'type' => 'int',
      'not null' => FALSE,
      'size' => 'normal',
      'unsigned' => TRUE,
    ],
    'revision_created' => [
      'type' => 'int',
      'not null' => FALSE,
      'size' => 'normal',
    ],
    'revision_log_message' => [
      'type' => 'text',
      'not null' => FALSE,
      'size' => 'big',
    ],
  ],
  'primary key' => [
    'vid',
  ],
  'indexes' => [
    'media__mid' => [
      'mid',
    ],
    'media_field__revision_user__target_id' => [
      'revision_user',
    ],
  ],
  'mysql_character_set' => 'utf8mb4',
]);

$connection->schema()->createTable('media_revision__field_media_file', [
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
    'field_media_file_target_id' => [
      'type' => 'int',
      'not null' => TRUE,
      'size' => 'normal',
      'unsigned' => TRUE,
    ],
    'field_media_file_display' => [
      'type' => 'int',
      'not null' => FALSE,
      'size' => 'tiny',
      'default' => '1',
      'unsigned' => TRUE,
    ],
    'field_media_file_description' => [
      'type' => 'text',
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
    'field_media_file_target_id' => [
      'field_media_file_target_id',
    ],
  ],
  'mysql_character_set' => 'utf8mb4',
]);

$connection->schema()->createTable('media_revision__field_media_image', [
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
    'field_media_image_target_id' => [
      'type' => 'int',
      'not null' => TRUE,
      'size' => 'normal',
      'unsigned' => TRUE,
    ],
    'field_media_image_alt' => [
      'type' => 'varchar',
      'not null' => FALSE,
      'length' => '512',
    ],
    'field_media_image_title' => [
      'type' => 'varchar',
      'not null' => FALSE,
      'length' => '1024',
    ],
    'field_media_image_width' => [
      'type' => 'int',
      'not null' => FALSE,
      'size' => 'normal',
      'unsigned' => TRUE,
    ],
    'field_media_image_height' => [
      'type' => 'int',
      'not null' => FALSE,
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
    'field_media_image_target_id' => [
      'field_media_image_target_id',
    ],
  ],
  'mysql_character_set' => 'utf8mb4',
]);


// Store the entity type definitions and field storage definitions.
$connection->merge('key_value')
  ->condition('collection', 'entity.definitions.installed')
  ->condition('name', 'media.entity_type')
  ->fields([
    'value' => 'O:36:"Drupal\Core\Entity\ContentEntityType":38:{s:25:" * revision_metadata_keys";a:3:{s:13:"revision_user";s:13:"revision_user";s:16:"revision_created";s:16:"revision_created";s:20:"revision_log_message";s:20:"revision_log_message";}s:15:" * static_cache";b:1;s:15:" * render_cache";b:1;s:19:" * persistent_cache";b:1;s:14:" * entity_keys";a:9:{s:2:"id";s:3:"mid";s:8:"revision";s:3:"vid";s:6:"bundle";s:6:"bundle";s:5:"label";s:4:"name";s:8:"langcode";s:8:"langcode";s:4:"uuid";s:4:"uuid";s:9:"published";s:6:"status";s:16:"default_langcode";s:16:"default_langcode";s:29:"revision_translation_affected";s:29:"revision_translation_affected";}s:5:" * id";s:5:"media";s:16:" * originalClass";s:25:"Drupal\media\Entity\Media";s:11:" * handlers";a:8:{s:7:"storage";s:46:"Drupal\Core\Entity\Sql\SqlContentEntityStorage";s:12:"view_builder";s:36:"Drupal\Core\Entity\EntityViewBuilder";s:12:"list_builder";s:36:"Drupal\Core\Entity\EntityListBuilder";s:6:"access";s:38:"Drupal\media\MediaAccessControlHandler";s:4:"form";a:4:{s:7:"default";s:22:"Drupal\media\MediaForm";s:3:"add";s:22:"Drupal\media\MediaForm";s:4:"edit";s:22:"Drupal\media\MediaForm";s:6:"delete";s:42:"Drupal\Core\Entity\ContentEntityDeleteForm";}s:11:"translation";s:52:"Drupal\content_translation\ContentTranslationHandler";s:10:"views_data";s:27:"Drupal\media\MediaViewsData";s:14:"route_provider";a:1:{s:4:"html";s:49:"Drupal\Core\Entity\Routing\AdminHtmlRouteProvider";}}s:19:" * admin_permission";s:16:"administer media";s:25:" * permission_granularity";s:11:"entity_type";s:8:" * links";a:6:{s:8:"add-page";s:10:"/media/add";s:8:"add-form";s:23:"/media/add/{media_type}";s:9:"canonical";s:14:"/media/{media}";s:11:"delete-form";s:21:"/media/{media}/delete";s:9:"edit-form";s:19:"/media/{media}/edit";s:8:"revision";s:46:"/media/{media}/revisions/{media_revision}/view";}s:17:" * label_callback";N;s:21:" * bundle_entity_type";s:10:"media_type";s:12:" * bundle_of";N;s:15:" * bundle_label";O:48:"Drupal\Core\StringTranslation\TranslatableMarkup":3:{s:9:" * string";s:10:"Media type";s:12:" * arguments";a:0:{}s:10:" * options";a:0:{}}s:13:" * base_table";s:5:"media";s:22:" * revision_data_table";s:20:"media_field_revision";s:17:" * revision_table";s:14:"media_revision";s:13:" * data_table";s:16:"media_field_data";s:15:" * translatable";b:1;s:19:" * show_revision_ui";b:1;s:8:" * label";O:48:"Drupal\Core\StringTranslation\TranslatableMarkup":3:{s:9:" * string";s:5:"Media";s:12:" * arguments";a:0:{}s:10:" * options";a:0:{}}s:19:" * label_collection";s:0:"";s:17:" * label_singular";O:48:"Drupal\Core\StringTranslation\TranslatableMarkup":3:{s:9:" * string";s:10:"media item";s:12:" * arguments";a:0:{}s:10:" * options";a:0:{}}s:15:" * label_plural";O:48:"Drupal\Core\StringTranslation\TranslatableMarkup":3:{s:9:" * string";s:11:"media items";s:12:" * arguments";a:0:{}s:10:" * options";a:0:{}}s:14:" * label_count";a:3:{s:8:"singular";s:17:"@count media item";s:6:"plural";s:18:"@count media items";s:7:"context";N;}s:15:" * uri_callback";N;s:8:" * group";s:7:"content";s:14:" * group_label";O:48:"Drupal\Core\StringTranslation\TranslatableMarkup":3:{s:9:" * string";s:7:"Content";s:12:" * arguments";a:0:{}s:10:" * options";a:1:{s:7:"context";s:17:"Entity type group";}}s:22:" * field_ui_base_route";s:27:"entity.media_type.edit_form";s:26:" * common_reference_target";b:1;s:22:" * list_cache_contexts";a:0:{}s:18:" * list_cache_tags";a:1:{i:0;s:10:"media_list";}s:14:" * constraints";a:1:{s:13:"EntityChanged";N;}s:13:" * additional";a:0:{}s:8:" * class";s:25:"Drupal\media\Entity\Media";s:11:" * provider";s:5:"media";s:20:" * stringTranslation";N;}',
    'name' => 'media.entity_type',
    'collection' => 'entity.definitions.installed',
  ])
  ->execute();

$connection->merge('key_value')
  ->condition('collection', 'entity.definitions.installed')
  ->condition('name', 'media.field_storage_definitions')
  ->fields([
    'value' => 'a:18:{s:3:"mid";O:37:"Drupal\Core\Field\BaseFieldDefinition":5:{s:7:" * type";s:7:"integer";s:9:" * schema";a:4:{s:7:"columns";a:1:{s:5:"value";a:3:{s:4:"type";s:3:"int";s:8:"unsigned";b:1;s:4:"size";s:6:"normal";}}s:11:"unique keys";a:0:{}s:7:"indexes";a:0:{}s:12:"foreign keys";a:0:{}}s:10:" * indexes";a:0:{}s:17:" * itemDefinition";O:51:"Drupal\Core\Field\TypedData\FieldItemDataDefinition":2:{s:18:" * fieldDefinition";r:2;s:13:" * definition";a:2:{s:4:"type";s:18:"field_item:integer";s:8:"settings";a:6:{s:8:"unsigned";b:1;s:4:"size";s:6:"normal";s:3:"min";s:0:"";s:3:"max";s:0:"";s:6:"prefix";s:0:"";s:6:"suffix";s:0:"";}}}s:13:" * definition";a:6:{s:5:"label";O:48:"Drupal\Core\StringTranslation\TranslatableMarkup":3:{s:9:" * string";s:2:"ID";s:12:" * arguments";a:0:{}s:10:" * options";a:0:{}}s:9:"read-only";b:1;s:8:"provider";s:5:"media";s:10:"field_name";s:3:"mid";s:11:"entity_type";s:5:"media";s:6:"bundle";N;}}s:4:"uuid";O:37:"Drupal\Core\Field\BaseFieldDefinition":5:{s:7:" * type";s:4:"uuid";s:9:" * schema";a:4:{s:7:"columns";a:1:{s:5:"value";a:3:{s:4:"type";s:13:"varchar_ascii";s:6:"length";i:128;s:6:"binary";b:0;}}s:11:"unique keys";a:1:{s:5:"value";a:1:{i:0;s:5:"value";}}s:7:"indexes";a:0:{}s:12:"foreign keys";a:0:{}}s:10:" * indexes";a:0:{}s:17:" * itemDefinition";O:51:"Drupal\Core\Field\TypedData\FieldItemDataDefinition":2:{s:18:" * fieldDefinition";r:35;s:13:" * definition";a:2:{s:4:"type";s:15:"field_item:uuid";s:8:"settings";a:3:{s:10:"max_length";i:128;s:8:"is_ascii";b:1;s:14:"case_sensitive";b:0;}}}s:13:" * definition";a:6:{s:5:"label";O:48:"Drupal\Core\StringTranslation\TranslatableMarkup":3:{s:9:" * string";s:4:"UUID";s:12:" * arguments";a:0:{}s:10:" * options";a:0:{}}s:9:"read-only";b:1;s:8:"provider";s:5:"media";s:10:"field_name";s:4:"uuid";s:11:"entity_type";s:5:"media";s:6:"bundle";N;}}s:3:"vid";O:37:"Drupal\Core\Field\BaseFieldDefinition":5:{s:7:" * type";s:7:"integer";s:9:" * schema";a:4:{s:7:"columns";a:1:{s:5:"value";a:3:{s:4:"type";s:3:"int";s:8:"unsigned";b:1;s:4:"size";s:6:"normal";}}s:11:"unique keys";a:0:{}s:7:"indexes";a:0:{}s:12:"foreign keys";a:0:{}}s:10:" * indexes";a:0:{}s:17:" * itemDefinition";O:51:"Drupal\Core\Field\TypedData\FieldItemDataDefinition":2:{s:18:" * fieldDefinition";r:67;s:13:" * definition";a:2:{s:4:"type";s:18:"field_item:integer";s:8:"settings";a:6:{s:8:"unsigned";b:1;s:4:"size";s:6:"normal";s:3:"min";s:0:"";s:3:"max";s:0:"";s:6:"prefix";s:0:"";s:6:"suffix";s:0:"";}}}s:13:" * definition";a:6:{s:5:"label";O:48:"Drupal\Core\StringTranslation\TranslatableMarkup":3:{s:9:" * string";s:11:"Revision ID";s:12:" * arguments";a:0:{}s:10:" * options";a:0:{}}s:9:"read-only";b:1;s:8:"provider";s:5:"media";s:10:"field_name";s:3:"vid";s:11:"entity_type";s:5:"media";s:6:"bundle";N;}}s:8:"langcode";O:37:"Drupal\Core\Field\BaseFieldDefinition":5:{s:7:" * type";s:8:"language";s:9:" * schema";a:4:{s:7:"columns";a:1:{s:5:"value";a:2:{s:4:"type";s:13:"varchar_ascii";s:6:"length";i:12;}}s:11:"unique keys";a:0:{}s:7:"indexes";a:0:{}s:12:"foreign keys";a:0:{}}s:10:" * indexes";a:0:{}s:17:" * itemDefinition";O:51:"Drupal\Core\Field\TypedData\FieldItemDataDefinition":2:{s:18:" * fieldDefinition";r:100;s:13:" * definition";a:2:{s:4:"type";s:19:"field_item:language";s:8:"settings";a:0:{}}}s:13:" * definition";a:8:{s:5:"label";O:48:"Drupal\Core\StringTranslation\TranslatableMarkup":3:{s:9:" * string";s:8:"Language";s:12:" * arguments";a:0:{}s:10:" * options";a:0:{}}s:7:"display";a:2:{s:4:"view";a:1:{s:7:"options";a:1:{s:6:"region";s:6:"hidden";}}s:4:"form";a:1:{s:7:"options";a:2:{s:4:"type";s:15:"language_select";s:6:"weight";i:2;}}}s:12:"revisionable";b:1;s:12:"translatable";b:1;s:8:"provider";s:5:"media";s:10:"field_name";s:8:"langcode";s:11:"entity_type";s:5:"media";s:6:"bundle";N;}}s:6:"bundle";O:37:"Drupal\Core\Field\BaseFieldDefinition":5:{s:7:" * type";s:16:"entity_reference";s:9:" * schema";a:4:{s:7:"columns";a:1:{s:9:"target_id";a:3:{s:11:"description";s:28:"The ID of the target entity.";s:4:"type";s:13:"varchar_ascii";s:6:"length";i:32;}}s:7:"indexes";a:1:{s:9:"target_id";a:1:{i:0;s:9:"target_id";}}s:11:"unique keys";a:0:{}s:12:"foreign keys";a:0:{}}s:10:" * indexes";a:0:{}s:17:" * itemDefinition";O:51:"Drupal\Core\Field\TypedData\FieldItemDataDefinition":2:{s:18:" * fieldDefinition";r:135;s:13:" * definition";a:2:{s:4:"type";s:27:"field_item:entity_reference";s:8:"settings";a:3:{s:11:"target_type";s:10:"media_type";s:7:"handler";s:7:"default";s:16:"handler_settings";a:0:{}}}}s:13:" * definition";a:7:{s:5:"label";s:10:"Media type";s:8:"required";b:1;s:9:"read-only";b:1;s:8:"provider";s:5:"media";s:10:"field_name";s:6:"bundle";s:11:"entity_type";s:5:"media";s:6:"bundle";N;}}s:16:"revision_created";O:37:"Drupal\Core\Field\BaseFieldDefinition":5:{s:7:" * type";s:7:"created";s:9:" * schema";a:4:{s:7:"columns";a:1:{s:5:"value";a:1:{s:4:"type";s:3:"int";}}s:11:"unique keys";a:0:{}s:7:"indexes";a:0:{}s:12:"foreign keys";a:0:{}}s:10:" * indexes";a:0:{}s:17:" * itemDefinition";O:51:"Drupal\Core\Field\TypedData\FieldItemDataDefinition":2:{s:18:" * fieldDefinition";r:165;s:13:" * definition";a:2:{s:4:"type";s:18:"field_item:created";s:8:"settings";a:0:{}}}s:13:" * definition";a:7:{s:5:"label";O:48:"Drupal\Core\StringTranslation\TranslatableMarkup":3:{s:9:" * string";s:20:"Revision create time";s:12:" * arguments";a:0:{}s:10:" * options";a:0:{}}s:11:"description";O:48:"Drupal\Core\StringTranslation\TranslatableMarkup":3:{s:9:" * string";s:47:"The time that the current revision was created.";s:12:" * arguments";a:0:{}s:10:" * options";a:0:{}}s:12:"revisionable";b:1;s:8:"provider";s:5:"media";s:10:"field_name";s:16:"revision_created";s:11:"entity_type";s:5:"media";s:6:"bundle";N;}}s:13:"revision_user";O:37:"Drupal\Core\Field\BaseFieldDefinition":5:{s:7:" * type";s:16:"entity_reference";s:9:" * schema";a:4:{s:7:"columns";a:1:{s:9:"target_id";a:3:{s:11:"description";s:28:"The ID of the target entity.";s:4:"type";s:3:"int";s:8:"unsigned";b:1;}}s:7:"indexes";a:1:{s:9:"target_id";a:1:{i:0;s:9:"target_id";}}s:11:"unique keys";a:0:{}s:12:"foreign keys";a:0:{}}s:10:" * indexes";a:0:{}s:17:" * itemDefinition";O:51:"Drupal\Core\Field\TypedData\FieldItemDataDefinition":2:{s:18:" * fieldDefinition";r:194;s:13:" * definition";a:2:{s:4:"type";s:27:"field_item:entity_reference";s:8:"settings";a:3:{s:11:"target_type";s:4:"user";s:7:"handler";s:7:"default";s:16:"handler_settings";a:0:{}}}}s:13:" * definition";a:7:{s:5:"label";O:48:"Drupal\Core\StringTranslation\TranslatableMarkup":3:{s:9:" * string";s:13:"Revision user";s:12:" * arguments";a:0:{}s:10:" * options";a:0:{}}s:11:"description";O:48:"Drupal\Core\StringTranslation\TranslatableMarkup":3:{s:9:" * string";s:50:"The user ID of the author of the current revision.";s:12:" * arguments";a:0:{}s:10:" * options";a:0:{}}s:12:"revisionable";b:1;s:8:"provider";s:5:"media";s:10:"field_name";s:13:"revision_user";s:11:"entity_type";s:5:"media";s:6:"bundle";N;}}s:20:"revision_log_message";O:37:"Drupal\Core\Field\BaseFieldDefinition":5:{s:7:" * type";s:11:"string_long";s:9:" * schema";a:4:{s:7:"columns";a:1:{s:5:"value";a:2:{s:4:"type";s:4:"text";s:4:"size";s:3:"big";}}s:11:"unique keys";a:0:{}s:7:"indexes";a:0:{}s:12:"foreign keys";a:0:{}}s:10:" * indexes";a:0:{}s:17:" * itemDefinition";O:51:"Drupal\Core\Field\TypedData\FieldItemDataDefinition":2:{s:18:" * fieldDefinition";r:230;s:13:" * definition";a:2:{s:4:"type";s:22:"field_item:string_long";s:8:"settings";a:1:{s:14:"case_sensitive";b:0;}}}s:13:" * definition";a:9:{s:5:"label";O:48:"Drupal\Core\StringTranslation\TranslatableMarkup":3:{s:9:" * string";s:20:"Revision log message";s:12:" * arguments";a:0:{}s:10:" * options";a:0:{}}s:11:"description";O:48:"Drupal\Core\StringTranslation\TranslatableMarkup":3:{s:9:" * string";s:43:"Briefly describe the changes you have made.";s:12:" * arguments";a:0:{}s:10:" * options";a:0:{}}s:12:"revisionable";b:1;s:13:"default_value";a:1:{i:0;a:1:{s:5:"value";s:0:"";}}s:7:"display";a:1:{s:4:"form";a:1:{s:7:"options";a:3:{s:4:"type";s:15:"string_textarea";s:6:"weight";i:25;s:8:"settings";a:1:{s:4:"rows";i:4;}}}}s:8:"provider";s:5:"media";s:10:"field_name";s:20:"revision_log_message";s:11:"entity_type";s:5:"media";s:6:"bundle";N;}}s:6:"status";O:37:"Drupal\Core\Field\BaseFieldDefinition":5:{s:7:" * type";s:7:"boolean";s:9:" * schema";a:4:{s:7:"columns";a:1:{s:5:"value";a:2:{s:4:"type";s:3:"int";s:4:"size";s:4:"tiny";}}s:11:"unique keys";a:0:{}s:7:"indexes";a:0:{}s:12:"foreign keys";a:0:{}}s:10:" * indexes";a:0:{}s:17:" * itemDefinition";O:51:"Drupal\Core\Field\TypedData\FieldItemDataDefinition":2:{s:18:" * fieldDefinition";r:271;s:13:" * definition";a:2:{s:4:"type";s:18:"field_item:boolean";s:8:"settings";a:2:{s:8:"on_label";O:48:"Drupal\Core\StringTranslation\TranslatableMarkup":3:{s:9:" * string";s:2:"On";s:12:" * arguments";a:0:{}s:10:" * options";a:0:{}}s:9:"off_label";O:48:"Drupal\Core\StringTranslation\TranslatableMarkup":3:{s:9:" * string";s:3:"Off";s:12:" * arguments";a:0:{}s:10:" * options";a:0:{}}}}}s:13:" * definition";a:9:{s:5:"label";O:48:"Drupal\Core\StringTranslation\TranslatableMarkup":3:{s:9:" * string";s:9:"Published";s:12:" * arguments";a:0:{}s:10:" * options";a:0:{}}s:12:"revisionable";b:1;s:12:"translatable";b:1;s:13:"default_value";a:1:{i:0;a:1:{s:5:"value";b:1;}}s:7:"display";a:1:{s:4:"form";a:2:{s:7:"options";a:3:{s:4:"type";s:16:"boolean_checkbox";s:8:"settings";a:1:{s:13:"display_label";b:1;}s:6:"weight";i:100;}s:12:"configurable";b:1;}}s:8:"provider";s:5:"media";s:10:"field_name";s:6:"status";s:11:"entity_type";s:5:"media";s:6:"bundle";N;}}s:4:"name";O:37:"Drupal\Core\Field\BaseFieldDefinition":5:{s:7:" * type";s:6:"string";s:9:" * schema";a:4:{s:7:"columns";a:1:{s:5:"value";a:3:{s:4:"type";s:7:"varchar";s:6:"length";i:255;s:6:"binary";b:0;}}s:11:"unique keys";a:0:{}s:7:"indexes";a:0:{}s:12:"foreign keys";a:0:{}}s:10:" * indexes";a:0:{}s:17:" * itemDefinition";O:51:"Drupal\Core\Field\TypedData\FieldItemDataDefinition":2:{s:18:" * fieldDefinition";r:317;s:13:" * definition";a:2:{s:4:"type";s:17:"field_item:string";s:8:"settings";a:3:{s:10:"max_length";i:255;s:8:"is_ascii";b:0;s:14:"case_sensitive";b:0;}}}s:13:" * definition";a:10:{s:5:"label";O:48:"Drupal\Core\StringTranslation\TranslatableMarkup":3:{s:9:" * string";s:4:"Name";s:12:" * arguments";a:0:{}s:10:" * options";a:0:{}}s:8:"required";b:1;s:12:"translatable";b:1;s:12:"revisionable";b:1;s:13:"default_value";a:1:{i:0;a:1:{s:5:"value";s:0:"";}}s:7:"display";a:2:{s:4:"form";a:2:{s:7:"options";a:2:{s:4:"type";s:16:"string_textfield";s:6:"weight";i:-5;}s:12:"configurable";b:1;}s:4:"view";a:1:{s:7:"options";a:3:{s:5:"label";s:6:"hidden";s:4:"type";s:6:"string";s:6:"weight";i:-5;}}}s:8:"provider";s:5:"media";s:10:"field_name";s:4:"name";s:11:"entity_type";s:5:"media";s:6:"bundle";N;}}s:9:"thumbnail";O:37:"Drupal\Core\Field\BaseFieldDefinition":5:{s:7:" * type";s:5:"image";s:9:" * schema";a:4:{s:7:"columns";a:5:{s:9:"target_id";a:3:{s:11:"description";s:26:"The ID of the file entity.";s:4:"type";s:3:"int";s:8:"unsigned";b:1;}s:3:"alt";a:3:{s:11:"description";s:56:"Alternative image text, for the image\'s \'alt\' attribute.";s:4:"type";s:7:"varchar";s:6:"length";i:512;}s:5:"title";a:3:{s:11:"description";s:52:"Image title text, for the image\'s \'title\' attribute.";s:4:"type";s:7:"varchar";s:6:"length";i:1024;}s:5:"width";a:3:{s:11:"description";s:33:"The width of the image in pixels.";s:4:"type";s:3:"int";s:8:"unsigned";b:1;}s:6:"height";a:3:{s:11:"description";s:34:"The height of the image in pixels.";s:4:"type";s:3:"int";s:8:"unsigned";b:1;}}s:7:"indexes";a:1:{s:9:"target_id";a:1:{i:0;s:9:"target_id";}}s:12:"foreign keys";a:1:{s:9:"target_id";a:2:{s:5:"table";s:12:"file_managed";s:7:"columns";a:1:{s:9:"target_id";s:3:"fid";}}}s:11:"unique keys";a:0:{}}s:10:" * indexes";a:0:{}s:17:" * itemDefinition";O:51:"Drupal\Core\Field\TypedData\FieldItemDataDefinition":2:{s:18:" * fieldDefinition";r:363;s:13:" * definition";a:2:{s:4:"type";s:16:"field_item:image";s:8:"settings";a:16:{s:13:"default_image";a:5:{s:4:"uuid";N;s:3:"alt";s:0:"";s:5:"title";s:0:"";s:5:"width";N;s:6:"height";N;}s:11:"target_type";s:4:"file";s:13:"display_field";b:0;s:15:"display_default";b:0;s:10:"uri_scheme";s:6:"public";s:15:"file_extensions";s:16:"png gif jpg jpeg";s:9:"alt_field";i:1;s:18:"alt_field_required";i:1;s:11:"title_field";i:0;s:20:"title_field_required";i:0;s:14:"max_resolution";s:0:"";s:14:"min_resolution";s:0:"";s:14:"file_directory";s:31:"[date:custom:Y]-[date:custom:m]";s:12:"max_filesize";s:0:"";s:7:"handler";s:7:"default";s:16:"handler_settings";a:0:{}}}}s:13:" * definition";a:10:{s:5:"label";O:48:"Drupal\Core\StringTranslation\TranslatableMarkup":3:{s:9:" * string";s:9:"Thumbnail";s:12:" * arguments";a:0:{}s:10:" * options";a:0:{}}s:11:"description";O:48:"Drupal\Core\StringTranslation\TranslatableMarkup":3:{s:9:" * string";s:32:"The thumbnail of the media item.";s:12:" * arguments";a:0:{}s:10:" * options";a:0:{}}s:12:"revisionable";b:1;s:12:"translatable";b:1;s:7:"display";a:1:{s:4:"view";a:2:{s:7:"options";a:4:{s:4:"type";s:5:"image";s:6:"weight";i:5;s:5:"label";s:6:"hidden";s:8:"settings";a:1:{s:11:"image_style";s:9:"thumbnail";}}s:12:"configurable";b:1;}}s:9:"read-only";b:1;s:8:"provider";s:5:"media";s:10:"field_name";s:9:"thumbnail";s:11:"entity_type";s:5:"media";s:6:"bundle";N;}}s:3:"uid";O:37:"Drupal\Core\Field\BaseFieldDefinition":5:{s:7:" * type";s:16:"entity_reference";s:9:" * schema";a:4:{s:7:"columns";a:1:{s:9:"target_id";a:3:{s:11:"description";s:28:"The ID of the target entity.";s:4:"type";s:3:"int";s:8:"unsigned";b:1;}}s:7:"indexes";a:1:{s:9:"target_id";a:1:{i:0;s:9:"target_id";}}s:11:"unique keys";a:0:{}s:12:"foreign keys";a:0:{}}s:10:" * indexes";a:0:{}s:17:" * itemDefinition";O:51:"Drupal\Core\Field\TypedData\FieldItemDataDefinition":2:{s:18:" * fieldDefinition";r:448;s:13:" * definition";a:2:{s:4:"type";s:27:"field_item:entity_reference";s:8:"settings";a:3:{s:11:"target_type";s:4:"user";s:7:"handler";s:7:"default";s:16:"handler_settings";a:0:{}}}}s:13:" * definition";a:10:{s:5:"label";O:48:"Drupal\Core\StringTranslation\TranslatableMarkup":3:{s:9:" * string";s:11:"Authored by";s:12:" * arguments";a:0:{}s:10:" * options";a:0:{}}s:11:"description";O:48:"Drupal\Core\StringTranslation\TranslatableMarkup":3:{s:9:" * string";s:26:"The user ID of the author.";s:12:" * arguments";a:0:{}s:10:" * options";a:0:{}}s:12:"revisionable";b:1;s:22:"default_value_callback";s:43:"Drupal\media\Entity\Media::getCurrentUserId";s:12:"translatable";b:1;s:7:"display";a:2:{s:4:"form";a:2:{s:7:"options";a:3:{s:4:"type";s:29:"entity_reference_autocomplete";s:6:"weight";i:5;s:8:"settings";a:4:{s:14:"match_operator";s:8:"CONTAINS";s:4:"size";s:2:"60";s:17:"autocomplete_type";s:4:"tags";s:11:"placeholder";s:0:"";}}s:12:"configurable";b:1;}s:4:"view";a:2:{s:7:"options";a:3:{s:5:"label";s:6:"hidden";s:4:"type";s:6:"author";s:6:"weight";i:0;}s:12:"configurable";b:1;}}s:8:"provider";s:5:"media";s:10:"field_name";s:3:"uid";s:11:"entity_type";s:5:"media";s:6:"bundle";N;}}s:7:"created";O:37:"Drupal\Core\Field\BaseFieldDefinition":5:{s:7:" * type";s:7:"created";s:9:" * schema";a:4:{s:7:"columns";a:1:{s:5:"value";a:1:{s:4:"type";s:3:"int";}}s:11:"unique keys";a:0:{}s:7:"indexes";a:0:{}s:12:"foreign keys";a:0:{}}s:10:" * indexes";a:0:{}s:17:" * itemDefinition";O:51:"Drupal\Core\Field\TypedData\FieldItemDataDefinition":2:{s:18:" * fieldDefinition";r:503;s:13:" * definition";a:2:{s:4:"type";s:18:"field_item:created";s:8:"settings";a:0:{}}}s:13:" * definition";a:10:{s:5:"label";O:48:"Drupal\Core\StringTranslation\TranslatableMarkup":3:{s:9:" * string";s:11:"Authored on";s:12:" * arguments";a:0:{}s:10:" * options";a:0:{}}s:11:"description";O:48:"Drupal\Core\StringTranslation\TranslatableMarkup":3:{s:9:" * string";s:36:"The time the media item was created.";s:12:" * arguments";a:0:{}s:10:" * options";a:0:{}}s:12:"translatable";b:1;s:12:"revisionable";b:1;s:22:"default_value_callback";s:41:"Drupal\media\Entity\Media::getRequestTime";s:7:"display";a:2:{s:4:"form";a:2:{s:7:"options";a:2:{s:4:"type";s:18:"datetime_timestamp";s:6:"weight";i:10;}s:12:"configurable";b:1;}s:4:"view";a:2:{s:7:"options";a:3:{s:5:"label";s:6:"hidden";s:4:"type";s:9:"timestamp";s:6:"weight";i:0;}s:12:"configurable";b:1;}}s:8:"provider";s:5:"media";s:10:"field_name";s:7:"created";s:11:"entity_type";s:5:"media";s:6:"bundle";N;}}s:7:"changed";O:37:"Drupal\Core\Field\BaseFieldDefinition":5:{s:7:" * type";s:7:"changed";s:9:" * schema";a:4:{s:7:"columns";a:1:{s:5:"value";a:1:{s:4:"type";s:3:"int";}}s:11:"unique keys";a:0:{}s:7:"indexes";a:0:{}s:12:"foreign keys";a:0:{}}s:10:" * indexes";a:0:{}s:17:" * itemDefinition";O:51:"Drupal\Core\Field\TypedData\FieldItemDataDefinition":2:{s:18:" * fieldDefinition";r:546;s:13:" * definition";a:2:{s:4:"type";s:18:"field_item:changed";s:8:"settings";a:0:{}}}s:13:" * definition";a:8:{s:5:"label";O:48:"Drupal\Core\StringTranslation\TranslatableMarkup":3:{s:9:" * string";s:7:"Changed";s:12:" * arguments";a:0:{}s:10:" * options";a:0:{}}s:11:"description";O:48:"Drupal\Core\StringTranslation\TranslatableMarkup":3:{s:9:" * string";s:40:"The time the media item was last edited.";s:12:" * arguments";a:0:{}s:10:" * options";a:0:{}}s:12:"translatable";b:1;s:12:"revisionable";b:1;s:8:"provider";s:5:"media";s:10:"field_name";s:7:"changed";s:11:"entity_type";s:5:"media";s:6:"bundle";N;}}s:16:"default_langcode";O:37:"Drupal\Core\Field\BaseFieldDefinition":5:{s:7:" * type";s:7:"boolean";s:9:" * schema";a:4:{s:7:"columns";a:1:{s:5:"value";a:2:{s:4:"type";s:3:"int";s:4:"size";s:4:"tiny";}}s:11:"unique keys";a:0:{}s:7:"indexes";a:0:{}s:12:"foreign keys";a:0:{}}s:10:" * indexes";a:0:{}s:17:" * itemDefinition";O:51:"Drupal\Core\Field\TypedData\FieldItemDataDefinition":2:{s:18:" * fieldDefinition";r:576;s:13:" * definition";a:2:{s:4:"type";s:18:"field_item:boolean";s:8:"settings";a:2:{s:8:"on_label";O:48:"Drupal\Core\StringTranslation\TranslatableMarkup":3:{s:9:" * string";s:2:"On";s:12:" * arguments";a:0:{}s:10:" * options";a:0:{}}s:9:"off_label";O:48:"Drupal\Core\StringTranslation\TranslatableMarkup":3:{s:9:" * string";s:3:"Off";s:12:" * arguments";a:0:{}s:10:" * options";a:0:{}}}}}s:13:" * definition";a:9:{s:5:"label";O:48:"Drupal\Core\StringTranslation\TranslatableMarkup":3:{s:9:" * string";s:19:"Default translation";s:12:" * arguments";a:0:{}s:10:" * options";a:0:{}}s:11:"description";O:48:"Drupal\Core\StringTranslation\TranslatableMarkup":3:{s:9:" * string";s:58:"A flag indicating whether this is the default translation.";s:12:" * arguments";a:0:{}s:10:" * options";a:0:{}}s:12:"translatable";b:1;s:12:"revisionable";b:1;s:13:"default_value";a:1:{i:0;a:1:{s:5:"value";b:1;}}s:8:"provider";s:5:"media";s:10:"field_name";s:16:"default_langcode";s:11:"entity_type";s:5:"media";s:6:"bundle";N;}}s:29:"revision_translation_affected";O:37:"Drupal\Core\Field\BaseFieldDefinition":5:{s:7:" * type";s:7:"boolean";s:9:" * schema";a:4:{s:7:"columns";a:1:{s:5:"value";a:2:{s:4:"type";s:3:"int";s:4:"size";s:4:"tiny";}}s:11:"unique keys";a:0:{}s:7:"indexes";a:0:{}s:12:"foreign keys";a:0:{}}s:10:" * indexes";a:0:{}s:17:" * itemDefinition";O:51:"Drupal\Core\Field\TypedData\FieldItemDataDefinition":2:{s:18:" * fieldDefinition";r:618;s:13:" * definition";a:2:{s:4:"type";s:18:"field_item:boolean";s:8:"settings";a:2:{s:8:"on_label";O:48:"Drupal\Core\StringTranslation\TranslatableMarkup":3:{s:9:" * string";s:2:"On";s:12:" * arguments";a:0:{}s:10:" * options";a:0:{}}s:9:"off_label";O:48:"Drupal\Core\StringTranslation\TranslatableMarkup":3:{s:9:" * string";s:3:"Off";s:12:" * arguments";a:0:{}s:10:" * options";a:0:{}}}}}s:13:" * definition";a:9:{s:5:"label";O:48:"Drupal\Core\StringTranslation\TranslatableMarkup":3:{s:9:" * string";s:29:"Revision translation affected";s:12:" * arguments";a:0:{}s:10:" * options";a:0:{}}s:11:"description";O:48:"Drupal\Core\StringTranslation\TranslatableMarkup":3:{s:9:" * string";s:72:"Indicates if the last edit of a translation belongs to current revision.";s:12:" * arguments";a:0:{}s:10:" * options";a:0:{}}s:9:"read-only";b:1;s:12:"revisionable";b:1;s:12:"translatable";b:1;s:8:"provider";s:5:"media";s:10:"field_name";s:29:"revision_translation_affected";s:11:"entity_type";s:5:"media";s:6:"bundle";N;}}s:17:"field_media_image";O:38:"Drupal\field\Entity\FieldStorageConfig":28:{s:5:" * id";s:23:"media.field_media_image";s:13:" * field_name";s:17:"field_media_image";s:14:" * entity_type";s:5:"media";s:7:" * type";s:5:"image";s:9:" * module";s:5:"image";s:11:" * settings";a:5:{s:13:"default_image";a:5:{s:4:"uuid";N;s:3:"alt";s:0:"";s:5:"title";s:0:"";s:5:"width";N;s:6:"height";N;}s:11:"target_type";s:4:"file";s:13:"display_field";b:0;s:15:"display_default";b:0;s:10:"uri_scheme";s:6:"public";}s:14:" * cardinality";i:1;s:15:" * translatable";b:1;s:9:" * locked";b:0;s:25:" * persist_with_no_fields";b:0;s:14:"custom_storage";b:0;s:10:" * indexes";a:0:{}s:10:" * deleted";b:0;s:13:" * originalId";s:23:"media.field_media_image";s:9:" * status";b:1;s:7:" * uuid";s:36:"75d4d2e3-85ec-43f2-8fdb-5fee698fb950";s:11:" * langcode";s:2:"en";s:23:" * third_party_settings";a:0:{}s:8:" * _core";a:1:{s:19:"default_config_hash";s:43:"7ZBrcl87ZXaw42v952wwcw_9cQgTBq5_5tgyUkE-VV0";}s:14:" * trustedData";b:1;s:15:" * entityTypeId";s:20:"field_storage_config";s:15:" * enforceIsNew";b:1;s:12:" * typedData";N;s:16:" * cacheContexts";a:0:{}s:12:" * cacheTags";a:0:{}s:14:" * cacheMaxAge";i:-1;s:14:" * _serviceIds";a:0:{}s:15:" * dependencies";a:2:{s:8:"enforced";a:1:{s:6:"module";a:1:{i:0;s:5:"media";}}s:6:"module";a:3:{i:0;s:4:"file";i:1;s:5:"image";i:2;s:5:"media";}}}s:16:"field_media_file";O:38:"Drupal\field\Entity\FieldStorageConfig":28:{s:5:" * id";s:22:"media.field_media_file";s:13:" * field_name";s:16:"field_media_file";s:14:" * entity_type";s:5:"media";s:7:" * type";s:4:"file";s:9:" * module";s:4:"file";s:11:" * settings";a:4:{s:10:"uri_scheme";s:6:"public";s:11:"target_type";s:4:"file";s:13:"display_field";b:0;s:15:"display_default";b:0;}s:14:" * cardinality";i:1;s:15:" * translatable";b:1;s:9:" * locked";b:0;s:25:" * persist_with_no_fields";b:0;s:14:"custom_storage";b:0;s:10:" * indexes";a:0:{}s:10:" * deleted";b:0;s:13:" * originalId";s:22:"media.field_media_file";s:9:" * status";b:1;s:7:" * uuid";s:36:"c66c117d-73ea-4a3e-a68d-58f3e368927e";s:11:" * langcode";s:2:"en";s:23:" * third_party_settings";a:0:{}s:8:" * _core";a:1:{s:19:"default_config_hash";s:43:"4GNilUMnj0opT050eZIkWhkfuzu69ClyEr-cHxofjQw";}s:14:" * trustedData";b:1;s:15:" * entityTypeId";s:20:"field_storage_config";s:15:" * enforceIsNew";b:1;s:12:" * typedData";N;s:16:" * cacheContexts";a:0:{}s:12:" * cacheTags";a:0:{}s:14:" * cacheMaxAge";i:-1;s:14:" * _serviceIds";a:0:{}s:15:" * dependencies";a:2:{s:8:"enforced";a:1:{s:6:"module";a:1:{i:0;s:5:"media";}}s:6:"module";a:2:{i:0;s:4:"file";i:1;s:5:"media";}}}}',
    'name' => 'media.field_storage_definitions',
    'collection' => 'entity.definitions.installed',
  ])
  ->execute();

$connection->merge('key_value')
  ->condition('collection', 'entity.definitions.installed')
  ->condition('name', 'media_type.entity_type')
  ->fields([
    'value' => 'O:42:"Drupal\Core\Config\Entity\ConfigEntityType":41:{s:16:" * config_prefix";s:4:"type";s:15:" * static_cache";b:0;s:14:" * lookup_keys";a:1:{i:0;s:4:"uuid";}s:16:" * config_export";a:9:{i:0;s:2:"id";i:1;s:5:"label";i:2;s:11:"description";i:3;s:6:"source";i:4;s:25:"queue_thumbnail_downloads";i:5;s:12:"new_revision";i:6;s:20:"source_configuration";i:7;s:9:"field_map";i:8;s:6:"status";}s:21:" * mergedConfigExport";a:0:{}s:15:" * render_cache";b:1;s:19:" * persistent_cache";b:1;s:14:" * entity_keys";a:9:{s:2:"id";s:2:"id";s:5:"label";s:5:"label";s:6:"status";s:6:"status";s:8:"revision";s:0:"";s:6:"bundle";s:0:"";s:8:"langcode";s:8:"langcode";s:16:"default_langcode";s:16:"default_langcode";s:29:"revision_translation_affected";s:29:"revision_translation_affected";s:4:"uuid";s:4:"uuid";}s:5:" * id";s:10:"media_type";s:16:" * originalClass";s:29:"Drupal\media\Entity\MediaType";s:11:" * handlers";a:5:{s:4:"form";a:3:{s:3:"add";s:26:"Drupal\media\MediaTypeForm";s:4:"edit";s:26:"Drupal\media\MediaTypeForm";s:6:"delete";s:44:"Drupal\media\Form\MediaTypeDeleteConfirmForm";}s:12:"list_builder";s:33:"Drupal\media\MediaTypeListBuilder";s:14:"route_provider";a:1:{s:4:"html";s:51:"Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider";}s:6:"access";s:45:"Drupal\Core\Entity\EntityAccessControlHandler";s:7:"storage";s:45:"Drupal\Core\Config\Entity\ConfigEntityStorage";}s:19:" * admin_permission";s:22:"administer media types";s:25:" * permission_granularity";s:11:"entity_type";s:8:" * links";a:4:{s:8:"add-form";s:26:"/admin/structure/media/add";s:9:"edit-form";s:42:"/admin/structure/media/manage/{media_type}";s:11:"delete-form";s:49:"/admin/structure/media/manage/{media_type}/delete";s:10:"collection";s:22:"/admin/structure/media";}s:17:" * label_callback";N;s:21:" * bundle_entity_type";N;s:12:" * bundle_of";s:5:"media";s:15:" * bundle_label";N;s:13:" * base_table";N;s:22:" * revision_data_table";N;s:17:" * revision_table";N;s:13:" * data_table";N;s:15:" * translatable";b:0;s:19:" * show_revision_ui";b:0;s:8:" * label";O:48:"Drupal\Core\StringTranslation\TranslatableMarkup":3:{s:9:" * string";s:10:"Media type";s:12:" * arguments";a:0:{}s:10:" * options";a:0:{}}s:19:" * label_collection";O:48:"Drupal\Core\StringTranslation\TranslatableMarkup":3:{s:9:" * string";s:11:"Media types";s:12:" * arguments";a:0:{}s:10:" * options";a:0:{}}s:17:" * label_singular";O:48:"Drupal\Core\StringTranslation\TranslatableMarkup":3:{s:9:" * string";s:10:"media type";s:12:" * arguments";a:0:{}s:10:" * options";a:0:{}}s:15:" * label_plural";O:48:"Drupal\Core\StringTranslation\TranslatableMarkup":3:{s:9:" * string";s:11:"media types";s:12:" * arguments";a:0:{}s:10:" * options";a:0:{}}s:14:" * label_count";a:3:{s:8:"singular";s:17:"@count media type";s:6:"plural";s:18:"@count media types";s:7:"context";N;}s:15:" * uri_callback";N;s:8:" * group";s:13:"configuration";s:14:" * group_label";O:48:"Drupal\Core\StringTranslation\TranslatableMarkup":3:{s:9:" * string";s:13:"Configuration";s:12:" * arguments";a:0:{}s:10:" * options";a:1:{s:7:"context";s:17:"Entity type group";}}s:22:" * field_ui_base_route";N;s:26:" * common_reference_target";b:0;s:22:" * list_cache_contexts";a:0:{}s:18:" * list_cache_tags";a:1:{i:0;s:22:"config:media_type_list";}s:14:" * constraints";a:0:{}s:13:" * additional";a:0:{}s:8:" * class";s:29:"Drupal\media\Entity\MediaType";s:11:" * provider";s:5:"media";s:20:" * stringTranslation";N;}',
    'name' => 'media_type.entity_type',
    'collection' => 'entity.definitions.installed',
  ])
  ->execute();
