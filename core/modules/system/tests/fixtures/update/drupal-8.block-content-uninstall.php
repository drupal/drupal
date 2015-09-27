<?php

/**
 * @file
 * Partial database to mimic the uninstallation of the block_content module.
 */

use Drupal\Core\Database\Database;

$connection = Database::getConnection();

$connection->schema()->dropTable('block_content');
$connection->schema()->dropTable('block_content__body');
$connection->schema()->dropTable('block_content_field_data');
$connection->schema()->dropTable('block_content_field_revision');
$connection->schema()->dropTable('block_content_revision');
$connection->schema()->dropTable('block_content_revision__body');

$connection->update('config')
  ->condition('collection', '')
  ->condition('name', 'core.extension')
  ->fields(['data' => 'a:2:{s:6:"module";a:39:{s:5:"block";i:0;s:10:"breakpoint";i:0;s:8:"ckeditor";i:0;s:5:"color";i:0;s:7:"comment";i:0;s:6:"config";i:0;s:7:"contact";i:0;s:10:"contextual";i:0;s:8:"datetime";i:0;s:5:"dblog";i:0;s:6:"editor";i:0;s:16:"entity_reference";i:0;s:5:"field";i:0;s:8:"field_ui";i:0;s:4:"file";i:0;s:6:"filter";i:0;s:4:"help";i:0;s:7:"history";i:0;s:5:"image";i:0;s:4:"link";i:0;s:7:"menu_ui";i:0;s:4:"node";i:0;s:7:"options";i:0;s:10:"page_cache";i:0;s:4:"path";i:0;s:9:"quickedit";i:0;s:3:"rdf";i:0;s:6:"search";i:0;s:8:"shortcut";i:0;s:6:"system";i:0;s:8:"taxonomy";i:0;s:4:"text";i:0;s:7:"toolbar";i:0;s:4:"tour";i:0;s:4:"user";i:0;s:8:"views_ui";i:0;s:17:"menu_link_content";i:1;s:5:"views";i:10;s:8:"standard";i:1000;}s:5:"theme";a:3:{s:6:"classy";i:0;s:6:"bartik";i:0;s:5:"seven";i:0;}}'])
  ->execute();

$connection->delete('config')
  ->condition('name', 'block_content.type.basic')
  ->execute();
$connection->delete('config')
  ->condition('name', 'core.entity_form_display.block_content.basic.default')
  ->execute();
$connection->delete('config')
  ->condition('name', 'core.entity_view_display.block_content.basic.default')
  ->execute();
$connection->delete('config')
  ->condition('name', 'core.entity_view_mode.block_content.full')
  ->execute();
$connection->delete('config')
  ->condition('name', 'field.field.block_content.basic.body')
  ->execute();
$connection->delete('config')
  ->condition('name', 'field.storage.block_content.body')
  ->execute();
$connection->delete('config')
  ->condition('name', 'views.view.block_content')
  ->execute();

$connection->delete('key_value')
  ->condition('collection', 'config.entity.key_store.block_content_type')
  ->execute();
$connection->delete('key_value')
  ->condition('collection', 'config.entity.key_store.entity_form_display')
  ->condition('value', '%.block_content.%', 'LIKE')
  ->execute();
$connection->delete('key_value')
  ->condition('collection', 'config.entity.key_store.entity_view_display')
  ->condition('value', '%.block_content.%', 'LIKE')
  ->execute();
$connection->delete('key_value')
  ->condition('collection', 'config.entity.key_store.entity_view_mode')
  ->condition('value', '%.block_content.%', 'LIKE')
  ->execute();
$connection->delete('key_value')
  ->condition('collection', 'config.entity.key_store.field_config')
  ->condition('value', '%.block_content.%', 'LIKE')
  ->execute();
$connection->delete('key_value')
  ->condition('collection', 'config.entity.key_store.field_storage_config')
  ->condition('value', '%.block_content.%', 'LIKE')
  ->execute();
$connection->delete('key_value')
  ->condition('collection', 'config.entity.key_store.view')
  ->condition('value', '%.block_content"%', 'LIKE')
  ->execute();

$connection->update('key_value')
  ->condition('collection', 'entity.definitions.bundle_field_map')
  ->condition('name', 'block_content')
  ->fields(['value' => 'a:0:{}'])
  ->execute();

$connection->delete('key_value')
  ->condition('collection', 'entity.definitions.installed')
  ->condition('name', 'block_content.entity_type')
  ->execute();
$connection->delete('key_value')
  ->condition('collection', 'entity.definitions.installed')
  ->condition('name', 'block_content.field_storage_definitions')
  ->execute();
$connection->delete('key_value')
  ->condition('collection', 'entity.definitions.installed')
  ->condition('name', 'block_content_type.entity_type')
  ->execute();
$connection->delete('key_value')
  ->condition('collection', 'entity.storage_schema.sql')
  ->condition('name', 'block_content.entity_schema_data')
  ->execute();
$connection->delete('key_value')
  ->condition('collection', 'entity.storage_schema.sql')
  ->condition('name', 'block_content.field_schema_data.%', 'LIKE')
  ->execute();

$connection->update('key_value')
  ->condition('collection', 'state')
  ->condition('name', 'router.path_roots')
  ->fields(['value' => 'a:27:{i:0;s:5:"admin";i:1;s:14:"block-category";i:2;s:7:"comment";i:3;s:8:"comments";i:4;s:7:"contact";i:5;s:4:"user";i:6;s:10:"contextual";i:7;s:6:"editor";i:8;s:4:"file";i:9;s:6:"filter";i:10;s:7:"history";i:11;s:5:"sites";i:12;s:6:"system";i:13;s:4:"node";i:14;s:9:"quickedit";i:15;s:6:"search";i:16;s:4:"cron";i:17;s:12:"machine_name";i:18;s:0:"";i:19;s:9:"<current>";i:20;s:5:"batch";i:21;s:10:"update.php";i:22;s:29:"entity_reference_autocomplete";i:23;s:8:"taxonomy";i:24;s:7:"toolbar";i:25;s:7:"rss.xml";i:26;s:5:"views";}'])
  ->execute();
$connection->update('key_value')
  ->condition('collection', 'state')
  ->condition('name', 'routing.non_admin_routes')
  ->fields(['value' => 'a:79:{i:0;s:27:"block.category_autocomplete";i:1;s:24:"entity.comment.edit_form";i:2;s:15:"comment.approve";i:3;s:24:"entity.comment.canonical";i:4;s:26:"entity.comment.delete_form";i:5;s:13:"comment.reply";i:6;s:31:"comment.new_comments_node_links";i:7;s:21:"comment.node_redirect";i:8;s:17:"contact.site_page";i:9;s:22:"contact.site_page_form";i:10;s:24:"entity.user.contact_form";i:11;s:17:"contextual.render";i:12;s:17:"editor.filter_xss";i:13;s:31:"editor.field_untransformed_text";i:14;s:19:"editor.image_dialog";i:15;s:18:"editor.link_dialog";i:16;s:18:"file.ajax_progress";i:17;s:15:"filter.tips_all";i:18;s:11:"filter.tips";i:19;s:26:"history.get_last_node_view";i:20;s:17:"history.read_node";i:21;s:18:"image.style_public";i:22;s:19:"image.style_private";i:23;s:13:"node.add_page";i:24;s:8:"node.add";i:25;s:19:"entity.node.preview";i:26;s:27:"entity.node.version_history";i:27;s:20:"entity.node.revision";i:28;s:28:"node.revision_revert_confirm";i:29;s:28:"node.revision_delete_confirm";i:30;s:18:"quickedit.metadata";i:31;s:21:"quickedit.attachments";i:32;s:20:"quickedit.field_form";i:33;s:21:"quickedit.entity_save";i:34;s:11:"search.view";i:35;s:23:"search.view_node_search";i:36;s:23:"search.help_node_search";i:37;s:23:"search.view_user_search";i:38;s:23:"search.help_user_search";i:39;s:19:"shortcut.set_switch";i:40;s:11:"system.ajax";i:41;s:10:"system.401";i:42;s:10:"system.403";i:43;s:10:"system.404";i:44;s:11:"system.cron";i:45;s:33:"system.machine_name_transliterate";i:46;s:12:"system.files";i:47;s:28:"system.private_file_download";i:48;s:16:"system.temporary";i:49;s:7:"<front>";i:50;s:6:"<none>";i:51;s:9:"<current>";i:52;s:15:"system.timezone";i:53;s:22:"system.batch_page.html";i:54;s:22:"system.batch_page.json";i:55;s:16:"system.db_update";i:56;s:26:"system.entity_autocomplete";i:57;s:30:"entity.taxonomy_term.edit_form";i:58;s:32:"entity.taxonomy_term.delete_form";i:59;s:16:"toolbar.subtrees";i:60;s:13:"user.register";i:61;s:11:"user.logout";i:62;s:9:"user.pass";i:63;s:9:"user.page";i:64;s:10:"user.login";i:65;s:19:"user.cancel_confirm";i:66;s:10:"user.reset";i:67;s:21:"view.frontpage.feed_1";i:68;s:21:"view.frontpage.page_1";i:69;s:25:"view.taxonomy_term.feed_1";i:70;s:25:"view.taxonomy_term.page_1";i:71;s:10:"views.ajax";i:72;s:21:"entity.node.canonical";i:73;s:23:"entity.node.delete_form";i:74;s:21:"entity.node.edit_form";i:75;s:21:"entity.user.canonical";i:76;s:21:"entity.user.edit_form";i:77;s:23:"entity.user.cancel_form";i:78;s:30:"entity.taxonomy_term.canonical";}'])
  ->execute();
$connection->update('key_value')
  ->condition('collection', 'state')
  ->condition('name', 'views.view_route_names')
  ->fields(['value' => 'a:8:{s:24:"user_admin_people.page_1";s:22:"entity.user.collection";s:20:"taxonomy_term.page_1";s:30:"entity.taxonomy_term.canonical";s:14:"content.page_1";s:20:"system.admin_content";s:12:"files.page_1";s:17:"view.files.page_1";s:12:"files.page_2";s:17:"view.files.page_2";s:16:"frontpage.feed_1";s:21:"view.frontpage.feed_1";s:16:"frontpage.page_1";s:21:"view.frontpage.page_1";s:20:"taxonomy_term.feed_1";s:25:"view.taxonomy_term.feed_1";}'])
  ->execute();

$connection->delete('key_value')
  ->condition('collection', 'system.schema')
  ->condition('name', 'block_content')
  ->execute();

$connection->delete('router')
  ->condition('name', 'block_content.%', 'LIKE')
  ->execute();
$connection->delete('router')
  ->condition('name', 'entity.block_content.%', 'LIKE')
  ->execute();
$connection->delete('router')
  ->condition('name', 'entity.entity_form_display.block_content.%', 'LIKE')
  ->execute();
$connection->delete('router')
  ->condition('name', 'entity.entity_view_display.block_content.%', 'LIKE')
  ->execute();
$connection->delete('router')
  ->condition('name', 'entity.field_config.block_content_%', 'LIKE')
  ->execute();
$connection->delete('router')
  ->condition('name', 'field_ui.field_storage_config_add_block_content')
  ->execute();
$connection->delete('router')
  ->condition('name', 'view.block_content.page_1')
  ->execute();
$connection->delete('router')
  ->condition('name', 'entity.block_content_type.collection')
  ->execute();
$connection->delete('router')
  ->condition('name', 'entity.block_content_type.%', 'LIKE')
  ->execute();

