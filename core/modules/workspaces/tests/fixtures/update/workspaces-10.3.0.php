<?php
// phpcs:ignoreFile

use Drupal\Core\Database\Database;

/**
 * This update fixture creates the following test workspaces:
 *   - 'Summer campaign' (id: summer)
 *   - 'Winter campaign' (id: winter)
 *
 * And the following test nodes and taxonomy terms.
 *
 * In Live:
 *
 *   - 'Live published'
 *     - revisions: 1
 *     - tags: 'live'
 *
 *   - 'Live unpublished'
 *     - revisions: 1
 *     - tags: 'live', 'live-wip'
 *
 * In the 'Summer campaign' workspace:
 *
 *   - 'Summer published'
 *     - revisions: 4 (5 in total)
 *     - tags: 'summer'
 *
 *   - 'Summer unpublished'
 *     - revisions: 5
 *     - tags: 'summer', 'summer-wip'
 *
 * In the 'Winter campaign' workspace:
 *
 *   - 'Winter published'
 *     - revisions: 4 (5 in total)
 *     - tags: 'winter'
 *
 *   - 'Winter unpublished'
 *     - revisions: 5
 *     - tags: 'winter', 'winter-wip'
 */

$connection = Database::getConnection();

// Set the schema version.
$connection->merge('key_value')
  ->fields([
    'value' => 'i:10000;',
    'name' => 'workspaces',
    'collection' => 'system.schema',
  ])
  ->condition('collection', 'system.schema')
  ->condition('name', 'workspaces')
  ->execute();

// Update core.extension.
$extensions = $connection->select('config')
  ->fields('config', ['data'])
  ->condition('collection', '')
  ->condition('name', 'core.extension')
  ->execute()
  ->fetchField();
$extensions = unserialize($extensions);
$extensions['module']['workspaces'] = 0;
$connection->update('config')
  ->fields(['data' => serialize($extensions)])
  ->condition('collection', '')
  ->condition('name', 'core.extension')
  ->execute();

// Add all workspaces_removed_post_updates() as existing updates.
require_once __DIR__ . '/../../../../workspaces/workspaces.post_update.php';
$existing_updates = $connection->select('key_value')
  ->fields('key_value', ['value'])
  ->condition('collection', 'post_update')
  ->condition('name', 'existing_updates')
  ->execute()
  ->fetchField();
$existing_updates = unserialize($existing_updates);
$existing_updates = array_merge(
  $existing_updates,
  array_keys(workspaces_removed_post_updates())
);
$connection->update('key_value')
  ->fields(['value' => serialize($existing_updates)])
  ->condition('collection', 'post_update')
  ->condition('name', 'existing_updates')
  ->execute();

// Update the installed definitions for supported entity types.
$key_value_updates = [
  [
    'collection' => "entity.definitions.installed",
    'name' => "block_content.entity_type",
    'value' => "O:36:\"Drupal\\Core\\Entity\\ContentEntityType\":41:{s:5:\"\0*\0id\";s:13:\"block_content\";s:8:\"\0*\0class\";s:40:\"Drupal\\block_content\\Entity\\BlockContent\";s:11:\"\0*\0provider\";s:13:\"block_content\";s:15:\"\0*\0static_cache\";b:1;s:15:\"\0*\0render_cache\";b:0;s:19:\"\0*\0persistent_cache\";b:1;s:14:\"\0*\0entity_keys\";a:9:{s:2:\"id\";s:2:\"id\";s:8:\"revision\";s:11:\"revision_id\";s:6:\"bundle\";s:4:\"type\";s:5:\"label\";s:4:\"info\";s:8:\"langcode\";s:8:\"langcode\";s:4:\"uuid\";s:4:\"uuid\";s:9:\"published\";s:6:\"status\";s:16:\"default_langcode\";s:16:\"default_langcode\";s:29:\"revision_translation_affected\";s:29:\"revision_translation_affected\";}s:16:\"\0*\0originalClass\";s:40:\"Drupal\\block_content\\Entity\\BlockContent\";s:11:\"\0*\0handlers\";a:9:{s:7:\"storage\";s:46:\"Drupal\\Core\\Entity\\Sql\\SqlContentEntityStorage\";s:14:\"storage_schema\";s:46:\"Drupal\\block_content\\BlockContentStorageSchema\";s:6:\"access\";s:53:\"Drupal\\block_content\\BlockContentAccessControlHandler\";s:12:\"list_builder\";s:44:\"Drupal\\block_content\\BlockContentListBuilder\";s:12:\"view_builder\";s:44:\"Drupal\\block_content\\BlockContentViewBuilder\";s:10:\"views_data\";s:42:\"Drupal\\block_content\\BlockContentViewsData\";s:4:\"form\";a:6:{s:3:\"add\";s:37:\"Drupal\\block_content\\BlockContentForm\";s:4:\"edit\";s:37:\"Drupal\\block_content\\BlockContentForm\";s:6:\"delete\";s:48:\"Drupal\\block_content\\Form\\BlockContentDeleteForm\";s:7:\"default\";s:37:\"Drupal\\block_content\\BlockContentForm\";s:15:\"revision-delete\";s:42:\"Drupal\\Core\\Entity\\Form\\RevisionDeleteForm\";s:15:\"revision-revert\";s:42:\"Drupal\\Core\\Entity\\Form\\RevisionRevertForm\";}s:14:\"route_provider\";a:1:{s:8:\"revision\";s:52:\"Drupal\\Core\\Entity\\Routing\\RevisionHtmlRouteProvider\";}s:11:\"translation\";s:51:\"Drupal\\block_content\\BlockContentTranslationHandler\";}s:19:\"\0*\0admin_permission\";s:24:\"administer block content\";s:24:\"\0*\0collection_permission\";s:20:\"access block library\";s:25:\"\0*\0permission_granularity\";s:11:\"entity_type\";s:8:\"\0*\0links\";a:8:{s:9:\"canonical\";s:36:\"/admin/content/block/{block_content}\";s:11:\"delete-form\";s:43:\"/admin/content/block/{block_content}/delete\";s:9:\"edit-form\";s:36:\"/admin/content/block/{block_content}\";s:10:\"collection\";s:20:\"/admin/content/block\";s:6:\"create\";s:6:\"/block\";s:20:\"revision-delete-form\";s:77:\"/admin/content/block/{block_content}/revision/{block_content_revision}/delete\";s:20:\"revision-revert-form\";s:77:\"/admin/content/block/{block_content}/revision/{block_content_revision}/revert\";s:15:\"version-history\";s:46:\"/admin/content/block/{block_content}/revisions\";}s:21:\"\0*\0bundle_entity_type\";s:18:\"block_content_type\";s:12:\"\0*\0bundle_of\";N;s:15:\"\0*\0bundle_label\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:10:\"Block type\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}s:13:\"\0*\0base_table\";s:13:\"block_content\";s:22:\"\0*\0revision_data_table\";s:28:\"block_content_field_revision\";s:17:\"\0*\0revision_table\";s:22:\"block_content_revision\";s:13:\"\0*\0data_table\";s:24:\"block_content_field_data\";s:11:\"\0*\0internal\";b:0;s:15:\"\0*\0translatable\";b:1;s:19:\"\0*\0show_revision_ui\";b:1;s:8:\"\0*\0label\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:13:\"Content block\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}s:19:\"\0*\0label_collection\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:14:\"Content blocks\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}s:17:\"\0*\0label_singular\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:13:\"content block\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}s:15:\"\0*\0label_plural\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:14:\"content blocks\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}s:14:\"\0*\0label_count\";a:3:{s:8:\"singular\";s:20:\"@count content block\";s:6:\"plural\";s:21:\"@count content blocks\";s:7:\"context\";N;}s:15:\"\0*\0uri_callback\";N;s:8:\"\0*\0group\";s:7:\"content\";s:14:\"\0*\0group_label\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:7:\"Content\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:1:{s:7:\"context\";s:17:\"Entity type group\";}}s:22:\"\0*\0field_ui_base_route\";s:35:\"entity.block_content_type.edit_form\";s:26:\"\0*\0common_reference_target\";b:0;s:22:\"\0*\0list_cache_contexts\";a:0:{}s:18:\"\0*\0list_cache_tags\";a:1:{i:0;s:18:\"block_content_list\";}s:14:\"\0*\0constraints\";a:2:{s:26:\"EntityUntranslatableFields\";N;s:25:\"BlockContentEntityChanged\";N;}s:13:\"\0*\0additional\";a:0:{}s:14:\"\0*\0_serviceIds\";a:0:{}s:18:\"\0*\0_entityStorages\";a:0:{}s:20:\"\0*\0stringTranslation\";N;s:25:\"\0*\0revision_metadata_keys\";a:5:{s:13:\"revision_user\";s:13:\"revision_user\";s:16:\"revision_created\";s:16:\"revision_created\";s:20:\"revision_log_message\";s:12:\"revision_log\";s:16:\"revision_default\";s:16:\"revision_default\";s:9:\"workspace\";s:9:\"workspace\";}}",
  ],
  [
    'collection' => "entity.definitions.installed",
    'name' => "block_content.field_storage_definitions",
    'value' => "a:17:{s:2:\"id\";O:37:\"Drupal\\Core\\Field\\BaseFieldDefinition\":5:{s:13:\"\0*\0definition\";a:8:{s:5:\"label\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:16:\"Content block ID\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}s:9:\"read-only\";b:1;s:11:\"description\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:21:\"The content block ID.\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}s:8:\"provider\";s:13:\"block_content\";s:10:\"field_name\";s:2:\"id\";s:11:\"entity_type\";s:13:\"block_content\";s:6:\"bundle\";N;s:13:\"initial_value\";N;}s:17:\"\0*\0itemDefinition\";O:51:\"Drupal\\Core\\Field\\TypedData\\FieldItemDataDefinition\":2:{s:13:\"\0*\0definition\";a:2:{s:4:\"type\";s:18:\"field_item:integer\";s:8:\"settings\";a:6:{s:8:\"unsigned\";b:1;s:4:\"size\";s:6:\"normal\";s:3:\"min\";s:0:\"\";s:3:\"max\";s:0:\"\";s:6:\"prefix\";s:0:\"\";s:6:\"suffix\";s:0:\"\";}}s:18:\"\0*\0fieldDefinition\";r:2;}s:7:\"\0*\0type\";s:7:\"integer\";s:9:\"\0*\0schema\";a:4:{s:7:\"columns\";a:1:{s:5:\"value\";a:3:{s:4:\"type\";s:3:\"int\";s:8:\"unsigned\";b:1;s:4:\"size\";s:6:\"normal\";}}s:11:\"unique keys\";a:0:{}s:7:\"indexes\";a:0:{}s:12:\"foreign keys\";a:0:{}}s:10:\"\0*\0indexes\";a:0:{}}s:4:\"uuid\";O:37:\"Drupal\\Core\\Field\\BaseFieldDefinition\":5:{s:13:\"\0*\0definition\";a:8:{s:5:\"label\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:4:\"UUID\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}s:9:\"read-only\";b:1;s:11:\"description\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:23:\"The content block UUID.\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}s:8:\"provider\";s:13:\"block_content\";s:10:\"field_name\";s:4:\"uuid\";s:11:\"entity_type\";s:13:\"block_content\";s:6:\"bundle\";N;s:13:\"initial_value\";N;}s:17:\"\0*\0itemDefinition\";O:51:\"Drupal\\Core\\Field\\TypedData\\FieldItemDataDefinition\":2:{s:13:\"\0*\0definition\";a:2:{s:4:\"type\";s:15:\"field_item:uuid\";s:8:\"settings\";a:3:{s:10:\"max_length\";i:128;s:8:\"is_ascii\";b:1;s:14:\"case_sensitive\";b:0;}}s:18:\"\0*\0fieldDefinition\";r:40;}s:7:\"\0*\0type\";s:4:\"uuid\";s:9:\"\0*\0schema\";a:4:{s:7:\"columns\";a:1:{s:5:\"value\";a:3:{s:4:\"type\";s:13:\"varchar_ascii\";s:6:\"length\";i:128;s:6:\"binary\";b:0;}}s:11:\"unique keys\";a:1:{s:5:\"value\";a:1:{i:0;s:5:\"value\";}}s:7:\"indexes\";a:0:{}s:12:\"foreign keys\";a:0:{}}s:10:\"\0*\0indexes\";a:0:{}}s:11:\"revision_id\";O:37:\"Drupal\\Core\\Field\\BaseFieldDefinition\":5:{s:13:\"\0*\0definition\";a:8:{s:5:\"label\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:11:\"Revision ID\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}s:9:\"read-only\";b:1;s:11:\"description\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:16:\"The revision ID.\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}s:8:\"provider\";s:13:\"block_content\";s:10:\"field_name\";s:11:\"revision_id\";s:11:\"entity_type\";s:13:\"block_content\";s:6:\"bundle\";N;s:13:\"initial_value\";N;}s:17:\"\0*\0itemDefinition\";O:51:\"Drupal\\Core\\Field\\TypedData\\FieldItemDataDefinition\":2:{s:13:\"\0*\0definition\";a:2:{s:4:\"type\";s:18:\"field_item:integer\";s:8:\"settings\";a:6:{s:8:\"unsigned\";b:1;s:4:\"size\";s:6:\"normal\";s:3:\"min\";s:0:\"\";s:3:\"max\";s:0:\"\";s:6:\"prefix\";s:0:\"\";s:6:\"suffix\";s:0:\"\";}}s:18:\"\0*\0fieldDefinition\";r:77;}s:7:\"\0*\0type\";s:7:\"integer\";s:9:\"\0*\0schema\";a:4:{s:7:\"columns\";a:1:{s:5:\"value\";a:3:{s:4:\"type\";s:3:\"int\";s:8:\"unsigned\";b:1;s:4:\"size\";s:6:\"normal\";}}s:11:\"unique keys\";a:0:{}s:7:\"indexes\";a:0:{}s:12:\"foreign keys\";a:0:{}}s:10:\"\0*\0indexes\";a:0:{}}s:8:\"langcode\";O:37:\"Drupal\\Core\\Field\\BaseFieldDefinition\":5:{s:13:\"\0*\0definition\";a:10:{s:5:\"label\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:8:\"Language\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}s:7:\"display\";a:2:{s:4:\"view\";a:1:{s:7:\"options\";a:1:{s:6:\"region\";s:6:\"hidden\";}}s:4:\"form\";a:1:{s:7:\"options\";a:2:{s:4:\"type\";s:15:\"language_select\";s:6:\"weight\";i:2;}}}s:12:\"revisionable\";b:1;s:12:\"translatable\";b:1;s:11:\"description\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:32:\"The content block language code.\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}s:8:\"provider\";s:13:\"block_content\";s:10:\"field_name\";s:8:\"langcode\";s:11:\"entity_type\";s:13:\"block_content\";s:6:\"bundle\";N;s:13:\"initial_value\";N;}s:17:\"\0*\0itemDefinition\";O:51:\"Drupal\\Core\\Field\\TypedData\\FieldItemDataDefinition\":2:{s:13:\"\0*\0definition\";a:2:{s:4:\"type\";s:19:\"field_item:language\";s:8:\"settings\";a:0:{}}s:18:\"\0*\0fieldDefinition\";r:115;}s:7:\"\0*\0type\";s:8:\"language\";s:9:\"\0*\0schema\";a:4:{s:7:\"columns\";a:1:{s:5:\"value\";a:2:{s:4:\"type\";s:13:\"varchar_ascii\";s:6:\"length\";i:12;}}s:11:\"unique keys\";a:0:{}s:7:\"indexes\";a:0:{}s:12:\"foreign keys\";a:0:{}}s:10:\"\0*\0indexes\";a:0:{}}s:4:\"type\";O:37:\"Drupal\\Core\\Field\\BaseFieldDefinition\":5:{s:13:\"\0*\0definition\";a:9:{s:5:\"label\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:10:\"Block type\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}s:8:\"required\";b:1;s:9:\"read-only\";b:1;s:11:\"description\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:15:\"The block type.\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}s:8:\"provider\";s:13:\"block_content\";s:10:\"field_name\";s:4:\"type\";s:11:\"entity_type\";s:13:\"block_content\";s:6:\"bundle\";N;s:13:\"initial_value\";N;}s:17:\"\0*\0itemDefinition\";O:51:\"Drupal\\Core\\Field\\TypedData\\FieldItemDataDefinition\":2:{s:13:\"\0*\0definition\";a:2:{s:4:\"type\";s:27:\"field_item:entity_reference\";s:8:\"settings\";a:3:{s:11:\"target_type\";s:18:\"block_content_type\";s:7:\"handler\";s:7:\"default\";s:16:\"handler_settings\";a:0:{}}}s:18:\"\0*\0fieldDefinition\";r:155;}s:7:\"\0*\0type\";s:16:\"entity_reference\";s:9:\"\0*\0schema\";a:4:{s:7:\"columns\";a:1:{s:9:\"target_id\";a:3:{s:11:\"description\";s:28:\"The ID of the target entity.\";s:4:\"type\";s:13:\"varchar_ascii\";s:6:\"length\";i:32;}}s:7:\"indexes\";a:1:{s:9:\"target_id\";a:1:{i:0;s:9:\"target_id\";}}s:11:\"unique keys\";a:0:{}s:12:\"foreign keys\";a:0:{}}s:10:\"\0*\0indexes\";a:0:{}}s:16:\"revision_created\";O:37:\"Drupal\\Core\\Field\\BaseFieldDefinition\":5:{s:13:\"\0*\0definition\";a:8:{s:5:\"label\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:20:\"Revision create time\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}s:11:\"description\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:47:\"The time that the current revision was created.\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}s:12:\"revisionable\";b:1;s:8:\"provider\";s:13:\"block_content\";s:10:\"field_name\";s:16:\"revision_created\";s:11:\"entity_type\";s:13:\"block_content\";s:6:\"bundle\";N;s:13:\"initial_value\";N;}s:17:\"\0*\0itemDefinition\";O:51:\"Drupal\\Core\\Field\\TypedData\\FieldItemDataDefinition\":2:{s:13:\"\0*\0definition\";a:2:{s:4:\"type\";s:18:\"field_item:created\";s:8:\"settings\";a:0:{}}s:18:\"\0*\0fieldDefinition\";r:193;}s:7:\"\0*\0type\";s:7:\"created\";s:9:\"\0*\0schema\";a:4:{s:7:\"columns\";a:1:{s:5:\"value\";a:1:{s:4:\"type\";s:3:\"int\";}}s:11:\"unique keys\";a:0:{}s:7:\"indexes\";a:0:{}s:12:\"foreign keys\";a:0:{}}s:10:\"\0*\0indexes\";a:0:{}}s:13:\"revision_user\";O:37:\"Drupal\\Core\\Field\\BaseFieldDefinition\":5:{s:13:\"\0*\0definition\";a:8:{s:5:\"label\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:13:\"Revision user\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}s:11:\"description\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:50:\"The user ID of the author of the current revision.\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}s:12:\"revisionable\";b:1;s:8:\"provider\";s:13:\"block_content\";s:10:\"field_name\";s:13:\"revision_user\";s:11:\"entity_type\";s:13:\"block_content\";s:6:\"bundle\";N;s:13:\"initial_value\";N;}s:17:\"\0*\0itemDefinition\";O:51:\"Drupal\\Core\\Field\\TypedData\\FieldItemDataDefinition\":2:{s:13:\"\0*\0definition\";a:2:{s:4:\"type\";s:27:\"field_item:entity_reference\";s:8:\"settings\";a:3:{s:11:\"target_type\";s:4:\"user\";s:7:\"handler\";s:7:\"default\";s:16:\"handler_settings\";a:0:{}}}s:18:\"\0*\0fieldDefinition\";r:223;}s:7:\"\0*\0type\";s:16:\"entity_reference\";s:9:\"\0*\0schema\";a:4:{s:7:\"columns\";a:1:{s:9:\"target_id\";a:3:{s:11:\"description\";s:28:\"The ID of the target entity.\";s:4:\"type\";s:3:\"int\";s:8:\"unsigned\";b:1;}}s:7:\"indexes\";a:1:{s:9:\"target_id\";a:1:{i:0;s:9:\"target_id\";}}s:11:\"unique keys\";a:0:{}s:12:\"foreign keys\";a:0:{}}s:10:\"\0*\0indexes\";a:0:{}}s:12:\"revision_log\";O:37:\"Drupal\\Core\\Field\\BaseFieldDefinition\":5:{s:13:\"\0*\0definition\";a:10:{s:5:\"label\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:20:\"Revision log message\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}s:11:\"description\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:54:\"The log entry explaining the changes in this revision.\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}s:12:\"revisionable\";b:1;s:13:\"default_value\";a:1:{i:0;a:1:{s:5:\"value\";s:0:\"\";}}s:7:\"display\";a:1:{s:4:\"form\";a:1:{s:7:\"options\";a:3:{s:4:\"type\";s:15:\"string_textarea\";s:6:\"weight\";i:25;s:8:\"settings\";a:1:{s:4:\"rows\";i:4;}}}}s:8:\"provider\";s:13:\"block_content\";s:10:\"field_name\";s:12:\"revision_log\";s:11:\"entity_type\";s:13:\"block_content\";s:6:\"bundle\";N;s:13:\"initial_value\";N;}s:17:\"\0*\0itemDefinition\";O:51:\"Drupal\\Core\\Field\\TypedData\\FieldItemDataDefinition\":2:{s:13:\"\0*\0definition\";a:2:{s:4:\"type\";s:22:\"field_item:string_long\";s:8:\"settings\";a:1:{s:14:\"case_sensitive\";b:0;}}s:18:\"\0*\0fieldDefinition\";r:260;}s:7:\"\0*\0type\";s:11:\"string_long\";s:9:\"\0*\0schema\";a:4:{s:7:\"columns\";a:1:{s:5:\"value\";a:2:{s:4:\"type\";s:4:\"text\";s:4:\"size\";s:3:\"big\";}}s:11:\"unique keys\";a:0:{}s:7:\"indexes\";a:0:{}s:12:\"foreign keys\";a:0:{}}s:10:\"\0*\0indexes\";a:0:{}}s:6:\"status\";O:37:\"Drupal\\Core\\Field\\BaseFieldDefinition\":5:{s:13:\"\0*\0definition\";a:9:{s:5:\"label\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:9:\"Published\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}s:12:\"revisionable\";b:1;s:12:\"translatable\";b:1;s:13:\"default_value\";a:1:{i:0;a:1:{s:5:\"value\";b:1;}}s:8:\"provider\";s:13:\"block_content\";s:10:\"field_name\";s:6:\"status\";s:11:\"entity_type\";s:13:\"block_content\";s:6:\"bundle\";N;s:13:\"initial_value\";N;}s:17:\"\0*\0itemDefinition\";O:51:\"Drupal\\Core\\Field\\TypedData\\FieldItemDataDefinition\":2:{s:13:\"\0*\0definition\";a:2:{s:4:\"type\";s:18:\"field_item:boolean\";s:8:\"settings\";a:2:{s:8:\"on_label\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:2:\"On\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}s:9:\"off_label\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:3:\"Off\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}}}s:18:\"\0*\0fieldDefinition\";r:302;}s:7:\"\0*\0type\";s:7:\"boolean\";s:9:\"\0*\0schema\";a:4:{s:7:\"columns\";a:1:{s:5:\"value\";a:2:{s:4:\"type\";s:3:\"int\";s:4:\"size\";s:4:\"tiny\";}}s:11:\"unique keys\";a:0:{}s:7:\"indexes\";a:0:{}s:12:\"foreign keys\";a:0:{}}s:10:\"\0*\0indexes\";a:0:{}}s:4:\"info\";O:37:\"Drupal\\Core\\Field\\BaseFieldDefinition\":5:{s:13:\"\0*\0definition\";a:11:{s:5:\"label\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:17:\"Block description\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}s:11:\"description\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:34:\"A brief description of your block.\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}s:12:\"revisionable\";b:1;s:12:\"translatable\";b:1;s:8:\"required\";b:1;s:7:\"display\";a:1:{s:4:\"form\";a:2:{s:7:\"options\";a:2:{s:4:\"type\";s:16:\"string_textfield\";s:6:\"weight\";i:-5;}s:12:\"configurable\";b:1;}}s:8:\"provider\";s:13:\"block_content\";s:10:\"field_name\";s:4:\"info\";s:11:\"entity_type\";s:13:\"block_content\";s:6:\"bundle\";N;s:13:\"initial_value\";N;}s:17:\"\0*\0itemDefinition\";O:51:\"Drupal\\Core\\Field\\TypedData\\FieldItemDataDefinition\":2:{s:13:\"\0*\0definition\";a:2:{s:4:\"type\";s:17:\"field_item:string\";s:8:\"settings\";a:3:{s:10:\"max_length\";i:255;s:8:\"is_ascii\";b:0;s:14:\"case_sensitive\";b:0;}}s:18:\"\0*\0fieldDefinition\";r:341;}s:7:\"\0*\0type\";s:6:\"string\";s:9:\"\0*\0schema\";a:4:{s:7:\"columns\";a:1:{s:5:\"value\";a:3:{s:4:\"type\";s:7:\"varchar\";s:6:\"length\";i:255;s:6:\"binary\";b:0;}}s:11:\"unique keys\";a:0:{}s:7:\"indexes\";a:0:{}s:12:\"foreign keys\";a:0:{}}s:10:\"\0*\0indexes\";a:0:{}}s:7:\"changed\";O:37:\"Drupal\\Core\\Field\\BaseFieldDefinition\":5:{s:13:\"\0*\0definition\";a:9:{s:5:\"label\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:7:\"Changed\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}s:11:\"description\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:48:\"The time that the content block was last edited.\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}s:12:\"translatable\";b:1;s:12:\"revisionable\";b:1;s:8:\"provider\";s:13:\"block_content\";s:10:\"field_name\";s:7:\"changed\";s:11:\"entity_type\";s:13:\"block_content\";s:6:\"bundle\";N;s:13:\"initial_value\";N;}s:17:\"\0*\0itemDefinition\";O:51:\"Drupal\\Core\\Field\\TypedData\\FieldItemDataDefinition\":2:{s:13:\"\0*\0definition\";a:2:{s:4:\"type\";s:18:\"field_item:changed\";s:8:\"settings\";a:0:{}}s:18:\"\0*\0fieldDefinition\";r:384;}s:7:\"\0*\0type\";s:7:\"changed\";s:9:\"\0*\0schema\";a:4:{s:7:\"columns\";a:1:{s:5:\"value\";a:1:{s:4:\"type\";s:3:\"int\";}}s:11:\"unique keys\";a:0:{}s:7:\"indexes\";a:0:{}s:12:\"foreign keys\";a:0:{}}s:10:\"\0*\0indexes\";a:0:{}}s:8:\"reusable\";O:37:\"Drupal\\Core\\Field\\BaseFieldDefinition\":5:{s:13:\"\0*\0definition\";a:10:{s:5:\"label\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:8:\"Reusable\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}s:11:\"description\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:52:\"A boolean indicating whether this block is reusable.\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}s:12:\"translatable\";b:0;s:12:\"revisionable\";b:0;s:13:\"default_value\";a:1:{i:0;a:1:{s:5:\"value\";b:1;}}s:8:\"provider\";s:13:\"block_content\";s:10:\"field_name\";s:8:\"reusable\";s:11:\"entity_type\";s:13:\"block_content\";s:6:\"bundle\";N;s:13:\"initial_value\";N;}s:17:\"\0*\0itemDefinition\";O:51:\"Drupal\\Core\\Field\\TypedData\\FieldItemDataDefinition\":2:{s:13:\"\0*\0definition\";a:2:{s:4:\"type\";s:18:\"field_item:boolean\";s:8:\"settings\";a:2:{s:8:\"on_label\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:2:\"On\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}s:9:\"off_label\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:3:\"Off\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}}}s:18:\"\0*\0fieldDefinition\";r:415;}s:7:\"\0*\0type\";s:7:\"boolean\";s:9:\"\0*\0schema\";a:4:{s:7:\"columns\";a:1:{s:5:\"value\";a:2:{s:4:\"type\";s:3:\"int\";s:4:\"size\";s:4:\"tiny\";}}s:11:\"unique keys\";a:0:{}s:7:\"indexes\";a:0:{}s:12:\"foreign keys\";a:0:{}}s:10:\"\0*\0indexes\";a:0:{}}s:16:\"default_langcode\";O:37:\"Drupal\\Core\\Field\\BaseFieldDefinition\":5:{s:13:\"\0*\0definition\";a:10:{s:5:\"label\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:19:\"Default translation\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}s:11:\"description\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:58:\"A flag indicating whether this is the default translation.\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}s:12:\"translatable\";b:1;s:12:\"revisionable\";b:1;s:13:\"default_value\";a:1:{i:0;a:1:{s:5:\"value\";b:1;}}s:8:\"provider\";s:13:\"block_content\";s:10:\"field_name\";s:16:\"default_langcode\";s:11:\"entity_type\";s:13:\"block_content\";s:6:\"bundle\";N;s:13:\"initial_value\";N;}s:17:\"\0*\0itemDefinition\";O:51:\"Drupal\\Core\\Field\\TypedData\\FieldItemDataDefinition\":2:{s:13:\"\0*\0definition\";a:2:{s:4:\"type\";s:18:\"field_item:boolean\";s:8:\"settings\";a:2:{s:8:\"on_label\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:2:\"On\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}s:9:\"off_label\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:3:\"Off\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}}}s:18:\"\0*\0fieldDefinition\";r:458;}s:7:\"\0*\0type\";s:7:\"boolean\";s:9:\"\0*\0schema\";a:4:{s:7:\"columns\";a:1:{s:5:\"value\";a:2:{s:4:\"type\";s:3:\"int\";s:4:\"size\";s:4:\"tiny\";}}s:11:\"unique keys\";a:0:{}s:7:\"indexes\";a:0:{}s:12:\"foreign keys\";a:0:{}}s:10:\"\0*\0indexes\";a:0:{}}s:16:\"revision_default\";O:37:\"Drupal\\Core\\Field\\BaseFieldDefinition\":5:{s:13:\"\0*\0definition\";a:11:{s:5:\"label\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:16:\"Default revision\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}s:11:\"description\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:72:\"A flag indicating whether this was a default revision when it was saved.\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}s:16:\"storage_required\";b:1;s:8:\"internal\";b:1;s:12:\"translatable\";b:0;s:12:\"revisionable\";b:1;s:8:\"provider\";s:13:\"block_content\";s:10:\"field_name\";s:16:\"revision_default\";s:11:\"entity_type\";s:13:\"block_content\";s:6:\"bundle\";N;s:13:\"initial_value\";N;}s:17:\"\0*\0itemDefinition\";O:51:\"Drupal\\Core\\Field\\TypedData\\FieldItemDataDefinition\":2:{s:13:\"\0*\0definition\";a:2:{s:4:\"type\";s:18:\"field_item:boolean\";s:8:\"settings\";a:2:{s:8:\"on_label\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:2:\"On\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}s:9:\"off_label\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:3:\"Off\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}}}s:18:\"\0*\0fieldDefinition\";r:501;}s:7:\"\0*\0type\";s:7:\"boolean\";s:9:\"\0*\0schema\";a:4:{s:7:\"columns\";a:1:{s:5:\"value\";a:2:{s:4:\"type\";s:3:\"int\";s:4:\"size\";s:4:\"tiny\";}}s:11:\"unique keys\";a:0:{}s:7:\"indexes\";a:0:{}s:12:\"foreign keys\";a:0:{}}s:10:\"\0*\0indexes\";a:0:{}}s:29:\"revision_translation_affected\";O:37:\"Drupal\\Core\\Field\\BaseFieldDefinition\":5:{s:13:\"\0*\0definition\";a:10:{s:5:\"label\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:29:\"Revision translation affected\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}s:11:\"description\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:72:\"Indicates if the last edit of a translation belongs to current revision.\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}s:9:\"read-only\";b:1;s:12:\"revisionable\";b:1;s:12:\"translatable\";b:1;s:8:\"provider\";s:13:\"block_content\";s:10:\"field_name\";s:29:\"revision_translation_affected\";s:11:\"entity_type\";s:13:\"block_content\";s:6:\"bundle\";N;s:13:\"initial_value\";N;}s:17:\"\0*\0itemDefinition\";O:51:\"Drupal\\Core\\Field\\TypedData\\FieldItemDataDefinition\":2:{s:13:\"\0*\0definition\";a:2:{s:4:\"type\";s:18:\"field_item:boolean\";s:8:\"settings\";a:2:{s:8:\"on_label\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:2:\"On\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}s:9:\"off_label\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:3:\"Off\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}}}s:18:\"\0*\0fieldDefinition\";r:543;}s:7:\"\0*\0type\";s:7:\"boolean\";s:9:\"\0*\0schema\";a:4:{s:7:\"columns\";a:1:{s:5:\"value\";a:2:{s:4:\"type\";s:3:\"int\";s:4:\"size\";s:4:\"tiny\";}}s:11:\"unique keys\";a:0:{}s:7:\"indexes\";a:0:{}s:12:\"foreign keys\";a:0:{}}s:10:\"\0*\0indexes\";a:0:{}}s:4:\"body\";O:38:\"Drupal\\field\\Entity\\FieldStorageConfig\":30:{s:15:\"\0*\0entityTypeId\";s:20:\"field_storage_config\";s:15:\"\0*\0enforceIsNew\";b:1;s:12:\"\0*\0typedData\";N;s:16:\"\0*\0cacheContexts\";a:0:{}s:12:\"\0*\0cacheTags\";a:0:{}s:14:\"\0*\0cacheMaxAge\";i:-1;s:14:\"\0*\0_serviceIds\";a:0:{}s:18:\"\0*\0_entityStorages\";a:0:{}s:13:\"\0*\0originalId\";s:18:\"block_content.body\";s:9:\"\0*\0status\";b:1;s:7:\"\0*\0uuid\";s:36:\"432c4e97-691a-4627-a935-82f33f198c43\";s:11:\"\0*\0langcode\";s:2:\"en\";s:23:\"\0*\0third_party_settings\";a:0:{}s:8:\"\0*\0_core\";a:1:{s:19:\"default_config_hash\";s:43:\"eS0snV_L3dx9shtWRTzm5eblwOJ7qKWC9IE-4GMTDFc\";}s:14:\"\0*\0trustedData\";b:1;s:15:\"\0*\0dependencies\";a:1:{s:6:\"module\";a:2:{i:0;s:13:\"block_content\";i:1;s:4:\"text\";}}s:12:\"\0*\0isSyncing\";b:0;s:5:\"\0*\0id\";s:18:\"block_content.body\";s:13:\"\0*\0field_name\";s:4:\"body\";s:14:\"\0*\0entity_type\";s:13:\"block_content\";s:7:\"\0*\0type\";s:17:\"text_with_summary\";s:9:\"\0*\0module\";s:4:\"text\";s:11:\"\0*\0settings\";a:0:{}s:14:\"\0*\0cardinality\";i:1;s:15:\"\0*\0translatable\";b:1;s:9:\"\0*\0locked\";b:0;s:25:\"\0*\0persist_with_no_fields\";b:1;s:14:\"custom_storage\";b:0;s:10:\"\0*\0indexes\";a:0:{}s:10:\"\0*\0deleted\";b:0;}s:9:\"workspace\";O:37:\"Drupal\\Core\\Field\\BaseFieldDefinition\":5:{s:13:\"\0*\0definition\";a:10:{s:5:\"label\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:9:\"Workspace\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}s:11:\"description\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:54:\"Indicates the workspace that this revision belongs to.\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}s:8:\"internal\";b:1;s:12:\"translatable\";b:0;s:12:\"revisionable\";b:1;s:8:\"provider\";s:10:\"workspaces\";s:10:\"field_name\";s:9:\"workspace\";s:11:\"entity_type\";s:13:\"block_content\";s:6:\"bundle\";N;s:13:\"initial_value\";N;}s:17:\"\0*\0itemDefinition\";O:51:\"Drupal\\Core\\Field\\TypedData\\FieldItemDataDefinition\":2:{s:13:\"\0*\0definition\";a:2:{s:4:\"type\";s:27:\"field_item:entity_reference\";s:8:\"settings\";a:3:{s:11:\"target_type\";s:9:\"workspace\";s:7:\"handler\";s:7:\"default\";s:16:\"handler_settings\";a:0:{}}}s:18:\"\0*\0fieldDefinition\";r:619;}s:7:\"\0*\0type\";s:16:\"entity_reference\";s:9:\"\0*\0schema\";a:4:{s:7:\"columns\";a:1:{s:9:\"target_id\";a:3:{s:11:\"description\";s:28:\"The ID of the target entity.\";s:4:\"type\";s:13:\"varchar_ascii\";s:6:\"length\";i:255;}}s:7:\"indexes\";a:1:{s:9:\"target_id\";a:1:{i:0;s:9:\"target_id\";}}s:11:\"unique keys\";a:0:{}s:12:\"foreign keys\";a:0:{}}s:10:\"\0*\0indexes\";a:0:{}}}",
  ],
  [
    'collection' => "entity.storage_schema.sql",
    'name' => "block_content.field_schema_data.workspace",
    'value' => "a:1:{s:22:\"block_content_revision\";a:2:{s:6:\"fields\";a:1:{s:9:\"workspace\";a:4:{s:11:\"description\";s:28:\"The ID of the target entity.\";s:4:\"type\";s:13:\"varchar_ascii\";s:6:\"length\";i:255;s:8:\"not null\";b:0;}}s:7:\"indexes\";a:1:{s:41:\"block_content_field__workspace__target_id\";a:1:{i:0;s:9:\"workspace\";}}}}",
  ],
  [
    'collection' => "entity.definitions.installed",
    'name' => "menu_link_content.entity_type",
    'value' => "O:36:\"Drupal\\Core\\Entity\\ContentEntityType\":40:{s:5:\"\0*\0id\";s:17:\"menu_link_content\";s:8:\"\0*\0class\";s:47:\"Drupal\\menu_link_content\\Entity\\MenuLinkContent\";s:11:\"\0*\0provider\";s:17:\"menu_link_content\";s:15:\"\0*\0static_cache\";b:1;s:15:\"\0*\0render_cache\";b:1;s:19:\"\0*\0persistent_cache\";b:1;s:14:\"\0*\0entity_keys\";a:9:{s:2:\"id\";s:2:\"id\";s:8:\"revision\";s:11:\"revision_id\";s:5:\"label\";s:5:\"title\";s:8:\"langcode\";s:8:\"langcode\";s:4:\"uuid\";s:4:\"uuid\";s:6:\"bundle\";s:6:\"bundle\";s:9:\"published\";s:7:\"enabled\";s:16:\"default_langcode\";s:16:\"default_langcode\";s:29:\"revision_translation_affected\";s:29:\"revision_translation_affected\";}s:16:\"\0*\0originalClass\";s:47:\"Drupal\\menu_link_content\\Entity\\MenuLinkContent\";s:11:\"\0*\0handlers\";a:7:{s:7:\"storage\";s:48:\"\\Drupal\\menu_link_content\\MenuLinkContentStorage\";s:14:\"storage_schema\";s:53:\"Drupal\\menu_link_content\\MenuLinkContentStorageSchema\";s:6:\"access\";s:60:\"Drupal\\menu_link_content\\MenuLinkContentAccessControlHandler\";s:4:\"form\";a:2:{s:7:\"default\";s:49:\"Drupal\\menu_link_content\\Form\\MenuLinkContentForm\";s:6:\"delete\";s:55:\"Drupal\\menu_link_content\\Form\\MenuLinkContentDeleteForm\";}s:12:\"list_builder\";s:44:\"Drupal\\menu_link_content\\MenuLinkListBuilder\";s:12:\"view_builder\";s:36:\"Drupal\\Core\\Entity\\EntityViewBuilder\";s:10:\"moderation\";s:0:\"\";}s:19:\"\0*\0admin_permission\";s:15:\"administer menu\";s:24:\"\0*\0collection_permission\";N;s:25:\"\0*\0permission_granularity\";s:11:\"entity_type\";s:8:\"\0*\0links\";a:3:{s:9:\"canonical\";s:51:\"/admin/structure/menu/item/{menu_link_content}/edit\";s:9:\"edit-form\";s:51:\"/admin/structure/menu/item/{menu_link_content}/edit\";s:11:\"delete-form\";s:53:\"/admin/structure/menu/item/{menu_link_content}/delete\";}s:21:\"\0*\0bundle_entity_type\";N;s:12:\"\0*\0bundle_of\";N;s:15:\"\0*\0bundle_label\";N;s:13:\"\0*\0base_table\";s:17:\"menu_link_content\";s:22:\"\0*\0revision_data_table\";s:32:\"menu_link_content_field_revision\";s:17:\"\0*\0revision_table\";s:26:\"menu_link_content_revision\";s:13:\"\0*\0data_table\";s:22:\"menu_link_content_data\";s:11:\"\0*\0internal\";b:0;s:15:\"\0*\0translatable\";b:1;s:19:\"\0*\0show_revision_ui\";b:0;s:8:\"\0*\0label\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:16:\"Custom menu link\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}s:19:\"\0*\0label_collection\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:17:\"Custom menu links\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}s:17:\"\0*\0label_singular\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:16:\"custom menu link\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}s:15:\"\0*\0label_plural\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:17:\"custom menu links\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}s:14:\"\0*\0label_count\";a:3:{s:8:\"singular\";s:23:\"@count custom menu link\";s:6:\"plural\";s:24:\"@count custom menu links\";s:7:\"context\";N;}s:15:\"\0*\0uri_callback\";N;s:8:\"\0*\0group\";s:7:\"content\";s:14:\"\0*\0group_label\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:7:\"Content\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:1:{s:7:\"context\";s:17:\"Entity type group\";}}s:22:\"\0*\0field_ui_base_route\";N;s:26:\"\0*\0common_reference_target\";b:0;s:22:\"\0*\0list_cache_contexts\";a:0:{}s:18:\"\0*\0list_cache_tags\";a:1:{i:0;s:22:\"menu_link_content_list\";}s:14:\"\0*\0constraints\";a:3:{s:17:\"MenuTreeHierarchy\";a:0:{}s:13:\"EntityChanged\";N;s:26:\"EntityUntranslatableFields\";N;}s:13:\"\0*\0additional\";a:0:{}s:14:\"\0*\0_serviceIds\";a:1:{s:17:\"stringTranslation\";s:18:\"string_translation\";}s:18:\"\0*\0_entityStorages\";a:0:{}s:25:\"\0*\0revision_metadata_keys\";a:5:{s:13:\"revision_user\";s:13:\"revision_user\";s:16:\"revision_created\";s:16:\"revision_created\";s:20:\"revision_log_message\";s:20:\"revision_log_message\";s:16:\"revision_default\";s:16:\"revision_default\";s:9:\"workspace\";s:9:\"workspace\";}}",
  ],
  [
    'collection' => "entity.definitions.installed",
    'name' => "menu_link_content.field_storage_definitions",
    'value' => "a:23:{s:2:\"id\";O:37:\"Drupal\\Core\\Field\\BaseFieldDefinition\":5:{s:13:\"\0*\0definition\";a:8:{s:5:\"label\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:9:\"Entity ID\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}s:9:\"read-only\";b:1;s:11:\"description\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:48:\"The entity ID for this menu link content entity.\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}s:8:\"provider\";s:17:\"menu_link_content\";s:10:\"field_name\";s:2:\"id\";s:11:\"entity_type\";s:17:\"menu_link_content\";s:6:\"bundle\";N;s:13:\"initial_value\";N;}s:17:\"\0*\0itemDefinition\";O:51:\"Drupal\\Core\\Field\\TypedData\\FieldItemDataDefinition\":2:{s:13:\"\0*\0definition\";a:2:{s:4:\"type\";s:18:\"field_item:integer\";s:8:\"settings\";a:6:{s:8:\"unsigned\";b:1;s:4:\"size\";s:6:\"normal\";s:3:\"min\";s:0:\"\";s:3:\"max\";s:0:\"\";s:6:\"prefix\";s:0:\"\";s:6:\"suffix\";s:0:\"\";}}s:18:\"\0*\0fieldDefinition\";r:2;}s:7:\"\0*\0type\";s:7:\"integer\";s:9:\"\0*\0schema\";a:4:{s:7:\"columns\";a:1:{s:5:\"value\";a:3:{s:4:\"type\";s:3:\"int\";s:8:\"unsigned\";b:1;s:4:\"size\";s:6:\"normal\";}}s:11:\"unique keys\";a:0:{}s:7:\"indexes\";a:0:{}s:12:\"foreign keys\";a:0:{}}s:10:\"\0*\0indexes\";a:0:{}}s:4:\"uuid\";O:37:\"Drupal\\Core\\Field\\BaseFieldDefinition\":5:{s:13:\"\0*\0definition\";a:8:{s:5:\"label\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:4:\"UUID\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}s:9:\"read-only\";b:1;s:11:\"description\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:27:\"The content menu link UUID.\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}s:8:\"provider\";s:17:\"menu_link_content\";s:10:\"field_name\";s:4:\"uuid\";s:11:\"entity_type\";s:17:\"menu_link_content\";s:6:\"bundle\";N;s:13:\"initial_value\";N;}s:17:\"\0*\0itemDefinition\";O:51:\"Drupal\\Core\\Field\\TypedData\\FieldItemDataDefinition\":2:{s:13:\"\0*\0definition\";a:2:{s:4:\"type\";s:15:\"field_item:uuid\";s:8:\"settings\";a:3:{s:10:\"max_length\";i:128;s:8:\"is_ascii\";b:1;s:14:\"case_sensitive\";b:0;}}s:18:\"\0*\0fieldDefinition\";r:40;}s:7:\"\0*\0type\";s:4:\"uuid\";s:9:\"\0*\0schema\";a:4:{s:7:\"columns\";a:1:{s:5:\"value\";a:3:{s:4:\"type\";s:13:\"varchar_ascii\";s:6:\"length\";i:128;s:6:\"binary\";b:0;}}s:11:\"unique keys\";a:1:{s:5:\"value\";a:1:{i:0;s:5:\"value\";}}s:7:\"indexes\";a:0:{}s:12:\"foreign keys\";a:0:{}}s:10:\"\0*\0indexes\";a:0:{}}s:11:\"revision_id\";O:37:\"Drupal\\Core\\Field\\BaseFieldDefinition\":5:{s:13:\"\0*\0definition\";a:7:{s:5:\"label\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:11:\"Revision ID\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}s:9:\"read-only\";b:1;s:8:\"provider\";s:17:\"menu_link_content\";s:10:\"field_name\";s:11:\"revision_id\";s:11:\"entity_type\";s:17:\"menu_link_content\";s:6:\"bundle\";N;s:13:\"initial_value\";N;}s:17:\"\0*\0itemDefinition\";O:51:\"Drupal\\Core\\Field\\TypedData\\FieldItemDataDefinition\":2:{s:13:\"\0*\0definition\";a:2:{s:4:\"type\";s:18:\"field_item:integer\";s:8:\"settings\";a:6:{s:8:\"unsigned\";b:1;s:4:\"size\";s:6:\"normal\";s:3:\"min\";s:0:\"\";s:3:\"max\";s:0:\"\";s:6:\"prefix\";s:0:\"\";s:6:\"suffix\";s:0:\"\";}}s:18:\"\0*\0fieldDefinition\";r:77;}s:7:\"\0*\0type\";s:7:\"integer\";s:9:\"\0*\0schema\";a:4:{s:7:\"columns\";a:1:{s:5:\"value\";a:3:{s:4:\"type\";s:3:\"int\";s:8:\"unsigned\";b:1;s:4:\"size\";s:6:\"normal\";}}s:11:\"unique keys\";a:0:{}s:7:\"indexes\";a:0:{}s:12:\"foreign keys\";a:0:{}}s:10:\"\0*\0indexes\";a:0:{}}s:8:\"langcode\";O:37:\"Drupal\\Core\\Field\\BaseFieldDefinition\":5:{s:13:\"\0*\0definition\";a:10:{s:5:\"label\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:8:\"Language\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}s:7:\"display\";a:2:{s:4:\"view\";a:1:{s:7:\"options\";a:1:{s:6:\"region\";s:6:\"hidden\";}}s:4:\"form\";a:1:{s:7:\"options\";a:2:{s:4:\"type\";s:15:\"language_select\";s:6:\"weight\";i:2;}}}s:12:\"revisionable\";b:1;s:12:\"translatable\";b:1;s:11:\"description\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:28:\"The menu link language code.\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}s:8:\"provider\";s:17:\"menu_link_content\";s:10:\"field_name\";s:8:\"langcode\";s:11:\"entity_type\";s:17:\"menu_link_content\";s:6:\"bundle\";N;s:13:\"initial_value\";N;}s:17:\"\0*\0itemDefinition\";O:51:\"Drupal\\Core\\Field\\TypedData\\FieldItemDataDefinition\":2:{s:13:\"\0*\0definition\";a:2:{s:4:\"type\";s:19:\"field_item:language\";s:8:\"settings\";a:0:{}}s:18:\"\0*\0fieldDefinition\";r:111;}s:7:\"\0*\0type\";s:8:\"language\";s:9:\"\0*\0schema\";a:4:{s:7:\"columns\";a:1:{s:5:\"value\";a:2:{s:4:\"type\";s:13:\"varchar_ascii\";s:6:\"length\";i:12;}}s:11:\"unique keys\";a:0:{}s:7:\"indexes\";a:0:{}s:12:\"foreign keys\";a:0:{}}s:10:\"\0*\0indexes\";a:0:{}}s:6:\"bundle\";O:37:\"Drupal\\Core\\Field\\BaseFieldDefinition\":5:{s:13:\"\0*\0definition\";a:9:{s:5:\"label\";s:23:\"Custom menu link bundle\";s:8:\"required\";b:1;s:9:\"read-only\";b:1;s:11:\"description\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:29:\"The content menu link bundle.\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}s:8:\"provider\";s:17:\"menu_link_content\";s:10:\"field_name\";s:6:\"bundle\";s:11:\"entity_type\";s:17:\"menu_link_content\";s:6:\"bundle\";N;s:13:\"initial_value\";N;}s:17:\"\0*\0itemDefinition\";O:51:\"Drupal\\Core\\Field\\TypedData\\FieldItemDataDefinition\":2:{s:13:\"\0*\0definition\";a:2:{s:4:\"type\";s:17:\"field_item:string\";s:8:\"settings\";a:3:{s:10:\"max_length\";i:32;s:8:\"is_ascii\";b:1;s:14:\"case_sensitive\";b:0;}}s:18:\"\0*\0fieldDefinition\";r:151;}s:7:\"\0*\0type\";s:6:\"string\";s:9:\"\0*\0schema\";a:4:{s:7:\"columns\";a:1:{s:5:\"value\";a:3:{s:4:\"type\";s:13:\"varchar_ascii\";s:6:\"length\";i:32;s:6:\"binary\";b:0;}}s:11:\"unique keys\";a:0:{}s:7:\"indexes\";a:0:{}s:12:\"foreign keys\";a:0:{}}s:10:\"\0*\0indexes\";a:0:{}}s:16:\"revision_created\";O:37:\"Drupal\\Core\\Field\\BaseFieldDefinition\":5:{s:13:\"\0*\0definition\";a:8:{s:5:\"label\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:20:\"Revision create time\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}s:11:\"description\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:47:\"The time that the current revision was created.\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}s:12:\"revisionable\";b:1;s:8:\"provider\";s:17:\"menu_link_content\";s:10:\"field_name\";s:16:\"revision_created\";s:11:\"entity_type\";s:17:\"menu_link_content\";s:6:\"bundle\";N;s:13:\"initial_value\";N;}s:17:\"\0*\0itemDefinition\";O:51:\"Drupal\\Core\\Field\\TypedData\\FieldItemDataDefinition\":2:{s:13:\"\0*\0definition\";a:2:{s:4:\"type\";s:18:\"field_item:created\";s:8:\"settings\";a:0:{}}s:18:\"\0*\0fieldDefinition\";r:184;}s:7:\"\0*\0type\";s:7:\"created\";s:9:\"\0*\0schema\";a:4:{s:7:\"columns\";a:1:{s:5:\"value\";a:1:{s:4:\"type\";s:3:\"int\";}}s:11:\"unique keys\";a:0:{}s:7:\"indexes\";a:0:{}s:12:\"foreign keys\";a:0:{}}s:10:\"\0*\0indexes\";a:0:{}}s:13:\"revision_user\";O:37:\"Drupal\\Core\\Field\\BaseFieldDefinition\":5:{s:13:\"\0*\0definition\";a:8:{s:5:\"label\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:13:\"Revision user\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}s:11:\"description\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:50:\"The user ID of the author of the current revision.\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}s:12:\"revisionable\";b:1;s:8:\"provider\";s:17:\"menu_link_content\";s:10:\"field_name\";s:13:\"revision_user\";s:11:\"entity_type\";s:17:\"menu_link_content\";s:6:\"bundle\";N;s:13:\"initial_value\";N;}s:17:\"\0*\0itemDefinition\";O:51:\"Drupal\\Core\\Field\\TypedData\\FieldItemDataDefinition\":2:{s:13:\"\0*\0definition\";a:2:{s:4:\"type\";s:27:\"field_item:entity_reference\";s:8:\"settings\";a:3:{s:11:\"target_type\";s:4:\"user\";s:7:\"handler\";s:7:\"default\";s:16:\"handler_settings\";a:0:{}}}s:18:\"\0*\0fieldDefinition\";r:214;}s:7:\"\0*\0type\";s:16:\"entity_reference\";s:9:\"\0*\0schema\";a:4:{s:7:\"columns\";a:1:{s:9:\"target_id\";a:3:{s:11:\"description\";s:28:\"The ID of the target entity.\";s:4:\"type\";s:3:\"int\";s:8:\"unsigned\";b:1;}}s:7:\"indexes\";a:1:{s:9:\"target_id\";a:1:{i:0;s:9:\"target_id\";}}s:11:\"unique keys\";a:0:{}s:12:\"foreign keys\";a:0:{}}s:10:\"\0*\0indexes\";a:0:{}}s:20:\"revision_log_message\";O:37:\"Drupal\\Core\\Field\\BaseFieldDefinition\":5:{s:13:\"\0*\0definition\";a:10:{s:5:\"label\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:20:\"Revision log message\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}s:11:\"description\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:43:\"Briefly describe the changes you have made.\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}s:12:\"revisionable\";b:1;s:13:\"default_value\";a:1:{i:0;a:1:{s:5:\"value\";s:0:\"\";}}s:7:\"display\";a:1:{s:4:\"form\";a:1:{s:7:\"options\";a:1:{s:6:\"region\";s:6:\"hidden\";}}}s:8:\"provider\";s:17:\"menu_link_content\";s:10:\"field_name\";s:20:\"revision_log_message\";s:11:\"entity_type\";s:17:\"menu_link_content\";s:6:\"bundle\";N;s:13:\"initial_value\";N;}s:17:\"\0*\0itemDefinition\";O:51:\"Drupal\\Core\\Field\\TypedData\\FieldItemDataDefinition\":2:{s:13:\"\0*\0definition\";a:2:{s:4:\"type\";s:22:\"field_item:string_long\";s:8:\"settings\";a:1:{s:14:\"case_sensitive\";b:0;}}s:18:\"\0*\0fieldDefinition\";r:251;}s:7:\"\0*\0type\";s:11:\"string_long\";s:9:\"\0*\0schema\";a:4:{s:7:\"columns\";a:1:{s:5:\"value\";a:2:{s:4:\"type\";s:4:\"text\";s:4:\"size\";s:3:\"big\";}}s:11:\"unique keys\";a:0:{}s:7:\"indexes\";a:0:{}s:12:\"foreign keys\";a:0:{}}s:10:\"\0*\0indexes\";a:0:{}}s:7:\"enabled\";O:37:\"Drupal\\Core\\Field\\BaseFieldDefinition\":5:{s:13:\"\0*\0definition\";a:11:{s:5:\"label\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:7:\"Enabled\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}s:12:\"revisionable\";b:1;s:12:\"translatable\";b:0;s:13:\"default_value\";a:1:{i:0;a:1:{s:5:\"value\";b:1;}}s:11:\"description\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:65:\"A flag for whether the link should be enabled in menus or hidden.\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}s:7:\"display\";a:2:{s:4:\"view\";a:1:{s:7:\"options\";a:3:{s:5:\"label\";s:6:\"hidden\";s:4:\"type\";s:7:\"boolean\";s:6:\"weight\";i:0;}}s:4:\"form\";a:1:{s:7:\"options\";a:2:{s:8:\"settings\";a:1:{s:13:\"display_label\";b:1;}s:6:\"weight\";i:-1;}}}s:8:\"provider\";s:17:\"menu_link_content\";s:10:\"field_name\";s:7:\"enabled\";s:11:\"entity_type\";s:17:\"menu_link_content\";s:6:\"bundle\";N;s:13:\"initial_value\";N;}s:17:\"\0*\0itemDefinition\";O:51:\"Drupal\\Core\\Field\\TypedData\\FieldItemDataDefinition\":2:{s:13:\"\0*\0definition\";a:2:{s:4:\"type\";s:18:\"field_item:boolean\";s:8:\"settings\";a:2:{s:8:\"on_label\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:2:\"On\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}s:9:\"off_label\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:3:\"Off\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}}}s:18:\"\0*\0fieldDefinition\";r:290;}s:7:\"\0*\0type\";s:7:\"boolean\";s:9:\"\0*\0schema\";a:4:{s:7:\"columns\";a:1:{s:5:\"value\";a:2:{s:4:\"type\";s:3:\"int\";s:4:\"size\";s:4:\"tiny\";}}s:11:\"unique keys\";a:0:{}s:7:\"indexes\";a:0:{}s:12:\"foreign keys\";a:0:{}}s:10:\"\0*\0indexes\";a:0:{}}s:5:\"title\";O:37:\"Drupal\\Core\\Field\\BaseFieldDefinition\":5:{s:13:\"\0*\0definition\";a:11:{s:5:\"label\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:15:\"Menu link title\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}s:11:\"description\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:46:\"The text to be used for this link in the menu.\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}s:8:\"required\";b:1;s:12:\"translatable\";b:1;s:12:\"revisionable\";b:1;s:7:\"display\";a:2:{s:4:\"view\";a:1:{s:7:\"options\";a:3:{s:5:\"label\";s:6:\"hidden\";s:4:\"type\";s:6:\"string\";s:6:\"weight\";i:-5;}}s:4:\"form\";a:2:{s:7:\"options\";a:2:{s:4:\"type\";s:16:\"string_textfield\";s:6:\"weight\";i:-5;}s:12:\"configurable\";b:1;}}s:8:\"provider\";s:17:\"menu_link_content\";s:10:\"field_name\";s:5:\"title\";s:11:\"entity_type\";s:17:\"menu_link_content\";s:6:\"bundle\";N;s:13:\"initial_value\";N;}s:17:\"\0*\0itemDefinition\";O:51:\"Drupal\\Core\\Field\\TypedData\\FieldItemDataDefinition\":2:{s:13:\"\0*\0definition\";a:2:{s:4:\"type\";s:17:\"field_item:string\";s:8:\"settings\";a:3:{s:10:\"max_length\";i:255;s:8:\"is_ascii\";b:0;s:14:\"case_sensitive\";b:0;}}s:18:\"\0*\0fieldDefinition\";r:344;}s:7:\"\0*\0type\";s:6:\"string\";s:9:\"\0*\0schema\";a:4:{s:7:\"columns\";a:1:{s:5:\"value\";a:3:{s:4:\"type\";s:7:\"varchar\";s:6:\"length\";i:255;s:6:\"binary\";b:0;}}s:11:\"unique keys\";a:0:{}s:7:\"indexes\";a:0:{}s:12:\"foreign keys\";a:0:{}}s:10:\"\0*\0indexes\";a:0:{}}s:11:\"description\";O:37:\"Drupal\\Core\\Field\\BaseFieldDefinition\":5:{s:13:\"\0*\0definition\";a:10:{s:5:\"label\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:11:\"Description\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}s:11:\"description\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:39:\"Shown when hovering over the menu link.\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}s:12:\"translatable\";b:1;s:12:\"revisionable\";b:1;s:7:\"display\";a:2:{s:4:\"view\";a:1:{s:7:\"options\";a:3:{s:5:\"label\";s:6:\"hidden\";s:4:\"type\";s:6:\"string\";s:6:\"weight\";i:0;}}s:4:\"form\";a:1:{s:7:\"options\";a:2:{s:4:\"type\";s:16:\"string_textfield\";s:6:\"weight\";i:0;}}}s:8:\"provider\";s:17:\"menu_link_content\";s:10:\"field_name\";s:11:\"description\";s:11:\"entity_type\";s:17:\"menu_link_content\";s:6:\"bundle\";N;s:13:\"initial_value\";N;}s:17:\"\0*\0itemDefinition\";O:51:\"Drupal\\Core\\Field\\TypedData\\FieldItemDataDefinition\":2:{s:13:\"\0*\0definition\";a:2:{s:4:\"type\";s:17:\"field_item:string\";s:8:\"settings\";a:3:{s:10:\"max_length\";i:255;s:8:\"is_ascii\";b:0;s:14:\"case_sensitive\";b:0;}}s:18:\"\0*\0fieldDefinition\";r:392;}s:7:\"\0*\0type\";s:6:\"string\";s:9:\"\0*\0schema\";a:4:{s:7:\"columns\";a:1:{s:5:\"value\";a:3:{s:4:\"type\";s:7:\"varchar\";s:6:\"length\";i:255;s:6:\"binary\";b:0;}}s:11:\"unique keys\";a:0:{}s:7:\"indexes\";a:0:{}s:12:\"foreign keys\";a:0:{}}s:10:\"\0*\0indexes\";a:0:{}}s:9:\"menu_name\";O:37:\"Drupal\\Core\\Field\\BaseFieldDefinition\":5:{s:13:\"\0*\0definition\";a:8:{s:5:\"label\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:9:\"Menu name\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}s:11:\"description\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:93:\"The menu name. All links with the same menu name (such as \"tools\") are part of the same menu.\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}s:13:\"default_value\";a:1:{i:0;a:1:{s:5:\"value\";s:5:\"tools\";}}s:8:\"provider\";s:17:\"menu_link_content\";s:10:\"field_name\";s:9:\"menu_name\";s:11:\"entity_type\";s:17:\"menu_link_content\";s:6:\"bundle\";N;s:13:\"initial_value\";N;}s:17:\"\0*\0itemDefinition\";O:51:\"Drupal\\Core\\Field\\TypedData\\FieldItemDataDefinition\":2:{s:13:\"\0*\0definition\";a:2:{s:4:\"type\";s:17:\"field_item:string\";s:8:\"settings\";a:3:{s:10:\"max_length\";i:255;s:8:\"is_ascii\";b:1;s:14:\"case_sensitive\";b:0;}}s:18:\"\0*\0fieldDefinition\";r:438;}s:7:\"\0*\0type\";s:6:\"string\";s:9:\"\0*\0schema\";a:4:{s:7:\"columns\";a:1:{s:5:\"value\";a:3:{s:4:\"type\";s:13:\"varchar_ascii\";s:6:\"length\";i:255;s:6:\"binary\";b:0;}}s:11:\"unique keys\";a:0:{}s:7:\"indexes\";a:0:{}s:12:\"foreign keys\";a:0:{}}s:10:\"\0*\0indexes\";a:0:{}}s:4:\"link\";O:37:\"Drupal\\Core\\Field\\BaseFieldDefinition\":5:{s:13:\"\0*\0definition\";a:10:{s:5:\"label\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:4:\"Link\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}s:11:\"description\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:38:\"The location this menu link points to.\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}s:12:\"revisionable\";b:1;s:8:\"required\";b:1;s:7:\"display\";a:1:{s:4:\"form\";a:1:{s:7:\"options\";a:2:{s:4:\"type\";s:12:\"link_default\";s:6:\"weight\";i:-2;}}}s:8:\"provider\";s:17:\"menu_link_content\";s:10:\"field_name\";s:4:\"link\";s:11:\"entity_type\";s:17:\"menu_link_content\";s:6:\"bundle\";N;s:13:\"initial_value\";N;}s:17:\"\0*\0itemDefinition\";O:51:\"Drupal\\Core\\Field\\TypedData\\FieldItemDataDefinition\":2:{s:13:\"\0*\0definition\";a:2:{s:4:\"type\";s:15:\"field_item:link\";s:8:\"settings\";a:2:{s:5:\"title\";i:0;s:9:\"link_type\";i:17;}}s:18:\"\0*\0fieldDefinition\";r:475;}s:7:\"\0*\0type\";s:4:\"link\";s:9:\"\0*\0schema\";a:4:{s:7:\"columns\";a:3:{s:3:\"uri\";a:3:{s:11:\"description\";s:20:\"The URI of the link.\";s:4:\"type\";s:7:\"varchar\";s:6:\"length\";i:2048;}s:5:\"title\";a:3:{s:11:\"description\";s:14:\"The link text.\";s:4:\"type\";s:7:\"varchar\";s:6:\"length\";i:255;}s:7:\"options\";a:4:{s:11:\"description\";s:41:\"Serialized array of options for the link.\";s:4:\"type\";s:4:\"blob\";s:4:\"size\";s:3:\"big\";s:9:\"serialize\";b:1;}}s:7:\"indexes\";a:1:{s:3:\"uri\";a:1:{i:0;a:2:{i:0;s:3:\"uri\";i:1;i:30;}}}s:11:\"unique keys\";a:0:{}s:12:\"foreign keys\";a:0:{}}s:10:\"\0*\0indexes\";a:0:{}}s:8:\"external\";O:37:\"Drupal\\Core\\Field\\BaseFieldDefinition\":5:{s:13:\"\0*\0definition\";a:9:{s:5:\"label\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:8:\"External\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}s:11:\"description\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:120:\"A flag to indicate if the link points to a full URL starting with a protocol, like http:// (1 = external, 0 = internal).\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}s:13:\"default_value\";a:1:{i:0;a:1:{s:5:\"value\";b:0;}}s:12:\"revisionable\";b:1;s:8:\"provider\";s:17:\"menu_link_content\";s:10:\"field_name\";s:8:\"external\";s:11:\"entity_type\";s:17:\"menu_link_content\";s:6:\"bundle\";N;s:13:\"initial_value\";N;}s:17:\"\0*\0itemDefinition\";O:51:\"Drupal\\Core\\Field\\TypedData\\FieldItemDataDefinition\":2:{s:13:\"\0*\0definition\";a:2:{s:4:\"type\";s:18:\"field_item:boolean\";s:8:\"settings\";a:2:{s:8:\"on_label\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:2:\"On\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}s:9:\"off_label\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:3:\"Off\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}}}s:18:\"\0*\0fieldDefinition\";r:528;}s:7:\"\0*\0type\";s:7:\"boolean\";s:9:\"\0*\0schema\";a:4:{s:7:\"columns\";a:1:{s:5:\"value\";a:2:{s:4:\"type\";s:3:\"int\";s:4:\"size\";s:4:\"tiny\";}}s:11:\"unique keys\";a:0:{}s:7:\"indexes\";a:0:{}s:12:\"foreign keys\";a:0:{}}s:10:\"\0*\0indexes\";a:0:{}}s:10:\"rediscover\";O:37:\"Drupal\\Core\\Field\\BaseFieldDefinition\":5:{s:13:\"\0*\0definition\";a:7:{s:5:\"label\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:54:\"Indicates whether the menu link should be rediscovered\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}s:13:\"default_value\";a:1:{i:0;a:1:{s:5:\"value\";b:0;}}s:8:\"provider\";s:17:\"menu_link_content\";s:10:\"field_name\";s:10:\"rediscover\";s:11:\"entity_type\";s:17:\"menu_link_content\";s:6:\"bundle\";N;s:13:\"initial_value\";N;}s:17:\"\0*\0itemDefinition\";O:51:\"Drupal\\Core\\Field\\TypedData\\FieldItemDataDefinition\":2:{s:13:\"\0*\0definition\";a:2:{s:4:\"type\";s:18:\"field_item:boolean\";s:8:\"settings\";a:2:{s:8:\"on_label\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:2:\"On\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}s:9:\"off_label\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:3:\"Off\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}}}s:18:\"\0*\0fieldDefinition\";r:570;}s:7:\"\0*\0type\";s:7:\"boolean\";s:9:\"\0*\0schema\";a:4:{s:7:\"columns\";a:1:{s:5:\"value\";a:2:{s:4:\"type\";s:3:\"int\";s:4:\"size\";s:4:\"tiny\";}}s:11:\"unique keys\";a:0:{}s:7:\"indexes\";a:0:{}s:12:\"foreign keys\";a:0:{}}s:10:\"\0*\0indexes\";a:0:{}}s:6:\"weight\";O:37:\"Drupal\\Core\\Field\\BaseFieldDefinition\":5:{s:13:\"\0*\0definition\";a:9:{s:5:\"label\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:6:\"Weight\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}s:11:\"description\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:172:\"Link weight among links in the same menu at the same depth. In the menu, the links with high weight will sink and links with a low weight will be positioned nearer the top.\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}s:13:\"default_value\";a:1:{i:0;a:1:{s:5:\"value\";i:0;}}s:7:\"display\";a:2:{s:4:\"view\";a:1:{s:7:\"options\";a:3:{s:5:\"label\";s:6:\"hidden\";s:4:\"type\";s:14:\"number_integer\";s:6:\"weight\";i:0;}}s:4:\"form\";a:1:{s:7:\"options\";a:2:{s:4:\"type\";s:6:\"number\";s:6:\"weight\";i:20;}}}s:8:\"provider\";s:17:\"menu_link_content\";s:10:\"field_name\";s:6:\"weight\";s:11:\"entity_type\";s:17:\"menu_link_content\";s:6:\"bundle\";N;s:13:\"initial_value\";N;}s:17:\"\0*\0itemDefinition\";O:51:\"Drupal\\Core\\Field\\TypedData\\FieldItemDataDefinition\":2:{s:13:\"\0*\0definition\";a:2:{s:4:\"type\";s:18:\"field_item:integer\";s:8:\"settings\";a:6:{s:8:\"unsigned\";b:0;s:4:\"size\";s:6:\"normal\";s:3:\"min\";s:0:\"\";s:3:\"max\";s:0:\"\";s:6:\"prefix\";s:0:\"\";s:6:\"suffix\";s:0:\"\";}}s:18:\"\0*\0fieldDefinition\";r:607;}s:7:\"\0*\0type\";s:7:\"integer\";s:9:\"\0*\0schema\";a:4:{s:7:\"columns\";a:1:{s:5:\"value\";a:3:{s:4:\"type\";s:3:\"int\";s:8:\"unsigned\";b:0;s:4:\"size\";s:6:\"normal\";}}s:11:\"unique keys\";a:0:{}s:7:\"indexes\";a:0:{}s:12:\"foreign keys\";a:0:{}}s:10:\"\0*\0indexes\";a:0:{}}s:8:\"expanded\";O:37:\"Drupal\\Core\\Field\\BaseFieldDefinition\":5:{s:13:\"\0*\0definition\";a:9:{s:5:\"label\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:16:\"Show as expanded\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}s:11:\"description\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:164:\"If selected and this menu link has children, the menu will always appear expanded. This option may be overridden for the entire menu tree when placing a menu block.\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}s:13:\"default_value\";a:1:{i:0;a:1:{s:5:\"value\";b:0;}}s:7:\"display\";a:2:{s:4:\"view\";a:1:{s:7:\"options\";a:3:{s:5:\"label\";s:6:\"hidden\";s:4:\"type\";s:7:\"boolean\";s:6:\"weight\";i:0;}}s:4:\"form\";a:1:{s:7:\"options\";a:2:{s:8:\"settings\";a:1:{s:13:\"display_label\";b:1;}s:6:\"weight\";i:0;}}}s:8:\"provider\";s:17:\"menu_link_content\";s:10:\"field_name\";s:8:\"expanded\";s:11:\"entity_type\";s:17:\"menu_link_content\";s:6:\"bundle\";N;s:13:\"initial_value\";N;}s:17:\"\0*\0itemDefinition\";O:51:\"Drupal\\Core\\Field\\TypedData\\FieldItemDataDefinition\":2:{s:13:\"\0*\0definition\";a:2:{s:4:\"type\";s:18:\"field_item:boolean\";s:8:\"settings\";a:2:{s:8:\"on_label\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:2:\"On\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}s:9:\"off_label\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:3:\"Off\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}}}s:18:\"\0*\0fieldDefinition\";r:657;}s:7:\"\0*\0type\";s:7:\"boolean\";s:9:\"\0*\0schema\";a:4:{s:7:\"columns\";a:1:{s:5:\"value\";a:2:{s:4:\"type\";s:3:\"int\";s:4:\"size\";s:4:\"tiny\";}}s:11:\"unique keys\";a:0:{}s:7:\"indexes\";a:0:{}s:12:\"foreign keys\";a:0:{}}s:10:\"\0*\0indexes\";a:0:{}}s:6:\"parent\";O:37:\"Drupal\\Core\\Field\\BaseFieldDefinition\":5:{s:13:\"\0*\0definition\";a:7:{s:5:\"label\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:16:\"Parent plugin ID\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}s:11:\"description\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:94:\"The ID of the parent menu link plugin, or empty string when at the top level of the hierarchy.\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}s:8:\"provider\";s:17:\"menu_link_content\";s:10:\"field_name\";s:6:\"parent\";s:11:\"entity_type\";s:17:\"menu_link_content\";s:6:\"bundle\";N;s:13:\"initial_value\";N;}s:17:\"\0*\0itemDefinition\";O:51:\"Drupal\\Core\\Field\\TypedData\\FieldItemDataDefinition\":2:{s:13:\"\0*\0definition\";a:2:{s:4:\"type\";s:17:\"field_item:string\";s:8:\"settings\";a:3:{s:10:\"max_length\";i:255;s:8:\"is_ascii\";b:0;s:14:\"case_sensitive\";b:0;}}s:18:\"\0*\0fieldDefinition\";r:709;}s:7:\"\0*\0type\";s:6:\"string\";s:9:\"\0*\0schema\";a:4:{s:7:\"columns\";a:1:{s:5:\"value\";a:3:{s:4:\"type\";s:7:\"varchar\";s:6:\"length\";i:255;s:6:\"binary\";b:0;}}s:11:\"unique keys\";a:0:{}s:7:\"indexes\";a:0:{}s:12:\"foreign keys\";a:0:{}}s:10:\"\0*\0indexes\";a:0:{}}s:7:\"changed\";O:37:\"Drupal\\Core\\Field\\BaseFieldDefinition\":5:{s:13:\"\0*\0definition\";a:9:{s:5:\"label\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:7:\"Changed\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}s:11:\"description\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:44:\"The time that the menu link was last edited.\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}s:12:\"translatable\";b:1;s:12:\"revisionable\";b:1;s:8:\"provider\";s:17:\"menu_link_content\";s:10:\"field_name\";s:7:\"changed\";s:11:\"entity_type\";s:17:\"menu_link_content\";s:6:\"bundle\";N;s:13:\"initial_value\";N;}s:17:\"\0*\0itemDefinition\";O:51:\"Drupal\\Core\\Field\\TypedData\\FieldItemDataDefinition\":2:{s:13:\"\0*\0definition\";a:2:{s:4:\"type\";s:18:\"field_item:changed\";s:8:\"settings\";a:0:{}}s:18:\"\0*\0fieldDefinition\";r:743;}s:7:\"\0*\0type\";s:7:\"changed\";s:9:\"\0*\0schema\";a:4:{s:7:\"columns\";a:1:{s:5:\"value\";a:1:{s:4:\"type\";s:3:\"int\";}}s:11:\"unique keys\";a:0:{}s:7:\"indexes\";a:0:{}s:12:\"foreign keys\";a:0:{}}s:10:\"\0*\0indexes\";a:0:{}}s:16:\"default_langcode\";O:37:\"Drupal\\Core\\Field\\BaseFieldDefinition\":5:{s:13:\"\0*\0definition\";a:10:{s:5:\"label\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:19:\"Default translation\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}s:11:\"description\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:58:\"A flag indicating whether this is the default translation.\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}s:12:\"translatable\";b:1;s:12:\"revisionable\";b:1;s:13:\"default_value\";a:1:{i:0;a:1:{s:5:\"value\";b:1;}}s:8:\"provider\";s:17:\"menu_link_content\";s:10:\"field_name\";s:16:\"default_langcode\";s:11:\"entity_type\";s:17:\"menu_link_content\";s:6:\"bundle\";N;s:13:\"initial_value\";N;}s:17:\"\0*\0itemDefinition\";O:51:\"Drupal\\Core\\Field\\TypedData\\FieldItemDataDefinition\":2:{s:13:\"\0*\0definition\";a:2:{s:4:\"type\";s:18:\"field_item:boolean\";s:8:\"settings\";a:2:{s:8:\"on_label\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:2:\"On\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}s:9:\"off_label\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:3:\"Off\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}}}s:18:\"\0*\0fieldDefinition\";r:774;}s:7:\"\0*\0type\";s:7:\"boolean\";s:9:\"\0*\0schema\";a:4:{s:7:\"columns\";a:1:{s:5:\"value\";a:2:{s:4:\"type\";s:3:\"int\";s:4:\"size\";s:4:\"tiny\";}}s:11:\"unique keys\";a:0:{}s:7:\"indexes\";a:0:{}s:12:\"foreign keys\";a:0:{}}s:10:\"\0*\0indexes\";a:0:{}}s:16:\"revision_default\";O:37:\"Drupal\\Core\\Field\\BaseFieldDefinition\":5:{s:13:\"\0*\0definition\";a:11:{s:5:\"label\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:16:\"Default revision\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}s:11:\"description\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:72:\"A flag indicating whether this was a default revision when it was saved.\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}s:16:\"storage_required\";b:1;s:8:\"internal\";b:1;s:12:\"translatable\";b:0;s:12:\"revisionable\";b:1;s:8:\"provider\";s:17:\"menu_link_content\";s:10:\"field_name\";s:16:\"revision_default\";s:11:\"entity_type\";s:17:\"menu_link_content\";s:6:\"bundle\";N;s:13:\"initial_value\";N;}s:17:\"\0*\0itemDefinition\";O:51:\"Drupal\\Core\\Field\\TypedData\\FieldItemDataDefinition\":2:{s:13:\"\0*\0definition\";a:2:{s:4:\"type\";s:18:\"field_item:boolean\";s:8:\"settings\";a:2:{s:8:\"on_label\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:2:\"On\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}s:9:\"off_label\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:3:\"Off\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}}}s:18:\"\0*\0fieldDefinition\";r:817;}s:7:\"\0*\0type\";s:7:\"boolean\";s:9:\"\0*\0schema\";a:4:{s:7:\"columns\";a:1:{s:5:\"value\";a:2:{s:4:\"type\";s:3:\"int\";s:4:\"size\";s:4:\"tiny\";}}s:11:\"unique keys\";a:0:{}s:7:\"indexes\";a:0:{}s:12:\"foreign keys\";a:0:{}}s:10:\"\0*\0indexes\";a:0:{}}s:29:\"revision_translation_affected\";O:37:\"Drupal\\Core\\Field\\BaseFieldDefinition\":5:{s:13:\"\0*\0definition\";a:10:{s:5:\"label\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:29:\"Revision translation affected\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}s:11:\"description\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:72:\"Indicates if the last edit of a translation belongs to current revision.\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}s:9:\"read-only\";b:1;s:12:\"revisionable\";b:1;s:12:\"translatable\";b:1;s:8:\"provider\";s:17:\"menu_link_content\";s:10:\"field_name\";s:29:\"revision_translation_affected\";s:11:\"entity_type\";s:17:\"menu_link_content\";s:6:\"bundle\";N;s:13:\"initial_value\";N;}s:17:\"\0*\0itemDefinition\";O:51:\"Drupal\\Core\\Field\\TypedData\\FieldItemDataDefinition\":2:{s:13:\"\0*\0definition\";a:2:{s:4:\"type\";s:18:\"field_item:boolean\";s:8:\"settings\";a:2:{s:8:\"on_label\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:2:\"On\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}s:9:\"off_label\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:3:\"Off\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}}}s:18:\"\0*\0fieldDefinition\";r:859;}s:7:\"\0*\0type\";s:7:\"boolean\";s:9:\"\0*\0schema\";a:4:{s:7:\"columns\";a:1:{s:5:\"value\";a:2:{s:4:\"type\";s:3:\"int\";s:4:\"size\";s:4:\"tiny\";}}s:11:\"unique keys\";a:0:{}s:7:\"indexes\";a:0:{}s:12:\"foreign keys\";a:0:{}}s:10:\"\0*\0indexes\";a:0:{}}s:9:\"workspace\";O:37:\"Drupal\\Core\\Field\\BaseFieldDefinition\":5:{s:13:\"\0*\0definition\";a:10:{s:5:\"label\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:9:\"Workspace\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}s:11:\"description\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:54:\"Indicates the workspace that this revision belongs to.\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}s:8:\"internal\";b:1;s:12:\"translatable\";b:0;s:12:\"revisionable\";b:1;s:8:\"provider\";s:10:\"workspaces\";s:10:\"field_name\";s:9:\"workspace\";s:11:\"entity_type\";s:17:\"menu_link_content\";s:6:\"bundle\";N;s:13:\"initial_value\";N;}s:17:\"\0*\0itemDefinition\";O:51:\"Drupal\\Core\\Field\\TypedData\\FieldItemDataDefinition\":2:{s:13:\"\0*\0definition\";a:2:{s:4:\"type\";s:27:\"field_item:entity_reference\";s:8:\"settings\";a:3:{s:11:\"target_type\";s:9:\"workspace\";s:7:\"handler\";s:7:\"default\";s:16:\"handler_settings\";a:0:{}}}s:18:\"\0*\0fieldDefinition\";r:900;}s:7:\"\0*\0type\";s:16:\"entity_reference\";s:9:\"\0*\0schema\";a:4:{s:7:\"columns\";a:1:{s:9:\"target_id\";a:3:{s:11:\"description\";s:28:\"The ID of the target entity.\";s:4:\"type\";s:13:\"varchar_ascii\";s:6:\"length\";i:255;}}s:7:\"indexes\";a:1:{s:9:\"target_id\";a:1:{i:0;s:9:\"target_id\";}}s:11:\"unique keys\";a:0:{}s:12:\"foreign keys\";a:0:{}}s:10:\"\0*\0indexes\";a:0:{}}}",
  ],
  [
    'collection' => "entity.storage_schema.sql",
    'name' => "menu_link_content.field_schema_data.workspace",
    'value' => "a:1:{s:26:\"menu_link_content_revision\";a:2:{s:6:\"fields\";a:1:{s:9:\"workspace\";a:4:{s:11:\"description\";s:28:\"The ID of the target entity.\";s:4:\"type\";s:13:\"varchar_ascii\";s:6:\"length\";i:255;s:8:\"not null\";b:0;}}s:7:\"indexes\";a:1:{s:45:\"menu_link_content_field__workspace__target_id\";a:1:{i:0;s:9:\"workspace\";}}}}",
  ],
  [
    'collection' => "entity.definitions.installed",
    'name' => "node.entity_type",
    'value' => "O:36:\"Drupal\\Core\\Entity\\ContentEntityType\":41:{s:5:\"\0*\0id\";s:4:\"node\";s:8:\"\0*\0class\";s:23:\"Drupal\\node\\Entity\\Node\";s:11:\"\0*\0provider\";s:4:\"node\";s:15:\"\0*\0static_cache\";b:1;s:15:\"\0*\0render_cache\";b:1;s:19:\"\0*\0persistent_cache\";b:1;s:14:\"\0*\0entity_keys\";a:12:{s:2:\"id\";s:3:\"nid\";s:8:\"revision\";s:3:\"vid\";s:6:\"bundle\";s:4:\"type\";s:5:\"label\";s:5:\"title\";s:8:\"langcode\";s:8:\"langcode\";s:4:\"uuid\";s:4:\"uuid\";s:6:\"status\";s:6:\"status\";s:9:\"published\";s:6:\"status\";s:3:\"uid\";s:3:\"uid\";s:5:\"owner\";s:3:\"uid\";s:16:\"default_langcode\";s:16:\"default_langcode\";s:29:\"revision_translation_affected\";s:29:\"revision_translation_affected\";}s:16:\"\0*\0originalClass\";s:23:\"Drupal\\node\\Entity\\Node\";s:11:\"\0*\0handlers\";a:9:{s:7:\"storage\";s:23:\"Drupal\\node\\NodeStorage\";s:14:\"storage_schema\";s:29:\"Drupal\\node\\NodeStorageSchema\";s:12:\"view_builder\";s:27:\"Drupal\\node\\NodeViewBuilder\";s:6:\"access\";s:36:\"Drupal\\node\\NodeAccessControlHandler\";s:10:\"views_data\";s:25:\"Drupal\\node\\NodeViewsData\";s:4:\"form\";a:4:{s:7:\"default\";s:20:\"Drupal\\node\\NodeForm\";s:6:\"delete\";s:31:\"Drupal\\node\\Form\\NodeDeleteForm\";s:4:\"edit\";s:20:\"Drupal\\node\\NodeForm\";s:23:\"delete-multiple-confirm\";s:31:\"Drupal\\node\\Form\\DeleteMultiple\";}s:14:\"route_provider\";a:1:{s:4:\"html\";s:36:\"Drupal\\node\\Entity\\NodeRouteProvider\";}s:12:\"list_builder\";s:27:\"Drupal\\node\\NodeListBuilder\";s:11:\"translation\";s:34:\"Drupal\\node\\NodeTranslationHandler\";}s:19:\"\0*\0admin_permission\";N;s:24:\"\0*\0collection_permission\";s:23:\"access content overview\";s:25:\"\0*\0permission_granularity\";s:6:\"bundle\";s:8:\"\0*\0links\";a:7:{s:9:\"canonical\";s:12:\"/node/{node}\";s:11:\"delete-form\";s:19:\"/node/{node}/delete\";s:20:\"delete-multiple-form\";s:26:\"/admin/content/node/delete\";s:9:\"edit-form\";s:17:\"/node/{node}/edit\";s:15:\"version-history\";s:22:\"/node/{node}/revisions\";s:8:\"revision\";s:43:\"/node/{node}/revisions/{node_revision}/view\";s:6:\"create\";s:5:\"/node\";}s:21:\"\0*\0bundle_entity_type\";s:9:\"node_type\";s:12:\"\0*\0bundle_of\";N;s:15:\"\0*\0bundle_label\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:12:\"Content type\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}s:13:\"\0*\0base_table\";s:4:\"node\";s:22:\"\0*\0revision_data_table\";s:19:\"node_field_revision\";s:17:\"\0*\0revision_table\";s:13:\"node_revision\";s:13:\"\0*\0data_table\";s:15:\"node_field_data\";s:11:\"\0*\0internal\";b:0;s:15:\"\0*\0translatable\";b:1;s:19:\"\0*\0show_revision_ui\";b:1;s:8:\"\0*\0label\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:7:\"Content\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}s:19:\"\0*\0label_collection\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:7:\"Content\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}s:17:\"\0*\0label_singular\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:12:\"content item\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}s:15:\"\0*\0label_plural\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:13:\"content items\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}s:14:\"\0*\0label_count\";a:3:{s:8:\"singular\";s:19:\"@count content item\";s:6:\"plural\";s:20:\"@count content items\";s:7:\"context\";N;}s:15:\"\0*\0uri_callback\";N;s:8:\"\0*\0group\";s:7:\"content\";s:14:\"\0*\0group_label\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:7:\"Content\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:1:{s:7:\"context\";s:17:\"Entity type group\";}}s:22:\"\0*\0field_ui_base_route\";s:26:\"entity.node_type.edit_form\";s:26:\"\0*\0common_reference_target\";b:1;s:22:\"\0*\0list_cache_contexts\";a:1:{i:0;s:21:\"user.node_grants:view\";}s:18:\"\0*\0list_cache_tags\";a:1:{i:0;s:9:\"node_list\";}s:14:\"\0*\0constraints\";a:2:{s:13:\"EntityChanged\";N;s:26:\"EntityUntranslatableFields\";N;}s:13:\"\0*\0additional\";a:0:{}s:14:\"\0*\0_serviceIds\";a:0:{}s:18:\"\0*\0_entityStorages\";a:0:{}s:20:\"\0*\0stringTranslation\";N;s:25:\"\0*\0revision_metadata_keys\";a:5:{s:13:\"revision_user\";s:12:\"revision_uid\";s:16:\"revision_created\";s:18:\"revision_timestamp\";s:20:\"revision_log_message\";s:12:\"revision_log\";s:16:\"revision_default\";s:16:\"revision_default\";s:9:\"workspace\";s:9:\"workspace\";}}",
  ],
  [
    'collection' => "entity.definitions.installed",
    'name' => "node.field_storage_definitions",
    'value' => "a:23:{s:3:\"nid\";O:37:\"Drupal\\Core\\Field\\BaseFieldDefinition\":5:{s:13:\"\0*\0definition\";a:7:{s:5:\"label\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:2:\"ID\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}s:9:\"read-only\";b:1;s:8:\"provider\";s:4:\"node\";s:10:\"field_name\";s:3:\"nid\";s:11:\"entity_type\";s:4:\"node\";s:6:\"bundle\";N;s:13:\"initial_value\";N;}s:17:\"\0*\0itemDefinition\";O:51:\"Drupal\\Core\\Field\\TypedData\\FieldItemDataDefinition\":2:{s:13:\"\0*\0definition\";a:2:{s:4:\"type\";s:18:\"field_item:integer\";s:8:\"settings\";a:6:{s:8:\"unsigned\";b:1;s:4:\"size\";s:6:\"normal\";s:3:\"min\";s:0:\"\";s:3:\"max\";s:0:\"\";s:6:\"prefix\";s:0:\"\";s:6:\"suffix\";s:0:\"\";}}s:18:\"\0*\0fieldDefinition\";r:2;}s:7:\"\0*\0type\";s:7:\"integer\";s:9:\"\0*\0schema\";a:4:{s:7:\"columns\";a:1:{s:5:\"value\";a:3:{s:4:\"type\";s:3:\"int\";s:8:\"unsigned\";b:1;s:4:\"size\";s:6:\"normal\";}}s:11:\"unique keys\";a:0:{}s:7:\"indexes\";a:0:{}s:12:\"foreign keys\";a:0:{}}s:10:\"\0*\0indexes\";a:0:{}}s:4:\"uuid\";O:37:\"Drupal\\Core\\Field\\BaseFieldDefinition\":5:{s:13:\"\0*\0definition\";a:7:{s:5:\"label\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:4:\"UUID\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}s:9:\"read-only\";b:1;s:8:\"provider\";s:4:\"node\";s:10:\"field_name\";s:4:\"uuid\";s:11:\"entity_type\";s:4:\"node\";s:6:\"bundle\";N;s:13:\"initial_value\";N;}s:17:\"\0*\0itemDefinition\";O:51:\"Drupal\\Core\\Field\\TypedData\\FieldItemDataDefinition\":2:{s:13:\"\0*\0definition\";a:2:{s:4:\"type\";s:15:\"field_item:uuid\";s:8:\"settings\";a:3:{s:10:\"max_length\";i:128;s:8:\"is_ascii\";b:1;s:14:\"case_sensitive\";b:0;}}s:18:\"\0*\0fieldDefinition\";r:36;}s:7:\"\0*\0type\";s:4:\"uuid\";s:9:\"\0*\0schema\";a:4:{s:7:\"columns\";a:1:{s:5:\"value\";a:3:{s:4:\"type\";s:13:\"varchar_ascii\";s:6:\"length\";i:128;s:6:\"binary\";b:0;}}s:11:\"unique keys\";a:1:{s:5:\"value\";a:1:{i:0;s:5:\"value\";}}s:7:\"indexes\";a:0:{}s:12:\"foreign keys\";a:0:{}}s:10:\"\0*\0indexes\";a:0:{}}s:3:\"vid\";O:37:\"Drupal\\Core\\Field\\BaseFieldDefinition\":5:{s:13:\"\0*\0definition\";a:7:{s:5:\"label\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:11:\"Revision ID\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}s:9:\"read-only\";b:1;s:8:\"provider\";s:4:\"node\";s:10:\"field_name\";s:3:\"vid\";s:11:\"entity_type\";s:4:\"node\";s:6:\"bundle\";N;s:13:\"initial_value\";N;}s:17:\"\0*\0itemDefinition\";O:51:\"Drupal\\Core\\Field\\TypedData\\FieldItemDataDefinition\":2:{s:13:\"\0*\0definition\";a:2:{s:4:\"type\";s:18:\"field_item:integer\";s:8:\"settings\";a:6:{s:8:\"unsigned\";b:1;s:4:\"size\";s:6:\"normal\";s:3:\"min\";s:0:\"\";s:3:\"max\";s:0:\"\";s:6:\"prefix\";s:0:\"\";s:6:\"suffix\";s:0:\"\";}}s:18:\"\0*\0fieldDefinition\";r:69;}s:7:\"\0*\0type\";s:7:\"integer\";s:9:\"\0*\0schema\";a:4:{s:7:\"columns\";a:1:{s:5:\"value\";a:3:{s:4:\"type\";s:3:\"int\";s:8:\"unsigned\";b:1;s:4:\"size\";s:6:\"normal\";}}s:11:\"unique keys\";a:0:{}s:7:\"indexes\";a:0:{}s:12:\"foreign keys\";a:0:{}}s:10:\"\0*\0indexes\";a:0:{}}s:8:\"langcode\";O:37:\"Drupal\\Core\\Field\\BaseFieldDefinition\":5:{s:13:\"\0*\0definition\";a:9:{s:5:\"label\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:8:\"Language\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}s:7:\"display\";a:2:{s:4:\"view\";a:1:{s:7:\"options\";a:1:{s:6:\"region\";s:6:\"hidden\";}}s:4:\"form\";a:1:{s:7:\"options\";a:2:{s:4:\"type\";s:15:\"language_select\";s:6:\"weight\";i:2;}}}s:12:\"revisionable\";b:1;s:12:\"translatable\";b:1;s:8:\"provider\";s:4:\"node\";s:10:\"field_name\";s:8:\"langcode\";s:11:\"entity_type\";s:4:\"node\";s:6:\"bundle\";N;s:13:\"initial_value\";N;}s:17:\"\0*\0itemDefinition\";O:51:\"Drupal\\Core\\Field\\TypedData\\FieldItemDataDefinition\":2:{s:13:\"\0*\0definition\";a:2:{s:4:\"type\";s:19:\"field_item:language\";s:8:\"settings\";a:0:{}}s:18:\"\0*\0fieldDefinition\";r:103;}s:7:\"\0*\0type\";s:8:\"language\";s:9:\"\0*\0schema\";a:4:{s:7:\"columns\";a:1:{s:5:\"value\";a:2:{s:4:\"type\";s:13:\"varchar_ascii\";s:6:\"length\";i:12;}}s:11:\"unique keys\";a:0:{}s:7:\"indexes\";a:0:{}s:12:\"foreign keys\";a:0:{}}s:10:\"\0*\0indexes\";a:0:{}}s:4:\"type\";O:37:\"Drupal\\Core\\Field\\BaseFieldDefinition\":5:{s:13:\"\0*\0definition\";a:8:{s:5:\"label\";s:12:\"Content type\";s:8:\"required\";b:1;s:9:\"read-only\";b:1;s:8:\"provider\";s:4:\"node\";s:10:\"field_name\";s:4:\"type\";s:11:\"entity_type\";s:4:\"node\";s:6:\"bundle\";N;s:13:\"initial_value\";N;}s:17:\"\0*\0itemDefinition\";O:51:\"Drupal\\Core\\Field\\TypedData\\FieldItemDataDefinition\":2:{s:13:\"\0*\0definition\";a:2:{s:4:\"type\";s:27:\"field_item:entity_reference\";s:8:\"settings\";a:3:{s:11:\"target_type\";s:9:\"node_type\";s:7:\"handler\";s:7:\"default\";s:16:\"handler_settings\";a:0:{}}}s:18:\"\0*\0fieldDefinition\";r:139;}s:7:\"\0*\0type\";s:16:\"entity_reference\";s:9:\"\0*\0schema\";a:4:{s:7:\"columns\";a:1:{s:9:\"target_id\";a:3:{s:11:\"description\";s:28:\"The ID of the target entity.\";s:4:\"type\";s:13:\"varchar_ascii\";s:6:\"length\";i:32;}}s:7:\"indexes\";a:1:{s:9:\"target_id\";a:1:{i:0;s:9:\"target_id\";}}s:11:\"unique keys\";a:0:{}s:12:\"foreign keys\";a:0:{}}s:10:\"\0*\0indexes\";a:0:{}}s:18:\"revision_timestamp\";O:37:\"Drupal\\Core\\Field\\BaseFieldDefinition\":5:{s:13:\"\0*\0definition\";a:8:{s:5:\"label\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:20:\"Revision create time\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}s:11:\"description\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:47:\"The time that the current revision was created.\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}s:12:\"revisionable\";b:1;s:8:\"provider\";s:4:\"node\";s:10:\"field_name\";s:18:\"revision_timestamp\";s:11:\"entity_type\";s:4:\"node\";s:6:\"bundle\";N;s:13:\"initial_value\";N;}s:17:\"\0*\0itemDefinition\";O:51:\"Drupal\\Core\\Field\\TypedData\\FieldItemDataDefinition\":2:{s:13:\"\0*\0definition\";a:2:{s:4:\"type\";s:18:\"field_item:created\";s:8:\"settings\";a:0:{}}s:18:\"\0*\0fieldDefinition\";r:170;}s:7:\"\0*\0type\";s:7:\"created\";s:9:\"\0*\0schema\";a:4:{s:7:\"columns\";a:1:{s:5:\"value\";a:1:{s:4:\"type\";s:3:\"int\";}}s:11:\"unique keys\";a:0:{}s:7:\"indexes\";a:0:{}s:12:\"foreign keys\";a:0:{}}s:10:\"\0*\0indexes\";a:0:{}}s:12:\"revision_uid\";O:37:\"Drupal\\Core\\Field\\BaseFieldDefinition\":5:{s:13:\"\0*\0definition\";a:8:{s:5:\"label\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:13:\"Revision user\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}s:11:\"description\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:50:\"The user ID of the author of the current revision.\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}s:12:\"revisionable\";b:1;s:8:\"provider\";s:4:\"node\";s:10:\"field_name\";s:12:\"revision_uid\";s:11:\"entity_type\";s:4:\"node\";s:6:\"bundle\";N;s:13:\"initial_value\";N;}s:17:\"\0*\0itemDefinition\";O:51:\"Drupal\\Core\\Field\\TypedData\\FieldItemDataDefinition\":2:{s:13:\"\0*\0definition\";a:2:{s:4:\"type\";s:27:\"field_item:entity_reference\";s:8:\"settings\";a:3:{s:11:\"target_type\";s:4:\"user\";s:7:\"handler\";s:7:\"default\";s:16:\"handler_settings\";a:0:{}}}s:18:\"\0*\0fieldDefinition\";r:200;}s:7:\"\0*\0type\";s:16:\"entity_reference\";s:9:\"\0*\0schema\";a:4:{s:7:\"columns\";a:1:{s:9:\"target_id\";a:3:{s:11:\"description\";s:28:\"The ID of the target entity.\";s:4:\"type\";s:3:\"int\";s:8:\"unsigned\";b:1;}}s:7:\"indexes\";a:1:{s:9:\"target_id\";a:1:{i:0;s:9:\"target_id\";}}s:11:\"unique keys\";a:0:{}s:12:\"foreign keys\";a:0:{}}s:10:\"\0*\0indexes\";a:0:{}}s:12:\"revision_log\";O:37:\"Drupal\\Core\\Field\\BaseFieldDefinition\":5:{s:13:\"\0*\0definition\";a:10:{s:5:\"label\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:20:\"Revision log message\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}s:11:\"description\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:43:\"Briefly describe the changes you have made.\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}s:12:\"revisionable\";b:1;s:13:\"default_value\";a:1:{i:0;a:1:{s:5:\"value\";s:0:\"\";}}s:7:\"display\";a:1:{s:4:\"form\";a:1:{s:7:\"options\";a:3:{s:4:\"type\";s:15:\"string_textarea\";s:6:\"weight\";i:25;s:8:\"settings\";a:1:{s:4:\"rows\";i:4;}}}}s:8:\"provider\";s:4:\"node\";s:10:\"field_name\";s:12:\"revision_log\";s:11:\"entity_type\";s:4:\"node\";s:6:\"bundle\";N;s:13:\"initial_value\";N;}s:17:\"\0*\0itemDefinition\";O:51:\"Drupal\\Core\\Field\\TypedData\\FieldItemDataDefinition\":2:{s:13:\"\0*\0definition\";a:2:{s:4:\"type\";s:22:\"field_item:string_long\";s:8:\"settings\";a:1:{s:14:\"case_sensitive\";b:0;}}s:18:\"\0*\0fieldDefinition\";r:237;}s:7:\"\0*\0type\";s:11:\"string_long\";s:9:\"\0*\0schema\";a:4:{s:7:\"columns\";a:1:{s:5:\"value\";a:2:{s:4:\"type\";s:4:\"text\";s:4:\"size\";s:3:\"big\";}}s:11:\"unique keys\";a:0:{}s:7:\"indexes\";a:0:{}s:12:\"foreign keys\";a:0:{}}s:10:\"\0*\0indexes\";a:0:{}}s:6:\"status\";O:37:\"Drupal\\Core\\Field\\BaseFieldDefinition\":5:{s:13:\"\0*\0definition\";a:10:{s:5:\"label\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:9:\"Published\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}s:12:\"revisionable\";b:1;s:12:\"translatable\";b:1;s:13:\"default_value\";a:1:{i:0;a:1:{s:5:\"value\";b:1;}}s:7:\"display\";a:1:{s:4:\"form\";a:2:{s:7:\"options\";a:3:{s:4:\"type\";s:16:\"boolean_checkbox\";s:8:\"settings\";a:1:{s:13:\"display_label\";b:1;}s:6:\"weight\";i:120;}s:12:\"configurable\";b:1;}}s:8:\"provider\";s:4:\"node\";s:10:\"field_name\";s:6:\"status\";s:11:\"entity_type\";s:4:\"node\";s:6:\"bundle\";N;s:13:\"initial_value\";N;}s:17:\"\0*\0itemDefinition\";O:51:\"Drupal\\Core\\Field\\TypedData\\FieldItemDataDefinition\":2:{s:13:\"\0*\0definition\";a:2:{s:4:\"type\";s:18:\"field_item:boolean\";s:8:\"settings\";a:2:{s:8:\"on_label\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:2:\"On\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}s:9:\"off_label\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:3:\"Off\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}}}s:18:\"\0*\0fieldDefinition\";r:279;}s:7:\"\0*\0type\";s:7:\"boolean\";s:9:\"\0*\0schema\";a:4:{s:7:\"columns\";a:1:{s:5:\"value\";a:2:{s:4:\"type\";s:3:\"int\";s:4:\"size\";s:4:\"tiny\";}}s:11:\"unique keys\";a:0:{}s:7:\"indexes\";a:0:{}s:12:\"foreign keys\";a:0:{}}s:10:\"\0*\0indexes\";a:0:{}}s:3:\"uid\";O:37:\"Drupal\\Core\\Field\\BaseFieldDefinition\":5:{s:13:\"\0*\0definition\";a:11:{s:5:\"label\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:11:\"Authored by\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}s:12:\"translatable\";b:1;s:22:\"default_value_callback\";s:46:\"Drupal\\node\\Entity\\Node::getDefaultEntityOwner\";s:11:\"description\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:35:\"The username of the content author.\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}s:12:\"revisionable\";b:1;s:7:\"display\";a:2:{s:4:\"view\";a:1:{s:7:\"options\";a:3:{s:5:\"label\";s:6:\"hidden\";s:4:\"type\";s:6:\"author\";s:6:\"weight\";i:0;}}s:4:\"form\";a:2:{s:7:\"options\";a:3:{s:4:\"type\";s:29:\"entity_reference_autocomplete\";s:6:\"weight\";i:5;s:8:\"settings\";a:3:{s:14:\"match_operator\";s:8:\"CONTAINS\";s:4:\"size\";s:2:\"60\";s:11:\"placeholder\";s:0:\"\";}}s:12:\"configurable\";b:1;}}s:8:\"provider\";s:4:\"node\";s:10:\"field_name\";s:3:\"uid\";s:11:\"entity_type\";s:4:\"node\";s:6:\"bundle\";N;s:13:\"initial_value\";N;}s:17:\"\0*\0itemDefinition\";O:51:\"Drupal\\Core\\Field\\TypedData\\FieldItemDataDefinition\":2:{s:13:\"\0*\0definition\";a:2:{s:4:\"type\";s:27:\"field_item:entity_reference\";s:8:\"settings\";a:3:{s:11:\"target_type\";s:4:\"user\";s:7:\"handler\";s:7:\"default\";s:16:\"handler_settings\";a:0:{}}}s:18:\"\0*\0fieldDefinition\";r:326;}s:7:\"\0*\0type\";s:16:\"entity_reference\";s:9:\"\0*\0schema\";a:4:{s:7:\"columns\";a:1:{s:9:\"target_id\";a:3:{s:11:\"description\";s:28:\"The ID of the target entity.\";s:4:\"type\";s:3:\"int\";s:8:\"unsigned\";b:1;}}s:7:\"indexes\";a:1:{s:9:\"target_id\";a:1:{i:0;s:9:\"target_id\";}}s:11:\"unique keys\";a:0:{}s:12:\"foreign keys\";a:0:{}}s:10:\"\0*\0indexes\";a:0:{}}s:5:\"title\";O:37:\"Drupal\\Core\\Field\\BaseFieldDefinition\":5:{s:13:\"\0*\0definition\";a:10:{s:5:\"label\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:5:\"Title\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}s:8:\"required\";b:1;s:12:\"translatable\";b:1;s:12:\"revisionable\";b:1;s:7:\"display\";a:2:{s:4:\"view\";a:1:{s:7:\"options\";a:3:{s:5:\"label\";s:6:\"hidden\";s:4:\"type\";s:6:\"string\";s:6:\"weight\";i:-5;}}s:4:\"form\";a:2:{s:7:\"options\";a:2:{s:4:\"type\";s:16:\"string_textfield\";s:6:\"weight\";i:-5;}s:12:\"configurable\";b:1;}}s:8:\"provider\";s:4:\"node\";s:10:\"field_name\";s:5:\"title\";s:11:\"entity_type\";s:4:\"node\";s:6:\"bundle\";N;s:13:\"initial_value\";N;}s:17:\"\0*\0itemDefinition\";O:51:\"Drupal\\Core\\Field\\TypedData\\FieldItemDataDefinition\":2:{s:13:\"\0*\0definition\";a:2:{s:4:\"type\";s:17:\"field_item:string\";s:8:\"settings\";a:3:{s:10:\"max_length\";i:255;s:8:\"is_ascii\";b:0;s:14:\"case_sensitive\";b:0;}}s:18:\"\0*\0fieldDefinition\";r:380;}s:7:\"\0*\0type\";s:6:\"string\";s:9:\"\0*\0schema\";a:4:{s:7:\"columns\";a:1:{s:5:\"value\";a:3:{s:4:\"type\";s:7:\"varchar\";s:6:\"length\";i:255;s:6:\"binary\";b:0;}}s:11:\"unique keys\";a:0:{}s:7:\"indexes\";a:0:{}s:12:\"foreign keys\";a:0:{}}s:10:\"\0*\0indexes\";a:0:{}}s:7:\"created\";O:37:\"Drupal\\Core\\Field\\BaseFieldDefinition\":5:{s:13:\"\0*\0definition\";a:10:{s:5:\"label\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:11:\"Authored on\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}s:11:\"description\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:47:\"The date and time that the content was created.\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}s:12:\"revisionable\";b:1;s:12:\"translatable\";b:1;s:7:\"display\";a:2:{s:4:\"view\";a:1:{s:7:\"options\";a:3:{s:5:\"label\";s:6:\"hidden\";s:4:\"type\";s:9:\"timestamp\";s:6:\"weight\";i:0;}}s:4:\"form\";a:2:{s:7:\"options\";a:2:{s:4:\"type\";s:18:\"datetime_timestamp\";s:6:\"weight\";i:10;}s:12:\"configurable\";b:1;}}s:8:\"provider\";s:4:\"node\";s:10:\"field_name\";s:7:\"created\";s:11:\"entity_type\";s:4:\"node\";s:6:\"bundle\";N;s:13:\"initial_value\";N;}s:17:\"\0*\0itemDefinition\";O:51:\"Drupal\\Core\\Field\\TypedData\\FieldItemDataDefinition\":2:{s:13:\"\0*\0definition\";a:2:{s:4:\"type\";s:18:\"field_item:created\";s:8:\"settings\";a:0:{}}s:18:\"\0*\0fieldDefinition\";r:424;}s:7:\"\0*\0type\";s:7:\"created\";s:9:\"\0*\0schema\";a:4:{s:7:\"columns\";a:1:{s:5:\"value\";a:1:{s:4:\"type\";s:3:\"int\";}}s:11:\"unique keys\";a:0:{}s:7:\"indexes\";a:0:{}s:12:\"foreign keys\";a:0:{}}s:10:\"\0*\0indexes\";a:0:{}}s:7:\"changed\";O:37:\"Drupal\\Core\\Field\\BaseFieldDefinition\":5:{s:13:\"\0*\0definition\";a:9:{s:5:\"label\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:7:\"Changed\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}s:11:\"description\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:39:\"The time that the node was last edited.\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}s:12:\"revisionable\";b:1;s:12:\"translatable\";b:1;s:8:\"provider\";s:4:\"node\";s:10:\"field_name\";s:7:\"changed\";s:11:\"entity_type\";s:4:\"node\";s:6:\"bundle\";N;s:13:\"initial_value\";N;}s:17:\"\0*\0itemDefinition\";O:51:\"Drupal\\Core\\Field\\TypedData\\FieldItemDataDefinition\":2:{s:13:\"\0*\0definition\";a:2:{s:4:\"type\";s:18:\"field_item:changed\";s:8:\"settings\";a:0:{}}s:18:\"\0*\0fieldDefinition\";r:466;}s:7:\"\0*\0type\";s:7:\"changed\";s:9:\"\0*\0schema\";a:4:{s:7:\"columns\";a:1:{s:5:\"value\";a:1:{s:4:\"type\";s:3:\"int\";}}s:11:\"unique keys\";a:0:{}s:7:\"indexes\";a:0:{}s:12:\"foreign keys\";a:0:{}}s:10:\"\0*\0indexes\";a:0:{}}s:7:\"promote\";O:37:\"Drupal\\Core\\Field\\BaseFieldDefinition\":5:{s:13:\"\0*\0definition\";a:10:{s:5:\"label\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:22:\"Promoted to front page\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}s:12:\"revisionable\";b:1;s:12:\"translatable\";b:1;s:13:\"default_value\";a:1:{i:0;a:1:{s:5:\"value\";b:1;}}s:7:\"display\";a:1:{s:4:\"form\";a:2:{s:7:\"options\";a:3:{s:4:\"type\";s:16:\"boolean_checkbox\";s:8:\"settings\";a:1:{s:13:\"display_label\";b:1;}s:6:\"weight\";i:15;}s:12:\"configurable\";b:1;}}s:8:\"provider\";s:4:\"node\";s:10:\"field_name\";s:7:\"promote\";s:11:\"entity_type\";s:4:\"node\";s:6:\"bundle\";N;s:13:\"initial_value\";N;}s:17:\"\0*\0itemDefinition\";O:51:\"Drupal\\Core\\Field\\TypedData\\FieldItemDataDefinition\":2:{s:13:\"\0*\0definition\";a:2:{s:4:\"type\";s:18:\"field_item:boolean\";s:8:\"settings\";a:2:{s:8:\"on_label\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:2:\"On\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}s:9:\"off_label\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:3:\"Off\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}}}s:18:\"\0*\0fieldDefinition\";r:497;}s:7:\"\0*\0type\";s:7:\"boolean\";s:9:\"\0*\0schema\";a:4:{s:7:\"columns\";a:1:{s:5:\"value\";a:2:{s:4:\"type\";s:3:\"int\";s:4:\"size\";s:4:\"tiny\";}}s:11:\"unique keys\";a:0:{}s:7:\"indexes\";a:0:{}s:12:\"foreign keys\";a:0:{}}s:10:\"\0*\0indexes\";a:0:{}}s:6:\"sticky\";O:37:\"Drupal\\Core\\Field\\BaseFieldDefinition\":5:{s:13:\"\0*\0definition\";a:10:{s:5:\"label\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:22:\"Sticky at top of lists\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}s:12:\"revisionable\";b:1;s:12:\"translatable\";b:1;s:13:\"default_value\";a:1:{i:0;a:1:{s:5:\"value\";b:0;}}s:7:\"display\";a:1:{s:4:\"form\";a:2:{s:7:\"options\";a:3:{s:4:\"type\";s:16:\"boolean_checkbox\";s:8:\"settings\";a:1:{s:13:\"display_label\";b:1;}s:6:\"weight\";i:16;}s:12:\"configurable\";b:1;}}s:8:\"provider\";s:4:\"node\";s:10:\"field_name\";s:6:\"sticky\";s:11:\"entity_type\";s:4:\"node\";s:6:\"bundle\";N;s:13:\"initial_value\";N;}s:17:\"\0*\0itemDefinition\";O:51:\"Drupal\\Core\\Field\\TypedData\\FieldItemDataDefinition\":2:{s:13:\"\0*\0definition\";a:2:{s:4:\"type\";s:18:\"field_item:boolean\";s:8:\"settings\";a:2:{s:8:\"on_label\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:2:\"On\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}s:9:\"off_label\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:3:\"Off\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}}}s:18:\"\0*\0fieldDefinition\";r:544;}s:7:\"\0*\0type\";s:7:\"boolean\";s:9:\"\0*\0schema\";a:4:{s:7:\"columns\";a:1:{s:5:\"value\";a:2:{s:4:\"type\";s:3:\"int\";s:4:\"size\";s:4:\"tiny\";}}s:11:\"unique keys\";a:0:{}s:7:\"indexes\";a:0:{}s:12:\"foreign keys\";a:0:{}}s:10:\"\0*\0indexes\";a:0:{}}s:16:\"default_langcode\";O:37:\"Drupal\\Core\\Field\\BaseFieldDefinition\":5:{s:13:\"\0*\0definition\";a:10:{s:5:\"label\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:19:\"Default translation\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}s:11:\"description\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:58:\"A flag indicating whether this is the default translation.\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}s:12:\"translatable\";b:1;s:12:\"revisionable\";b:1;s:13:\"default_value\";a:1:{i:0;a:1:{s:5:\"value\";b:1;}}s:8:\"provider\";s:4:\"node\";s:10:\"field_name\";s:16:\"default_langcode\";s:11:\"entity_type\";s:4:\"node\";s:6:\"bundle\";N;s:13:\"initial_value\";N;}s:17:\"\0*\0itemDefinition\";O:51:\"Drupal\\Core\\Field\\TypedData\\FieldItemDataDefinition\":2:{s:13:\"\0*\0definition\";a:2:{s:4:\"type\";s:18:\"field_item:boolean\";s:8:\"settings\";a:2:{s:8:\"on_label\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:2:\"On\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}s:9:\"off_label\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:3:\"Off\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}}}s:18:\"\0*\0fieldDefinition\";r:591;}s:7:\"\0*\0type\";s:7:\"boolean\";s:9:\"\0*\0schema\";a:4:{s:7:\"columns\";a:1:{s:5:\"value\";a:2:{s:4:\"type\";s:3:\"int\";s:4:\"size\";s:4:\"tiny\";}}s:11:\"unique keys\";a:0:{}s:7:\"indexes\";a:0:{}s:12:\"foreign keys\";a:0:{}}s:10:\"\0*\0indexes\";a:0:{}}s:16:\"revision_default\";O:37:\"Drupal\\Core\\Field\\BaseFieldDefinition\":5:{s:13:\"\0*\0definition\";a:11:{s:5:\"label\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:16:\"Default revision\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}s:11:\"description\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:72:\"A flag indicating whether this was a default revision when it was saved.\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}s:16:\"storage_required\";b:1;s:8:\"internal\";b:1;s:12:\"translatable\";b:0;s:12:\"revisionable\";b:1;s:8:\"provider\";s:4:\"node\";s:10:\"field_name\";s:16:\"revision_default\";s:11:\"entity_type\";s:4:\"node\";s:6:\"bundle\";N;s:13:\"initial_value\";N;}s:17:\"\0*\0itemDefinition\";O:51:\"Drupal\\Core\\Field\\TypedData\\FieldItemDataDefinition\":2:{s:13:\"\0*\0definition\";a:2:{s:4:\"type\";s:18:\"field_item:boolean\";s:8:\"settings\";a:2:{s:8:\"on_label\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:2:\"On\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}s:9:\"off_label\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:3:\"Off\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}}}s:18:\"\0*\0fieldDefinition\";r:634;}s:7:\"\0*\0type\";s:7:\"boolean\";s:9:\"\0*\0schema\";a:4:{s:7:\"columns\";a:1:{s:5:\"value\";a:2:{s:4:\"type\";s:3:\"int\";s:4:\"size\";s:4:\"tiny\";}}s:11:\"unique keys\";a:0:{}s:7:\"indexes\";a:0:{}s:12:\"foreign keys\";a:0:{}}s:10:\"\0*\0indexes\";a:0:{}}s:29:\"revision_translation_affected\";O:37:\"Drupal\\Core\\Field\\BaseFieldDefinition\":5:{s:13:\"\0*\0definition\";a:10:{s:5:\"label\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:29:\"Revision translation affected\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}s:11:\"description\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:72:\"Indicates if the last edit of a translation belongs to current revision.\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}s:9:\"read-only\";b:1;s:12:\"revisionable\";b:1;s:12:\"translatable\";b:1;s:8:\"provider\";s:4:\"node\";s:10:\"field_name\";s:29:\"revision_translation_affected\";s:11:\"entity_type\";s:4:\"node\";s:6:\"bundle\";N;s:13:\"initial_value\";N;}s:17:\"\0*\0itemDefinition\";O:51:\"Drupal\\Core\\Field\\TypedData\\FieldItemDataDefinition\":2:{s:13:\"\0*\0definition\";a:2:{s:4:\"type\";s:18:\"field_item:boolean\";s:8:\"settings\";a:2:{s:8:\"on_label\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:2:\"On\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}s:9:\"off_label\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:3:\"Off\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}}}s:18:\"\0*\0fieldDefinition\";r:676;}s:7:\"\0*\0type\";s:7:\"boolean\";s:9:\"\0*\0schema\";a:4:{s:7:\"columns\";a:1:{s:5:\"value\";a:2:{s:4:\"type\";s:3:\"int\";s:4:\"size\";s:4:\"tiny\";}}s:11:\"unique keys\";a:0:{}s:7:\"indexes\";a:0:{}s:12:\"foreign keys\";a:0:{}}s:10:\"\0*\0indexes\";a:0:{}}s:4:\"body\";O:38:\"Drupal\\field\\Entity\\FieldStorageConfig\":30:{s:15:\"\0*\0entityTypeId\";s:20:\"field_storage_config\";s:15:\"\0*\0enforceIsNew\";b:1;s:12:\"\0*\0typedData\";N;s:16:\"\0*\0cacheContexts\";a:0:{}s:12:\"\0*\0cacheTags\";a:0:{}s:14:\"\0*\0cacheMaxAge\";i:-1;s:14:\"\0*\0_serviceIds\";a:0:{}s:18:\"\0*\0_entityStorages\";a:0:{}s:13:\"\0*\0originalId\";s:9:\"node.body\";s:9:\"\0*\0status\";b:1;s:7:\"\0*\0uuid\";s:36:\"fbc271f4-84bf-496b-9f37-4690bceafd9c\";s:11:\"\0*\0langcode\";s:2:\"en\";s:23:\"\0*\0third_party_settings\";a:0:{}s:8:\"\0*\0_core\";a:1:{s:19:\"default_config_hash\";s:43:\"EBUo7qOWqaiZaQ_RC9sLY5IoDKphS34v77VIHSACmVY\";}s:14:\"\0*\0trustedData\";b:1;s:15:\"\0*\0dependencies\";a:1:{s:6:\"module\";a:2:{i:0;s:4:\"node\";i:1;s:4:\"text\";}}s:12:\"\0*\0isSyncing\";b:0;s:5:\"\0*\0id\";s:9:\"node.body\";s:13:\"\0*\0field_name\";s:4:\"body\";s:14:\"\0*\0entity_type\";s:4:\"node\";s:7:\"\0*\0type\";s:17:\"text_with_summary\";s:9:\"\0*\0module\";s:4:\"text\";s:11:\"\0*\0settings\";a:0:{}s:14:\"\0*\0cardinality\";i:1;s:15:\"\0*\0translatable\";b:1;s:9:\"\0*\0locked\";b:0;s:25:\"\0*\0persist_with_no_fields\";b:1;s:14:\"custom_storage\";b:0;s:10:\"\0*\0indexes\";a:0:{}s:10:\"\0*\0deleted\";b:0;}s:11:\"field_image\";O:38:\"Drupal\\field\\Entity\\FieldStorageConfig\":30:{s:15:\"\0*\0entityTypeId\";s:20:\"field_storage_config\";s:15:\"\0*\0enforceIsNew\";b:1;s:12:\"\0*\0typedData\";N;s:16:\"\0*\0cacheContexts\";a:0:{}s:12:\"\0*\0cacheTags\";a:0:{}s:14:\"\0*\0cacheMaxAge\";i:-1;s:14:\"\0*\0_serviceIds\";a:0:{}s:18:\"\0*\0_entityStorages\";a:0:{}s:13:\"\0*\0originalId\";s:16:\"node.field_image\";s:9:\"\0*\0status\";b:1;s:7:\"\0*\0uuid\";s:36:\"270e0beb-87b5-4249-9ac7-a40351dfb7ec\";s:11:\"\0*\0langcode\";s:2:\"en\";s:23:\"\0*\0third_party_settings\";a:0:{}s:8:\"\0*\0_core\";a:1:{s:19:\"default_config_hash\";s:43:\"EymokncRIZ7SgQT2IdOQhQJicX4nNc0K89ik-LxmOHE\";}s:14:\"\0*\0trustedData\";b:1;s:15:\"\0*\0dependencies\";a:1:{s:6:\"module\";a:3:{i:0;s:4:\"file\";i:1;s:5:\"image\";i:2;s:4:\"node\";}}s:12:\"\0*\0isSyncing\";b:0;s:5:\"\0*\0id\";s:16:\"node.field_image\";s:13:\"\0*\0field_name\";s:11:\"field_image\";s:14:\"\0*\0entity_type\";s:4:\"node\";s:7:\"\0*\0type\";s:5:\"image\";s:9:\"\0*\0module\";s:5:\"image\";s:11:\"\0*\0settings\";a:5:{s:11:\"target_type\";s:4:\"file\";s:13:\"display_field\";b:0;s:15:\"display_default\";b:0;s:10:\"uri_scheme\";s:6:\"public\";s:13:\"default_image\";a:5:{s:4:\"uuid\";N;s:3:\"alt\";s:0:\"\";s:5:\"title\";s:0:\"\";s:5:\"width\";N;s:6:\"height\";N;}}s:14:\"\0*\0cardinality\";i:1;s:15:\"\0*\0translatable\";b:1;s:9:\"\0*\0locked\";b:0;s:25:\"\0*\0persist_with_no_fields\";b:0;s:14:\"custom_storage\";b:0;s:10:\"\0*\0indexes\";a:1:{s:9:\"target_id\";a:1:{i:0;s:9:\"target_id\";}}s:10:\"\0*\0deleted\";b:0;}s:7:\"comment\";O:38:\"Drupal\\field\\Entity\\FieldStorageConfig\":30:{s:15:\"\0*\0entityTypeId\";s:20:\"field_storage_config\";s:15:\"\0*\0enforceIsNew\";b:1;s:12:\"\0*\0typedData\";N;s:16:\"\0*\0cacheContexts\";a:0:{}s:12:\"\0*\0cacheTags\";a:0:{}s:14:\"\0*\0cacheMaxAge\";i:-1;s:14:\"\0*\0_serviceIds\";a:0:{}s:18:\"\0*\0_entityStorages\";a:0:{}s:13:\"\0*\0originalId\";s:12:\"node.comment\";s:9:\"\0*\0status\";b:1;s:7:\"\0*\0uuid\";s:36:\"b5caebd8-290c-4db7-b03a-afa7b925c14c\";s:11:\"\0*\0langcode\";s:2:\"en\";s:23:\"\0*\0third_party_settings\";a:0:{}s:8:\"\0*\0_core\";a:1:{s:19:\"default_config_hash\";s:43:\"ktCna9xmWvYZIUfOCUyDQvedn5RtnS4CRmEIwNmvYjc\";}s:14:\"\0*\0trustedData\";b:1;s:15:\"\0*\0dependencies\";a:1:{s:6:\"module\";a:2:{i:0;s:7:\"comment\";i:1;s:4:\"node\";}}s:12:\"\0*\0isSyncing\";b:0;s:5:\"\0*\0id\";s:12:\"node.comment\";s:13:\"\0*\0field_name\";s:7:\"comment\";s:14:\"\0*\0entity_type\";s:4:\"node\";s:7:\"\0*\0type\";s:7:\"comment\";s:9:\"\0*\0module\";s:7:\"comment\";s:11:\"\0*\0settings\";a:1:{s:12:\"comment_type\";s:7:\"comment\";}s:14:\"\0*\0cardinality\";i:1;s:15:\"\0*\0translatable\";b:1;s:9:\"\0*\0locked\";b:0;s:25:\"\0*\0persist_with_no_fields\";b:0;s:14:\"custom_storage\";b:0;s:10:\"\0*\0indexes\";a:0:{}s:10:\"\0*\0deleted\";b:0;}s:10:\"field_tags\";O:38:\"Drupal\\field\\Entity\\FieldStorageConfig\":30:{s:15:\"\0*\0entityTypeId\";s:20:\"field_storage_config\";s:15:\"\0*\0enforceIsNew\";b:1;s:12:\"\0*\0typedData\";N;s:16:\"\0*\0cacheContexts\";a:0:{}s:12:\"\0*\0cacheTags\";a:0:{}s:14:\"\0*\0cacheMaxAge\";i:-1;s:14:\"\0*\0_serviceIds\";a:0:{}s:18:\"\0*\0_entityStorages\";a:0:{}s:13:\"\0*\0originalId\";s:15:\"node.field_tags\";s:9:\"\0*\0status\";b:1;s:7:\"\0*\0uuid\";s:36:\"fe879a0a-8688-4019-8105-9fad40d3cc1b\";s:11:\"\0*\0langcode\";s:2:\"en\";s:23:\"\0*\0third_party_settings\";a:0:{}s:8:\"\0*\0_core\";a:1:{s:19:\"default_config_hash\";s:43:\"WpOE_bs8Bs_HY2ns7n2r__de-xno0-Bxkqep5-MsHAs\";}s:14:\"\0*\0trustedData\";b:1;s:15:\"\0*\0dependencies\";a:1:{s:6:\"module\";a:2:{i:0;s:4:\"node\";i:1;s:8:\"taxonomy\";}}s:12:\"\0*\0isSyncing\";b:0;s:5:\"\0*\0id\";s:15:\"node.field_tags\";s:13:\"\0*\0field_name\";s:10:\"field_tags\";s:14:\"\0*\0entity_type\";s:4:\"node\";s:7:\"\0*\0type\";s:16:\"entity_reference\";s:9:\"\0*\0module\";s:4:\"core\";s:11:\"\0*\0settings\";a:1:{s:11:\"target_type\";s:13:\"taxonomy_term\";}s:14:\"\0*\0cardinality\";i:-1;s:15:\"\0*\0translatable\";b:1;s:9:\"\0*\0locked\";b:0;s:25:\"\0*\0persist_with_no_fields\";b:0;s:14:\"custom_storage\";b:0;s:10:\"\0*\0indexes\";a:0:{}s:10:\"\0*\0deleted\";b:0;}s:9:\"workspace\";O:37:\"Drupal\\Core\\Field\\BaseFieldDefinition\":5:{s:13:\"\0*\0definition\";a:10:{s:5:\"label\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:9:\"Workspace\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}s:11:\"description\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:54:\"Indicates the workspace that this revision belongs to.\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}s:8:\"internal\";b:1;s:12:\"translatable\";b:0;s:12:\"revisionable\";b:1;s:8:\"provider\";s:10:\"workspaces\";s:10:\"field_name\";s:9:\"workspace\";s:11:\"entity_type\";s:4:\"node\";s:6:\"bundle\";N;s:13:\"initial_value\";N;}s:17:\"\0*\0itemDefinition\";O:51:\"Drupal\\Core\\Field\\TypedData\\FieldItemDataDefinition\":2:{s:13:\"\0*\0definition\";a:2:{s:4:\"type\";s:27:\"field_item:entity_reference\";s:8:\"settings\";a:3:{s:11:\"target_type\";s:9:\"workspace\";s:7:\"handler\";s:7:\"default\";s:16:\"handler_settings\";a:0:{}}}s:18:\"\0*\0fieldDefinition\";r:872;}s:7:\"\0*\0type\";s:16:\"entity_reference\";s:9:\"\0*\0schema\";a:4:{s:7:\"columns\";a:1:{s:9:\"target_id\";a:3:{s:11:\"description\";s:28:\"The ID of the target entity.\";s:4:\"type\";s:13:\"varchar_ascii\";s:6:\"length\";i:255;}}s:7:\"indexes\";a:1:{s:9:\"target_id\";a:1:{i:0;s:9:\"target_id\";}}s:11:\"unique keys\";a:0:{}s:12:\"foreign keys\";a:0:{}}s:10:\"\0*\0indexes\";a:0:{}}}",
  ],
  [
    'collection' => "entity.storage_schema.sql",
    'name' => "node.field_schema_data.workspace",
    'value' => "a:1:{s:13:\"node_revision\";a:2:{s:6:\"fields\";a:1:{s:9:\"workspace\";a:4:{s:11:\"description\";s:28:\"The ID of the target entity.\";s:4:\"type\";s:13:\"varchar_ascii\";s:6:\"length\";i:255;s:8:\"not null\";b:0;}}s:7:\"indexes\";a:1:{s:32:\"node_field__workspace__target_id\";a:1:{i:0;s:9:\"workspace\";}}}}",
  ],
  [
    'collection' => "entity.definitions.installed",
    'name' => "path_alias.entity_type",
    'value' => "O:36:\"Drupal\\Core\\Entity\\ContentEntityType\":41:{s:5:\"\0*\0id\";s:10:\"path_alias\";s:8:\"\0*\0class\";s:34:\"Drupal\\path_alias\\Entity\\PathAlias\";s:11:\"\0*\0provider\";s:10:\"path_alias\";s:15:\"\0*\0static_cache\";b:1;s:15:\"\0*\0render_cache\";b:1;s:19:\"\0*\0persistent_cache\";b:1;s:14:\"\0*\0entity_keys\";a:8:{s:2:\"id\";s:2:\"id\";s:8:\"revision\";s:11:\"revision_id\";s:8:\"langcode\";s:8:\"langcode\";s:4:\"uuid\";s:4:\"uuid\";s:9:\"published\";s:6:\"status\";s:6:\"bundle\";s:0:\"\";s:16:\"default_langcode\";s:16:\"default_langcode\";s:29:\"revision_translation_affected\";s:29:\"revision_translation_affected\";}s:16:\"\0*\0originalClass\";s:34:\"Drupal\\path_alias\\Entity\\PathAlias\";s:11:\"\0*\0handlers\";a:4:{s:7:\"storage\";s:34:\"Drupal\\path_alias\\PathAliasStorage\";s:14:\"storage_schema\";s:40:\"Drupal\\path_alias\\PathAliasStorageSchema\";s:6:\"access\";s:45:\"Drupal\\Core\\Entity\\EntityAccessControlHandler\";s:12:\"view_builder\";s:36:\"Drupal\\Core\\Entity\\EntityViewBuilder\";}s:19:\"\0*\0admin_permission\";s:22:\"administer url aliases\";s:24:\"\0*\0collection_permission\";N;s:25:\"\0*\0permission_granularity\";s:11:\"entity_type\";s:8:\"\0*\0links\";a:0:{}s:21:\"\0*\0bundle_entity_type\";N;s:12:\"\0*\0bundle_of\";N;s:15:\"\0*\0bundle_label\";N;s:13:\"\0*\0base_table\";s:10:\"path_alias\";s:22:\"\0*\0revision_data_table\";N;s:17:\"\0*\0revision_table\";s:19:\"path_alias_revision\";s:13:\"\0*\0data_table\";N;s:11:\"\0*\0internal\";b:0;s:15:\"\0*\0translatable\";b:0;s:19:\"\0*\0show_revision_ui\";b:0;s:8:\"\0*\0label\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:9:\"URL alias\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}s:19:\"\0*\0label_collection\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:11:\"URL aliases\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}s:17:\"\0*\0label_singular\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:9:\"URL alias\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}s:15:\"\0*\0label_plural\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:11:\"URL aliases\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}s:14:\"\0*\0label_count\";a:3:{s:8:\"singular\";s:16:\"@count URL alias\";s:6:\"plural\";s:18:\"@count URL aliases\";s:7:\"context\";N;}s:15:\"\0*\0uri_callback\";N;s:8:\"\0*\0group\";s:7:\"content\";s:14:\"\0*\0group_label\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:7:\"Content\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:1:{s:7:\"context\";s:17:\"Entity type group\";}}s:22:\"\0*\0field_ui_base_route\";N;s:26:\"\0*\0common_reference_target\";b:0;s:22:\"\0*\0list_cache_contexts\";a:0:{}s:18:\"\0*\0list_cache_tags\";a:1:{i:0;s:11:\"route_match\";}s:14:\"\0*\0constraints\";a:2:{s:15:\"UniquePathAlias\";a:0:{}s:26:\"EntityUntranslatableFields\";N;}s:13:\"\0*\0additional\";a:0:{}s:14:\"\0*\0_serviceIds\";a:0:{}s:18:\"\0*\0_entityStorages\";a:0:{}s:20:\"\0*\0stringTranslation\";N;s:25:\"\0*\0revision_metadata_keys\";a:2:{s:16:\"revision_default\";s:16:\"revision_default\";s:9:\"workspace\";s:9:\"workspace\";}}",
  ],
  [
    'collection' => "entity.definitions.installed",
    'name' => "path_alias.field_storage_definitions",
    'value' => "a:9:{s:2:\"id\";O:37:\"Drupal\\Core\\Field\\BaseFieldDefinition\":5:{s:13:\"\0*\0definition\";a:7:{s:5:\"label\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:2:\"ID\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}s:9:\"read-only\";b:1;s:8:\"provider\";s:10:\"path_alias\";s:10:\"field_name\";s:2:\"id\";s:11:\"entity_type\";s:10:\"path_alias\";s:6:\"bundle\";N;s:13:\"initial_value\";N;}s:17:\"\0*\0itemDefinition\";O:51:\"Drupal\\Core\\Field\\TypedData\\FieldItemDataDefinition\":2:{s:13:\"\0*\0definition\";a:2:{s:4:\"type\";s:18:\"field_item:integer\";s:8:\"settings\";a:6:{s:8:\"unsigned\";b:1;s:4:\"size\";s:6:\"normal\";s:3:\"min\";s:0:\"\";s:3:\"max\";s:0:\"\";s:6:\"prefix\";s:0:\"\";s:6:\"suffix\";s:0:\"\";}}s:18:\"\0*\0fieldDefinition\";r:2;}s:7:\"\0*\0type\";s:7:\"integer\";s:9:\"\0*\0schema\";a:4:{s:7:\"columns\";a:1:{s:5:\"value\";a:3:{s:4:\"type\";s:3:\"int\";s:8:\"unsigned\";b:1;s:4:\"size\";s:6:\"normal\";}}s:11:\"unique keys\";a:0:{}s:7:\"indexes\";a:0:{}s:12:\"foreign keys\";a:0:{}}s:10:\"\0*\0indexes\";a:0:{}}s:4:\"uuid\";O:37:\"Drupal\\Core\\Field\\BaseFieldDefinition\":5:{s:13:\"\0*\0definition\";a:7:{s:5:\"label\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:4:\"UUID\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}s:9:\"read-only\";b:1;s:8:\"provider\";s:10:\"path_alias\";s:10:\"field_name\";s:4:\"uuid\";s:11:\"entity_type\";s:10:\"path_alias\";s:6:\"bundle\";N;s:13:\"initial_value\";N;}s:17:\"\0*\0itemDefinition\";O:51:\"Drupal\\Core\\Field\\TypedData\\FieldItemDataDefinition\":2:{s:13:\"\0*\0definition\";a:2:{s:4:\"type\";s:15:\"field_item:uuid\";s:8:\"settings\";a:3:{s:10:\"max_length\";i:128;s:8:\"is_ascii\";b:1;s:14:\"case_sensitive\";b:0;}}s:18:\"\0*\0fieldDefinition\";r:36;}s:7:\"\0*\0type\";s:4:\"uuid\";s:9:\"\0*\0schema\";a:4:{s:7:\"columns\";a:1:{s:5:\"value\";a:3:{s:4:\"type\";s:13:\"varchar_ascii\";s:6:\"length\";i:128;s:6:\"binary\";b:0;}}s:11:\"unique keys\";a:1:{s:5:\"value\";a:1:{i:0;s:5:\"value\";}}s:7:\"indexes\";a:0:{}s:12:\"foreign keys\";a:0:{}}s:10:\"\0*\0indexes\";a:0:{}}s:11:\"revision_id\";O:37:\"Drupal\\Core\\Field\\BaseFieldDefinition\":5:{s:13:\"\0*\0definition\";a:7:{s:5:\"label\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:11:\"Revision ID\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}s:9:\"read-only\";b:1;s:8:\"provider\";s:10:\"path_alias\";s:10:\"field_name\";s:11:\"revision_id\";s:11:\"entity_type\";s:10:\"path_alias\";s:6:\"bundle\";N;s:13:\"initial_value\";N;}s:17:\"\0*\0itemDefinition\";O:51:\"Drupal\\Core\\Field\\TypedData\\FieldItemDataDefinition\":2:{s:13:\"\0*\0definition\";a:2:{s:4:\"type\";s:18:\"field_item:integer\";s:8:\"settings\";a:6:{s:8:\"unsigned\";b:1;s:4:\"size\";s:6:\"normal\";s:3:\"min\";s:0:\"\";s:3:\"max\";s:0:\"\";s:6:\"prefix\";s:0:\"\";s:6:\"suffix\";s:0:\"\";}}s:18:\"\0*\0fieldDefinition\";r:69;}s:7:\"\0*\0type\";s:7:\"integer\";s:9:\"\0*\0schema\";a:4:{s:7:\"columns\";a:1:{s:5:\"value\";a:3:{s:4:\"type\";s:3:\"int\";s:8:\"unsigned\";b:1;s:4:\"size\";s:6:\"normal\";}}s:11:\"unique keys\";a:0:{}s:7:\"indexes\";a:0:{}s:12:\"foreign keys\";a:0:{}}s:10:\"\0*\0indexes\";a:0:{}}s:8:\"langcode\";O:37:\"Drupal\\Core\\Field\\BaseFieldDefinition\":5:{s:13:\"\0*\0definition\";a:9:{s:5:\"label\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:8:\"Language\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}s:7:\"display\";a:2:{s:4:\"view\";a:1:{s:7:\"options\";a:1:{s:6:\"region\";s:6:\"hidden\";}}s:4:\"form\";a:1:{s:7:\"options\";a:2:{s:4:\"type\";s:15:\"language_select\";s:6:\"weight\";i:2;}}}s:12:\"revisionable\";b:1;s:13:\"default_value\";a:1:{i:0;a:1:{s:5:\"value\";s:3:\"und\";}}s:8:\"provider\";s:10:\"path_alias\";s:10:\"field_name\";s:8:\"langcode\";s:11:\"entity_type\";s:10:\"path_alias\";s:6:\"bundle\";N;s:13:\"initial_value\";N;}s:17:\"\0*\0itemDefinition\";O:51:\"Drupal\\Core\\Field\\TypedData\\FieldItemDataDefinition\":2:{s:13:\"\0*\0definition\";a:2:{s:4:\"type\";s:19:\"field_item:language\";s:8:\"settings\";a:0:{}}s:18:\"\0*\0fieldDefinition\";r:103;}s:7:\"\0*\0type\";s:8:\"language\";s:9:\"\0*\0schema\";a:4:{s:7:\"columns\";a:1:{s:5:\"value\";a:2:{s:4:\"type\";s:13:\"varchar_ascii\";s:6:\"length\";i:12;}}s:11:\"unique keys\";a:0:{}s:7:\"indexes\";a:0:{}s:12:\"foreign keys\";a:0:{}}s:10:\"\0*\0indexes\";a:0:{}}s:4:\"path\";O:37:\"Drupal\\Core\\Field\\BaseFieldDefinition\":5:{s:13:\"\0*\0definition\";a:9:{s:5:\"label\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:11:\"System path\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}s:11:\"description\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:36:\"The path that this alias belongs to.\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}s:8:\"required\";b:1;s:12:\"revisionable\";b:1;s:8:\"provider\";s:10:\"path_alias\";s:10:\"field_name\";s:4:\"path\";s:11:\"entity_type\";s:10:\"path_alias\";s:6:\"bundle\";N;s:13:\"initial_value\";N;}s:17:\"\0*\0itemDefinition\";O:51:\"Drupal\\Core\\Field\\TypedData\\FieldItemDataDefinition\":2:{s:13:\"\0*\0definition\";a:3:{s:4:\"type\";s:17:\"field_item:string\";s:8:\"settings\";a:3:{s:10:\"max_length\";i:255;s:8:\"is_ascii\";b:0;s:14:\"case_sensitive\";b:0;}s:11:\"constraints\";a:1:{s:11:\"ComplexData\";a:1:{s:5:\"value\";a:2:{s:9:\"ValidPath\";a:0:{}s:5:\"Regex\";a:2:{s:7:\"pattern\";s:6:\"/^\\//i\";s:7:\"message\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:42:\"The source path has to start with a slash.\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}}}}}}s:18:\"\0*\0fieldDefinition\";r:141;}s:7:\"\0*\0type\";s:6:\"string\";s:9:\"\0*\0schema\";a:4:{s:7:\"columns\";a:1:{s:5:\"value\";a:3:{s:4:\"type\";s:7:\"varchar\";s:6:\"length\";i:255;s:6:\"binary\";b:0;}}s:11:\"unique keys\";a:0:{}s:7:\"indexes\";a:0:{}s:12:\"foreign keys\";a:0:{}}s:10:\"\0*\0indexes\";a:0:{}}s:5:\"alias\";O:37:\"Drupal\\Core\\Field\\BaseFieldDefinition\":5:{s:13:\"\0*\0definition\";a:9:{s:5:\"label\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:9:\"URL alias\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}s:11:\"description\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:29:\"An alias used with this path.\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}s:8:\"required\";b:1;s:12:\"revisionable\";b:1;s:8:\"provider\";s:10:\"path_alias\";s:10:\"field_name\";s:5:\"alias\";s:11:\"entity_type\";s:10:\"path_alias\";s:6:\"bundle\";N;s:13:\"initial_value\";N;}s:17:\"\0*\0itemDefinition\";O:51:\"Drupal\\Core\\Field\\TypedData\\FieldItemDataDefinition\":2:{s:13:\"\0*\0definition\";a:3:{s:4:\"type\";s:17:\"field_item:string\";s:8:\"settings\";a:3:{s:10:\"max_length\";i:255;s:8:\"is_ascii\";b:0;s:14:\"case_sensitive\";b:0;}s:11:\"constraints\";a:1:{s:11:\"ComplexData\";a:1:{s:5:\"value\";a:1:{s:5:\"Regex\";a:2:{s:7:\"pattern\";s:6:\"/^\\//i\";s:7:\"message\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:41:\"The alias path has to start with a slash.\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}}}}}}s:18:\"\0*\0fieldDefinition\";r:187;}s:7:\"\0*\0type\";s:6:\"string\";s:9:\"\0*\0schema\";a:4:{s:7:\"columns\";a:1:{s:5:\"value\";a:3:{s:4:\"type\";s:7:\"varchar\";s:6:\"length\";i:255;s:6:\"binary\";b:0;}}s:11:\"unique keys\";a:0:{}s:7:\"indexes\";a:0:{}s:12:\"foreign keys\";a:0:{}}s:10:\"\0*\0indexes\";a:0:{}}s:6:\"status\";O:37:\"Drupal\\Core\\Field\\BaseFieldDefinition\":5:{s:13:\"\0*\0definition\";a:9:{s:5:\"label\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:9:\"Published\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}s:12:\"revisionable\";b:1;s:12:\"translatable\";b:0;s:13:\"default_value\";a:1:{i:0;a:1:{s:5:\"value\";b:1;}}s:8:\"provider\";s:10:\"path_alias\";s:10:\"field_name\";s:6:\"status\";s:11:\"entity_type\";s:10:\"path_alias\";s:6:\"bundle\";N;s:13:\"initial_value\";N;}s:17:\"\0*\0itemDefinition\";O:51:\"Drupal\\Core\\Field\\TypedData\\FieldItemDataDefinition\":2:{s:13:\"\0*\0definition\";a:2:{s:4:\"type\";s:18:\"field_item:boolean\";s:8:\"settings\";a:2:{s:8:\"on_label\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:2:\"On\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}s:9:\"off_label\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:3:\"Off\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}}}s:18:\"\0*\0fieldDefinition\";r:232;}s:7:\"\0*\0type\";s:7:\"boolean\";s:9:\"\0*\0schema\";a:4:{s:7:\"columns\";a:1:{s:5:\"value\";a:2:{s:4:\"type\";s:3:\"int\";s:4:\"size\";s:4:\"tiny\";}}s:11:\"unique keys\";a:0:{}s:7:\"indexes\";a:0:{}s:12:\"foreign keys\";a:0:{}}s:10:\"\0*\0indexes\";a:0:{}}s:16:\"revision_default\";O:37:\"Drupal\\Core\\Field\\BaseFieldDefinition\":5:{s:13:\"\0*\0definition\";a:11:{s:5:\"label\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:16:\"Default revision\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}s:11:\"description\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:72:\"A flag indicating whether this was a default revision when it was saved.\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}s:16:\"storage_required\";b:1;s:8:\"internal\";b:1;s:12:\"translatable\";b:0;s:12:\"revisionable\";b:1;s:8:\"provider\";s:10:\"path_alias\";s:10:\"field_name\";s:16:\"revision_default\";s:11:\"entity_type\";s:10:\"path_alias\";s:6:\"bundle\";N;s:13:\"initial_value\";N;}s:17:\"\0*\0itemDefinition\";O:51:\"Drupal\\Core\\Field\\TypedData\\FieldItemDataDefinition\":2:{s:13:\"\0*\0definition\";a:2:{s:4:\"type\";s:18:\"field_item:boolean\";s:8:\"settings\";a:2:{s:8:\"on_label\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:2:\"On\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}s:9:\"off_label\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:3:\"Off\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}}}s:18:\"\0*\0fieldDefinition\";r:271;}s:7:\"\0*\0type\";s:7:\"boolean\";s:9:\"\0*\0schema\";a:4:{s:7:\"columns\";a:1:{s:5:\"value\";a:2:{s:4:\"type\";s:3:\"int\";s:4:\"size\";s:4:\"tiny\";}}s:11:\"unique keys\";a:0:{}s:7:\"indexes\";a:0:{}s:12:\"foreign keys\";a:0:{}}s:10:\"\0*\0indexes\";a:0:{}}s:9:\"workspace\";O:37:\"Drupal\\Core\\Field\\BaseFieldDefinition\":5:{s:13:\"\0*\0definition\";a:10:{s:5:\"label\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:9:\"Workspace\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}s:11:\"description\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:54:\"Indicates the workspace that this revision belongs to.\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}s:8:\"internal\";b:1;s:12:\"translatable\";b:0;s:12:\"revisionable\";b:1;s:8:\"provider\";s:10:\"workspaces\";s:10:\"field_name\";s:9:\"workspace\";s:11:\"entity_type\";s:10:\"path_alias\";s:6:\"bundle\";N;s:13:\"initial_value\";N;}s:17:\"\0*\0itemDefinition\";O:51:\"Drupal\\Core\\Field\\TypedData\\FieldItemDataDefinition\":2:{s:13:\"\0*\0definition\";a:2:{s:4:\"type\";s:27:\"field_item:entity_reference\";s:8:\"settings\";a:3:{s:11:\"target_type\";s:9:\"workspace\";s:7:\"handler\";s:7:\"default\";s:16:\"handler_settings\";a:0:{}}}s:18:\"\0*\0fieldDefinition\";r:313;}s:7:\"\0*\0type\";s:16:\"entity_reference\";s:9:\"\0*\0schema\";a:4:{s:7:\"columns\";a:1:{s:9:\"target_id\";a:3:{s:11:\"description\";s:28:\"The ID of the target entity.\";s:4:\"type\";s:13:\"varchar_ascii\";s:6:\"length\";i:255;}}s:7:\"indexes\";a:1:{s:9:\"target_id\";a:1:{i:0;s:9:\"target_id\";}}s:11:\"unique keys\";a:0:{}s:12:\"foreign keys\";a:0:{}}s:10:\"\0*\0indexes\";a:0:{}}}",
  ],
  [
    'collection' => "entity.storage_schema.sql",
    'name' => "path_alias.field_schema_data.workspace",
    'value' => "a:1:{s:19:\"path_alias_revision\";a:2:{s:6:\"fields\";a:1:{s:9:\"workspace\";a:4:{s:11:\"description\";s:28:\"The ID of the target entity.\";s:4:\"type\";s:13:\"varchar_ascii\";s:6:\"length\";i:255;s:8:\"not null\";b:0;}}s:7:\"indexes\";a:1:{s:38:\"path_alias_field__workspace__target_id\";a:1:{i:0;s:9:\"workspace\";}}}}",
  ],
  [
    'collection' => "entity.definitions.installed",
    'name' => "taxonomy_term.entity_type",
    'value' => "O:36:\"Drupal\\Core\\Entity\\ContentEntityType\":41:{s:5:\"\0*\0id\";s:13:\"taxonomy_term\";s:8:\"\0*\0class\";s:27:\"Drupal\\taxonomy\\Entity\\Term\";s:11:\"\0*\0provider\";s:8:\"taxonomy\";s:15:\"\0*\0static_cache\";b:1;s:15:\"\0*\0render_cache\";b:1;s:19:\"\0*\0persistent_cache\";b:1;s:14:\"\0*\0entity_keys\";a:9:{s:2:\"id\";s:3:\"tid\";s:8:\"revision\";s:11:\"revision_id\";s:6:\"bundle\";s:3:\"vid\";s:5:\"label\";s:4:\"name\";s:8:\"langcode\";s:8:\"langcode\";s:4:\"uuid\";s:4:\"uuid\";s:9:\"published\";s:6:\"status\";s:16:\"default_langcode\";s:16:\"default_langcode\";s:29:\"revision_translation_affected\";s:29:\"revision_translation_affected\";}s:16:\"\0*\0originalClass\";s:27:\"Drupal\\taxonomy\\Entity\\Term\";s:11:\"\0*\0handlers\";a:9:{s:7:\"storage\";s:27:\"Drupal\\taxonomy\\TermStorage\";s:14:\"storage_schema\";s:33:\"Drupal\\taxonomy\\TermStorageSchema\";s:12:\"view_builder\";s:36:\"Drupal\\Core\\Entity\\EntityViewBuilder\";s:12:\"list_builder\";s:36:\"Drupal\\Core\\Entity\\EntityListBuilder\";s:6:\"access\";s:40:\"Drupal\\taxonomy\\TermAccessControlHandler\";s:10:\"views_data\";s:29:\"Drupal\\taxonomy\\TermViewsData\";s:4:\"form\";a:4:{s:7:\"default\";s:24:\"Drupal\\taxonomy\\TermForm\";s:6:\"delete\";s:35:\"Drupal\\taxonomy\\Form\\TermDeleteForm\";s:15:\"revision-delete\";s:42:\"Drupal\\Core\\Entity\\Form\\RevisionDeleteForm\";s:15:\"revision-revert\";s:42:\"Drupal\\Core\\Entity\\Form\\RevisionRevertForm\";}s:14:\"route_provider\";a:1:{s:8:\"revision\";s:52:\"Drupal\\Core\\Entity\\Routing\\RevisionHtmlRouteProvider\";}s:11:\"translation\";s:38:\"Drupal\\taxonomy\\TermTranslationHandler\";}s:19:\"\0*\0admin_permission\";N;s:24:\"\0*\0collection_permission\";s:24:\"access taxonomy overview\";s:25:\"\0*\0permission_granularity\";s:6:\"bundle\";s:8:\"\0*\0links\";a:8:{s:9:\"canonical\";s:30:\"/taxonomy/term/{taxonomy_term}\";s:11:\"delete-form\";s:37:\"/taxonomy/term/{taxonomy_term}/delete\";s:9:\"edit-form\";s:35:\"/taxonomy/term/{taxonomy_term}/edit\";s:6:\"create\";s:14:\"/taxonomy/term\";s:8:\"revision\";s:69:\"/taxonomy/term/{taxonomy_term}/revision/{taxonomy_term_revision}/view\";s:20:\"revision-delete-form\";s:71:\"/taxonomy/term/{taxonomy_term}/revision/{taxonomy_term_revision}/delete\";s:20:\"revision-revert-form\";s:71:\"/taxonomy/term/{taxonomy_term}/revision/{taxonomy_term_revision}/revert\";s:15:\"version-history\";s:40:\"/taxonomy/term/{taxonomy_term}/revisions\";}s:21:\"\0*\0bundle_entity_type\";s:19:\"taxonomy_vocabulary\";s:12:\"\0*\0bundle_of\";N;s:15:\"\0*\0bundle_label\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:10:\"Vocabulary\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}s:13:\"\0*\0base_table\";s:18:\"taxonomy_term_data\";s:22:\"\0*\0revision_data_table\";s:28:\"taxonomy_term_field_revision\";s:17:\"\0*\0revision_table\";s:22:\"taxonomy_term_revision\";s:13:\"\0*\0data_table\";s:24:\"taxonomy_term_field_data\";s:11:\"\0*\0internal\";b:0;s:15:\"\0*\0translatable\";b:1;s:19:\"\0*\0show_revision_ui\";b:1;s:8:\"\0*\0label\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:13:\"Taxonomy term\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}s:19:\"\0*\0label_collection\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:14:\"Taxonomy terms\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}s:17:\"\0*\0label_singular\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:13:\"taxonomy term\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}s:15:\"\0*\0label_plural\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:14:\"taxonomy terms\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}s:14:\"\0*\0label_count\";a:3:{s:8:\"singular\";s:20:\"@count taxonomy term\";s:6:\"plural\";s:21:\"@count taxonomy terms\";s:7:\"context\";N;}s:15:\"\0*\0uri_callback\";N;s:8:\"\0*\0group\";s:7:\"content\";s:14:\"\0*\0group_label\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:7:\"Content\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:1:{s:7:\"context\";s:17:\"Entity type group\";}}s:22:\"\0*\0field_ui_base_route\";s:40:\"entity.taxonomy_vocabulary.overview_form\";s:26:\"\0*\0common_reference_target\";b:1;s:22:\"\0*\0list_cache_contexts\";a:0:{}s:18:\"\0*\0list_cache_tags\";a:1:{i:0;s:18:\"taxonomy_term_list\";}s:14:\"\0*\0constraints\";a:3:{s:17:\"TaxonomyHierarchy\";a:0:{}s:13:\"EntityChanged\";N;s:26:\"EntityUntranslatableFields\";N;}s:13:\"\0*\0additional\";a:0:{}s:14:\"\0*\0_serviceIds\";a:0:{}s:18:\"\0*\0_entityStorages\";a:0:{}s:20:\"\0*\0stringTranslation\";N;s:25:\"\0*\0revision_metadata_keys\";a:5:{s:13:\"revision_user\";s:13:\"revision_user\";s:16:\"revision_created\";s:16:\"revision_created\";s:20:\"revision_log_message\";s:20:\"revision_log_message\";s:16:\"revision_default\";s:16:\"revision_default\";s:9:\"workspace\";s:9:\"workspace\";}}",
  ],
  [
    'collection' => "entity.definitions.installed",
    'name' => "taxonomy_term.field_storage_definitions",
    'value' => "a:18:{s:3:\"tid\";O:37:\"Drupal\\Core\\Field\\BaseFieldDefinition\":5:{s:13:\"\0*\0definition\";a:8:{s:5:\"label\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:7:\"Term ID\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}s:9:\"read-only\";b:1;s:11:\"description\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:12:\"The term ID.\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}s:8:\"provider\";s:8:\"taxonomy\";s:10:\"field_name\";s:3:\"tid\";s:11:\"entity_type\";s:13:\"taxonomy_term\";s:6:\"bundle\";N;s:13:\"initial_value\";N;}s:17:\"\0*\0itemDefinition\";O:51:\"Drupal\\Core\\Field\\TypedData\\FieldItemDataDefinition\":2:{s:13:\"\0*\0definition\";a:2:{s:4:\"type\";s:18:\"field_item:integer\";s:8:\"settings\";a:6:{s:8:\"unsigned\";b:1;s:4:\"size\";s:6:\"normal\";s:3:\"min\";s:0:\"\";s:3:\"max\";s:0:\"\";s:6:\"prefix\";s:0:\"\";s:6:\"suffix\";s:0:\"\";}}s:18:\"\0*\0fieldDefinition\";r:2;}s:7:\"\0*\0type\";s:7:\"integer\";s:9:\"\0*\0schema\";a:4:{s:7:\"columns\";a:1:{s:5:\"value\";a:3:{s:4:\"type\";s:3:\"int\";s:8:\"unsigned\";b:1;s:4:\"size\";s:6:\"normal\";}}s:11:\"unique keys\";a:0:{}s:7:\"indexes\";a:0:{}s:12:\"foreign keys\";a:0:{}}s:10:\"\0*\0indexes\";a:0:{}}s:4:\"uuid\";O:37:\"Drupal\\Core\\Field\\BaseFieldDefinition\":5:{s:13:\"\0*\0definition\";a:8:{s:5:\"label\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:4:\"UUID\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}s:9:\"read-only\";b:1;s:11:\"description\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:14:\"The term UUID.\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}s:8:\"provider\";s:8:\"taxonomy\";s:10:\"field_name\";s:4:\"uuid\";s:11:\"entity_type\";s:13:\"taxonomy_term\";s:6:\"bundle\";N;s:13:\"initial_value\";N;}s:17:\"\0*\0itemDefinition\";O:51:\"Drupal\\Core\\Field\\TypedData\\FieldItemDataDefinition\":2:{s:13:\"\0*\0definition\";a:2:{s:4:\"type\";s:15:\"field_item:uuid\";s:8:\"settings\";a:3:{s:10:\"max_length\";i:128;s:8:\"is_ascii\";b:1;s:14:\"case_sensitive\";b:0;}}s:18:\"\0*\0fieldDefinition\";r:40;}s:7:\"\0*\0type\";s:4:\"uuid\";s:9:\"\0*\0schema\";a:4:{s:7:\"columns\";a:1:{s:5:\"value\";a:3:{s:4:\"type\";s:13:\"varchar_ascii\";s:6:\"length\";i:128;s:6:\"binary\";b:0;}}s:11:\"unique keys\";a:1:{s:5:\"value\";a:1:{i:0;s:5:\"value\";}}s:7:\"indexes\";a:0:{}s:12:\"foreign keys\";a:0:{}}s:10:\"\0*\0indexes\";a:0:{}}s:11:\"revision_id\";O:37:\"Drupal\\Core\\Field\\BaseFieldDefinition\":5:{s:13:\"\0*\0definition\";a:7:{s:5:\"label\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:11:\"Revision ID\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}s:9:\"read-only\";b:1;s:8:\"provider\";s:8:\"taxonomy\";s:10:\"field_name\";s:11:\"revision_id\";s:11:\"entity_type\";s:13:\"taxonomy_term\";s:6:\"bundle\";N;s:13:\"initial_value\";N;}s:17:\"\0*\0itemDefinition\";O:51:\"Drupal\\Core\\Field\\TypedData\\FieldItemDataDefinition\":2:{s:13:\"\0*\0definition\";a:2:{s:4:\"type\";s:18:\"field_item:integer\";s:8:\"settings\";a:6:{s:8:\"unsigned\";b:1;s:4:\"size\";s:6:\"normal\";s:3:\"min\";s:0:\"\";s:3:\"max\";s:0:\"\";s:6:\"prefix\";s:0:\"\";s:6:\"suffix\";s:0:\"\";}}s:18:\"\0*\0fieldDefinition\";r:77;}s:7:\"\0*\0type\";s:7:\"integer\";s:9:\"\0*\0schema\";a:4:{s:7:\"columns\";a:1:{s:5:\"value\";a:3:{s:4:\"type\";s:3:\"int\";s:8:\"unsigned\";b:1;s:4:\"size\";s:6:\"normal\";}}s:11:\"unique keys\";a:0:{}s:7:\"indexes\";a:0:{}s:12:\"foreign keys\";a:0:{}}s:10:\"\0*\0indexes\";a:0:{}}s:8:\"langcode\";O:37:\"Drupal\\Core\\Field\\BaseFieldDefinition\":5:{s:13:\"\0*\0definition\";a:10:{s:5:\"label\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:8:\"Language\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}s:7:\"display\";a:2:{s:4:\"view\";a:1:{s:7:\"options\";a:1:{s:6:\"region\";s:6:\"hidden\";}}s:4:\"form\";a:1:{s:7:\"options\";a:2:{s:4:\"type\";s:15:\"language_select\";s:6:\"weight\";i:2;}}}s:12:\"revisionable\";b:1;s:12:\"translatable\";b:1;s:11:\"description\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:23:\"The term language code.\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}s:8:\"provider\";s:8:\"taxonomy\";s:10:\"field_name\";s:8:\"langcode\";s:11:\"entity_type\";s:13:\"taxonomy_term\";s:6:\"bundle\";N;s:13:\"initial_value\";N;}s:17:\"\0*\0itemDefinition\";O:51:\"Drupal\\Core\\Field\\TypedData\\FieldItemDataDefinition\":2:{s:13:\"\0*\0definition\";a:2:{s:4:\"type\";s:19:\"field_item:language\";s:8:\"settings\";a:0:{}}s:18:\"\0*\0fieldDefinition\";r:111;}s:7:\"\0*\0type\";s:8:\"language\";s:9:\"\0*\0schema\";a:4:{s:7:\"columns\";a:1:{s:5:\"value\";a:2:{s:4:\"type\";s:13:\"varchar_ascii\";s:6:\"length\";i:12;}}s:11:\"unique keys\";a:0:{}s:7:\"indexes\";a:0:{}s:12:\"foreign keys\";a:0:{}}s:10:\"\0*\0indexes\";a:0:{}}s:3:\"vid\";O:37:\"Drupal\\Core\\Field\\BaseFieldDefinition\":5:{s:13:\"\0*\0definition\";a:9:{s:5:\"label\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:10:\"Vocabulary\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}s:8:\"required\";b:1;s:9:\"read-only\";b:1;s:11:\"description\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:45:\"The vocabulary to which the term is assigned.\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}s:8:\"provider\";s:8:\"taxonomy\";s:10:\"field_name\";s:3:\"vid\";s:11:\"entity_type\";s:13:\"taxonomy_term\";s:6:\"bundle\";N;s:13:\"initial_value\";N;}s:17:\"\0*\0itemDefinition\";O:51:\"Drupal\\Core\\Field\\TypedData\\FieldItemDataDefinition\":2:{s:13:\"\0*\0definition\";a:2:{s:4:\"type\";s:27:\"field_item:entity_reference\";s:8:\"settings\";a:3:{s:11:\"target_type\";s:19:\"taxonomy_vocabulary\";s:7:\"handler\";s:7:\"default\";s:16:\"handler_settings\";a:0:{}}}s:18:\"\0*\0fieldDefinition\";r:151;}s:7:\"\0*\0type\";s:16:\"entity_reference\";s:9:\"\0*\0schema\";a:4:{s:7:\"columns\";a:1:{s:9:\"target_id\";a:3:{s:11:\"description\";s:28:\"The ID of the target entity.\";s:4:\"type\";s:13:\"varchar_ascii\";s:6:\"length\";i:32;}}s:7:\"indexes\";a:1:{s:9:\"target_id\";a:1:{i:0;s:9:\"target_id\";}}s:11:\"unique keys\";a:0:{}s:12:\"foreign keys\";a:0:{}}s:10:\"\0*\0indexes\";a:0:{}}s:16:\"revision_created\";O:37:\"Drupal\\Core\\Field\\BaseFieldDefinition\":5:{s:13:\"\0*\0definition\";a:8:{s:5:\"label\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:20:\"Revision create time\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}s:11:\"description\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:47:\"The time that the current revision was created.\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}s:12:\"revisionable\";b:1;s:8:\"provider\";s:8:\"taxonomy\";s:10:\"field_name\";s:16:\"revision_created\";s:11:\"entity_type\";s:13:\"taxonomy_term\";s:6:\"bundle\";N;s:13:\"initial_value\";N;}s:17:\"\0*\0itemDefinition\";O:51:\"Drupal\\Core\\Field\\TypedData\\FieldItemDataDefinition\":2:{s:13:\"\0*\0definition\";a:2:{s:4:\"type\";s:18:\"field_item:created\";s:8:\"settings\";a:0:{}}s:18:\"\0*\0fieldDefinition\";r:189;}s:7:\"\0*\0type\";s:7:\"created\";s:9:\"\0*\0schema\";a:4:{s:7:\"columns\";a:1:{s:5:\"value\";a:1:{s:4:\"type\";s:3:\"int\";}}s:11:\"unique keys\";a:0:{}s:7:\"indexes\";a:0:{}s:12:\"foreign keys\";a:0:{}}s:10:\"\0*\0indexes\";a:0:{}}s:13:\"revision_user\";O:37:\"Drupal\\Core\\Field\\BaseFieldDefinition\":5:{s:13:\"\0*\0definition\";a:8:{s:5:\"label\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:13:\"Revision user\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}s:11:\"description\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:50:\"The user ID of the author of the current revision.\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}s:12:\"revisionable\";b:1;s:8:\"provider\";s:8:\"taxonomy\";s:10:\"field_name\";s:13:\"revision_user\";s:11:\"entity_type\";s:13:\"taxonomy_term\";s:6:\"bundle\";N;s:13:\"initial_value\";N;}s:17:\"\0*\0itemDefinition\";O:51:\"Drupal\\Core\\Field\\TypedData\\FieldItemDataDefinition\":2:{s:13:\"\0*\0definition\";a:2:{s:4:\"type\";s:27:\"field_item:entity_reference\";s:8:\"settings\";a:3:{s:11:\"target_type\";s:4:\"user\";s:7:\"handler\";s:7:\"default\";s:16:\"handler_settings\";a:0:{}}}s:18:\"\0*\0fieldDefinition\";r:219;}s:7:\"\0*\0type\";s:16:\"entity_reference\";s:9:\"\0*\0schema\";a:4:{s:7:\"columns\";a:1:{s:9:\"target_id\";a:3:{s:11:\"description\";s:28:\"The ID of the target entity.\";s:4:\"type\";s:3:\"int\";s:8:\"unsigned\";b:1;}}s:7:\"indexes\";a:1:{s:9:\"target_id\";a:1:{i:0;s:9:\"target_id\";}}s:11:\"unique keys\";a:0:{}s:12:\"foreign keys\";a:0:{}}s:10:\"\0*\0indexes\";a:0:{}}s:20:\"revision_log_message\";O:37:\"Drupal\\Core\\Field\\BaseFieldDefinition\":5:{s:13:\"\0*\0definition\";a:10:{s:5:\"label\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:20:\"Revision log message\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}s:11:\"description\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:43:\"Briefly describe the changes you have made.\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}s:12:\"revisionable\";b:1;s:13:\"default_value\";a:1:{i:0;a:1:{s:5:\"value\";s:0:\"\";}}s:7:\"display\";a:1:{s:4:\"form\";a:1:{s:7:\"options\";a:3:{s:4:\"type\";s:15:\"string_textarea\";s:6:\"weight\";i:25;s:8:\"settings\";a:1:{s:4:\"rows\";i:4;}}}}s:8:\"provider\";s:8:\"taxonomy\";s:10:\"field_name\";s:20:\"revision_log_message\";s:11:\"entity_type\";s:13:\"taxonomy_term\";s:6:\"bundle\";N;s:13:\"initial_value\";N;}s:17:\"\0*\0itemDefinition\";O:51:\"Drupal\\Core\\Field\\TypedData\\FieldItemDataDefinition\":2:{s:13:\"\0*\0definition\";a:2:{s:4:\"type\";s:22:\"field_item:string_long\";s:8:\"settings\";a:1:{s:14:\"case_sensitive\";b:0;}}s:18:\"\0*\0fieldDefinition\";r:256;}s:7:\"\0*\0type\";s:11:\"string_long\";s:9:\"\0*\0schema\";a:4:{s:7:\"columns\";a:1:{s:5:\"value\";a:2:{s:4:\"type\";s:4:\"text\";s:4:\"size\";s:3:\"big\";}}s:11:\"unique keys\";a:0:{}s:7:\"indexes\";a:0:{}s:12:\"foreign keys\";a:0:{}}s:10:\"\0*\0indexes\";a:0:{}}s:6:\"status\";O:37:\"Drupal\\Core\\Field\\BaseFieldDefinition\":5:{s:13:\"\0*\0definition\";a:10:{s:5:\"label\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:9:\"Published\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}s:12:\"revisionable\";b:1;s:12:\"translatable\";b:1;s:13:\"default_value\";a:1:{i:0;a:1:{s:5:\"value\";b:1;}}s:7:\"display\";a:1:{s:4:\"form\";a:2:{s:7:\"options\";a:3:{s:4:\"type\";s:16:\"boolean_checkbox\";s:8:\"settings\";a:1:{s:13:\"display_label\";b:1;}s:6:\"weight\";i:100;}s:12:\"configurable\";b:1;}}s:8:\"provider\";s:8:\"taxonomy\";s:10:\"field_name\";s:6:\"status\";s:11:\"entity_type\";s:13:\"taxonomy_term\";s:6:\"bundle\";N;s:13:\"initial_value\";N;}s:17:\"\0*\0itemDefinition\";O:51:\"Drupal\\Core\\Field\\TypedData\\FieldItemDataDefinition\":2:{s:13:\"\0*\0definition\";a:3:{s:4:\"type\";s:18:\"field_item:boolean\";s:8:\"settings\";a:2:{s:8:\"on_label\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:2:\"On\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}s:9:\"off_label\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:3:\"Off\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}}s:5:\"class\";s:22:\"Drupal\\user\\StatusItem\";}s:18:\"\0*\0fieldDefinition\";r:298;}s:7:\"\0*\0type\";s:7:\"boolean\";s:9:\"\0*\0schema\";a:4:{s:7:\"columns\";a:1:{s:5:\"value\";a:2:{s:4:\"type\";s:3:\"int\";s:4:\"size\";s:4:\"tiny\";}}s:11:\"unique keys\";a:0:{}s:7:\"indexes\";a:0:{}s:12:\"foreign keys\";a:0:{}}s:10:\"\0*\0indexes\";a:0:{}}s:4:\"name\";O:37:\"Drupal\\Core\\Field\\BaseFieldDefinition\":5:{s:13:\"\0*\0definition\";a:10:{s:5:\"label\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:4:\"Name\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}s:12:\"translatable\";b:1;s:12:\"revisionable\";b:1;s:8:\"required\";b:1;s:7:\"display\";a:2:{s:4:\"view\";a:1:{s:7:\"options\";a:3:{s:5:\"label\";s:6:\"hidden\";s:4:\"type\";s:6:\"string\";s:6:\"weight\";i:-5;}}s:4:\"form\";a:2:{s:7:\"options\";a:2:{s:4:\"type\";s:16:\"string_textfield\";s:6:\"weight\";i:-5;}s:12:\"configurable\";b:1;}}s:8:\"provider\";s:8:\"taxonomy\";s:10:\"field_name\";s:4:\"name\";s:11:\"entity_type\";s:13:\"taxonomy_term\";s:6:\"bundle\";N;s:13:\"initial_value\";N;}s:17:\"\0*\0itemDefinition\";O:51:\"Drupal\\Core\\Field\\TypedData\\FieldItemDataDefinition\":2:{s:13:\"\0*\0definition\";a:2:{s:4:\"type\";s:17:\"field_item:string\";s:8:\"settings\";a:3:{s:10:\"max_length\";i:255;s:8:\"is_ascii\";b:0;s:14:\"case_sensitive\";b:0;}}s:18:\"\0*\0fieldDefinition\";r:346;}s:7:\"\0*\0type\";s:6:\"string\";s:9:\"\0*\0schema\";a:4:{s:7:\"columns\";a:1:{s:5:\"value\";a:3:{s:4:\"type\";s:7:\"varchar\";s:6:\"length\";i:255;s:6:\"binary\";b:0;}}s:11:\"unique keys\";a:0:{}s:7:\"indexes\";a:0:{}s:12:\"foreign keys\";a:0:{}}s:10:\"\0*\0indexes\";a:0:{}}s:11:\"description\";O:37:\"Drupal\\Core\\Field\\BaseFieldDefinition\":5:{s:13:\"\0*\0definition\";a:9:{s:5:\"label\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:11:\"Description\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}s:12:\"translatable\";b:1;s:12:\"revisionable\";b:1;s:7:\"display\";a:2:{s:4:\"view\";a:2:{s:7:\"options\";a:3:{s:5:\"label\";s:6:\"hidden\";s:4:\"type\";s:12:\"text_default\";s:6:\"weight\";i:0;}s:12:\"configurable\";b:1;}s:4:\"form\";a:2:{s:7:\"options\";a:2:{s:4:\"type\";s:14:\"text_textfield\";s:6:\"weight\";i:0;}s:12:\"configurable\";b:1;}}s:8:\"provider\";s:8:\"taxonomy\";s:10:\"field_name\";s:11:\"description\";s:11:\"entity_type\";s:13:\"taxonomy_term\";s:6:\"bundle\";N;s:13:\"initial_value\";N;}s:17:\"\0*\0itemDefinition\";O:51:\"Drupal\\Core\\Field\\TypedData\\FieldItemDataDefinition\":2:{s:13:\"\0*\0definition\";a:2:{s:4:\"type\";s:20:\"field_item:text_long\";s:8:\"settings\";a:1:{s:15:\"allowed_formats\";a:0:{}}}s:18:\"\0*\0fieldDefinition\";r:390;}s:7:\"\0*\0type\";s:9:\"text_long\";s:9:\"\0*\0schema\";a:4:{s:7:\"columns\";a:2:{s:5:\"value\";a:2:{s:4:\"type\";s:4:\"text\";s:4:\"size\";s:3:\"big\";}s:6:\"format\";a:2:{s:4:\"type\";s:13:\"varchar_ascii\";s:6:\"length\";i:255;}}s:7:\"indexes\";a:1:{s:6:\"format\";a:1:{i:0;s:6:\"format\";}}s:11:\"unique keys\";a:0:{}s:12:\"foreign keys\";a:0:{}}s:10:\"\0*\0indexes\";a:0:{}}s:6:\"weight\";O:37:\"Drupal\\Core\\Field\\BaseFieldDefinition\":5:{s:13:\"\0*\0definition\";a:8:{s:5:\"label\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:6:\"Weight\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}s:11:\"description\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:51:\"The weight of this term in relation to other terms.\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}s:13:\"default_value\";a:1:{i:0;a:1:{s:5:\"value\";i:0;}}s:8:\"provider\";s:8:\"taxonomy\";s:10:\"field_name\";s:6:\"weight\";s:11:\"entity_type\";s:13:\"taxonomy_term\";s:6:\"bundle\";N;s:13:\"initial_value\";N;}s:17:\"\0*\0itemDefinition\";O:51:\"Drupal\\Core\\Field\\TypedData\\FieldItemDataDefinition\":2:{s:13:\"\0*\0definition\";a:2:{s:4:\"type\";s:18:\"field_item:integer\";s:8:\"settings\";a:6:{s:8:\"unsigned\";b:0;s:4:\"size\";s:6:\"normal\";s:3:\"min\";s:0:\"\";s:3:\"max\";s:0:\"\";s:6:\"prefix\";s:0:\"\";s:6:\"suffix\";s:0:\"\";}}s:18:\"\0*\0fieldDefinition\";r:436;}s:7:\"\0*\0type\";s:7:\"integer\";s:9:\"\0*\0schema\";a:4:{s:7:\"columns\";a:1:{s:5:\"value\";a:3:{s:4:\"type\";s:3:\"int\";s:8:\"unsigned\";b:0;s:4:\"size\";s:6:\"normal\";}}s:11:\"unique keys\";a:0:{}s:7:\"indexes\";a:0:{}s:12:\"foreign keys\";a:0:{}}s:10:\"\0*\0indexes\";a:0:{}}s:6:\"parent\";O:37:\"Drupal\\Core\\Field\\BaseFieldDefinition\":5:{s:13:\"\0*\0definition\";a:7:{s:5:\"label\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:12:\"Term Parents\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}s:11:\"description\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:25:\"The parents of this term.\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}s:11:\"cardinality\";i:-1;s:8:\"provider\";s:8:\"taxonomy\";s:10:\"field_name\";s:6:\"parent\";s:11:\"entity_type\";s:13:\"taxonomy_term\";s:6:\"bundle\";N;}s:17:\"\0*\0itemDefinition\";O:51:\"Drupal\\Core\\Field\\TypedData\\FieldItemDataDefinition\":2:{s:13:\"\0*\0definition\";a:2:{s:4:\"type\";s:27:\"field_item:entity_reference\";s:8:\"settings\";a:3:{s:11:\"target_type\";s:13:\"taxonomy_term\";s:7:\"handler\";s:7:\"default\";s:16:\"handler_settings\";a:0:{}}}s:18:\"\0*\0fieldDefinition\";r:476;}s:7:\"\0*\0type\";s:16:\"entity_reference\";s:9:\"\0*\0schema\";a:4:{s:7:\"columns\";a:1:{s:9:\"target_id\";a:3:{s:11:\"description\";s:28:\"The ID of the target entity.\";s:4:\"type\";s:3:\"int\";s:8:\"unsigned\";b:1;}}s:7:\"indexes\";a:1:{s:9:\"target_id\";a:1:{i:0;s:9:\"target_id\";}}s:11:\"unique keys\";a:0:{}s:12:\"foreign keys\";a:0:{}}s:10:\"\0*\0indexes\";a:0:{}}s:7:\"changed\";O:37:\"Drupal\\Core\\Field\\BaseFieldDefinition\":5:{s:13:\"\0*\0definition\";a:9:{s:5:\"label\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:7:\"Changed\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}s:11:\"description\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:39:\"The time that the term was last edited.\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}s:12:\"translatable\";b:1;s:12:\"revisionable\";b:1;s:8:\"provider\";s:8:\"taxonomy\";s:10:\"field_name\";s:7:\"changed\";s:11:\"entity_type\";s:13:\"taxonomy_term\";s:6:\"bundle\";N;s:13:\"initial_value\";N;}s:17:\"\0*\0itemDefinition\";O:51:\"Drupal\\Core\\Field\\TypedData\\FieldItemDataDefinition\":2:{s:13:\"\0*\0definition\";a:2:{s:4:\"type\";s:18:\"field_item:changed\";s:8:\"settings\";a:0:{}}s:18:\"\0*\0fieldDefinition\";r:512;}s:7:\"\0*\0type\";s:7:\"changed\";s:9:\"\0*\0schema\";a:4:{s:7:\"columns\";a:1:{s:5:\"value\";a:1:{s:4:\"type\";s:3:\"int\";}}s:11:\"unique keys\";a:0:{}s:7:\"indexes\";a:0:{}s:12:\"foreign keys\";a:0:{}}s:10:\"\0*\0indexes\";a:0:{}}s:16:\"default_langcode\";O:37:\"Drupal\\Core\\Field\\BaseFieldDefinition\":5:{s:13:\"\0*\0definition\";a:10:{s:5:\"label\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:19:\"Default translation\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}s:11:\"description\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:58:\"A flag indicating whether this is the default translation.\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}s:12:\"translatable\";b:1;s:12:\"revisionable\";b:1;s:13:\"default_value\";a:1:{i:0;a:1:{s:5:\"value\";b:1;}}s:8:\"provider\";s:8:\"taxonomy\";s:10:\"field_name\";s:16:\"default_langcode\";s:11:\"entity_type\";s:13:\"taxonomy_term\";s:6:\"bundle\";N;s:13:\"initial_value\";N;}s:17:\"\0*\0itemDefinition\";O:51:\"Drupal\\Core\\Field\\TypedData\\FieldItemDataDefinition\":2:{s:13:\"\0*\0definition\";a:2:{s:4:\"type\";s:18:\"field_item:boolean\";s:8:\"settings\";a:2:{s:8:\"on_label\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:2:\"On\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}s:9:\"off_label\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:3:\"Off\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}}}s:18:\"\0*\0fieldDefinition\";r:543;}s:7:\"\0*\0type\";s:7:\"boolean\";s:9:\"\0*\0schema\";a:4:{s:7:\"columns\";a:1:{s:5:\"value\";a:2:{s:4:\"type\";s:3:\"int\";s:4:\"size\";s:4:\"tiny\";}}s:11:\"unique keys\";a:0:{}s:7:\"indexes\";a:0:{}s:12:\"foreign keys\";a:0:{}}s:10:\"\0*\0indexes\";a:0:{}}s:16:\"revision_default\";O:37:\"Drupal\\Core\\Field\\BaseFieldDefinition\":5:{s:13:\"\0*\0definition\";a:11:{s:5:\"label\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:16:\"Default revision\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}s:11:\"description\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:72:\"A flag indicating whether this was a default revision when it was saved.\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}s:16:\"storage_required\";b:1;s:8:\"internal\";b:1;s:12:\"translatable\";b:0;s:12:\"revisionable\";b:1;s:8:\"provider\";s:8:\"taxonomy\";s:10:\"field_name\";s:16:\"revision_default\";s:11:\"entity_type\";s:13:\"taxonomy_term\";s:6:\"bundle\";N;s:13:\"initial_value\";N;}s:17:\"\0*\0itemDefinition\";O:51:\"Drupal\\Core\\Field\\TypedData\\FieldItemDataDefinition\":2:{s:13:\"\0*\0definition\";a:2:{s:4:\"type\";s:18:\"field_item:boolean\";s:8:\"settings\";a:2:{s:8:\"on_label\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:2:\"On\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}s:9:\"off_label\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:3:\"Off\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}}}s:18:\"\0*\0fieldDefinition\";r:586;}s:7:\"\0*\0type\";s:7:\"boolean\";s:9:\"\0*\0schema\";a:4:{s:7:\"columns\";a:1:{s:5:\"value\";a:2:{s:4:\"type\";s:3:\"int\";s:4:\"size\";s:4:\"tiny\";}}s:11:\"unique keys\";a:0:{}s:7:\"indexes\";a:0:{}s:12:\"foreign keys\";a:0:{}}s:10:\"\0*\0indexes\";a:0:{}}s:29:\"revision_translation_affected\";O:37:\"Drupal\\Core\\Field\\BaseFieldDefinition\":5:{s:13:\"\0*\0definition\";a:10:{s:5:\"label\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:29:\"Revision translation affected\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}s:11:\"description\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:72:\"Indicates if the last edit of a translation belongs to current revision.\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}s:9:\"read-only\";b:1;s:12:\"revisionable\";b:1;s:12:\"translatable\";b:1;s:8:\"provider\";s:8:\"taxonomy\";s:10:\"field_name\";s:29:\"revision_translation_affected\";s:11:\"entity_type\";s:13:\"taxonomy_term\";s:6:\"bundle\";N;s:13:\"initial_value\";N;}s:17:\"\0*\0itemDefinition\";O:51:\"Drupal\\Core\\Field\\TypedData\\FieldItemDataDefinition\":2:{s:13:\"\0*\0definition\";a:2:{s:4:\"type\";s:18:\"field_item:boolean\";s:8:\"settings\";a:2:{s:8:\"on_label\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:2:\"On\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}s:9:\"off_label\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:3:\"Off\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}}}s:18:\"\0*\0fieldDefinition\";r:628;}s:7:\"\0*\0type\";s:7:\"boolean\";s:9:\"\0*\0schema\";a:4:{s:7:\"columns\";a:1:{s:5:\"value\";a:2:{s:4:\"type\";s:3:\"int\";s:4:\"size\";s:4:\"tiny\";}}s:11:\"unique keys\";a:0:{}s:7:\"indexes\";a:0:{}s:12:\"foreign keys\";a:0:{}}s:10:\"\0*\0indexes\";a:0:{}}s:9:\"workspace\";O:37:\"Drupal\\Core\\Field\\BaseFieldDefinition\":5:{s:13:\"\0*\0definition\";a:10:{s:5:\"label\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:9:\"Workspace\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}s:11:\"description\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\"\0*\0string\";s:54:\"Indicates the workspace that this revision belongs to.\";s:12:\"\0*\0arguments\";a:0:{}s:10:\"\0*\0options\";a:0:{}}s:8:\"internal\";b:1;s:12:\"translatable\";b:0;s:12:\"revisionable\";b:1;s:8:\"provider\";s:10:\"workspaces\";s:10:\"field_name\";s:9:\"workspace\";s:11:\"entity_type\";s:13:\"taxonomy_term\";s:6:\"bundle\";N;s:13:\"initial_value\";N;}s:17:\"\0*\0itemDefinition\";O:51:\"Drupal\\Core\\Field\\TypedData\\FieldItemDataDefinition\":2:{s:13:\"\0*\0definition\";a:2:{s:4:\"type\";s:27:\"field_item:entity_reference\";s:8:\"settings\";a:3:{s:11:\"target_type\";s:9:\"workspace\";s:7:\"handler\";s:7:\"default\";s:16:\"handler_settings\";a:0:{}}}s:18:\"\0*\0fieldDefinition\";r:669;}s:7:\"\0*\0type\";s:16:\"entity_reference\";s:9:\"\0*\0schema\";a:4:{s:7:\"columns\";a:1:{s:9:\"target_id\";a:3:{s:11:\"description\";s:28:\"The ID of the target entity.\";s:4:\"type\";s:13:\"varchar_ascii\";s:6:\"length\";i:255;}}s:7:\"indexes\";a:1:{s:9:\"target_id\";a:1:{i:0;s:9:\"target_id\";}}s:11:\"unique keys\";a:0:{}s:12:\"foreign keys\";a:0:{}}s:10:\"\0*\0indexes\";a:0:{}}}",
  ],
  [
    'collection' => "entity.storage_schema.sql",
    'name' => "taxonomy_term.field_schema_data.workspace",
    'value' => "a:1:{s:22:\"taxonomy_term_revision\";a:2:{s:6:\"fields\";a:1:{s:9:\"workspace\";a:4:{s:11:\"description\";s:28:\"The ID of the target entity.\";s:4:\"type\";s:13:\"varchar_ascii\";s:6:\"length\";i:255;s:8:\"not null\";b:0;}}s:7:\"indexes\";a:1:{s:41:\"taxonomy_term_field__workspace__target_id\";a:1:{i:0;s:9:\"workspace\";}}}}",
  ],
];
foreach ($key_value_updates as $key_value_update) {
  $connection->delete('key_value')
    ->condition('collection', $key_value_update['collection'])
    ->condition('name', $key_value_update['name'])
    ->execute();

  $connection->insert('key_value')
    ->fields(array_keys($key_value_update))
    ->values($key_value_update)
    ->execute();
}

// Add the installed definitions for the workspace entity type and its fields.
$connection->insert('key_value')
  ->fields([
    'collection',
    'name',
    'value',
  ])
  ->values([
    'collection' => 'entity.definitions.installed',
    'name' => 'workspace.entity_type',
    'value' => 'O:36:"Drupal\Core\Entity\ContentEntityType":41:{s:5:" * id";s:9:"workspace";s:8:" * class";s:34:"Drupal\workspaces\Entity\Workspace";s:11:" * provider";s:10:"workspaces";s:15:" * static_cache";b:1;s:15:" * render_cache";b:1;s:19:" * persistent_cache";b:1;s:14:" * entity_keys";a:10:{s:2:"id";s:2:"id";s:8:"revision";s:11:"revision_id";s:4:"uuid";s:4:"uuid";s:5:"label";s:5:"label";s:3:"uid";s:3:"uid";s:5:"owner";s:3:"uid";s:6:"bundle";s:0:"";s:8:"langcode";s:0:"";s:16:"default_langcode";s:16:"default_langcode";s:29:"revision_translation_affected";s:29:"revision_translation_affected";}s:16:" * originalClass";s:34:"Drupal\workspaces\Entity\Workspace";s:11:" * handlers";a:8:{s:12:"list_builder";s:39:"\Drupal\workspaces\WorkspaceListBuilder";s:12:"view_builder";s:38:"Drupal\workspaces\WorkspaceViewBuilder";s:6:"access";s:47:"Drupal\workspaces\WorkspaceAccessControlHandler";s:10:"views_data";s:28:"Drupal\views\EntityViewsData";s:14:"route_provider";a:1:{s:4:"html";s:50:"\Drupal\Core\Entity\Routing\AdminHtmlRouteProvider";}s:4:"form";a:5:{s:7:"default";s:37:"\Drupal\workspaces\Form\WorkspaceForm";s:3:"add";s:37:"\Drupal\workspaces\Form\WorkspaceForm";s:4:"edit";s:37:"\Drupal\workspaces\Form\WorkspaceForm";s:6:"delete";s:43:"\Drupal\workspaces\Form\WorkspaceDeleteForm";s:8:"activate";s:45:"\Drupal\workspaces\Form\WorkspaceActivateForm";}s:9:"workspace";s:57:"\Drupal\workspaces\Entity\Handler\IgnoredWorkspaceHandler";s:7:"storage";s:46:"Drupal\Core\Entity\Sql\SqlContentEntityStorage";}s:19:" * admin_permission";s:21:"administer workspaces";s:24:" * collection_permission";N;s:25:" * permission_granularity";s:11:"entity_type";s:8:" * links";a:6:{s:9:"canonical";s:52:"/admin/config/workflow/workspaces/manage/{workspace}";s:8:"add-form";s:37:"/admin/config/workflow/workspaces/add";s:9:"edit-form";s:57:"/admin/config/workflow/workspaces/manage/{workspace}/edit";s:11:"delete-form";s:59:"/admin/config/workflow/workspaces/manage/{workspace}/delete";s:13:"activate-form";s:61:"/admin/config/workflow/workspaces/manage/{workspace}/activate";s:10:"collection";s:33:"/admin/config/workflow/workspaces";}s:21:" * bundle_entity_type";N;s:12:" * bundle_of";N;s:15:" * bundle_label";N;s:13:" * base_table";s:9:"workspace";s:22:" * revision_data_table";s:24:"workspace_field_revision";s:17:" * revision_table";s:18:"workspace_revision";s:13:" * data_table";s:20:"workspace_field_data";s:11:" * internal";b:0;s:15:" * translatable";b:0;s:19:" * show_revision_ui";b:0;s:8:" * label";O:48:"Drupal\Core\StringTranslation\TranslatableMarkup":3:{s:9:" * string";s:9:"Workspace";s:12:" * arguments";a:0:{}s:10:" * options";a:0:{}}s:19:" * label_collection";O:48:"Drupal\Core\StringTranslation\TranslatableMarkup":3:{s:9:" * string";s:10:"Workspaces";s:12:" * arguments";a:0:{}s:10:" * options";a:0:{}}s:17:" * label_singular";O:48:"Drupal\Core\StringTranslation\TranslatableMarkup":3:{s:9:" * string";s:9:"workspace";s:12:" * arguments";a:0:{}s:10:" * options";a:0:{}}s:15:" * label_plural";O:48:"Drupal\Core\StringTranslation\TranslatableMarkup":3:{s:9:" * string";s:10:"workspaces";s:12:" * arguments";a:0:{}s:10:" * options";a:0:{}}s:14:" * label_count";a:3:{s:8:"singular";s:16:"@count workspace";s:6:"plural";s:17:"@count workspaces";s:7:"context";N;}s:15:" * uri_callback";N;s:8:" * group";s:7:"content";s:14:" * group_label";O:48:"Drupal\Core\StringTranslation\TranslatableMarkup":3:{s:9:" * string";s:7:"Content";s:12:" * arguments";a:0:{}s:10:" * options";a:1:{s:7:"context";s:17:"Entity type group";}}s:22:" * field_ui_base_route";s:27:"entity.workspace.collection";s:26:" * common_reference_target";b:0;s:22:" * list_cache_contexts";a:0:{}s:18:" * list_cache_tags";a:1:{i:0;s:14:"workspace_list";}s:14:" * constraints";a:2:{s:13:"EntityChanged";N;s:26:"EntityUntranslatableFields";N;}s:13:" * additional";a:0:{}s:14:" * _serviceIds";a:0:{}s:18:" * _entityStorages";a:0:{}s:20:" * stringTranslation";N;s:25:" * revision_metadata_keys";a:1:{s:16:"revision_default";s:16:"revision_default";}}',
  ])
  ->values([
    'collection' => 'entity.definitions.installed',
    'name' => 'workspace.field_storage_definitions',
    'value' => 'a:9:{s:2:"id";O:37:"Drupal\Core\Field\BaseFieldDefinition":5:{s:13:" * definition";a:9:{s:5:"label";O:48:"Drupal\Core\StringTranslation\TranslatableMarkup":3:{s:9:" * string";s:12:"Workspace ID";s:12:" * arguments";a:0:{}s:10:" * options";a:0:{}}s:11:"description";O:48:"Drupal\Core\StringTranslation\TranslatableMarkup":3:{s:9:" * string";s:17:"The workspace ID.";s:12:" * arguments";a:0:{}s:10:" * options";a:0:{}}s:8:"required";b:1;s:11:"constraints";a:2:{s:11:"UniqueField";N;s:16:"DeletedWorkspace";N;}s:8:"provider";s:10:"workspaces";s:10:"field_name";s:2:"id";s:11:"entity_type";s:9:"workspace";s:6:"bundle";N;s:13:"initial_value";N;}s:17:" * itemDefinition";O:51:"Drupal\Core\Field\TypedData\FieldItemDataDefinition":2:{s:13:" * definition";a:3:{s:4:"type";s:17:"field_item:string";s:8:"settings";a:3:{s:10:"max_length";i:128;s:8:"is_ascii";b:0;s:14:"case_sensitive";b:0;}s:11:"constraints";a:1:{s:11:"ComplexData";a:1:{s:5:"value";a:1:{s:5:"Regex";a:1:{s:7:"pattern";s:14:"/^[a-z0-9_]+$/";}}}}}s:18:" * fieldDefinition";r:2;}s:7:" * type";s:6:"string";s:9:" * schema";a:4:{s:7:"columns";a:1:{s:5:"value";a:3:{s:4:"type";s:7:"varchar";s:6:"length";i:128;s:6:"binary";b:0;}}s:11:"unique keys";a:0:{}s:7:"indexes";a:0:{}s:12:"foreign keys";a:0:{}}s:10:" * indexes";a:0:{}}s:4:"uuid";O:37:"Drupal\Core\Field\BaseFieldDefinition":5:{s:13:" * definition";a:7:{s:5:"label";O:48:"Drupal\Core\StringTranslation\TranslatableMarkup":3:{s:9:" * string";s:4:"UUID";s:12:" * arguments";a:0:{}s:10:" * options";a:0:{}}s:9:"read-only";b:1;s:8:"provider";s:10:"workspaces";s:10:"field_name";s:4:"uuid";s:11:"entity_type";s:9:"workspace";s:6:"bundle";N;s:13:"initial_value";N;}s:17:" * itemDefinition";O:51:"Drupal\Core\Field\TypedData\FieldItemDataDefinition":2:{s:13:" * definition";a:2:{s:4:"type";s:15:"field_item:uuid";s:8:"settings";a:3:{s:10:"max_length";i:128;s:8:"is_ascii";b:1;s:14:"case_sensitive";b:0;}}s:18:" * fieldDefinition";r:45;}s:7:" * type";s:4:"uuid";s:9:" * schema";a:4:{s:7:"columns";a:1:{s:5:"value";a:3:{s:4:"type";s:13:"varchar_ascii";s:6:"length";i:128;s:6:"binary";b:0;}}s:11:"unique keys";a:1:{s:5:"value";a:1:{i:0;s:5:"value";}}s:7:"indexes";a:0:{}s:12:"foreign keys";a:0:{}}s:10:" * indexes";a:0:{}}s:11:"revision_id";O:37:"Drupal\Core\Field\BaseFieldDefinition":5:{s:13:" * definition";a:7:{s:5:"label";O:48:"Drupal\Core\StringTranslation\TranslatableMarkup":3:{s:9:" * string";s:11:"Revision ID";s:12:" * arguments";a:0:{}s:10:" * options";a:0:{}}s:9:"read-only";b:1;s:8:"provider";s:10:"workspaces";s:10:"field_name";s:11:"revision_id";s:11:"entity_type";s:9:"workspace";s:6:"bundle";N;s:13:"initial_value";N;}s:17:" * itemDefinition";O:51:"Drupal\Core\Field\TypedData\FieldItemDataDefinition":2:{s:13:" * definition";a:2:{s:4:"type";s:18:"field_item:integer";s:8:"settings";a:6:{s:8:"unsigned";b:1;s:4:"size";s:6:"normal";s:3:"min";s:0:"";s:3:"max";s:0:"";s:6:"prefix";s:0:"";s:6:"suffix";s:0:"";}}s:18:" * fieldDefinition";r:78;}s:7:" * type";s:7:"integer";s:9:" * schema";a:4:{s:7:"columns";a:1:{s:5:"value";a:3:{s:4:"type";s:3:"int";s:8:"unsigned";b:1;s:4:"size";s:6:"normal";}}s:11:"unique keys";a:0:{}s:7:"indexes";a:0:{}s:12:"foreign keys";a:0:{}}s:10:" * indexes";a:0:{}}s:3:"uid";O:37:"Drupal\Core\Field\BaseFieldDefinition":5:{s:13:" * definition";a:10:{s:5:"label";O:48:"Drupal\Core\StringTranslation\TranslatableMarkup":3:{s:9:" * string";s:5:"Owner";s:12:" * arguments";a:0:{}s:10:" * options";a:0:{}}s:12:"translatable";b:0;s:22:"default_value_callback";s:57:"Drupal\workspaces\Entity\Workspace::getDefaultEntityOwner";s:11:"description";O:48:"Drupal\Core\StringTranslation\TranslatableMarkup":3:{s:9:" * string";s:20:"The workspace owner.";s:12:" * arguments";a:0:{}s:10:" * options";a:0:{}}s:7:"display";a:1:{s:4:"form";a:2:{s:7:"options";a:2:{s:4:"type";s:29:"entity_reference_autocomplete";s:6:"weight";i:5;}s:12:"configurable";b:1;}}s:8:"provider";s:10:"workspaces";s:10:"field_name";s:3:"uid";s:11:"entity_type";s:9:"workspace";s:6:"bundle";N;s:13:"initial_value";N;}s:17:" * itemDefinition";O:51:"Drupal\Core\Field\TypedData\FieldItemDataDefinition":2:{s:13:" * definition";a:2:{s:4:"type";s:27:"field_item:entity_reference";s:8:"settings";a:3:{s:11:"target_type";s:4:"user";s:7:"handler";s:7:"default";s:16:"handler_settings";a:0:{}}}s:18:" * fieldDefinition";r:112;}s:7:" * type";s:16:"entity_reference";s:9:" * schema";a:4:{s:7:"columns";a:1:{s:9:"target_id";a:3:{s:11:"description";s:28:"The ID of the target entity.";s:4:"type";s:3:"int";s:8:"unsigned";b:1;}}s:7:"indexes";a:1:{s:9:"target_id";a:1:{i:0;s:9:"target_id";}}s:11:"unique keys";a:0:{}s:12:"foreign keys";a:0:{}}s:10:" * indexes";a:0:{}}s:5:"label";O:37:"Drupal\Core\Field\BaseFieldDefinition":5:{s:13:" * definition";a:9:{s:5:"label";O:48:"Drupal\Core\StringTranslation\TranslatableMarkup":3:{s:9:" * string";s:14:"Workspace name";s:12:" * arguments";a:0:{}s:10:" * options";a:0:{}}s:11:"description";O:48:"Drupal\Core\StringTranslation\TranslatableMarkup":3:{s:9:" * string";s:19:"The workspace name.";s:12:" * arguments";a:0:{}s:10:" * options";a:0:{}}s:12:"revisionable";b:1;s:8:"required";b:1;s:8:"provider";s:10:"workspaces";s:10:"field_name";s:5:"label";s:11:"entity_type";s:9:"workspace";s:6:"bundle";N;s:13:"initial_value";N;}s:17:" * itemDefinition";O:51:"Drupal\Core\Field\TypedData\FieldItemDataDefinition":2:{s:13:" * definition";a:2:{s:4:"type";s:17:"field_item:string";s:8:"settings";a:3:{s:10:"max_length";i:128;s:8:"is_ascii";b:0;s:14:"case_sensitive";b:0;}}s:18:" * fieldDefinition";r:156;}s:7:" * type";s:6:"string";s:9:" * schema";a:4:{s:7:"columns";a:1:{s:5:"value";a:3:{s:4:"type";s:7:"varchar";s:6:"length";i:128;s:6:"binary";b:0;}}s:11:"unique keys";a:0:{}s:7:"indexes";a:0:{}s:12:"foreign keys";a:0:{}}s:10:" * indexes";a:0:{}}s:6:"parent";O:37:"Drupal\Core\Field\BaseFieldDefinition":5:{s:13:" * definition";a:9:{s:5:"label";O:48:"Drupal\Core\StringTranslation\TranslatableMarkup":3:{s:9:" * string";s:6:"Parent";s:12:" * arguments";a:0:{}s:10:" * options";a:0:{}}s:11:"description";O:48:"Drupal\Core\StringTranslation\TranslatableMarkup":3:{s:9:" * string";s:21:"The parent workspace.";s:12:" * arguments";a:0:{}s:10:" * options";a:0:{}}s:9:"read-only";b:1;s:7:"display";a:1:{s:4:"form";a:2:{s:7:"options";a:2:{s:4:"type";s:14:"options_select";s:6:"weight";i:10;}s:12:"configurable";b:1;}}s:8:"provider";s:10:"workspaces";s:10:"field_name";s:6:"parent";s:11:"entity_type";s:9:"workspace";s:6:"bundle";N;s:13:"initial_value";N;}s:17:" * itemDefinition";O:51:"Drupal\Core\Field\TypedData\FieldItemDataDefinition":2:{s:13:" * definition";a:2:{s:4:"type";s:27:"field_item:entity_reference";s:8:"settings";a:3:{s:11:"target_type";s:9:"workspace";s:7:"handler";s:7:"default";s:16:"handler_settings";a:0:{}}}s:18:" * fieldDefinition";r:192;}s:7:" * type";s:16:"entity_reference";s:9:" * schema";a:4:{s:7:"columns";a:1:{s:9:"target_id";a:3:{s:11:"description";s:28:"The ID of the target entity.";s:4:"type";s:13:"varchar_ascii";s:6:"length";i:255;}}s:7:"indexes";a:1:{s:9:"target_id";a:1:{i:0;s:9:"target_id";}}s:11:"unique keys";a:0:{}s:12:"foreign keys";a:0:{}}s:10:" * indexes";a:0:{}}s:7:"changed";O:37:"Drupal\Core\Field\BaseFieldDefinition":5:{s:13:" * definition";a:8:{s:5:"label";O:48:"Drupal\Core\StringTranslation\TranslatableMarkup":3:{s:9:" * string";s:7:"Changed";s:12:" * arguments";a:0:{}s:10:" * options";a:0:{}}s:11:"description";O:48:"Drupal\Core\StringTranslation\TranslatableMarkup":3:{s:9:" * string";s:44:"The time that the workspace was last edited.";s:12:" * arguments";a:0:{}s:10:" * options";a:0:{}}s:12:"revisionable";b:1;s:8:"provider";s:10:"workspaces";s:10:"field_name";s:7:"changed";s:11:"entity_type";s:9:"workspace";s:6:"bundle";N;s:13:"initial_value";N;}s:17:" * itemDefinition";O:51:"Drupal\Core\Field\TypedData\FieldItemDataDefinition":2:{s:13:" * definition";a:2:{s:4:"type";s:18:"field_item:changed";s:8:"settings";a:0:{}}s:18:" * fieldDefinition";r:235;}s:7:" * type";s:7:"changed";s:9:" * schema";a:4:{s:7:"columns";a:1:{s:5:"value";a:1:{s:4:"type";s:3:"int";}}s:11:"unique keys";a:0:{}s:7:"indexes";a:0:{}s:12:"foreign keys";a:0:{}}s:10:" * indexes";a:0:{}}s:7:"created";O:37:"Drupal\Core\Field\BaseFieldDefinition":5:{s:13:" * definition";a:7:{s:5:"label";O:48:"Drupal\Core\StringTranslation\TranslatableMarkup":3:{s:9:" * string";s:7:"Created";s:12:" * arguments";a:0:{}s:10:" * options";a:0:{}}s:11:"description";O:48:"Drupal\Core\StringTranslation\TranslatableMarkup":3:{s:9:" * string";s:40:"The time that the workspace was created.";s:12:" * arguments";a:0:{}s:10:" * options";a:0:{}}s:8:"provider";s:10:"workspaces";s:10:"field_name";s:7:"created";s:11:"entity_type";s:9:"workspace";s:6:"bundle";N;s:13:"initial_value";N;}s:17:" * itemDefinition";O:51:"Drupal\Core\Field\TypedData\FieldItemDataDefinition":2:{s:13:" * definition";a:2:{s:4:"type";s:18:"field_item:created";s:8:"settings";a:0:{}}s:18:" * fieldDefinition";r:265;}s:7:" * type";s:7:"created";s:9:" * schema";a:4:{s:7:"columns";a:1:{s:5:"value";a:1:{s:4:"type";s:3:"int";}}s:11:"unique keys";a:0:{}s:7:"indexes";a:0:{}s:12:"foreign keys";a:0:{}}s:10:" * indexes";a:0:{}}s:16:"revision_default";O:37:"Drupal\Core\Field\BaseFieldDefinition":5:{s:13:" * definition";a:11:{s:5:"label";O:48:"Drupal\Core\StringTranslation\TranslatableMarkup":3:{s:9:" * string";s:16:"Default revision";s:12:" * arguments";a:0:{}s:10:" * options";a:0:{}}s:11:"description";O:48:"Drupal\Core\StringTranslation\TranslatableMarkup":3:{s:9:" * string";s:72:"A flag indicating whether this was a default revision when it was saved.";s:12:" * arguments";a:0:{}s:10:" * options";a:0:{}}s:16:"storage_required";b:1;s:8:"internal";b:1;s:12:"translatable";b:0;s:12:"revisionable";b:1;s:8:"provider";s:10:"workspaces";s:10:"field_name";s:16:"revision_default";s:11:"entity_type";s:9:"workspace";s:6:"bundle";N;s:13:"initial_value";N;}s:17:" * itemDefinition";O:51:"Drupal\Core\Field\TypedData\FieldItemDataDefinition":2:{s:13:" * definition";a:2:{s:4:"type";s:18:"field_item:boolean";s:8:"settings";a:2:{s:8:"on_label";O:48:"Drupal\Core\StringTranslation\TranslatableMarkup":3:{s:9:" * string";s:2:"On";s:12:" * arguments";a:0:{}s:10:" * options";a:0:{}}s:9:"off_label";O:48:"Drupal\Core\StringTranslation\TranslatableMarkup":3:{s:9:" * string";s:3:"Off";s:12:" * arguments";a:0:{}s:10:" * options";a:0:{}}}}s:18:" * fieldDefinition";r:294;}s:7:" * type";s:7:"boolean";s:9:" * schema";a:4:{s:7:"columns";a:1:{s:5:"value";a:2:{s:4:"type";s:3:"int";s:4:"size";s:4:"tiny";}}s:11:"unique keys";a:0:{}s:7:"indexes";a:0:{}s:12:"foreign keys";a:0:{}}s:10:" * indexes";a:0:{}}}',
  ])
  ->values([
    'collection' => 'entity.storage_schema.sql',
    'name' => 'workspace.entity_schema_data',
    'value' => 'a:2:{s:9:"workspace";a:2:{s:11:"primary key";a:1:{i:0;s:2:"id";}s:11:"unique keys";a:1:{s:22:"workspace__revision_id";a:1:{i:0;s:11:"revision_id";}}}s:18:"workspace_revision";a:2:{s:11:"primary key";a:1:{i:0;s:11:"revision_id";}s:7:"indexes";a:1:{s:13:"workspace__id";a:1:{i:0;s:2:"id";}}}}',
  ])
  ->values([
    'collection' => 'entity.storage_schema.sql',
    'name' => 'workspace.field_schema_data.changed',
    'value' => 'a:2:{s:9:"workspace";a:1:{s:6:"fields";a:1:{s:7:"changed";a:2:{s:4:"type";s:3:"int";s:8:"not null";b:0;}}}s:18:"workspace_revision";a:1:{s:6:"fields";a:1:{s:7:"changed";a:2:{s:4:"type";s:3:"int";s:8:"not null";b:0;}}}}',
  ])
  ->values([
    'collection' => 'entity.storage_schema.sql',
    'name' => 'workspace.field_schema_data.created',
    'value' => 'a:1:{s:9:"workspace";a:1:{s:6:"fields";a:1:{s:7:"created";a:2:{s:4:"type";s:3:"int";s:8:"not null";b:0;}}}}',
  ])
  ->values([
    'collection' => 'entity.storage_schema.sql',
    'name' => 'workspace.field_schema_data.id',
    'value' => 'a:2:{s:9:"workspace";a:1:{s:6:"fields";a:1:{s:2:"id";a:4:{s:4:"type";s:7:"varchar";s:6:"length";i:128;s:6:"binary";b:0;s:8:"not null";b:1;}}}s:18:"workspace_revision";a:1:{s:6:"fields";a:1:{s:2:"id";a:4:{s:4:"type";s:7:"varchar";s:6:"length";i:128;s:6:"binary";b:0;s:8:"not null";b:1;}}}}',
  ])
  ->values([
    'collection' => 'entity.storage_schema.sql',
    'name' => 'workspace.field_schema_data.label',
    'value' => 'a:2:{s:9:"workspace";a:1:{s:6:"fields";a:1:{s:5:"label";a:4:{s:4:"type";s:7:"varchar";s:6:"length";i:128;s:6:"binary";b:0;s:8:"not null";b:0;}}}s:18:"workspace_revision";a:1:{s:6:"fields";a:1:{s:5:"label";a:4:{s:4:"type";s:7:"varchar";s:6:"length";i:128;s:6:"binary";b:0;s:8:"not null";b:0;}}}}',
  ])
  ->values([
    'collection' => 'entity.storage_schema.sql',
    'name' => 'workspace.field_schema_data.parent',
    'value' => 'a:1:{s:9:"workspace";a:2:{s:6:"fields";a:1:{s:6:"parent";a:4:{s:11:"description";s:28:"The ID of the target entity.";s:4:"type";s:13:"varchar_ascii";s:6:"length";i:255;s:8:"not null";b:0;}}s:7:"indexes";a:1:{s:34:"workspace_field__parent__target_id";a:1:{i:0;s:6:"parent";}}}}',
  ])
  ->values([
    'collection' => 'entity.storage_schema.sql',
    'name' => 'workspace.field_schema_data.revision_default',
    'value' => 'a:1:{s:18:"workspace_revision";a:1:{s:6:"fields";a:1:{s:16:"revision_default";a:3:{s:4:"type";s:3:"int";s:4:"size";s:4:"tiny";s:8:"not null";b:0;}}}}',
  ])
  ->values([
    'collection' => 'entity.storage_schema.sql',
    'name' => 'workspace.field_schema_data.revision_id',
    'value' => 'a:2:{s:9:"workspace";a:1:{s:6:"fields";a:1:{s:11:"revision_id";a:4:{s:4:"type";s:3:"int";s:8:"unsigned";b:1;s:4:"size";s:6:"normal";s:8:"not null";b:0;}}}s:18:"workspace_revision";a:1:{s:6:"fields";a:1:{s:11:"revision_id";a:4:{s:4:"type";s:6:"serial";s:8:"unsigned";b:1;s:4:"size";s:6:"normal";s:8:"not null";b:1;}}}}',
  ])
  ->values([
    'collection' => 'entity.storage_schema.sql',
    'name' => 'workspace.field_schema_data.uid',
    'value' => 'a:1:{s:9:"workspace";a:2:{s:6:"fields";a:1:{s:3:"uid";a:4:{s:11:"description";s:28:"The ID of the target entity.";s:4:"type";s:3:"int";s:8:"unsigned";b:1;s:8:"not null";b:1;}}s:7:"indexes";a:1:{s:31:"workspace_field__uid__target_id";a:1:{i:0;s:3:"uid";}}}}',
  ])
  ->values([
    'collection' => 'entity.storage_schema.sql',
    'name' => 'workspace.field_schema_data.uuid',
    'value' => 'a:1:{s:9:"workspace";a:2:{s:6:"fields";a:1:{s:4:"uuid";a:4:{s:4:"type";s:13:"varchar_ascii";s:6:"length";i:128;s:6:"binary";b:0;s:8:"not null";b:1;}}s:11:"unique keys";a:1:{s:28:"workspace_field__uuid__value";a:1:{i:0;s:4:"uuid";}}}}',
  ])
  ->execute();

// Recreate the revision tables for supported entity types.
$connection->schema()->dropTable('block_content_revision');
$connection->schema()->createTable('block_content_revision', [
  'fields' => [
    'id' => [
      'type' => 'int',
      'not null' => TRUE,
      'size' => 'normal',
      'unsigned' => TRUE,
    ],
    'revision_id' => [
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
    'revision_log' => [
      'type' => 'text',
      'not null' => FALSE,
      'size' => 'big',
    ],
    'revision_default' => [
      'type' => 'int',
      'not null' => FALSE,
      'size' => 'tiny',
    ],
    'workspace' => [
      'type' => 'varchar_ascii',
      'not null' => FALSE,
      'length' => '255',
    ],
  ],
  'primary key' => [
    'revision_id',
  ],
  'indexes' => [
    'block_content__id' => [
      'id',
    ],
    'block_content_field__revision_user__target_id' => [
      'revision_user',
    ],
    'block_content_field__workspace__target_id' => [
      'workspace',
    ],
  ],
  'mysql_character_set' => 'utf8mb4',
]);

$connection->schema()->dropTable('menu_link_content_revision');
$connection->schema()->createTable('menu_link_content_revision', [
  'fields' => [
    'id' => [
      'type' => 'int',
      'not null' => TRUE,
      'size' => 'normal',
      'unsigned' => TRUE,
    ],
    'revision_id' => [
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
    'revision_default' => [
      'type' => 'int',
      'not null' => FALSE,
      'size' => 'tiny',
    ],
    'workspace' => [
      'type' => 'varchar_ascii',
      'not null' => FALSE,
      'length' => '255',
    ],
  ],
  'primary key' => [
    'revision_id',
  ],
  'indexes' => [
    'menu_link_content__id' => [
      'id',
    ],
    'menu_link_content__ef029a1897' => [
      'revision_user',
    ],
    'menu_link_content_field__workspace__target_id' => [
      'workspace',
    ],
  ],
  'mysql_character_set' => 'utf8mb4',
]);

$connection->schema()->dropTable('node_revision');
$connection->schema()->createTable('node_revision', [
  'fields' => [
    'nid' => [
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
    'revision_uid' => [
      'type' => 'int',
      'not null' => FALSE,
      'size' => 'normal',
      'unsigned' => TRUE,
    ],
    'revision_timestamp' => [
      'type' => 'int',
      'not null' => FALSE,
      'size' => 'normal',
    ],
    'revision_log' => [
      'type' => 'text',
      'not null' => FALSE,
      'size' => 'big',
    ],
    'revision_default' => [
      'type' => 'int',
      'not null' => FALSE,
      'size' => 'tiny',
    ],
    'workspace' => [
      'type' => 'varchar_ascii',
      'not null' => FALSE,
      'length' => '255',
    ],
  ],
  'primary key' => [
    'vid',
  ],
  'indexes' => [
    'node__nid' => [
      'nid',
    ],
    'node_field__langcode' => [
      'langcode',
    ],
    'node_field__revision_uid__target_id' => [
      'revision_uid',
    ],
    'node_field__workspace__target_id' => [
      'workspace',
    ],
  ],
  'mysql_character_set' => 'utf8mb4',
]);

$connection->schema()->dropTable('path_alias_revision');
$connection->schema()->createTable('path_alias_revision', [
  'fields' => [
    'id' => [
      'type' => 'int',
      'not null' => TRUE,
      'size' => 'normal',
      'unsigned' => TRUE,
    ],
    'revision_id' => [
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
    'path' => [
      'type' => 'varchar',
      'not null' => FALSE,
      'length' => '255',
    ],
    'alias' => [
      'type' => 'varchar',
      'not null' => FALSE,
      'length' => '255',
    ],
    'status' => [
      'type' => 'int',
      'not null' => TRUE,
      'size' => 'tiny',
    ],
    'revision_default' => [
      'type' => 'int',
      'not null' => FALSE,
      'size' => 'tiny',
    ],
    'workspace' => [
      'type' => 'varchar_ascii',
      'not null' => FALSE,
      'length' => '255',
    ],
  ],
  'primary key' => [
    'revision_id',
  ],
  'indexes' => [
    'path_alias__id' => [
      'id',
    ],
    'path_alias_field__workspace__target_id' => [
      'workspace',
    ],
  ],
  'mysql_character_set' => 'utf8mb4',
]);

$connection->schema()->dropTable('taxonomy_term_revision');
$connection->schema()->createTable('taxonomy_term_revision', [
  'fields' => [
    'tid' => [
      'type' => 'int',
      'not null' => TRUE,
      'size' => 'normal',
      'unsigned' => TRUE,
    ],
    'revision_id' => [
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
    'revision_default' => [
      'type' => 'int',
      'not null' => FALSE,
      'size' => 'tiny',
    ],
    'workspace' => [
      'type' => 'varchar_ascii',
      'not null' => FALSE,
      'length' => '255',
    ],
  ],
  'primary key' => [
    'revision_id',
  ],
  'indexes' => [
    'taxonomy_term__tid' => [
      'tid',
    ],
    'taxonomy_term_field__revision_user__target_id' => [
      'revision_user',
    ],
    'taxonomy_term_field__workspace__target_id' => [
      'workspace',
    ],
  ],
  'mysql_character_set' => 'utf8mb4',
]);

// Add the workspace tables and fill them with test data.
$connection->schema()->createTable('workspace', [
  'fields' => [
    'id' => [
      'type' => 'varchar',
      'not null' => TRUE,
      'length' => '128',
    ],
    'revision_id' => [
      'type' => 'int',
      'not null' => FALSE,
      'size' => 'normal',
      'unsigned' => TRUE,
    ],
    'uuid' => [
      'type' => 'varchar_ascii',
      'not null' => TRUE,
      'length' => '128',
    ],
    'uid' => [
      'type' => 'int',
      'not null' => TRUE,
      'size' => 'normal',
      'unsigned' => TRUE,
    ],
    'label' => [
      'type' => 'varchar',
      'not null' => FALSE,
      'length' => '128',
    ],
    'parent' => [
      'type' => 'varchar_ascii',
      'not null' => FALSE,
      'length' => '255',
    ],
    'changed' => [
      'type' => 'int',
      'not null' => FALSE,
      'size' => 'normal',
    ],
    'created' => [
      'type' => 'int',
      'not null' => FALSE,
      'size' => 'normal',
    ],
  ],
  'primary key' => [
    'id',
  ],
  'unique keys' => [
    'workspace_field__uuid__value' => [
      'uuid',
    ],
    'workspace__revision_id' => [
      'revision_id',
    ],
  ],
  'indexes' => [
    'workspace_field__uid__target_id' => [
      'uid',
    ],
    'workspace_field__parent__target_id' => [
      'parent',
    ],
  ],
  'mysql_character_set' => 'utf8mb4',
]);

$connection->insert('workspace')
  ->fields([
    'id',
    'revision_id',
    'uuid',
    'uid',
    'label',
    'parent',
    'changed',
    'created',
  ])
  ->values([
    'id' => 'summer',
    'revision_id' => '2',
    'uuid' => '31113ba1-c097-4c5e-aa3c-4ba6f2c8b28c',
    'uid' => '1',
    'label' => 'Summer campaign',
    'parent' => NULL,
    'changed' => '1755698838',
    'created' => '1755698838',
  ])
  ->values([
    'id' => 'winter',
    'revision_id' => '3',
    'uuid' => 'b8b3e2b9-4c8b-42b3-870b-92ed04ec566c',
    'uid' => '1',
    'label' => 'Winter campaign',
    'parent' => NULL,
    'changed' => '1755698849',
    'created' => '1755698849',
  ])
  ->execute();

$connection->schema()->createTable('workspace_association', [
  'fields' => [
    'workspace' => [
      'type' => 'varchar_ascii',
      'not null' => TRUE,
      'length' => '128',
      'default' => '',
    ],
    'target_entity_type_id' => [
      'type' => 'varchar_ascii',
      'not null' => TRUE,
      'length' => '32',
      'default' => '',
    ],
    'target_entity_id' => [
      'type' => 'int',
      'not null' => TRUE,
      'size' => 'normal',
      'unsigned' => TRUE,
    ],
    'target_entity_revision_id' => [
      'type' => 'int',
      'not null' => TRUE,
      'size' => 'normal',
      'unsigned' => TRUE,
    ],
  ],
  'primary key' => [
    'workspace',
    'target_entity_type_id',
    'target_entity_id',
  ],
  'indexes' => [
    'target_entity_revision_id' => [
      'target_entity_revision_id',
    ],
  ],
  'mysql_character_set' => 'utf8mb4',
]);

$connection->insert('workspace_association')
  ->fields([
    'workspace',
    'target_entity_type_id',
    'target_entity_id',
    'target_entity_revision_id',
  ])
  ->values([
    'workspace' => 'summer',
    'target_entity_type_id' => 'taxonomy_term',
    'target_entity_id' => '3',
    'target_entity_revision_id' => '4',
  ])
  ->values([
    'workspace' => 'summer',
    'target_entity_type_id' => 'taxonomy_term',
    'target_entity_id' => '4',
    'target_entity_revision_id' => '6',
  ])
  ->values([
    'workspace' => 'summer',
    'target_entity_type_id' => 'node',
    'target_entity_id' => '3',
    'target_entity_revision_id' => '7',
  ])
  ->values([
    'workspace' => 'winter',
    'target_entity_type_id' => 'taxonomy_term',
    'target_entity_id' => '5',
    'target_entity_revision_id' => '8',
  ])
  ->values([
    'workspace' => 'winter',
    'target_entity_type_id' => 'taxonomy_term',
    'target_entity_id' => '6',
    'target_entity_revision_id' => '10',
  ])
  ->values([
    'workspace' => 'summer',
    'target_entity_type_id' => 'node',
    'target_entity_id' => '4',
    'target_entity_revision_id' => '12',
  ])
  ->values([
    'workspace' => 'winter',
    'target_entity_type_id' => 'node',
    'target_entity_id' => '5',
    'target_entity_revision_id' => '17',
  ])
  ->values([
    'workspace' => 'winter',
    'target_entity_type_id' => 'node',
    'target_entity_id' => '6',
    'target_entity_revision_id' => '22',
  ])
  ->execute();

$connection->schema()->createTable('workspace_revision', [
  'fields' => [
    'id' => [
      'type' => 'varchar',
      'not null' => TRUE,
      'length' => '128',
    ],
    'revision_id' => [
      'type' => 'serial',
      'not null' => TRUE,
      'size' => 'normal',
      'unsigned' => TRUE,
    ],
    'label' => [
      'type' => 'varchar',
      'not null' => FALSE,
      'length' => '128',
    ],
    'changed' => [
      'type' => 'int',
      'not null' => FALSE,
      'size' => 'normal',
    ],
    'revision_default' => [
      'type' => 'int',
      'not null' => FALSE,
      'size' => 'tiny',
    ],
  ],
  'primary key' => [
    'revision_id',
  ],
  'indexes' => [
    'workspace__id' => [
      'id',
    ],
  ],
  'mysql_character_set' => 'utf8mb4',
]);

$connection->insert('workspace_revision')
  ->fields([
    'id',
    'revision_id',
    'label',
    'changed',
    'revision_default',
  ])
  ->values([
    'id' => 'summer',
    'revision_id' => '2',
    'label' => 'Summer campaign',
    'changed' => '1755698838',
    'revision_default' => '1',
  ])
  ->values([
    'id' => 'winter',
    'revision_id' => '3',
    'label' => 'Winter campaign',
    'changed' => '1755698849',
    'revision_default' => '1',
  ])
  ->execute();

// Add test data for nodes.
$connection->insert('node')
  ->fields([
    'nid',
    'vid',
    'type',
    'uuid',
    'langcode',
  ])
  ->values([
    'nid' => '1',
    'vid' => '1',
    'type' => 'article',
    'uuid' => 'a66e0e39-1df3-432b-9bd3-cdcf5417b7c3',
    'langcode' => 'en',
  ])
  ->values([
    'nid' => '2',
    'vid' => '2',
    'type' => 'article',
    'uuid' => 'feca0807-4241-4740-9c48-63ca945b73ad',
    'langcode' => 'en',
  ])
  ->values([
    'nid' => '3',
    'vid' => '3',
    'type' => 'article',
    'uuid' => '71133e2e-88a3-407e-9cb2-ed49ffa6a3d5',
    'langcode' => 'en',
  ])
  ->values([
    'nid' => '4',
    'vid' => '8',
    'type' => 'article',
    'uuid' => '76126926-9af6-4bd1-befa-6813e2f86ad6',
    'langcode' => 'en',
  ])
  ->values([
    'nid' => '5',
    'vid' => '13',
    'type' => 'article',
    'uuid' => '4310ebc3-57e8-4373-89b7-f47dd2284133',
    'langcode' => 'en',
  ])
  ->values([
    'nid' => '6',
    'vid' => '18',
    'type' => 'article',
    'uuid' => '2d363115-ce1b-4d1a-80f0-953871ffbb0c',
    'langcode' => 'en',
  ])
  ->execute();

$connection->insert('node__comment')
  ->fields([
    'bundle',
    'deleted',
    'entity_id',
    'revision_id',
    'langcode',
    'delta',
    'comment_status',
  ])
  ->values([
    'bundle' => 'article',
    'deleted' => '0',
    'entity_id' => '1',
    'revision_id' => '1',
    'langcode' => 'en',
    'delta' => '0',
    'comment_status' => '2',
  ])
  ->values([
    'bundle' => 'article',
    'deleted' => '0',
    'entity_id' => '2',
    'revision_id' => '2',
    'langcode' => 'en',
    'delta' => '0',
    'comment_status' => '2',
  ])
  ->values([
    'bundle' => 'article',
    'deleted' => '0',
    'entity_id' => '3',
    'revision_id' => '3',
    'langcode' => 'en',
    'delta' => '0',
    'comment_status' => '2',
  ])
  ->values([
    'bundle' => 'article',
    'deleted' => '0',
    'entity_id' => '4',
    'revision_id' => '8',
    'langcode' => 'en',
    'delta' => '0',
    'comment_status' => '2',
  ])
  ->values([
    'bundle' => 'article',
    'deleted' => '0',
    'entity_id' => '5',
    'revision_id' => '13',
    'langcode' => 'en',
    'delta' => '0',
    'comment_status' => '2',
  ])
  ->values([
    'bundle' => 'article',
    'deleted' => '0',
    'entity_id' => '6',
    'revision_id' => '18',
    'langcode' => 'en',
    'delta' => '0',
    'comment_status' => '2',
  ])
  ->execute();

$connection->insert('node__field_tags')
  ->fields([
    'bundle',
    'deleted',
    'entity_id',
    'revision_id',
    'langcode',
    'delta',
    'field_tags_target_id',
  ])
  ->values([
    'bundle' => 'article',
    'deleted' => '0',
    'entity_id' => '1',
    'revision_id' => '1',
    'langcode' => 'en',
    'delta' => '0',
    'field_tags_target_id' => '1',
  ])
  ->values([
    'bundle' => 'article',
    'deleted' => '0',
    'entity_id' => '2',
    'revision_id' => '2',
    'langcode' => 'en',
    'delta' => '0',
    'field_tags_target_id' => '1',
  ])
  ->values([
    'bundle' => 'article',
    'deleted' => '0',
    'entity_id' => '2',
    'revision_id' => '2',
    'langcode' => 'en',
    'delta' => '1',
    'field_tags_target_id' => '2',
  ])
  ->values([
    'bundle' => 'article',
    'deleted' => '0',
    'entity_id' => '3',
    'revision_id' => '3',
    'langcode' => 'en',
    'delta' => '0',
    'field_tags_target_id' => '3',
  ])
  ->values([
    'bundle' => 'article',
    'deleted' => '0',
    'entity_id' => '4',
    'revision_id' => '8',
    'langcode' => 'en',
    'delta' => '0',
    'field_tags_target_id' => '3',
  ])
  ->values([
    'bundle' => 'article',
    'deleted' => '0',
    'entity_id' => '4',
    'revision_id' => '8',
    'langcode' => 'en',
    'delta' => '1',
    'field_tags_target_id' => '4',
  ])
  ->values([
    'bundle' => 'article',
    'deleted' => '0',
    'entity_id' => '5',
    'revision_id' => '13',
    'langcode' => 'en',
    'delta' => '0',
    'field_tags_target_id' => '5',
  ])
  ->values([
    'bundle' => 'article',
    'deleted' => '0',
    'entity_id' => '6',
    'revision_id' => '18',
    'langcode' => 'en',
    'delta' => '0',
    'field_tags_target_id' => '5',
  ])
  ->values([
    'bundle' => 'article',
    'deleted' => '0',
    'entity_id' => '6',
    'revision_id' => '18',
    'langcode' => 'en',
    'delta' => '1',
    'field_tags_target_id' => '6',
  ])
  ->execute();

$connection->insert('node_field_data')
  ->fields([
    'nid',
    'vid',
    'type',
    'langcode',
    'status',
    'uid',
    'title',
    'created',
    'changed',
    'promote',
    'sticky',
    'default_langcode',
    'revision_translation_affected',
  ])
  ->values([
    'nid' => '1',
    'vid' => '1',
    'type' => 'article',
    'langcode' => 'en',
    'status' => '1',
    'uid' => '1',
    'title' => 'Live published',
    'created' => '1755698853',
    'changed' => '1755698868',
    'promote' => '1',
    'sticky' => '0',
    'default_langcode' => '1',
    'revision_translation_affected' => '1',
  ])
  ->values([
    'nid' => '2',
    'vid' => '2',
    'type' => 'article',
    'langcode' => 'en',
    'status' => '0',
    'uid' => '1',
    'title' => 'Live unpublished',
    'created' => '1755698872',
    'changed' => '1755698888',
    'promote' => '1',
    'sticky' => '0',
    'default_langcode' => '1',
    'revision_translation_affected' => '1',
  ])
  ->values([
    'nid' => '3',
    'vid' => '3',
    'type' => 'article',
    'langcode' => 'en',
    'status' => '0',
    'uid' => '1',
    'title' => 'Summer published r1',
    'created' => '1755698911',
    'changed' => '1755698937',
    'promote' => '1',
    'sticky' => '0',
    'default_langcode' => '1',
    'revision_translation_affected' => '1',
  ])
  ->values([
    'nid' => '4',
    'vid' => '8',
    'type' => 'article',
    'langcode' => 'en',
    'status' => '0',
    'uid' => '1',
    'title' => 'Summer unpublished r1',
    'created' => '1755698956',
    'changed' => '1755698987',
    'promote' => '1',
    'sticky' => '0',
    'default_langcode' => '1',
    'revision_translation_affected' => '1',
  ])
  ->values([
    'nid' => '5',
    'vid' => '13',
    'type' => 'article',
    'langcode' => 'en',
    'status' => '0',
    'uid' => '1',
    'title' => 'Winter published r1',
    'created' => '1755699022',
    'changed' => '1755699040',
    'promote' => '1',
    'sticky' => '0',
    'default_langcode' => '1',
    'revision_translation_affected' => '1',
  ])
  ->values([
    'nid' => '6',
    'vid' => '18',
    'type' => 'article',
    'langcode' => 'en',
    'status' => '0',
    'uid' => '1',
    'title' => 'Winter unpublished r1',
    'created' => '1755699057',
    'changed' => '1755699078',
    'promote' => '1',
    'sticky' => '0',
    'default_langcode' => '1',
    'revision_translation_affected' => '1',
  ])
  ->execute();

$connection->insert('node_field_revision')
  ->fields([
    'nid',
    'vid',
    'langcode',
    'status',
    'uid',
    'title',
    'created',
    'changed',
    'promote',
    'sticky',
    'default_langcode',
    'revision_translation_affected',
  ])
  ->values([
    'nid' => '1',
    'vid' => '1',
    'langcode' => 'en',
    'status' => '1',
    'uid' => '1',
    'title' => 'Live published',
    'created' => '1755698853',
    'changed' => '1755698868',
    'promote' => '1',
    'sticky' => '0',
    'default_langcode' => '1',
    'revision_translation_affected' => '1',
  ])
  ->values([
    'nid' => '2',
    'vid' => '2',
    'langcode' => 'en',
    'status' => '0',
    'uid' => '1',
    'title' => 'Live unpublished',
    'created' => '1755698872',
    'changed' => '1755698888',
    'promote' => '1',
    'sticky' => '0',
    'default_langcode' => '1',
    'revision_translation_affected' => '1',
  ])
  ->values([
    'nid' => '3',
    'vid' => '3',
    'langcode' => 'en',
    'status' => '0',
    'uid' => '1',
    'title' => 'Summer published r1',
    'created' => '1755698911',
    'changed' => '1755698937',
    'promote' => '1',
    'sticky' => '0',
    'default_langcode' => '1',
    'revision_translation_affected' => '1',
  ])
  ->values([
    'nid' => '3',
    'vid' => '4',
    'langcode' => 'en',
    'status' => '1',
    'uid' => '1',
    'title' => 'Summer published r1',
    'created' => '1755698911',
    'changed' => '1755698937',
    'promote' => '1',
    'sticky' => '0',
    'default_langcode' => '1',
    'revision_translation_affected' => '1',
  ])
  ->values([
    'nid' => '3',
    'vid' => '5',
    'langcode' => 'en',
    'status' => '1',
    'uid' => '1',
    'title' => 'Summer published r2',
    'created' => '1755698911',
    'changed' => '1755698944',
    'promote' => '1',
    'sticky' => '0',
    'default_langcode' => '1',
    'revision_translation_affected' => '1',
  ])
  ->values([
    'nid' => '3',
    'vid' => '6',
    'langcode' => 'en',
    'status' => '1',
    'uid' => '1',
    'title' => 'Summer published r3',
    'created' => '1755698911',
    'changed' => '1755698948',
    'promote' => '1',
    'sticky' => '0',
    'default_langcode' => '1',
    'revision_translation_affected' => '1',
  ])
  ->values([
    'nid' => '3',
    'vid' => '7',
    'langcode' => 'en',
    'status' => '1',
    'uid' => '1',
    'title' => 'Summer published r4',
    'created' => '1755698911',
    'changed' => '1755698953',
    'promote' => '1',
    'sticky' => '0',
    'default_langcode' => '1',
    'revision_translation_affected' => '1',
  ])
  ->values([
    'nid' => '4',
    'vid' => '8',
    'langcode' => 'en',
    'status' => '0',
    'uid' => '1',
    'title' => 'Summer unpublished r1',
    'created' => '1755698956',
    'changed' => '1755698987',
    'promote' => '1',
    'sticky' => '0',
    'default_langcode' => '1',
    'revision_translation_affected' => '1',
  ])
  ->values([
    'nid' => '4',
    'vid' => '9',
    'langcode' => 'en',
    'status' => '0',
    'uid' => '1',
    'title' => 'Summer unpublished r2',
    'created' => '1755698956',
    'changed' => '1755698991',
    'promote' => '1',
    'sticky' => '0',
    'default_langcode' => '1',
    'revision_translation_affected' => '1',
  ])
  ->values([
    'nid' => '4',
    'vid' => '10',
    'langcode' => 'en',
    'status' => '0',
    'uid' => '1',
    'title' => 'Summer unpublished r3',
    'created' => '1755698956',
    'changed' => '1755698994',
    'promote' => '1',
    'sticky' => '0',
    'default_langcode' => '1',
    'revision_translation_affected' => '1',
  ])
  ->values([
    'nid' => '4',
    'vid' => '11',
    'langcode' => 'en',
    'status' => '0',
    'uid' => '1',
    'title' => 'Summer unpublished r4',
    'created' => '1755698956',
    'changed' => '1755698998',
    'promote' => '1',
    'sticky' => '0',
    'default_langcode' => '1',
    'revision_translation_affected' => '1',
  ])
  ->values([
    'nid' => '4',
    'vid' => '12',
    'langcode' => 'en',
    'status' => '0',
    'uid' => '1',
    'title' => 'Summer unpublished r5',
    'created' => '1755698956',
    'changed' => '1755699003',
    'promote' => '1',
    'sticky' => '0',
    'default_langcode' => '1',
    'revision_translation_affected' => '1',
  ])
  ->values([
    'nid' => '5',
    'vid' => '13',
    'langcode' => 'en',
    'status' => '0',
    'uid' => '1',
    'title' => 'Winter published r1',
    'created' => '1755699022',
    'changed' => '1755699040',
    'promote' => '1',
    'sticky' => '0',
    'default_langcode' => '1',
    'revision_translation_affected' => '1',
  ])
  ->values([
    'nid' => '5',
    'vid' => '14',
    'langcode' => 'en',
    'status' => '1',
    'uid' => '1',
    'title' => 'Winter published r1',
    'created' => '1755699022',
    'changed' => '1755699040',
    'promote' => '1',
    'sticky' => '0',
    'default_langcode' => '1',
    'revision_translation_affected' => '1',
  ])
  ->values([
    'nid' => '5',
    'vid' => '15',
    'langcode' => 'en',
    'status' => '1',
    'uid' => '1',
    'title' => 'Winter published r2',
    'created' => '1755699022',
    'changed' => '1755699045',
    'promote' => '1',
    'sticky' => '0',
    'default_langcode' => '1',
    'revision_translation_affected' => '1',
  ])
  ->values([
    'nid' => '5',
    'vid' => '16',
    'langcode' => 'en',
    'status' => '1',
    'uid' => '1',
    'title' => 'Winter published r3',
    'created' => '1755699022',
    'changed' => '1755699049',
    'promote' => '1',
    'sticky' => '0',
    'default_langcode' => '1',
    'revision_translation_affected' => '1',
  ])
  ->values([
    'nid' => '5',
    'vid' => '17',
    'langcode' => 'en',
    'status' => '1',
    'uid' => '1',
    'title' => 'Winter published r4',
    'created' => '1755699022',
    'changed' => '1755699053',
    'promote' => '1',
    'sticky' => '0',
    'default_langcode' => '1',
    'revision_translation_affected' => '1',
  ])
  ->values([
    'nid' => '6',
    'vid' => '18',
    'langcode' => 'en',
    'status' => '0',
    'uid' => '1',
    'title' => 'Winter unpublished r1',
    'created' => '1755699057',
    'changed' => '1755699078',
    'promote' => '1',
    'sticky' => '0',
    'default_langcode' => '1',
    'revision_translation_affected' => '1',
  ])
  ->values([
    'nid' => '6',
    'vid' => '19',
    'langcode' => 'en',
    'status' => '0',
    'uid' => '1',
    'title' => 'Winter unpublished r2',
    'created' => '1755699057',
    'changed' => '1755699082',
    'promote' => '1',
    'sticky' => '0',
    'default_langcode' => '1',
    'revision_translation_affected' => '1',
  ])
  ->values([
    'nid' => '6',
    'vid' => '20',
    'langcode' => 'en',
    'status' => '0',
    'uid' => '1',
    'title' => 'Winter unpublished r3',
    'created' => '1755699057',
    'changed' => '1755699086',
    'promote' => '1',
    'sticky' => '0',
    'default_langcode' => '1',
    'revision_translation_affected' => '1',
  ])
  ->values([
    'nid' => '6',
    'vid' => '21',
    'langcode' => 'en',
    'status' => '0',
    'uid' => '1',
    'title' => 'Winter unpublished r4',
    'created' => '1755699057',
    'changed' => '1755699091',
    'promote' => '1',
    'sticky' => '0',
    'default_langcode' => '1',
    'revision_translation_affected' => '1',
  ])
  ->values([
    'nid' => '6',
    'vid' => '22',
    'langcode' => 'en',
    'status' => '0',
    'uid' => '1',
    'title' => 'Winter unpublished r5',
    'created' => '1755699057',
    'changed' => '1755699095',
    'promote' => '1',
    'sticky' => '0',
    'default_langcode' => '1',
    'revision_translation_affected' => '1',
  ])
  ->execute();

$connection->insert('node_revision')
  ->fields([
    'nid',
    'vid',
    'langcode',
    'revision_uid',
    'revision_timestamp',
    'revision_log',
    'revision_default',
    'workspace',
  ])
  ->values([
    'nid' => '1',
    'vid' => '1',
    'langcode' => 'en',
    'revision_uid' => '1',
    'revision_timestamp' => '1755698868',
    'revision_log' => NULL,
    'revision_default' => '1',
    'workspace' => NULL,
  ])
  ->values([
    'nid' => '2',
    'vid' => '2',
    'langcode' => 'en',
    'revision_uid' => '1',
    'revision_timestamp' => '1755698888',
    'revision_log' => NULL,
    'revision_default' => '1',
    'workspace' => NULL,
  ])
  ->values([
    'nid' => '3',
    'vid' => '3',
    'langcode' => 'en',
    'revision_uid' => '1',
    'revision_timestamp' => '1755698937',
    'revision_log' => NULL,
    'revision_default' => '1',
    'workspace' => 'summer',
  ])
  ->values([
    'nid' => '3',
    'vid' => '4',
    'langcode' => 'en',
    'revision_uid' => '1',
    'revision_timestamp' => '1755698937',
    'revision_log' => NULL,
    'revision_default' => '0',
    'workspace' => 'summer',
  ])
  ->values([
    'nid' => '3',
    'vid' => '5',
    'langcode' => 'en',
    'revision_uid' => '1',
    'revision_timestamp' => '1755698944',
    'revision_log' => NULL,
    'revision_default' => '0',
    'workspace' => 'summer',
  ])
  ->values([
    'nid' => '3',
    'vid' => '6',
    'langcode' => 'en',
    'revision_uid' => '1',
    'revision_timestamp' => '1755698948',
    'revision_log' => NULL,
    'revision_default' => '0',
    'workspace' => 'summer',
  ])
  ->values([
    'nid' => '3',
    'vid' => '7',
    'langcode' => 'en',
    'revision_uid' => '1',
    'revision_timestamp' => '1755698953',
    'revision_log' => NULL,
    'revision_default' => '0',
    'workspace' => 'summer',
  ])
  ->values([
    'nid' => '4',
    'vid' => '8',
    'langcode' => 'en',
    'revision_uid' => '1',
    'revision_timestamp' => '1755698987',
    'revision_log' => NULL,
    'revision_default' => '1',
    'workspace' => 'summer',
  ])
  ->values([
    'nid' => '4',
    'vid' => '9',
    'langcode' => 'en',
    'revision_uid' => '1',
    'revision_timestamp' => '1755698991',
    'revision_log' => NULL,
    'revision_default' => '0',
    'workspace' => 'summer',
  ])
  ->values([
    'nid' => '4',
    'vid' => '10',
    'langcode' => 'en',
    'revision_uid' => '1',
    'revision_timestamp' => '1755698994',
    'revision_log' => NULL,
    'revision_default' => '0',
    'workspace' => 'summer',
  ])
  ->values([
    'nid' => '4',
    'vid' => '11',
    'langcode' => 'en',
    'revision_uid' => '1',
    'revision_timestamp' => '1755698998',
    'revision_log' => NULL,
    'revision_default' => '0',
    'workspace' => 'summer',
  ])
  ->values([
    'nid' => '4',
    'vid' => '12',
    'langcode' => 'en',
    'revision_uid' => '1',
    'revision_timestamp' => '1755699003',
    'revision_log' => NULL,
    'revision_default' => '0',
    'workspace' => 'summer',
  ])
  ->values([
    'nid' => '5',
    'vid' => '13',
    'langcode' => 'en',
    'revision_uid' => '1',
    'revision_timestamp' => '1755699040',
    'revision_log' => NULL,
    'revision_default' => '1',
    'workspace' => 'winter',
  ])
  ->values([
    'nid' => '5',
    'vid' => '14',
    'langcode' => 'en',
    'revision_uid' => '1',
    'revision_timestamp' => '1755699040',
    'revision_log' => NULL,
    'revision_default' => '0',
    'workspace' => 'winter',
  ])
  ->values([
    'nid' => '5',
    'vid' => '15',
    'langcode' => 'en',
    'revision_uid' => '1',
    'revision_timestamp' => '1755699045',
    'revision_log' => NULL,
    'revision_default' => '0',
    'workspace' => 'winter',
  ])
  ->values([
    'nid' => '5',
    'vid' => '16',
    'langcode' => 'en',
    'revision_uid' => '1',
    'revision_timestamp' => '1755699049',
    'revision_log' => NULL,
    'revision_default' => '0',
    'workspace' => 'winter',
  ])
  ->values([
    'nid' => '5',
    'vid' => '17',
    'langcode' => 'en',
    'revision_uid' => '1',
    'revision_timestamp' => '1755699053',
    'revision_log' => NULL,
    'revision_default' => '0',
    'workspace' => 'winter',
  ])
  ->values([
    'nid' => '6',
    'vid' => '18',
    'langcode' => 'en',
    'revision_uid' => '1',
    'revision_timestamp' => '1755699078',
    'revision_log' => NULL,
    'revision_default' => '1',
    'workspace' => 'winter',
  ])
  ->values([
    'nid' => '6',
    'vid' => '19',
    'langcode' => 'en',
    'revision_uid' => '1',
    'revision_timestamp' => '1755699082',
    'revision_log' => NULL,
    'revision_default' => '0',
    'workspace' => 'winter',
  ])
  ->values([
    'nid' => '6',
    'vid' => '20',
    'langcode' => 'en',
    'revision_uid' => '1',
    'revision_timestamp' => '1755699086',
    'revision_log' => NULL,
    'revision_default' => '0',
    'workspace' => 'winter',
  ])
  ->values([
    'nid' => '6',
    'vid' => '21',
    'langcode' => 'en',
    'revision_uid' => '1',
    'revision_timestamp' => '1755699091',
    'revision_log' => NULL,
    'revision_default' => '0',
    'workspace' => 'winter',
  ])
  ->values([
    'nid' => '6',
    'vid' => '22',
    'langcode' => 'en',
    'revision_uid' => '1',
    'revision_timestamp' => '1755699095',
    'revision_log' => NULL,
    'revision_default' => '0',
    'workspace' => 'winter',
  ])
  ->execute();

$connection->insert('node_revision__comment')
  ->fields([
    'bundle',
    'deleted',
    'entity_id',
    'revision_id',
    'langcode',
    'delta',
    'comment_status',
  ])
  ->values([
    'bundle' => 'article',
    'deleted' => '0',
    'entity_id' => '1',
    'revision_id' => '1',
    'langcode' => 'en',
    'delta' => '0',
    'comment_status' => '2',
  ])
  ->values([
    'bundle' => 'article',
    'deleted' => '0',
    'entity_id' => '2',
    'revision_id' => '2',
    'langcode' => 'en',
    'delta' => '0',
    'comment_status' => '2',
  ])
  ->values([
    'bundle' => 'article',
    'deleted' => '0',
    'entity_id' => '3',
    'revision_id' => '3',
    'langcode' => 'en',
    'delta' => '0',
    'comment_status' => '2',
  ])
  ->values([
    'bundle' => 'article',
    'deleted' => '0',
    'entity_id' => '3',
    'revision_id' => '4',
    'langcode' => 'en',
    'delta' => '0',
    'comment_status' => '2',
  ])
  ->values([
    'bundle' => 'article',
    'deleted' => '0',
    'entity_id' => '3',
    'revision_id' => '5',
    'langcode' => 'en',
    'delta' => '0',
    'comment_status' => '2',
  ])
  ->values([
    'bundle' => 'article',
    'deleted' => '0',
    'entity_id' => '3',
    'revision_id' => '6',
    'langcode' => 'en',
    'delta' => '0',
    'comment_status' => '2',
  ])
  ->values([
    'bundle' => 'article',
    'deleted' => '0',
    'entity_id' => '3',
    'revision_id' => '7',
    'langcode' => 'en',
    'delta' => '0',
    'comment_status' => '2',
  ])
  ->values([
    'bundle' => 'article',
    'deleted' => '0',
    'entity_id' => '4',
    'revision_id' => '8',
    'langcode' => 'en',
    'delta' => '0',
    'comment_status' => '2',
  ])
  ->values([
    'bundle' => 'article',
    'deleted' => '0',
    'entity_id' => '4',
    'revision_id' => '9',
    'langcode' => 'en',
    'delta' => '0',
    'comment_status' => '2',
  ])
  ->values([
    'bundle' => 'article',
    'deleted' => '0',
    'entity_id' => '4',
    'revision_id' => '10',
    'langcode' => 'en',
    'delta' => '0',
    'comment_status' => '2',
  ])
  ->values([
    'bundle' => 'article',
    'deleted' => '0',
    'entity_id' => '4',
    'revision_id' => '11',
    'langcode' => 'en',
    'delta' => '0',
    'comment_status' => '2',
  ])
  ->values([
    'bundle' => 'article',
    'deleted' => '0',
    'entity_id' => '4',
    'revision_id' => '12',
    'langcode' => 'en',
    'delta' => '0',
    'comment_status' => '2',
  ])
  ->values([
    'bundle' => 'article',
    'deleted' => '0',
    'entity_id' => '5',
    'revision_id' => '13',
    'langcode' => 'en',
    'delta' => '0',
    'comment_status' => '2',
  ])
  ->values([
    'bundle' => 'article',
    'deleted' => '0',
    'entity_id' => '5',
    'revision_id' => '14',
    'langcode' => 'en',
    'delta' => '0',
    'comment_status' => '2',
  ])
  ->values([
    'bundle' => 'article',
    'deleted' => '0',
    'entity_id' => '5',
    'revision_id' => '15',
    'langcode' => 'en',
    'delta' => '0',
    'comment_status' => '2',
  ])
  ->values([
    'bundle' => 'article',
    'deleted' => '0',
    'entity_id' => '5',
    'revision_id' => '16',
    'langcode' => 'en',
    'delta' => '0',
    'comment_status' => '2',
  ])
  ->values([
    'bundle' => 'article',
    'deleted' => '0',
    'entity_id' => '5',
    'revision_id' => '17',
    'langcode' => 'en',
    'delta' => '0',
    'comment_status' => '2',
  ])
  ->values([
    'bundle' => 'article',
    'deleted' => '0',
    'entity_id' => '6',
    'revision_id' => '18',
    'langcode' => 'en',
    'delta' => '0',
    'comment_status' => '2',
  ])
  ->values([
    'bundle' => 'article',
    'deleted' => '0',
    'entity_id' => '6',
    'revision_id' => '19',
    'langcode' => 'en',
    'delta' => '0',
    'comment_status' => '2',
  ])
  ->values([
    'bundle' => 'article',
    'deleted' => '0',
    'entity_id' => '6',
    'revision_id' => '20',
    'langcode' => 'en',
    'delta' => '0',
    'comment_status' => '2',
  ])
  ->values([
    'bundle' => 'article',
    'deleted' => '0',
    'entity_id' => '6',
    'revision_id' => '21',
    'langcode' => 'en',
    'delta' => '0',
    'comment_status' => '2',
  ])
  ->values([
    'bundle' => 'article',
    'deleted' => '0',
    'entity_id' => '6',
    'revision_id' => '22',
    'langcode' => 'en',
    'delta' => '0',
    'comment_status' => '2',
  ])
  ->execute();

$connection->insert('node_revision__field_tags')
  ->fields([
    'bundle',
    'deleted',
    'entity_id',
    'revision_id',
    'langcode',
    'delta',
    'field_tags_target_id',
  ])
  ->values([
    'bundle' => 'article',
    'deleted' => '0',
    'entity_id' => '1',
    'revision_id' => '1',
    'langcode' => 'en',
    'delta' => '0',
    'field_tags_target_id' => '1',
  ])
  ->values([
    'bundle' => 'article',
    'deleted' => '0',
    'entity_id' => '2',
    'revision_id' => '2',
    'langcode' => 'en',
    'delta' => '0',
    'field_tags_target_id' => '1',
  ])
  ->values([
    'bundle' => 'article',
    'deleted' => '0',
    'entity_id' => '2',
    'revision_id' => '2',
    'langcode' => 'en',
    'delta' => '1',
    'field_tags_target_id' => '2',
  ])
  ->values([
    'bundle' => 'article',
    'deleted' => '0',
    'entity_id' => '3',
    'revision_id' => '3',
    'langcode' => 'en',
    'delta' => '0',
    'field_tags_target_id' => '3',
  ])
  ->values([
    'bundle' => 'article',
    'deleted' => '0',
    'entity_id' => '3',
    'revision_id' => '4',
    'langcode' => 'en',
    'delta' => '0',
    'field_tags_target_id' => '3',
  ])
  ->values([
    'bundle' => 'article',
    'deleted' => '0',
    'entity_id' => '3',
    'revision_id' => '5',
    'langcode' => 'en',
    'delta' => '0',
    'field_tags_target_id' => '3',
  ])
  ->values([
    'bundle' => 'article',
    'deleted' => '0',
    'entity_id' => '3',
    'revision_id' => '6',
    'langcode' => 'en',
    'delta' => '0',
    'field_tags_target_id' => '3',
  ])
  ->values([
    'bundle' => 'article',
    'deleted' => '0',
    'entity_id' => '3',
    'revision_id' => '7',
    'langcode' => 'en',
    'delta' => '0',
    'field_tags_target_id' => '3',
  ])
  ->values([
    'bundle' => 'article',
    'deleted' => '0',
    'entity_id' => '4',
    'revision_id' => '8',
    'langcode' => 'en',
    'delta' => '0',
    'field_tags_target_id' => '3',
  ])
  ->values([
    'bundle' => 'article',
    'deleted' => '0',
    'entity_id' => '4',
    'revision_id' => '8',
    'langcode' => 'en',
    'delta' => '1',
    'field_tags_target_id' => '4',
  ])
  ->values([
    'bundle' => 'article',
    'deleted' => '0',
    'entity_id' => '4',
    'revision_id' => '9',
    'langcode' => 'en',
    'delta' => '0',
    'field_tags_target_id' => '3',
  ])
  ->values([
    'bundle' => 'article',
    'deleted' => '0',
    'entity_id' => '4',
    'revision_id' => '9',
    'langcode' => 'en',
    'delta' => '1',
    'field_tags_target_id' => '4',
  ])
  ->values([
    'bundle' => 'article',
    'deleted' => '0',
    'entity_id' => '4',
    'revision_id' => '10',
    'langcode' => 'en',
    'delta' => '0',
    'field_tags_target_id' => '3',
  ])
  ->values([
    'bundle' => 'article',
    'deleted' => '0',
    'entity_id' => '4',
    'revision_id' => '10',
    'langcode' => 'en',
    'delta' => '1',
    'field_tags_target_id' => '4',
  ])
  ->values([
    'bundle' => 'article',
    'deleted' => '0',
    'entity_id' => '4',
    'revision_id' => '11',
    'langcode' => 'en',
    'delta' => '0',
    'field_tags_target_id' => '3',
  ])
  ->values([
    'bundle' => 'article',
    'deleted' => '0',
    'entity_id' => '4',
    'revision_id' => '11',
    'langcode' => 'en',
    'delta' => '1',
    'field_tags_target_id' => '4',
  ])
  ->values([
    'bundle' => 'article',
    'deleted' => '0',
    'entity_id' => '4',
    'revision_id' => '12',
    'langcode' => 'en',
    'delta' => '0',
    'field_tags_target_id' => '3',
  ])
  ->values([
    'bundle' => 'article',
    'deleted' => '0',
    'entity_id' => '4',
    'revision_id' => '12',
    'langcode' => 'en',
    'delta' => '1',
    'field_tags_target_id' => '4',
  ])
  ->values([
    'bundle' => 'article',
    'deleted' => '0',
    'entity_id' => '5',
    'revision_id' => '13',
    'langcode' => 'en',
    'delta' => '0',
    'field_tags_target_id' => '5',
  ])
  ->values([
    'bundle' => 'article',
    'deleted' => '0',
    'entity_id' => '5',
    'revision_id' => '14',
    'langcode' => 'en',
    'delta' => '0',
    'field_tags_target_id' => '5',
  ])
  ->values([
    'bundle' => 'article',
    'deleted' => '0',
    'entity_id' => '5',
    'revision_id' => '15',
    'langcode' => 'en',
    'delta' => '0',
    'field_tags_target_id' => '5',
  ])
  ->values([
    'bundle' => 'article',
    'deleted' => '0',
    'entity_id' => '5',
    'revision_id' => '16',
    'langcode' => 'en',
    'delta' => '0',
    'field_tags_target_id' => '5',
  ])
  ->values([
    'bundle' => 'article',
    'deleted' => '0',
    'entity_id' => '5',
    'revision_id' => '17',
    'langcode' => 'en',
    'delta' => '0',
    'field_tags_target_id' => '5',
  ])
  ->values([
    'bundle' => 'article',
    'deleted' => '0',
    'entity_id' => '6',
    'revision_id' => '18',
    'langcode' => 'en',
    'delta' => '0',
    'field_tags_target_id' => '5',
  ])
  ->values([
    'bundle' => 'article',
    'deleted' => '0',
    'entity_id' => '6',
    'revision_id' => '18',
    'langcode' => 'en',
    'delta' => '1',
    'field_tags_target_id' => '6',
  ])
  ->values([
    'bundle' => 'article',
    'deleted' => '0',
    'entity_id' => '6',
    'revision_id' => '19',
    'langcode' => 'en',
    'delta' => '0',
    'field_tags_target_id' => '5',
  ])
  ->values([
    'bundle' => 'article',
    'deleted' => '0',
    'entity_id' => '6',
    'revision_id' => '19',
    'langcode' => 'en',
    'delta' => '1',
    'field_tags_target_id' => '6',
  ])
  ->values([
    'bundle' => 'article',
    'deleted' => '0',
    'entity_id' => '6',
    'revision_id' => '20',
    'langcode' => 'en',
    'delta' => '0',
    'field_tags_target_id' => '5',
  ])
  ->values([
    'bundle' => 'article',
    'deleted' => '0',
    'entity_id' => '6',
    'revision_id' => '20',
    'langcode' => 'en',
    'delta' => '1',
    'field_tags_target_id' => '6',
  ])
  ->values([
    'bundle' => 'article',
    'deleted' => '0',
    'entity_id' => '6',
    'revision_id' => '21',
    'langcode' => 'en',
    'delta' => '0',
    'field_tags_target_id' => '5',
  ])
  ->values([
    'bundle' => 'article',
    'deleted' => '0',
    'entity_id' => '6',
    'revision_id' => '21',
    'langcode' => 'en',
    'delta' => '1',
    'field_tags_target_id' => '6',
  ])
  ->values([
    'bundle' => 'article',
    'deleted' => '0',
    'entity_id' => '6',
    'revision_id' => '22',
    'langcode' => 'en',
    'delta' => '0',
    'field_tags_target_id' => '5',
  ])
  ->values([
    'bundle' => 'article',
    'deleted' => '0',
    'entity_id' => '6',
    'revision_id' => '22',
    'langcode' => 'en',
    'delta' => '1',
    'field_tags_target_id' => '6',
  ])
  ->execute();

// Add test data for taxonomy terms.
$connection->insert('taxonomy_index')
  ->fields([
    'nid',
    'tid',
    'status',
    'sticky',
    'created',
  ])
  ->values([
    'nid' => '1',
    'tid' => '1',
    'status' => '1',
    'sticky' => '0',
    'created' => '1755698853',
  ])
  ->execute();

$connection->insert('taxonomy_term__parent')
  ->fields([
    'bundle',
    'deleted',
    'entity_id',
    'revision_id',
    'langcode',
    'delta',
    'parent_target_id',
  ])
  ->values([
    'bundle' => 'tags',
    'deleted' => '0',
    'entity_id' => '1',
    'revision_id' => '1',
    'langcode' => 'en',
    'delta' => '0',
    'parent_target_id' => '0',
  ])
  ->values([
    'bundle' => 'tags',
    'deleted' => '0',
    'entity_id' => '2',
    'revision_id' => '2',
    'langcode' => 'en',
    'delta' => '0',
    'parent_target_id' => '0',
  ])
  ->values([
    'bundle' => 'tags',
    'deleted' => '0',
    'entity_id' => '3',
    'revision_id' => '3',
    'langcode' => 'en',
    'delta' => '0',
    'parent_target_id' => '0',
  ])
  ->values([
    'bundle' => 'tags',
    'deleted' => '0',
    'entity_id' => '4',
    'revision_id' => '5',
    'langcode' => 'en',
    'delta' => '0',
    'parent_target_id' => '0',
  ])
  ->values([
    'bundle' => 'tags',
    'deleted' => '0',
    'entity_id' => '5',
    'revision_id' => '7',
    'langcode' => 'en',
    'delta' => '0',
    'parent_target_id' => '0',
  ])
  ->values([
    'bundle' => 'tags',
    'deleted' => '0',
    'entity_id' => '6',
    'revision_id' => '9',
    'langcode' => 'en',
    'delta' => '0',
    'parent_target_id' => '0',
  ])
  ->execute();

$connection->insert('taxonomy_term_data')
  ->fields([
    'tid',
    'revision_id',
    'vid',
    'uuid',
    'langcode',
  ])
  ->values([
    'tid' => '1',
    'revision_id' => '1',
    'vid' => 'tags',
    'uuid' => 'c6d49392-4c77-47c0-999c-e45c72cf80dc',
    'langcode' => 'en',
  ])
  ->values([
    'tid' => '2',
    'revision_id' => '2',
    'vid' => 'tags',
    'uuid' => '54e42d74-2151-463a-8218-f88718aea47c',
    'langcode' => 'en',
  ])
  ->values([
    'tid' => '3',
    'revision_id' => '3',
    'vid' => 'tags',
    'uuid' => 'a26410e8-6950-47f8-ba29-89515f82fd05',
    'langcode' => 'en',
  ])
  ->values([
    'tid' => '4',
    'revision_id' => '5',
    'vid' => 'tags',
    'uuid' => '411e8a09-a538-4d88-b676-b4f18d7f0e55',
    'langcode' => 'en',
  ])
  ->values([
    'tid' => '5',
    'revision_id' => '7',
    'vid' => 'tags',
    'uuid' => '20b706a0-3723-4b06-b01c-4a02e37787c3',
    'langcode' => 'en',
  ])
  ->values([
    'tid' => '6',
    'revision_id' => '9',
    'vid' => 'tags',
    'uuid' => '5e2318bc-3e35-4f7a-9e2a-5a8e25e52efc',
    'langcode' => 'en',
  ])
  ->execute();

$connection->insert('taxonomy_term_field_data')
  ->fields([
    'tid',
    'revision_id',
    'vid',
    'langcode',
    'status',
    'name',
    'description__value',
    'description__format',
    'weight',
    'changed',
    'default_langcode',
    'revision_translation_affected',
  ])
  ->values([
    'tid' => '1',
    'revision_id' => '1',
    'vid' => 'tags',
    'langcode' => 'en',
    'status' => '1',
    'name' => 'live',
    'description__value' => NULL,
    'description__format' => NULL,
    'weight' => '0',
    'changed' => '1755698868',
    'default_langcode' => '1',
    'revision_translation_affected' => '1',
  ])
  ->values([
    'tid' => '2',
    'revision_id' => '2',
    'vid' => 'tags',
    'langcode' => 'en',
    'status' => '1',
    'name' => 'live-wip',
    'description__value' => NULL,
    'description__format' => NULL,
    'weight' => '0',
    'changed' => '1755698888',
    'default_langcode' => '1',
    'revision_translation_affected' => '1',
  ])
  ->values([
    'tid' => '3',
    'revision_id' => '3',
    'vid' => 'tags',
    'langcode' => 'en',
    'status' => '0',
    'name' => 'summer',
    'description__value' => NULL,
    'description__format' => NULL,
    'weight' => '0',
    'changed' => '1755698937',
    'default_langcode' => '1',
    'revision_translation_affected' => '1',
  ])
  ->values([
    'tid' => '4',
    'revision_id' => '5',
    'vid' => 'tags',
    'langcode' => 'en',
    'status' => '0',
    'name' => 'summer-wip',
    'description__value' => NULL,
    'description__format' => NULL,
    'weight' => '0',
    'changed' => '1755698987',
    'default_langcode' => '1',
    'revision_translation_affected' => '1',
  ])
  ->values([
    'tid' => '5',
    'revision_id' => '7',
    'vid' => 'tags',
    'langcode' => 'en',
    'status' => '0',
    'name' => 'winter',
    'description__value' => NULL,
    'description__format' => NULL,
    'weight' => '0',
    'changed' => '1755699040',
    'default_langcode' => '1',
    'revision_translation_affected' => '1',
  ])
  ->values([
    'tid' => '6',
    'revision_id' => '9',
    'vid' => 'tags',
    'langcode' => 'en',
    'status' => '0',
    'name' => 'winter-wip',
    'description__value' => NULL,
    'description__format' => NULL,
    'weight' => '0',
    'changed' => '1755699078',
    'default_langcode' => '1',
    'revision_translation_affected' => '1',
  ])
  ->execute();

$connection->insert('taxonomy_term_field_revision')
  ->fields([
    'tid',
    'revision_id',
    'langcode',
    'status',
    'name',
    'description__value',
    'description__format',
    'changed',
    'default_langcode',
    'revision_translation_affected',
  ])
  ->values([
    'tid' => '1',
    'revision_id' => '1',
    'langcode' => 'en',
    'status' => '1',
    'name' => 'live',
    'description__value' => NULL,
    'description__format' => NULL,
    'changed' => '1755698868',
    'default_langcode' => '1',
    'revision_translation_affected' => '1',
  ])
  ->values([
    'tid' => '2',
    'revision_id' => '2',
    'langcode' => 'en',
    'status' => '1',
    'name' => 'live-wip',
    'description__value' => NULL,
    'description__format' => NULL,
    'changed' => '1755698888',
    'default_langcode' => '1',
    'revision_translation_affected' => '1',
  ])
  ->values([
    'tid' => '3',
    'revision_id' => '3',
    'langcode' => 'en',
    'status' => '0',
    'name' => 'summer',
    'description__value' => NULL,
    'description__format' => NULL,
    'changed' => '1755698937',
    'default_langcode' => '1',
    'revision_translation_affected' => '1',
  ])
  ->values([
    'tid' => '3',
    'revision_id' => '4',
    'langcode' => 'en',
    'status' => '1',
    'name' => 'summer',
    'description__value' => NULL,
    'description__format' => NULL,
    'changed' => '1755698937',
    'default_langcode' => '1',
    'revision_translation_affected' => '1',
  ])
  ->values([
    'tid' => '4',
    'revision_id' => '5',
    'langcode' => 'en',
    'status' => '0',
    'name' => 'summer-wip',
    'description__value' => NULL,
    'description__format' => NULL,
    'changed' => '1755698987',
    'default_langcode' => '1',
    'revision_translation_affected' => '1',
  ])
  ->values([
    'tid' => '4',
    'revision_id' => '6',
    'langcode' => 'en',
    'status' => '1',
    'name' => 'summer-wip',
    'description__value' => NULL,
    'description__format' => NULL,
    'changed' => '1755698987',
    'default_langcode' => '1',
    'revision_translation_affected' => '1',
  ])
  ->values([
    'tid' => '5',
    'revision_id' => '7',
    'langcode' => 'en',
    'status' => '0',
    'name' => 'winter',
    'description__value' => NULL,
    'description__format' => NULL,
    'changed' => '1755699040',
    'default_langcode' => '1',
    'revision_translation_affected' => '1',
  ])
  ->values([
    'tid' => '5',
    'revision_id' => '8',
    'langcode' => 'en',
    'status' => '1',
    'name' => 'winter',
    'description__value' => NULL,
    'description__format' => NULL,
    'changed' => '1755699040',
    'default_langcode' => '1',
    'revision_translation_affected' => '1',
  ])
  ->values([
    'tid' => '6',
    'revision_id' => '9',
    'langcode' => 'en',
    'status' => '0',
    'name' => 'winter-wip',
    'description__value' => NULL,
    'description__format' => NULL,
    'changed' => '1755699078',
    'default_langcode' => '1',
    'revision_translation_affected' => '1',
  ])
  ->values([
    'tid' => '6',
    'revision_id' => '10',
    'langcode' => 'en',
    'status' => '1',
    'name' => 'winter-wip',
    'description__value' => NULL,
    'description__format' => NULL,
    'changed' => '1755699078',
    'default_langcode' => '1',
    'revision_translation_affected' => '1',
  ])
  ->execute();

$connection->insert('taxonomy_term_revision')
  ->fields([
    'tid',
    'revision_id',
    'langcode',
    'revision_user',
    'revision_created',
    'revision_log_message',
    'revision_default',
    'workspace',
  ])
  ->values([
    'tid' => '1',
    'revision_id' => '1',
    'langcode' => 'en',
    'revision_user' => NULL,
    'revision_created' => '1755698868',
    'revision_log_message' => NULL,
    'revision_default' => '1',
    'workspace' => NULL,
  ])
  ->values([
    'tid' => '2',
    'revision_id' => '2',
    'langcode' => 'en',
    'revision_user' => NULL,
    'revision_created' => '1755698888',
    'revision_log_message' => NULL,
    'revision_default' => '1',
    'workspace' => NULL,
  ])
  ->values([
    'tid' => '3',
    'revision_id' => '3',
    'langcode' => 'en',
    'revision_user' => NULL,
    'revision_created' => '1755698937',
    'revision_log_message' => NULL,
    'revision_default' => '1',
    'workspace' => 'summer',
  ])
  ->values([
    'tid' => '3',
    'revision_id' => '4',
    'langcode' => 'en',
    'revision_user' => NULL,
    'revision_created' => '1755698937',
    'revision_log_message' => NULL,
    'revision_default' => '0',
    'workspace' => 'summer',
  ])
  ->values([
    'tid' => '4',
    'revision_id' => '5',
    'langcode' => 'en',
    'revision_user' => NULL,
    'revision_created' => '1755698987',
    'revision_log_message' => NULL,
    'revision_default' => '1',
    'workspace' => 'summer',
  ])
  ->values([
    'tid' => '4',
    'revision_id' => '6',
    'langcode' => 'en',
    'revision_user' => NULL,
    'revision_created' => '1755698987',
    'revision_log_message' => NULL,
    'revision_default' => '0',
    'workspace' => 'summer',
  ])
  ->values([
    'tid' => '5',
    'revision_id' => '7',
    'langcode' => 'en',
    'revision_user' => NULL,
    'revision_created' => '1755699040',
    'revision_log_message' => NULL,
    'revision_default' => '1',
    'workspace' => 'winter',
  ])
  ->values([
    'tid' => '5',
    'revision_id' => '8',
    'langcode' => 'en',
    'revision_user' => NULL,
    'revision_created' => '1755699040',
    'revision_log_message' => NULL,
    'revision_default' => '0',
    'workspace' => 'winter',
  ])
  ->values([
    'tid' => '6',
    'revision_id' => '9',
    'langcode' => 'en',
    'revision_user' => NULL,
    'revision_created' => '1755699078',
    'revision_log_message' => NULL,
    'revision_default' => '1',
    'workspace' => 'winter',
  ])
  ->values([
    'tid' => '6',
    'revision_id' => '10',
    'langcode' => 'en',
    'revision_user' => NULL,
    'revision_created' => '1755699078',
    'revision_log_message' => NULL,
    'revision_default' => '0',
    'workspace' => 'winter',
  ])
  ->execute();

$connection->insert('taxonomy_term_revision__parent')
  ->fields([
    'bundle',
    'deleted',
    'entity_id',
    'revision_id',
    'langcode',
    'delta',
    'parent_target_id',
  ])
  ->values([
    'bundle' => 'tags',
    'deleted' => '0',
    'entity_id' => '1',
    'revision_id' => '1',
    'langcode' => 'en',
    'delta' => '0',
    'parent_target_id' => '0',
  ])
  ->values([
    'bundle' => 'tags',
    'deleted' => '0',
    'entity_id' => '2',
    'revision_id' => '2',
    'langcode' => 'en',
    'delta' => '0',
    'parent_target_id' => '0',
  ])
  ->values([
    'bundle' => 'tags',
    'deleted' => '0',
    'entity_id' => '3',
    'revision_id' => '3',
    'langcode' => 'en',
    'delta' => '0',
    'parent_target_id' => '0',
  ])
  ->values([
    'bundle' => 'tags',
    'deleted' => '0',
    'entity_id' => '3',
    'revision_id' => '4',
    'langcode' => 'en',
    'delta' => '0',
    'parent_target_id' => '0',
  ])
  ->values([
    'bundle' => 'tags',
    'deleted' => '0',
    'entity_id' => '4',
    'revision_id' => '5',
    'langcode' => 'en',
    'delta' => '0',
    'parent_target_id' => '0',
  ])
  ->values([
    'bundle' => 'tags',
    'deleted' => '0',
    'entity_id' => '4',
    'revision_id' => '6',
    'langcode' => 'en',
    'delta' => '0',
    'parent_target_id' => '0',
  ])
  ->values([
    'bundle' => 'tags',
    'deleted' => '0',
    'entity_id' => '5',
    'revision_id' => '7',
    'langcode' => 'en',
    'delta' => '0',
    'parent_target_id' => '0',
  ])
  ->values([
    'bundle' => 'tags',
    'deleted' => '0',
    'entity_id' => '5',
    'revision_id' => '8',
    'langcode' => 'en',
    'delta' => '0',
    'parent_target_id' => '0',
  ])
  ->values([
    'bundle' => 'tags',
    'deleted' => '0',
    'entity_id' => '6',
    'revision_id' => '9',
    'langcode' => 'en',
    'delta' => '0',
    'parent_target_id' => '0',
  ])
  ->values([
    'bundle' => 'tags',
    'deleted' => '0',
    'entity_id' => '6',
    'revision_id' => '10',
    'langcode' => 'en',
    'delta' => '0',
    'parent_target_id' => '0',
  ])
  ->execute();
