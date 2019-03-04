<?php
// @codingStandardsIgnoreFile

use Drupal\Core\Database\Database;

$connection = Database::getConnection();

// Set the schema version.
$connection->merge('key_value')
  ->fields([
    'value' => 'i:8000;',
    'name' => 'responsive_image',
    'collection' => 'system.schema',
  ])
  ->condition('collection', 'system.schema')
  ->condition('name', 'responsive_image')
  ->execute();

// Update core.extension.
$extensions = $connection->select('config')
  ->fields('config', ['data'])
  ->condition('collection', '')
  ->condition('name', 'core.extension')
  ->execute()
  ->fetchField();
$extensions = unserialize($extensions);
$extensions['module']['responsive_image'] = 8000;
$connection->update('config')
  ->fields([
    'data' => serialize($extensions),
    'collection' => '',
    'name' => 'core.extension',
  ])
  ->condition('collection', '')
  ->condition('name', 'core.extension')
  ->execute();

$connection->merge('key_value')
  ->condition('collection', 'entity.definitions.installed')
  ->condition('name', 'responsive_image_style.entity_type')
  ->fields([
    'value' => 'O:42:"Drupal\Core\Config\Entity\ConfigEntityType":44:{s:16:" * config_prefix";s:6:"styles";s:15:" * static_cache";b:0;s:14:" * lookup_keys";a:1:{i:0;s:4:"uuid";}s:16:" * config_export";a:5:{i:0;s:2:"id";i:1;s:5:"label";i:2;s:20:"image_style_mappings";i:3;s:16:"breakpoint_group";i:4;s:20:"fallback_image_style";}s:21:" * mergedConfigExport";a:0:{}s:15:" * render_cache";b:1;s:19:" * persistent_cache";b:1;s:14:" * entity_keys";a:8:{s:2:"id";s:2:"id";s:5:"label";s:5:"label";s:8:"revision";s:0:"";s:6:"bundle";s:0:"";s:8:"langcode";s:8:"langcode";s:16:"default_langcode";s:16:"default_langcode";s:29:"revision_translation_affected";s:29:"revision_translation_affected";s:4:"uuid";s:4:"uuid";}s:5:" * id";s:22:"responsive_image_style";s:16:" * originalClass";s:51:"Drupal\responsive_image\Entity\ResponsiveImageStyle";s:11:" * handlers";a:4:{s:12:"list_builder";s:55:"Drupal\responsive_image\ResponsiveImageStyleListBuilder";s:4:"form";a:4:{s:4:"edit";s:48:"Drupal\responsive_image\ResponsiveImageStyleForm";s:3:"add";s:48:"Drupal\responsive_image\ResponsiveImageStyleForm";s:6:"delete";s:35:"Drupal\Core\Entity\EntityDeleteForm";s:9:"duplicate";s:48:"Drupal\responsive_image\ResponsiveImageStyleForm";}s:6:"access";s:45:"Drupal\Core\Entity\EntityAccessControlHandler";s:7:"storage";s:45:"Drupal\Core\Config\Entity\ConfigEntityStorage";}s:19:" * admin_permission";s:28:"administer responsive images";s:25:" * permission_granularity";s:11:"entity_type";s:8:" * links";a:4:{s:9:"edit-form";s:67:"/admin/config/media/responsive-image-style/{responsive_image_style}";s:14:"duplicate-form";s:77:"/admin/config/media/responsive-image-style/{responsive_image_style}/duplicate";s:11:"delete-form";s:74:"/admin/config/media/responsive-image-style/{responsive_image_style}/delete";s:10:"collection";s:42:"/admin/config/media/responsive-image-style";}s:17:" * label_callback";N;s:21:" * bundle_entity_type";N;s:12:" * bundle_of";N;s:15:" * bundle_label";N;s:13:" * base_table";N;s:22:" * revision_data_table";N;s:17:" * revision_table";N;s:13:" * data_table";N;s:11:" * internal";b:0;s:15:" * translatable";b:0;s:19:" * show_revision_ui";b:0;s:8:" * label";O:48:"Drupal\Core\StringTranslation\TranslatableMarkup":3:{s:9:" * string";s:22:"Responsive image style";s:12:" * arguments";a:0:{}s:10:" * options";a:0:{}}s:19:" * label_collection";O:48:"Drupal\Core\StringTranslation\TranslatableMarkup":3:{s:9:" * string";s:23:"Responsive image styles";s:12:" * arguments";a:0:{}s:10:" * options";a:0:{}}s:17:" * label_singular";O:48:"Drupal\Core\StringTranslation\TranslatableMarkup":3:{s:9:" * string";s:22:"responsive image style";s:12:" * arguments";a:0:{}s:10:" * options";a:0:{}}s:15:" * label_plural";O:48:"Drupal\Core\StringTranslation\TranslatableMarkup":3:{s:9:" * string";s:23:"responsive image styles";s:12:" * arguments";a:0:{}s:10:" * options";a:0:{}}s:14:" * label_count";a:3:{s:8:"singular";s:29:"@count responsive image style";s:6:"plural";s:30:"@count responsive image styles";s:7:"context";N;}s:15:" * uri_callback";N;s:8:" * group";s:13:"configuration";s:14:" * group_label";O:48:"Drupal\Core\StringTranslation\TranslatableMarkup":3:{s:9:" * string";s:13:"Configuration";s:12:" * arguments";a:0:{}s:10:" * options";a:1:{s:7:"context";s:17:"Entity type group";}}s:22:" * field_ui_base_route";N;s:26:" * common_reference_target";b:0;s:22:" * list_cache_contexts";a:0:{}s:18:" * list_cache_tags";a:1:{i:0;s:34:"config:responsive_image_style_list";}s:14:" * constraints";a:0:{}s:13:" * additional";a:0:{}s:8:" * class";s:51:"Drupal\responsive_image\Entity\ResponsiveImageStyle";s:11:" * provider";s:16:"responsive_image";s:14:" * _serviceIds";a:0:{}s:18:" * _entityStorages";a:0:{}s:20:" * stringTranslation";N;}',
    'name' => 'responsive_image_style.entity_type',
    'collection' => 'entity.definitions.installed',
  ])
  ->execute();
