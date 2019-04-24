<?php

/**
 * @file
 * Contains database additions to drupal-8.4.0.bare.standard.php.gz for testing
 * upgrade path of https://www.drupal.org/project/drupal/issues/2904550.
 */

use Drupal\Core\Database\Database;

$connection = Database::getConnection();

$connection->delete('config')
  ->condition('name', [
    'core.extension',
    'core.entity_view_display.node.page.default',
    'core.entity_form_display.node.page.default',
  ], 'IN')
  ->execute();

$connection->insert('config')
  ->fields([
    'collection',
    'name',
    'data',
  ])
  ->values([
    'collection' => '',
    'name' => 'core.extension',
    'data' => 'a:4:{s:6:"module";a:44:{s:14:"automated_cron";i:0;s:5:"block";i:0;s:13:"block_content";i:0;s:10:"breakpoint";i:0;s:8:"ckeditor";i:0;s:5:"color";i:0;s:7:"comment";i:0;s:6:"config";i:0;s:7:"contact";i:0;s:10:"contextual";i:0;s:8:"datetime";i:0;s:5:"dblog";i:0;s:18:"dynamic_page_cache";i:0;s:6:"editor";i:0;s:5:"field";i:0;s:12:"field_layout";i:0;s:8:"field_ui";i:0;s:4:"file";i:0;s:6:"filter";i:0;s:4:"help";i:0;s:7:"history";i:0;s:5:"image";i:0;s:16:"layout_discovery";i:0;s:4:"link";i:0;s:7:"menu_ui";i:0;s:4:"node";i:0;s:7:"options";i:0;s:10:"page_cache";i:0;s:4:"path";i:0;s:9:"quickedit";i:0;s:3:"rdf";i:0;s:6:"search";i:0;s:8:"shortcut";i:0;s:6:"system";i:0;s:8:"taxonomy";i:0;s:4:"text";i:0;s:7:"toolbar";i:0;s:4:"tour";i:0;s:6:"update";i:0;s:4:"user";i:0;s:8:"views_ui";i:0;s:17:"menu_link_content";i:1;s:5:"views";i:10;s:8:"standard";i:1000;}s:5:"theme";a:5:{s:6:"stable";i:0;s:6:"classy";i:0;s:6:"bartik";i:0;s:5:"seven";i:0;s:17:"test_layout_theme";i:0;}s:7:"profile";s:8:"standard";s:5:"_core";a:1:{s:19:"default_config_hash";s:43:"R4IF-ClDHXxblLcG0L7MgsLvfBIMAvi_skumNFQwkDc";}}',
  ])
  ->values([
    'collection' => '',
    'name' => 'core.entity_view_display.node.page.default',
    'data' => 'a:12:{s:4:"uuid";s:36:"bf0e7e89-41b9-4031-adef-09933affbfe0";s:8:"langcode";s:2:"en";s:6:"status";b:1;s:12:"dependencies";a:2:{s:6:"config";a:2:{i:0;s:26:"field.field.node.page.body";i:1;s:14:"node.type.page";}s:6:"module";a:4:{i:0;s:12:"field_layout";i:1;s:17:"test_layout_theme";i:2;s:4:"text";i:3;s:4:"user";}}s:20:"third_party_settings";a:1:{s:12:"field_layout";a:2:{s:2:"id";s:17:"test_layout_theme";s:8:"settings";a:0:{}}}s:5:"_core";a:1:{s:19:"default_config_hash";s:43:"g1S3_GLaxq4l3I9RIca5Mlz02MxI2KmOquZpHw59akM";}s:2:"id";s:17:"node.page.default";s:16:"targetEntityType";s:4:"node";s:6:"bundle";s:4:"page";s:4:"mode";s:7:"default";s:7:"content";a:2:{s:4:"body";a:6:{s:5:"label";s:6:"hidden";s:4:"type";s:12:"text_default";s:6:"weight";i:100;s:6:"region";s:7:"content";s:8:"settings";a:0:{}s:20:"third_party_settings";a:0:{}}s:5:"links";a:4:{s:6:"weight";i:101;s:6:"region";s:7:"content";s:8:"settings";a:0:{}s:20:"third_party_settings";a:0:{}}}s:6:"hidden";a:0:{}}',
  ])
  ->values([
    'collection' => '',
    'name' => 'core.entity_form_display.node.page.default',
    'data' => 'a:12:{s:4:"uuid";s:36:"6d390c8a-e5aa-41ee-98d3-1d422e497283";s:8:"langcode";s:2:"en";s:6:"status";b:1;s:12:"dependencies";a:2:{s:6:"config";a:2:{i:0;s:26:"field.field.node.page.body";i:1;s:14:"node.type.page";}s:6:"module";a:4:{i:0;s:12:"field_layout";i:1;s:4:"path";i:2;s:17:"test_layout_theme";i:3;s:4:"text";}}s:20:"third_party_settings";a:1:{s:12:"field_layout";a:2:{s:2:"id";s:17:"test_layout_theme";s:8:"settings";a:0:{}}}s:5:"_core";a:1:{s:19:"default_config_hash";s:43:"sb0qCkzU_8mNq29NehYAU8jCBXWPLeX0UN8sYFVGVcw";}s:2:"id";s:17:"node.page.default";s:16:"targetEntityType";s:4:"node";s:6:"bundle";s:4:"page";s:4:"mode";s:7:"default";s:7:"content";a:8:{s:4:"body";a:5:{s:4:"type";s:26:"text_textarea_with_summary";s:6:"weight";i:31;s:6:"region";s:7:"content";s:8:"settings";a:3:{s:4:"rows";i:9;s:12:"summary_rows";i:3;s:11:"placeholder";s:0:"";}s:20:"third_party_settings";a:0:{}}s:7:"created";a:5:{s:4:"type";s:18:"datetime_timestamp";s:6:"weight";i:10;s:6:"region";s:7:"content";s:8:"settings";a:0:{}s:20:"third_party_settings";a:0:{}}s:4:"path";a:5:{s:4:"type";s:4:"path";s:6:"weight";i:30;s:6:"region";s:7:"content";s:8:"settings";a:0:{}s:20:"third_party_settings";a:0:{}}s:7:"promote";a:5:{s:4:"type";s:16:"boolean_checkbox";s:8:"settings";a:1:{s:13:"display_label";b:1;}s:6:"weight";i:15;s:6:"region";s:7:"content";s:20:"third_party_settings";a:0:{}}s:6:"status";a:5:{s:4:"type";s:16:"boolean_checkbox";s:8:"settings";a:1:{s:13:"display_label";b:1;}s:6:"weight";i:120;s:6:"region";s:7:"content";s:20:"third_party_settings";a:0:{}}s:6:"sticky";a:5:{s:4:"type";s:16:"boolean_checkbox";s:8:"settings";a:1:{s:13:"display_label";b:1;}s:6:"weight";i:16;s:6:"region";s:7:"content";s:20:"third_party_settings";a:0:{}}s:5:"title";a:5:{s:4:"type";s:16:"string_textfield";s:6:"weight";i:-5;s:6:"region";s:7:"content";s:8:"settings";a:2:{s:4:"size";i:60;s:11:"placeholder";s:0:"";}s:20:"third_party_settings";a:0:{}}s:3:"uid";a:5:{s:4:"type";s:29:"entity_reference_autocomplete";s:6:"weight";i:5;s:6:"region";s:7:"content";s:8:"settings";a:3:{s:14:"match_operator";s:8:"CONTAINS";s:4:"size";i:60;s:11:"placeholder";s:0:"";}s:20:"third_party_settings";a:0:{}}}s:6:"hidden";a:0:{}}',
  ])
  ->execute();
