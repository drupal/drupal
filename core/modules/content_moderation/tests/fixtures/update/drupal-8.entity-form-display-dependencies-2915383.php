<?php
// @codingStandardsIgnoreFile
/**
 * @file
 * Content for the update path test in #2915383.
 *
 * @see \Drupal\Tests\content_moderation\Functional\EntityFormDisplayDependenciesUpdateTest
 */

use Drupal\Core\Database\Database;

$connection = Database::getConnection();

$connection->update('config')
  ->fields([
    'data' => 'a:11:{s:4:"uuid";s:36:"16624d7d-0800-4ed7-9861-41f7e71394a8";s:8:"langcode";s:2:"en";s:6:"status";b:1;s:12:"dependencies";a:2:{s:6:"config";a:2:{i:0;s:24:"block_content.type.basic";i:1;s:36:"field.field.block_content.basic.body";}s:6:"module";a:2:{i:0;s:18:"content_moderation";i:1;s:4:"text";}}s:5:"_core";a:1:{s:19:"default_config_hash";s:43:"e1Nu5xXAuF_QplbBUhQBPLnYWvHtDX0MkZnpuCiY8uM";}s:2:"id";s:27:"block_content.basic.default";s:16:"targetEntityType";s:13:"block_content";s:6:"bundle";s:5:"basic";s:4:"mode";s:7:"default";s:7:"content";a:3:{s:4:"body";a:5:{s:4:"type";s:26:"text_textarea_with_summary";s:6:"weight";i:-4;s:6:"region";s:7:"content";s:8:"settings";a:3:{s:4:"rows";i:9;s:12:"summary_rows";i:3;s:11:"placeholder";s:0:"";}s:20:"third_party_settings";a:0:{}}s:4:"info";a:5:{s:4:"type";s:16:"string_textfield";s:6:"weight";i:-5;s:6:"region";s:7:"content";s:8:"settings";a:2:{s:4:"size";i:60;s:11:"placeholder";s:0:"";}s:20:"third_party_settings";a:0:{}}s:16:"moderation_state";a:5:{s:4:"type";s:24:"moderation_state_default";s:6:"weight";i:100;s:8:"settings";a:0:{}s:6:"region";s:7:"content";s:20:"third_party_settings";a:0:{}}}s:6:"hidden";a:0:{}}',
  ])
  ->condition('name', 'core.entity_form_display.block_content.basic.default')
  ->execute();

$connection->update('config')
  ->fields([
    'data' => 'a:11:{s:4:"uuid";s:36:"af6ca931-0ecc-46c0-8097-ffb383db6287";s:8:"langcode";s:2:"en";s:6:"status";b:1;s:12:"dependencies";a:2:{s:6:"config";a:6:{i:0;s:29:"field.field.node.article.body";i:1;s:32:"field.field.node.article.comment";i:2;s:36:"field.field.node.article.field_image";i:3;s:35:"field.field.node.article.field_tags";i:4;s:21:"image.style.thumbnail";i:5;s:17:"node.type.article";}s:6:"module";a:5:{i:0;s:7:"comment";i:1;s:18:"content_moderation";i:2;s:5:"image";i:3;s:4:"path";i:4;s:4:"text";}}s:5:"_core";a:1:{s:19:"default_config_hash";s:43:"buc38w3gxCqFnjINJhMiJvPpj9jWflKvlKDyBVMPVvw";}s:2:"id";s:20:"node.article.default";s:16:"targetEntityType";s:4:"node";s:6:"bundle";s:7:"article";s:4:"mode";s:7:"default";s:7:"content";a:12:{s:4:"body";a:5:{s:4:"type";s:26:"text_textarea_with_summary";s:6:"weight";i:1;s:6:"region";s:7:"content";s:8:"settings";a:3:{s:4:"rows";i:9;s:12:"summary_rows";i:3;s:11:"placeholder";s:0:"";}s:20:"third_party_settings";a:0:{}}s:7:"comment";a:5:{s:4:"type";s:15:"comment_default";s:6:"weight";i:20;s:6:"region";s:7:"content";s:8:"settings";a:0:{}s:20:"third_party_settings";a:0:{}}s:7:"created";a:5:{s:4:"type";s:18:"datetime_timestamp";s:6:"weight";i:10;s:6:"region";s:7:"content";s:8:"settings";a:0:{}s:20:"third_party_settings";a:0:{}}s:11:"field_image";a:5:{s:4:"type";s:11:"image_image";s:6:"weight";i:4;s:6:"region";s:7:"content";s:8:"settings";a:2:{s:18:"progress_indicator";s:8:"throbber";s:19:"preview_image_style";s:9:"thumbnail";}s:20:"third_party_settings";a:0:{}}s:10:"field_tags";a:5:{s:4:"type";s:34:"entity_reference_autocomplete_tags";s:6:"weight";i:3;s:6:"region";s:7:"content";s:8:"settings";a:3:{s:14:"match_operator";s:8:"CONTAINS";s:4:"size";i:60;s:11:"placeholder";s:0:"";}s:20:"third_party_settings";a:0:{}}s:16:"moderation_state";a:5:{s:4:"type";s:24:"moderation_state_default";s:6:"weight";i:100;s:8:"settings";a:0:{}s:6:"region";s:7:"content";s:20:"third_party_settings";a:0:{}}s:4:"path";a:5:{s:4:"type";s:4:"path";s:6:"weight";i:30;s:6:"region";s:7:"content";s:8:"settings";a:0:{}s:20:"third_party_settings";a:0:{}}s:7:"promote";a:5:{s:4:"type";s:16:"boolean_checkbox";s:8:"settings";a:1:{s:13:"display_label";b:1;}s:6:"weight";i:15;s:6:"region";s:7:"content";s:20:"third_party_settings";a:0:{}}s:6:"status";a:5:{s:4:"type";s:16:"boolean_checkbox";s:8:"settings";a:1:{s:13:"display_label";b:1;}s:6:"weight";i:120;s:6:"region";s:7:"content";s:20:"third_party_settings";a:0:{}}s:6:"sticky";a:5:{s:4:"type";s:16:"boolean_checkbox";s:8:"settings";a:1:{s:13:"display_label";b:1;}s:6:"weight";i:16;s:6:"region";s:7:"content";s:20:"third_party_settings";a:0:{}}s:5:"title";a:5:{s:4:"type";s:16:"string_textfield";s:6:"weight";i:0;s:6:"region";s:7:"content";s:8:"settings";a:2:{s:4:"size";i:60;s:11:"placeholder";s:0:"";}s:20:"third_party_settings";a:0:{}}s:3:"uid";a:5:{s:4:"type";s:29:"entity_reference_autocomplete";s:6:"weight";i:5;s:6:"region";s:7:"content";s:8:"settings";a:3:{s:14:"match_operator";s:8:"CONTAINS";s:4:"size";i:60;s:11:"placeholder";s:0:"";}s:20:"third_party_settings";a:0:{}}}s:6:"hidden";a:0:{}}',
  ])
  ->condition('name', 'core.entity_form_display.node.article.default')
  ->execute();

$connection->update('config')
  ->fields([
    'data' => 'a:9:{s:4:"uuid";s:36:"08b548c7-ff59-468b-9347-7d697680d035";s:8:"langcode";s:2:"en";s:6:"status";b:1;s:12:"dependencies";a:2:{s:6:"config";a:2:{i:0;s:17:"node.type.article";i:1;s:14:"node.type.page";}s:6:"module";a:1:{i:0;s:18:"content_moderation";}}s:5:"_core";a:1:{s:19:"default_config_hash";s:43:"T_JxNjYlfoRBi7Bj1zs5Xv9xv1btuBkKp5C1tNrjMhI";}s:2:"id";s:9:"editorial";s:5:"label";s:9:"Editorial";s:4:"type";s:18:"content_moderation";s:13:"type_settings";a:3:{s:6:"states";a:3:{s:8:"archived";a:4:{s:5:"label";s:8:"Archived";s:6:"weight";i:5;s:9:"published";b:0;s:16:"default_revision";b:1;}s:5:"draft";a:4:{s:5:"label";s:5:"Draft";s:9:"published";b:0;s:16:"default_revision";b:0;s:6:"weight";i:-5;}s:9:"published";a:4:{s:5:"label";s:9:"Published";s:9:"published";b:1;s:16:"default_revision";b:1;s:6:"weight";i:0;}}s:11:"transitions";a:5:{s:7:"archive";a:4:{s:5:"label";s:7:"Archive";s:4:"from";a:1:{i:0;s:9:"published";}s:2:"to";s:8:"archived";s:6:"weight";i:2;}s:14:"archived_draft";a:4:{s:5:"label";s:16:"Restore to Draft";s:4:"from";a:1:{i:0;s:8:"archived";}s:2:"to";s:5:"draft";s:6:"weight";i:3;}s:18:"archived_published";a:4:{s:5:"label";s:7:"Restore";s:4:"from";a:1:{i:0;s:8:"archived";}s:2:"to";s:9:"published";s:6:"weight";i:4;}s:16:"create_new_draft";a:4:{s:5:"label";s:16:"Create New Draft";s:2:"to";s:5:"draft";s:6:"weight";i:0;s:4:"from";a:2:{i:0;s:5:"draft";i:1;s:9:"published";}}s:7:"publish";a:4:{s:5:"label";s:7:"Publish";s:2:"to";s:9:"published";s:6:"weight";i:1;s:4:"from";a:2:{i:0;s:5:"draft";i:1;s:9:"published";}}}s:12:"entity_types";a:1:{s:4:"node";a:2:{i:0;s:7:"article";i:1;s:4:"page";}}}}',
  ])
  ->condition('name', 'workflows.workflow.editorial')
  ->execute();
