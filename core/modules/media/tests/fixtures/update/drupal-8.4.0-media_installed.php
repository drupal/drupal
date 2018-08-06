<?php
// @codingStandardsIgnoreFile
/**
 * @file
 * Contains database additions to drupal-8.4.0.bare.standard.php.gz for testing
 * the upgrade paths of media module.
 */

use Drupal\Core\Database\Database;

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

// Insert Media's config objects.
$connection->insert('config')
->fields(array(
  'collection',
  'name',
  'data',
))
->values(array(
  'collection' => '',
  'name' => 'core.entity_form_display.media.file.default',
  'data' => 'a:11:{s:4:"uuid";s:36:"94a6eff8-01b1-49e1-80f2-2a8d0e434bf4";s:8:"langcode";s:2:"en";s:6:"status";b:1;s:12:"dependencies";a:2:{s:6:"config";a:2:{i:0;s:39:"field.field.media.file.field_media_file";i:1;s:15:"media.type.file";}s:6:"module";a:2:{i:0;s:4:"file";i:1;s:4:"path";}}s:5:"_core";a:1:{s:19:"default_config_hash";s:43:"hXrcSi0w6aTt3jtOSoec3ah6KyHcQM__VFL8eWmmkfw";}s:2:"id";s:18:"media.file.default";s:16:"targetEntityType";s:5:"media";s:6:"bundle";s:4:"file";s:4:"mode";s:7:"default";s:7:"content";a:6:{s:7:"created";a:5:{s:4:"type";s:18:"datetime_timestamp";s:6:"weight";i:10;s:6:"region";s:7:"content";s:8:"settings";a:0:{}s:20:"third_party_settings";a:0:{}}s:16:"field_media_file";a:5:{s:8:"settings";a:1:{s:18:"progress_indicator";s:8:"throbber";}s:20:"third_party_settings";a:0:{}s:4:"type";s:12:"file_generic";s:6:"weight";i:26;s:6:"region";s:7:"content";}s:4:"name";a:5:{s:4:"type";s:16:"string_textfield";s:6:"weight";i:-5;s:6:"region";s:7:"content";s:8:"settings";a:2:{s:4:"size";i:60;s:11:"placeholder";s:0:"";}s:20:"third_party_settings";a:0:{}}s:4:"path";a:5:{s:4:"type";s:4:"path";s:6:"weight";i:30;s:6:"region";s:7:"content";s:8:"settings";a:0:{}s:20:"third_party_settings";a:0:{}}s:6:"status";a:5:{s:4:"type";s:16:"boolean_checkbox";s:8:"settings";a:1:{s:13:"display_label";b:1;}s:6:"weight";i:100;s:6:"region";s:7:"content";s:20:"third_party_settings";a:0:{}}s:3:"uid";a:5:{s:4:"type";s:29:"entity_reference_autocomplete";s:6:"weight";i:5;s:8:"settings";a:3:{s:14:"match_operator";s:8:"CONTAINS";s:4:"size";i:60;s:11:"placeholder";s:0:"";}s:6:"region";s:7:"content";s:20:"third_party_settings";a:0:{}}}s:6:"hidden";a:0:{}}',
))
->values(array(
  'collection' => '',
  'name' => 'core.entity_form_display.media.image.default',
  'data' => 'a:11:{s:4:"uuid";s:36:"5d083dca-077f-4dee-a7bc-beb1cdb1bfed";s:8:"langcode";s:2:"en";s:6:"status";b:1;s:12:"dependencies";a:2:{s:6:"config";a:3:{i:0;s:41:"field.field.media.image.field_media_image";i:1;s:21:"image.style.thumbnail";i:2;s:16:"media.type.image";}s:6:"module";a:2:{i:0;s:5:"image";i:1;s:4:"path";}}s:5:"_core";a:1:{s:19:"default_config_hash";s:43:"6Yw_Qc7BjR5JTvdpHMYqAjhoE_I2Q8fRVYCZPjfsERs";}s:2:"id";s:19:"media.image.default";s:16:"targetEntityType";s:5:"media";s:6:"bundle";s:5:"image";s:4:"mode";s:7:"default";s:7:"content";a:6:{s:7:"created";a:5:{s:4:"type";s:18:"datetime_timestamp";s:6:"weight";i:10;s:6:"region";s:7:"content";s:8:"settings";a:0:{}s:20:"third_party_settings";a:0:{}}s:17:"field_media_image";a:5:{s:8:"settings";a:2:{s:18:"progress_indicator";s:8:"throbber";s:19:"preview_image_style";s:9:"thumbnail";}s:20:"third_party_settings";a:0:{}s:4:"type";s:11:"image_image";s:6:"weight";i:26;s:6:"region";s:7:"content";}s:4:"name";a:5:{s:4:"type";s:16:"string_textfield";s:6:"weight";i:-5;s:6:"region";s:7:"content";s:8:"settings";a:2:{s:4:"size";i:60;s:11:"placeholder";s:0:"";}s:20:"third_party_settings";a:0:{}}s:4:"path";a:5:{s:4:"type";s:4:"path";s:6:"weight";i:30;s:6:"region";s:7:"content";s:8:"settings";a:0:{}s:20:"third_party_settings";a:0:{}}s:6:"status";a:5:{s:4:"type";s:16:"boolean_checkbox";s:8:"settings";a:1:{s:13:"display_label";b:1;}s:6:"weight";i:100;s:6:"region";s:7:"content";s:20:"third_party_settings";a:0:{}}s:3:"uid";a:5:{s:4:"type";s:29:"entity_reference_autocomplete";s:6:"weight";i:5;s:8:"settings";a:3:{s:14:"match_operator";s:8:"CONTAINS";s:4:"size";i:60;s:11:"placeholder";s:0:"";}s:6:"region";s:7:"content";s:20:"third_party_settings";a:0:{}}}s:6:"hidden";a:0:{}}',
))
->values(array(
  'collection' => '',
  'name' => 'core.entity_view_display.media.file.default',
  'data' => 'a:11:{s:4:"uuid";s:36:"4c2eab37-3139-429c-8a72-89e61b0e9d71";s:8:"langcode";s:2:"en";s:6:"status";b:1;s:12:"dependencies";a:2:{s:6:"config";a:2:{i:0;s:39:"field.field.media.file.field_media_file";i:1;s:15:"media.type.file";}s:6:"module";a:1:{i:0;s:4:"file";}}s:5:"_core";a:1:{s:19:"default_config_hash";s:43:"UT7DLCZ855GoaFZGnFwtEu42SskWbmmHvYDj4uUcuYQ";}s:2:"id";s:18:"media.file.default";s:16:"targetEntityType";s:5:"media";s:6:"bundle";s:4:"file";s:4:"mode";s:7:"default";s:7:"content";a:1:{s:16:"field_media_file";a:6:{s:5:"label";s:15:"visually_hidden";s:8:"settings";a:0:{}s:20:"third_party_settings";a:0:{}s:4:"type";s:12:"file_default";s:6:"weight";i:0;s:6:"region";s:7:"content";}}s:6:"hidden";a:3:{s:7:"created";b:1;s:9:"thumbnail";b:1;s:3:"uid";b:1;}}',
))
->values(array(
  'collection' => '',
  'name' => 'core.entity_view_display.media.image.default',
  'data' => 'a:11:{s:4:"uuid";s:36:"02272f0b-f511-4811-90c9-ea566372b668";s:8:"langcode";s:2:"en";s:6:"status";b:1;s:12:"dependencies";a:2:{s:6:"config";a:3:{i:0;s:41:"field.field.media.image.field_media_image";i:1;s:18:"image.style.medium";i:2;s:16:"media.type.image";}s:6:"module";a:1:{i:0;s:5:"image";}}s:5:"_core";a:1:{s:19:"default_config_hash";s:43:"Z-q-BsMo4WG234VrI7ezloYiI3MjbnKTZ_7YldIoo40";}s:2:"id";s:19:"media.image.default";s:16:"targetEntityType";s:5:"media";s:6:"bundle";s:5:"image";s:4:"mode";s:7:"default";s:7:"content";a:1:{s:17:"field_media_image";a:6:{s:5:"label";s:15:"visually_hidden";s:8:"settings";a:2:{s:11:"image_style";s:6:"medium";s:10:"image_link";s:4:"file";}s:20:"third_party_settings";a:0:{}s:4:"type";s:5:"image";s:6:"weight";i:0;s:6:"region";s:7:"content";}}s:6:"hidden";a:3:{s:7:"created";b:1;s:9:"thumbnail";b:1;s:3:"uid";b:1;}}',
))
->values(array(
  'collection' => '',
  'name' => 'field.field.media.file.field_media_file',
  'data' => 'a:17:{s:4:"uuid";s:36:"512ab3be-971f-4b59-b4f4-5fd1d5d50177";s:8:"langcode";s:2:"en";s:6:"status";b:1;s:12:"dependencies";a:3:{s:6:"config";a:2:{i:0;s:36:"field.storage.media.field_media_file";i:1;s:15:"media.type.file";}s:8:"enforced";a:1:{s:6:"module";a:1:{i:0;s:5:"media";}}s:6:"module";a:1:{i:0;s:4:"file";}}s:5:"_core";a:1:{s:19:"default_config_hash";s:43:"_C8rbTRQABc6PuyRw2LD9zdf_muwzZVumKG6HSfzqfI";}s:2:"id";s:27:"media.file.field_media_file";s:10:"field_name";s:16:"field_media_file";s:11:"entity_type";s:5:"media";s:6:"bundle";s:4:"file";s:5:"label";s:4:"File";s:11:"description";s:0:"";s:8:"required";b:1;s:12:"translatable";b:1;s:13:"default_value";a:0:{}s:22:"default_value_callback";s:0:"";s:8:"settings";a:6:{s:14:"file_directory";s:31:"[date:custom:Y]-[date:custom:m]";s:15:"file_extensions";s:96:"txt rtf doc docx ppt pptx xls xlsx pdf odf odg odp ods odt fodt fods fodp fodg key numbers pages";s:12:"max_filesize";s:0:"";s:7:"handler";s:12:"default:file";s:16:"handler_settings";a:0:{}s:17:"description_field";b:0;}s:10:"field_type";s:4:"file";}',
))
->values(array(
  'collection' => '',
  'name' => 'field.field.media.image.field_media_image',
  'data' => 'a:17:{s:4:"uuid";s:36:"3705074a-b693-4a44-badc-48f1c71f06b5";s:8:"langcode";s:2:"en";s:6:"status";b:1;s:12:"dependencies";a:3:{s:6:"config";a:2:{i:0;s:37:"field.storage.media.field_media_image";i:1;s:16:"media.type.image";}s:8:"enforced";a:1:{s:6:"module";a:1:{i:0;s:5:"media";}}s:6:"module";a:1:{i:0;s:5:"image";}}s:5:"_core";a:1:{s:19:"default_config_hash";s:43:"pzPA-2JwyxlJ3qMb4L9viAnhNhbEhl2couH8A3FO020";}s:2:"id";s:29:"media.image.field_media_image";s:10:"field_name";s:17:"field_media_image";s:11:"entity_type";s:5:"media";s:6:"bundle";s:5:"image";s:5:"label";s:5:"Image";s:11:"description";s:0:"";s:8:"required";b:1;s:12:"translatable";b:1;s:13:"default_value";a:0:{}s:22:"default_value_callback";s:0:"";s:8:"settings";a:12:{s:9:"alt_field";b:1;s:18:"alt_field_required";b:1;s:11:"title_field";b:0;s:20:"title_field_required";b:0;s:14:"max_resolution";s:0:"";s:14:"min_resolution";s:0:"";s:13:"default_image";a:5:{s:4:"uuid";N;s:3:"alt";s:0:"";s:5:"title";s:0:"";s:5:"width";N;s:6:"height";N;}s:14:"file_directory";s:31:"[date:custom:Y]-[date:custom:m]";s:15:"file_extensions";s:16:"png gif jpg jpeg";s:12:"max_filesize";s:0:"";s:7:"handler";s:12:"default:file";s:16:"handler_settings";a:0:{}}s:10:"field_type";s:5:"image";}',
))
->values(array(
  'collection' => '',
  'name' => 'field.storage.media.field_media_file',
  'data' => 'a:17:{s:4:"uuid";s:36:"9a4345cc-dc31-47d6-8735-2ddad3e67677";s:8:"langcode";s:2:"en";s:6:"status";b:1;s:12:"dependencies";a:2:{s:8:"enforced";a:1:{s:6:"module";a:1:{i:0;s:5:"media";}}s:6:"module";a:2:{i:0;s:4:"file";i:1;s:5:"media";}}s:5:"_core";a:1:{s:19:"default_config_hash";s:43:"4GNilUMnj0opT050eZIkWhkfuzu69ClyEr-cHxofjQw";}s:2:"id";s:22:"media.field_media_file";s:10:"field_name";s:16:"field_media_file";s:11:"entity_type";s:5:"media";s:4:"type";s:4:"file";s:8:"settings";a:4:{s:10:"uri_scheme";s:6:"public";s:11:"target_type";s:4:"file";s:13:"display_field";b:0;s:15:"display_default";b:0;}s:6:"module";s:4:"file";s:6:"locked";b:0;s:11:"cardinality";i:1;s:12:"translatable";b:1;s:7:"indexes";a:0:{}s:22:"persist_with_no_fields";b:0;s:14:"custom_storage";b:0;}',
))
->values(array(
  'collection' => '',
  'name' => 'field.storage.media.field_media_image',
  'data' => 'a:17:{s:4:"uuid";s:36:"d3fd56b6-fa0f-4690-9885-ccceb29a0ba3";s:8:"langcode";s:2:"en";s:6:"status";b:1;s:12:"dependencies";a:2:{s:8:"enforced";a:1:{s:6:"module";a:1:{i:0;s:5:"media";}}s:6:"module";a:3:{i:0;s:4:"file";i:1;s:5:"image";i:2;s:5:"media";}}s:5:"_core";a:1:{s:19:"default_config_hash";s:43:"7ZBrcl87ZXaw42v952wwcw_9cQgTBq5_5tgyUkE-VV0";}s:2:"id";s:23:"media.field_media_image";s:10:"field_name";s:17:"field_media_image";s:11:"entity_type";s:5:"media";s:4:"type";s:5:"image";s:8:"settings";a:5:{s:13:"default_image";a:5:{s:4:"uuid";N;s:3:"alt";s:0:"";s:5:"title";s:0:"";s:5:"width";N;s:6:"height";N;}s:11:"target_type";s:4:"file";s:13:"display_field";b:0;s:15:"display_default";b:0;s:10:"uri_scheme";s:6:"public";}s:6:"module";s:5:"image";s:6:"locked";b:0;s:11:"cardinality";i:1;s:12:"translatable";b:1;s:7:"indexes";a:0:{}s:22:"persist_with_no_fields";b:0;s:14:"custom_storage";b:0;}',
))
->values(array(
  'collection' => '',
  'name' => 'media.settings',
  'data' => 'a:2:{s:13:"icon_base_uri";s:28:"public://media-icons/generic";s:5:"_core";a:1:{s:19:"default_config_hash";s:43:"f_ouXNygByhTko09cj2hDG_CeERO4jKCV8zrIvORnd0";}}',
))
->values(array(
  'collection' => '',
  'name' => 'media.type.file',
  'data' => 'a:13:{s:4:"uuid";s:36:"f561bea5-6743-45dc-9ddf-07a61b589a70";s:8:"langcode";s:2:"en";s:6:"status";b:1;s:12:"dependencies";a:0:{}s:5:"_core";a:1:{s:19:"default_config_hash";s:43:"s6qJnINiq7zI-ZoQOXM_gtiDpIsHT3fm7RySP1F_BE0";}s:2:"id";s:4:"file";s:5:"label";s:4:"File";s:11:"description";s:35:"Use local files for reusable media.";s:6:"source";s:4:"file";s:25:"queue_thumbnail_downloads";b:0;s:12:"new_revision";b:1;s:20:"source_configuration";a:1:{s:12:"source_field";s:16:"field_media_file";}s:9:"field_map";a:0:{}}',
))
->values(array(
  'collection' => '',
  'name' => 'media.type.image',
  'data' => 'a:13:{s:4:"uuid";s:36:"1fc52468-4606-4313-9b6a-2a7631eb8d06";s:8:"langcode";s:2:"en";s:6:"status";b:1;s:12:"dependencies";a:0:{}s:5:"_core";a:1:{s:19:"default_config_hash";s:43:"u7OxrscEED35iDR3R0akmw7QNvBSMEPJXFtKP57qBh8";}s:2:"id";s:5:"image";s:5:"label";s:5:"Image";s:11:"description";s:36:"Use local images for reusable media.";s:6:"source";s:5:"image";s:25:"queue_thumbnail_downloads";b:0;s:12:"new_revision";b:1;s:20:"source_configuration";a:1:{s:12:"source_field";s:17:"field_media_image";}s:9:"field_map";a:0:{}}',
))
->values(array(
  'collection' => '',
  'name' => 'views.view.media',
  'data' => 'a:14:{s:4:"uuid";s:36:"eedf319c-a746-4091-a8c2-a9f98722cde9";s:8:"langcode";s:2:"en";s:6:"status";b:1;s:12:"dependencies";a:2:{s:6:"config";a:1:{i:0;s:21:"image.style.thumbnail";}s:6:"module";a:3:{i:0;s:5:"image";i:1;s:5:"media";i:2;s:4:"user";}}s:5:"_core";a:1:{s:19:"default_config_hash";s:43:"MSzaJjMXciXBt9NEFvf84VTylGFxbhiAQUdDc572T2c";}s:2:"id";s:5:"media";s:5:"label";s:5:"Media";s:6:"module";s:5:"views";s:11:"description";s:0:"";s:3:"tag";s:0:"";s:10:"base_table";s:16:"media_field_data";s:10:"base_field";s:3:"mid";s:4:"core";s:3:"8.x";s:7:"display";a:2:{s:7:"default";a:6:{s:14:"display_plugin";s:7:"default";s:2:"id";s:7:"default";s:13:"display_title";s:6:"Master";s:8:"position";i:0;s:15:"display_options";a:17:{s:6:"access";a:2:{s:4:"type";s:4:"perm";s:7:"options";a:1:{s:4:"perm";s:21:"access media overview";}}s:5:"cache";a:2:{s:4:"type";s:3:"tag";s:7:"options";a:0:{}}s:5:"query";a:2:{s:4:"type";s:11:"views_query";s:7:"options";a:5:{s:19:"disable_sql_rewrite";b:0;s:8:"distinct";b:0;s:7:"replica";b:0;s:13:"query_comment";s:0:"";s:10:"query_tags";a:0:{}}}s:12:"exposed_form";a:2:{s:4:"type";s:5:"basic";s:7:"options";a:7:{s:13:"submit_button";s:6:"Filter";s:12:"reset_button";b:0;s:18:"reset_button_label";s:5:"Reset";s:19:"exposed_sorts_label";s:7:"Sort by";s:17:"expose_sort_order";b:1;s:14:"sort_asc_label";s:3:"Asc";s:15:"sort_desc_label";s:4:"Desc";}}s:5:"pager";a:2:{s:4:"type";s:4:"full";s:7:"options";a:7:{s:14:"items_per_page";i:50;s:6:"offset";i:0;s:2:"id";i:0;s:11:"total_pages";N;s:6:"expose";a:7:{s:14:"items_per_page";b:0;s:20:"items_per_page_label";s:14:"Items per page";s:22:"items_per_page_options";s:13:"5, 10, 25, 50";s:26:"items_per_page_options_all";b:0;s:32:"items_per_page_options_all_label";s:7:"- All -";s:6:"offset";b:0;s:12:"offset_label";s:6:"Offset";}s:4:"tags";a:4:{s:8:"previous";s:12:"‹ Previous";s:4:"next";s:8:"Next ›";s:5:"first";s:8:"« First";s:4:"last";s:7:"Last »";}s:8:"quantity";i:9;}}s:5:"style";a:2:{s:4:"type";s:5:"table";s:7:"options";a:12:{s:8:"grouping";a:0:{}s:9:"row_class";s:0:"";s:17:"default_row_class";b:1;s:8:"override";b:1;s:6:"sticky";b:0;s:7:"caption";s:0:"";s:7:"summary";s:0:"";s:11:"description";s:0:"";s:7:"columns";a:6:{s:4:"name";s:4:"name";s:6:"bundle";s:6:"bundle";s:7:"changed";s:7:"changed";s:3:"uid";s:3:"uid";s:6:"status";s:6:"status";s:20:"thumbnail__target_id";s:20:"thumbnail__target_id";}s:4:"info";a:6:{s:4:"name";a:6:{s:8:"sortable";b:1;s:18:"default_sort_order";s:3:"asc";s:5:"align";s:0:"";s:9:"separator";s:0:"";s:12:"empty_column";b:0;s:10:"responsive";s:0:"";}s:6:"bundle";a:6:{s:8:"sortable";b:1;s:18:"default_sort_order";s:3:"asc";s:5:"align";s:0:"";s:9:"separator";s:0:"";s:12:"empty_column";b:0;s:10:"responsive";s:0:"";}s:7:"changed";a:6:{s:8:"sortable";b:1;s:18:"default_sort_order";s:4:"desc";s:5:"align";s:0:"";s:9:"separator";s:0:"";s:12:"empty_column";b:0;s:10:"responsive";s:0:"";}s:3:"uid";a:6:{s:8:"sortable";b:0;s:18:"default_sort_order";s:3:"asc";s:5:"align";s:0:"";s:9:"separator";s:0:"";s:12:"empty_column";b:0;s:10:"responsive";s:0:"";}s:6:"status";a:6:{s:8:"sortable";b:1;s:18:"default_sort_order";s:3:"asc";s:5:"align";s:0:"";s:9:"separator";s:0:"";s:12:"empty_column";b:0;s:10:"responsive";s:0:"";}s:20:"thumbnail__target_id";a:6:{s:8:"sortable";b:0;s:18:"default_sort_order";s:3:"asc";s:5:"align";s:0:"";s:9:"separator";s:0:"";s:12:"empty_column";b:0;s:10:"responsive";s:0:"";}}s:7:"default";s:7:"changed";s:11:"empty_table";b:1;}}s:3:"row";a:1:{s:4:"type";s:6:"fields";}s:6:"fields";a:7:{s:20:"thumbnail__target_id";a:37:{s:2:"id";s:20:"thumbnail__target_id";s:5:"table";s:16:"media_field_data";s:5:"field";s:20:"thumbnail__target_id";s:12:"relationship";s:4:"none";s:10:"group_type";s:5:"group";s:11:"admin_label";s:0:"";s:5:"label";s:9:"Thumbnail";s:7:"exclude";b:0;s:5:"alter";a:26:{s:10:"alter_text";b:0;s:4:"text";s:0:"";s:9:"make_link";b:0;s:4:"path";s:0:"";s:8:"absolute";b:0;s:8:"external";b:0;s:14:"replace_spaces";b:0;s:9:"path_case";s:4:"none";s:15:"trim_whitespace";b:0;s:3:"alt";s:0:"";s:3:"rel";s:0:"";s:10:"link_class";s:0:"";s:6:"prefix";s:0:"";s:6:"suffix";s:0:"";s:6:"target";s:0:"";s:5:"nl2br";b:0;s:10:"max_length";i:0;s:13:"word_boundary";b:1;s:8:"ellipsis";b:1;s:9:"more_link";b:0;s:14:"more_link_text";s:0:"";s:14:"more_link_path";s:0:"";s:10:"strip_tags";b:0;s:4:"trim";b:0;s:13:"preserve_tags";s:0:"";s:4:"html";b:0;}s:12:"element_type";s:0:"";s:13:"element_class";s:0:"";s:18:"element_label_type";s:0:"";s:19:"element_label_class";s:0:"";s:19:"element_label_colon";b:1;s:20:"element_wrapper_type";s:0:"";s:21:"element_wrapper_class";s:0:"";s:23:"element_default_classes";b:1;s:5:"empty";s:0:"";s:10:"hide_empty";b:0;s:10:"empty_zero";b:0;s:16:"hide_alter_empty";b:1;s:17:"click_sort_column";s:9:"target_id";s:4:"type";s:5:"image";s:8:"settings";a:2:{s:11:"image_style";s:9:"thumbnail";s:10:"image_link";s:0:"";}s:12:"group_column";s:0:"";s:13:"group_columns";a:0:{}s:10:"group_rows";b:1;s:11:"delta_limit";i:0;s:12:"delta_offset";i:0;s:14:"delta_reversed";b:0;s:16:"delta_first_last";b:0;s:10:"multi_type";s:9:"separator";s:9:"separator";s:2:", ";s:17:"field_api_classes";b:0;s:11:"entity_type";s:5:"media";s:12:"entity_field";s:9:"thumbnail";s:9:"plugin_id";s:5:"field";}s:4:"name";a:37:{s:2:"id";s:4:"name";s:5:"table";s:16:"media_field_data";s:5:"field";s:4:"name";s:11:"entity_type";s:5:"media";s:12:"entity_field";s:5:"media";s:5:"alter";a:8:{s:10:"alter_text";b:0;s:9:"make_link";b:0;s:8:"absolute";b:0;s:4:"trim";b:0;s:13:"word_boundary";b:0;s:8:"ellipsis";b:0;s:10:"strip_tags";b:0;s:4:"html";b:0;}s:10:"hide_empty";b:0;s:10:"empty_zero";b:0;s:8:"settings";a:1:{s:14:"link_to_entity";b:1;}s:9:"plugin_id";s:5:"field";s:12:"relationship";s:4:"none";s:10:"group_type";s:5:"group";s:11:"admin_label";s:0:"";s:5:"label";s:10:"Media name";s:7:"exclude";b:0;s:12:"element_type";s:0:"";s:13:"element_class";s:0:"";s:18:"element_label_type";s:0:"";s:19:"element_label_class";s:0:"";s:19:"element_label_colon";b:1;s:20:"element_wrapper_type";s:0:"";s:21:"element_wrapper_class";s:0:"";s:23:"element_default_classes";b:1;s:5:"empty";s:0:"";s:16:"hide_alter_empty";b:1;s:17:"click_sort_column";s:5:"value";s:4:"type";s:6:"string";s:12:"group_column";s:5:"value";s:13:"group_columns";a:0:{}s:10:"group_rows";b:1;s:11:"delta_limit";i:0;s:12:"delta_offset";i:0;s:14:"delta_reversed";b:0;s:16:"delta_first_last";b:0;s:10:"multi_type";s:9:"separator";s:9:"separator";s:2:", ";s:17:"field_api_classes";b:0;}s:6:"bundle";a:37:{s:2:"id";s:6:"bundle";s:5:"table";s:16:"media_field_data";s:5:"field";s:6:"bundle";s:12:"relationship";s:4:"none";s:10:"group_type";s:5:"group";s:11:"admin_label";s:0:"";s:5:"label";s:6:"Source";s:7:"exclude";b:0;s:5:"alter";a:26:{s:10:"alter_text";b:0;s:4:"text";s:0:"";s:9:"make_link";b:0;s:4:"path";s:0:"";s:8:"absolute";b:0;s:8:"external";b:0;s:14:"replace_spaces";b:0;s:9:"path_case";s:4:"none";s:15:"trim_whitespace";b:0;s:3:"alt";s:0:"";s:3:"rel";s:0:"";s:10:"link_class";s:0:"";s:6:"prefix";s:0:"";s:6:"suffix";s:0:"";s:6:"target";s:0:"";s:5:"nl2br";b:0;s:10:"max_length";i:0;s:13:"word_boundary";b:1;s:8:"ellipsis";b:1;s:9:"more_link";b:0;s:14:"more_link_text";s:0:"";s:14:"more_link_path";s:0:"";s:10:"strip_tags";b:0;s:4:"trim";b:0;s:13:"preserve_tags";s:0:"";s:4:"html";b:0;}s:12:"element_type";s:0:"";s:13:"element_class";s:0:"";s:18:"element_label_type";s:0:"";s:19:"element_label_class";s:0:"";s:19:"element_label_colon";b:1;s:20:"element_wrapper_type";s:0:"";s:21:"element_wrapper_class";s:0:"";s:23:"element_default_classes";b:1;s:5:"empty";s:0:"";s:10:"hide_empty";b:0;s:10:"empty_zero";b:0;s:16:"hide_alter_empty";b:1;s:17:"click_sort_column";s:9:"target_id";s:4:"type";s:22:"entity_reference_label";s:8:"settings";a:1:{s:4:"link";b:0;}s:12:"group_column";s:9:"target_id";s:13:"group_columns";a:0:{}s:10:"group_rows";b:1;s:11:"delta_limit";i:0;s:12:"delta_offset";i:0;s:14:"delta_reversed";b:0;s:16:"delta_first_last";b:0;s:10:"multi_type";s:9:"separator";s:9:"separator";s:2:", ";s:17:"field_api_classes";b:0;s:11:"entity_type";s:5:"media";s:12:"entity_field";s:6:"bundle";s:9:"plugin_id";s:5:"field";}s:3:"uid";a:37:{s:2:"id";s:3:"uid";s:5:"table";s:16:"media_field_data";s:5:"field";s:3:"uid";s:12:"relationship";s:4:"none";s:10:"group_type";s:5:"group";s:11:"admin_label";s:0:"";s:5:"label";s:6:"Author";s:7:"exclude";b:0;s:5:"alter";a:26:{s:10:"alter_text";b:0;s:4:"text";s:0:"";s:9:"make_link";b:0;s:4:"path";s:0:"";s:8:"absolute";b:0;s:8:"external";b:0;s:14:"replace_spaces";b:0;s:9:"path_case";s:4:"none";s:15:"trim_whitespace";b:0;s:3:"alt";s:0:"";s:3:"rel";s:0:"";s:10:"link_class";s:0:"";s:6:"prefix";s:0:"";s:6:"suffix";s:0:"";s:6:"target";s:0:"";s:5:"nl2br";b:0;s:10:"max_length";i:0;s:13:"word_boundary";b:1;s:8:"ellipsis";b:1;s:9:"more_link";b:0;s:14:"more_link_text";s:0:"";s:14:"more_link_path";s:0:"";s:10:"strip_tags";b:0;s:4:"trim";b:0;s:13:"preserve_tags";s:0:"";s:4:"html";b:0;}s:12:"element_type";s:0:"";s:13:"element_class";s:0:"";s:18:"element_label_type";s:0:"";s:19:"element_label_class";s:0:"";s:19:"element_label_colon";b:1;s:20:"element_wrapper_type";s:0:"";s:21:"element_wrapper_class";s:0:"";s:23:"element_default_classes";b:1;s:5:"empty";s:0:"";s:10:"hide_empty";b:0;s:10:"empty_zero";b:0;s:16:"hide_alter_empty";b:1;s:17:"click_sort_column";s:9:"target_id";s:4:"type";s:22:"entity_reference_label";s:8:"settings";a:1:{s:4:"link";b:1;}s:12:"group_column";s:9:"target_id";s:13:"group_columns";a:0:{}s:10:"group_rows";b:1;s:11:"delta_limit";i:0;s:12:"delta_offset";i:0;s:14:"delta_reversed";b:0;s:16:"delta_first_last";b:0;s:10:"multi_type";s:9:"separator";s:9:"separator";s:2:", ";s:17:"field_api_classes";b:0;s:11:"entity_type";s:5:"media";s:12:"entity_field";s:3:"uid";s:9:"plugin_id";s:5:"field";}s:6:"status";a:37:{s:2:"id";s:6:"status";s:5:"table";s:16:"media_field_data";s:5:"field";s:6:"status";s:12:"relationship";s:4:"none";s:10:"group_type";s:5:"group";s:11:"admin_label";s:0:"";s:5:"label";s:6:"Status";s:7:"exclude";b:0;s:5:"alter";a:26:{s:10:"alter_text";b:0;s:4:"text";s:0:"";s:9:"make_link";b:0;s:4:"path";s:0:"";s:8:"absolute";b:0;s:8:"external";b:0;s:14:"replace_spaces";b:0;s:9:"path_case";s:4:"none";s:15:"trim_whitespace";b:0;s:3:"alt";s:0:"";s:3:"rel";s:0:"";s:10:"link_class";s:0:"";s:6:"prefix";s:0:"";s:6:"suffix";s:0:"";s:6:"target";s:0:"";s:5:"nl2br";b:0;s:10:"max_length";i:0;s:13:"word_boundary";b:1;s:8:"ellipsis";b:1;s:9:"more_link";b:0;s:14:"more_link_text";s:0:"";s:14:"more_link_path";s:0:"";s:10:"strip_tags";b:0;s:4:"trim";b:0;s:13:"preserve_tags";s:0:"";s:4:"html";b:0;}s:12:"element_type";s:0:"";s:13:"element_class";s:0:"";s:18:"element_label_type";s:0:"";s:19:"element_label_class";s:0:"";s:19:"element_label_colon";b:1;s:20:"element_wrapper_type";s:0:"";s:21:"element_wrapper_class";s:0:"";s:23:"element_default_classes";b:1;s:5:"empty";s:0:"";s:10:"hide_empty";b:0;s:10:"empty_zero";b:0;s:16:"hide_alter_empty";b:1;s:17:"click_sort_column";s:5:"value";s:4:"type";s:7:"boolean";s:8:"settings";a:3:{s:6:"format";s:6:"custom";s:18:"format_custom_true";s:9:"Published";s:19:"format_custom_false";s:11:"Unpublished";}s:12:"group_column";s:5:"value";s:13:"group_columns";a:0:{}s:10:"group_rows";b:1;s:11:"delta_limit";i:0;s:12:"delta_offset";i:0;s:14:"delta_reversed";b:0;s:16:"delta_first_last";b:0;s:10:"multi_type";s:9:"separator";s:9:"separator";s:2:", ";s:17:"field_api_classes";b:0;s:11:"entity_type";s:5:"media";s:12:"entity_field";s:6:"status";s:9:"plugin_id";s:5:"field";}s:7:"changed";a:37:{s:2:"id";s:7:"changed";s:5:"table";s:16:"media_field_data";s:5:"field";s:7:"changed";s:12:"relationship";s:4:"none";s:10:"group_type";s:5:"group";s:11:"admin_label";s:0:"";s:5:"label";s:7:"Updated";s:7:"exclude";b:0;s:5:"alter";a:26:{s:10:"alter_text";b:0;s:4:"text";s:0:"";s:9:"make_link";b:0;s:4:"path";s:0:"";s:8:"absolute";b:0;s:8:"external";b:0;s:14:"replace_spaces";b:0;s:9:"path_case";s:4:"none";s:15:"trim_whitespace";b:0;s:3:"alt";s:0:"";s:3:"rel";s:0:"";s:10:"link_class";s:0:"";s:6:"prefix";s:0:"";s:6:"suffix";s:0:"";s:6:"target";s:0:"";s:5:"nl2br";b:0;s:10:"max_length";i:0;s:13:"word_boundary";b:1;s:8:"ellipsis";b:1;s:9:"more_link";b:0;s:14:"more_link_text";s:0:"";s:14:"more_link_path";s:0:"";s:10:"strip_tags";b:0;s:4:"trim";b:0;s:13:"preserve_tags";s:0:"";s:4:"html";b:0;}s:12:"element_type";s:0:"";s:13:"element_class";s:0:"";s:18:"element_label_type";s:0:"";s:19:"element_label_class";s:0:"";s:19:"element_label_colon";b:1;s:20:"element_wrapper_type";s:0:"";s:21:"element_wrapper_class";s:0:"";s:23:"element_default_classes";b:1;s:5:"empty";s:0:"";s:10:"hide_empty";b:0;s:10:"empty_zero";b:0;s:16:"hide_alter_empty";b:1;s:17:"click_sort_column";s:5:"value";s:4:"type";s:9:"timestamp";s:8:"settings";a:3:{s:11:"date_format";s:5:"short";s:18:"custom_date_format";s:0:"";s:8:"timezone";s:0:"";}s:12:"group_column";s:5:"value";s:13:"group_columns";a:0:{}s:10:"group_rows";b:1;s:11:"delta_limit";i:0;s:12:"delta_offset";i:0;s:14:"delta_reversed";b:0;s:16:"delta_first_last";b:0;s:10:"multi_type";s:9:"separator";s:9:"separator";s:2:", ";s:17:"field_api_classes";b:0;s:11:"entity_type";s:5:"media";s:12:"entity_field";s:7:"changed";s:9:"plugin_id";s:5:"field";}s:10:"operations";a:24:{s:2:"id";s:10:"operations";s:5:"table";s:5:"media";s:5:"field";s:10:"operations";s:12:"relationship";s:4:"none";s:10:"group_type";s:5:"group";s:11:"admin_label";s:0:"";s:5:"label";s:10:"Operations";s:7:"exclude";b:0;s:5:"alter";a:26:{s:10:"alter_text";b:0;s:4:"text";s:0:"";s:9:"make_link";b:0;s:4:"path";s:0:"";s:8:"absolute";b:0;s:8:"external";b:0;s:14:"replace_spaces";b:0;s:9:"path_case";s:4:"none";s:15:"trim_whitespace";b:0;s:3:"alt";s:0:"";s:3:"rel";s:0:"";s:10:"link_class";s:0:"";s:6:"prefix";s:0:"";s:6:"suffix";s:0:"";s:6:"target";s:0:"";s:5:"nl2br";b:0;s:10:"max_length";i:0;s:13:"word_boundary";b:1;s:8:"ellipsis";b:1;s:9:"more_link";b:0;s:14:"more_link_text";s:0:"";s:14:"more_link_path";s:0:"";s:10:"strip_tags";b:0;s:4:"trim";b:0;s:13:"preserve_tags";s:0:"";s:4:"html";b:0;}s:12:"element_type";s:0:"";s:13:"element_class";s:0:"";s:18:"element_label_type";s:0:"";s:19:"element_label_class";s:0:"";s:19:"element_label_colon";b:1;s:20:"element_wrapper_type";s:0:"";s:21:"element_wrapper_class";s:0:"";s:23:"element_default_classes";b:1;s:5:"empty";s:0:"";s:10:"hide_empty";b:0;s:10:"empty_zero";b:0;s:16:"hide_alter_empty";b:1;s:11:"destination";b:1;s:11:"entity_type";s:5:"media";s:9:"plugin_id";s:17:"entity_operations";}}s:7:"filters";a:4:{s:4:"name";a:16:{s:2:"id";s:4:"name";s:5:"table";s:16:"media_field_data";s:5:"field";s:4:"name";s:12:"relationship";s:4:"none";s:10:"group_type";s:5:"group";s:11:"admin_label";s:0:"";s:8:"operator";s:8:"contains";s:5:"value";s:0:"";s:5:"group";i:1;s:7:"exposed";b:1;s:6:"expose";a:10:{s:11:"operator_id";s:7:"name_op";s:5:"label";s:10:"Media name";s:11:"description";s:0:"";s:12:"use_operator";b:0;s:8:"operator";s:7:"name_op";s:10:"identifier";s:4:"name";s:8:"required";b:0;s:8:"remember";b:0;s:8:"multiple";b:0;s:14:"remember_roles";a:3:{s:13:"authenticated";s:13:"authenticated";s:9:"anonymous";s:1:"0";s:13:"administrator";s:1:"0";}}s:10:"is_grouped";b:0;s:10:"group_info";a:10:{s:5:"label";s:0:"";s:11:"description";s:0:"";s:10:"identifier";s:0:"";s:8:"optional";b:1;s:6:"widget";s:6:"select";s:8:"multiple";b:0;s:8:"remember";b:0;s:13:"default_group";s:3:"All";s:22:"default_group_multiple";a:0:{}s:11:"group_items";a:0:{}}s:11:"entity_type";s:5:"media";s:12:"entity_field";s:4:"name";s:9:"plugin_id";s:6:"string";}s:6:"bundle";a:16:{s:2:"id";s:6:"bundle";s:5:"table";s:16:"media_field_data";s:5:"field";s:6:"bundle";s:12:"relationship";s:4:"none";s:10:"group_type";s:5:"group";s:11:"admin_label";s:0:"";s:8:"operator";s:2:"in";s:5:"value";a:0:{}s:5:"group";i:1;s:7:"exposed";b:1;s:6:"expose";a:11:{s:11:"operator_id";s:9:"bundle_op";s:5:"label";s:6:"Source";s:11:"description";s:0:"";s:12:"use_operator";b:0;s:8:"operator";s:9:"bundle_op";s:10:"identifier";s:6:"source";s:8:"required";b:0;s:8:"remember";b:0;s:8:"multiple";b:0;s:14:"remember_roles";a:3:{s:13:"authenticated";s:13:"authenticated";s:9:"anonymous";s:1:"0";s:13:"administrator";s:1:"0";}s:6:"reduce";b:0;}s:10:"is_grouped";b:0;s:10:"group_info";a:10:{s:5:"label";s:0:"";s:11:"description";s:0:"";s:10:"identifier";s:0:"";s:8:"optional";b:1;s:6:"widget";s:6:"select";s:8:"multiple";b:0;s:8:"remember";b:0;s:13:"default_group";s:3:"All";s:22:"default_group_multiple";a:0:{}s:11:"group_items";a:0:{}}s:11:"entity_type";s:5:"media";s:12:"entity_field";s:6:"bundle";s:9:"plugin_id";s:6:"bundle";}s:6:"status";a:16:{s:2:"id";s:6:"status";s:5:"table";s:16:"media_field_data";s:5:"field";s:6:"status";s:12:"relationship";s:4:"none";s:10:"group_type";s:5:"group";s:11:"admin_label";s:0:"";s:8:"operator";s:1:"=";s:5:"value";s:1:"1";s:5:"group";i:1;s:7:"exposed";b:1;s:6:"expose";a:10:{s:11:"operator_id";s:0:"";s:5:"label";s:4:"True";s:11:"description";N;s:12:"use_operator";b:0;s:8:"operator";s:9:"status_op";s:10:"identifier";s:6:"status";s:8:"required";b:1;s:8:"remember";b:0;s:8:"multiple";b:0;s:14:"remember_roles";a:1:{s:13:"authenticated";s:13:"authenticated";}}s:10:"is_grouped";b:1;s:10:"group_info";a:10:{s:5:"label";s:16:"Published status";s:11:"description";s:0:"";s:10:"identifier";s:6:"status";s:8:"optional";b:1;s:6:"widget";s:6:"select";s:8:"multiple";b:0;s:8:"remember";b:0;s:13:"default_group";s:3:"All";s:22:"default_group_multiple";a:0:{}s:11:"group_items";a:2:{i:1;a:3:{s:5:"title";s:9:"Published";s:8:"operator";s:1:"=";s:5:"value";s:1:"1";}i:2;a:3:{s:5:"title";s:11:"Unpublished";s:8:"operator";s:1:"=";s:5:"value";s:1:"0";}}}s:9:"plugin_id";s:7:"boolean";s:11:"entity_type";s:5:"media";s:12:"entity_field";s:6:"status";}s:8:"langcode";a:16:{s:2:"id";s:8:"langcode";s:5:"table";s:16:"media_field_data";s:5:"field";s:8:"langcode";s:12:"relationship";s:4:"none";s:10:"group_type";s:5:"group";s:11:"admin_label";s:0:"";s:8:"operator";s:2:"in";s:5:"value";a:0:{}s:5:"group";i:1;s:7:"exposed";b:1;s:6:"expose";a:11:{s:11:"operator_id";s:11:"langcode_op";s:5:"label";s:8:"Language";s:11:"description";s:0:"";s:12:"use_operator";b:0;s:8:"operator";s:11:"langcode_op";s:10:"identifier";s:8:"langcode";s:8:"required";b:0;s:8:"remember";b:0;s:8:"multiple";b:0;s:14:"remember_roles";a:3:{s:13:"authenticated";s:13:"authenticated";s:9:"anonymous";s:1:"0";s:13:"administrator";s:1:"0";}s:6:"reduce";b:0;}s:10:"is_grouped";b:0;s:10:"group_info";a:10:{s:5:"label";s:0:"";s:11:"description";s:0:"";s:10:"identifier";s:0:"";s:8:"optional";b:1;s:6:"widget";s:6:"select";s:8:"multiple";b:0;s:8:"remember";b:0;s:13:"default_group";s:3:"All";s:22:"default_group_multiple";a:0:{}s:11:"group_items";a:0:{}}s:11:"entity_type";s:5:"media";s:12:"entity_field";s:8:"langcode";s:9:"plugin_id";s:8:"language";}}s:5:"sorts";a:1:{s:7:"created";a:13:{s:2:"id";s:7:"created";s:5:"table";s:16:"media_field_data";s:5:"field";s:7:"created";s:5:"order";s:4:"DESC";s:11:"entity_type";s:5:"media";s:12:"entity_field";s:7:"created";s:9:"plugin_id";s:4:"date";s:12:"relationship";s:4:"none";s:10:"group_type";s:5:"group";s:11:"admin_label";s:0:"";s:7:"exposed";b:0;s:6:"expose";a:1:{s:5:"label";s:0:"";}s:11:"granularity";s:6:"second";}}s:5:"title";s:5:"Media";s:6:"header";a:0:{}s:6:"footer";a:0:{}s:5:"empty";a:1:{s:16:"area_text_custom";a:10:{s:2:"id";s:16:"area_text_custom";s:5:"table";s:5:"views";s:5:"field";s:16:"area_text_custom";s:12:"relationship";s:4:"none";s:10:"group_type";s:5:"group";s:11:"admin_label";s:0:"";s:5:"empty";b:1;s:8:"tokenize";b:0;s:7:"content";s:21:"No content available.";s:9:"plugin_id";s:11:"text_custom";}}s:13:"relationships";a:0:{}s:9:"arguments";a:0:{}s:17:"display_extenders";a:0:{}}s:14:"cache_metadata";a:3:{s:7:"max-age";i:0;s:8:"contexts";a:5:{i:0;s:26:"languages:language_content";i:1;s:28:"languages:language_interface";i:2;s:3:"url";i:3;s:14:"url.query_args";i:4;s:16:"user.permissions";}s:4:"tags";a:0:{}}}s:15:"media_page_list";a:6:{s:14:"display_plugin";s:4:"page";s:2:"id";s:15:"media_page_list";s:13:"display_title";s:5:"Media";s:8:"position";i:1;s:15:"display_options";a:4:{s:17:"display_extenders";a:0:{}s:4:"path";s:19:"admin/content/media";s:4:"menu";a:8:{s:4:"type";s:3:"tab";s:5:"title";s:5:"Media";s:11:"description";s:0:"";s:8:"expanded";b:0;s:6:"parent";s:0:"";s:6:"weight";i:0;s:7:"context";s:1:"0";s:9:"menu_name";s:4:"main";}s:19:"display_description";s:0:"";}s:14:"cache_metadata";a:3:{s:7:"max-age";i:0;s:8:"contexts";a:5:{i:0;s:26:"languages:language_content";i:1;s:28:"languages:language_interface";i:2;s:3:"url";i:3;s:14:"url.query_args";i:4;s:16:"user.permissions";}s:4:"tags";a:0:{}}}}}',
))
->execute();

// Insert Media's key_value entries.
$connection->insert('key_value')
->fields(array(
  'collection',
  'name',
  'value',
))
->values(array(
  'collection' => 'entity.definitions.bundle_field_map',
  'name' => 'media',
  'value' => 'a:2:{s:16:"field_media_file";a:2:{s:4:"type";s:4:"file";s:7:"bundles";a:1:{s:4:"file";s:4:"file";}}s:17:"field_media_image";a:2:{s:4:"type";s:5:"image";s:7:"bundles";a:1:{s:5:"image";s:5:"image";}}}',
))
->values(array(
  'collection' => 'entity.definitions.installed',
  'name' => 'media.entity_type',
  'value' => 'O:36:"Drupal\Core\Entity\ContentEntityType":38:{s:25:" * revision_metadata_keys";a:3:{s:13:"revision_user";s:13:"revision_user";s:16:"revision_created";s:16:"revision_created";s:20:"revision_log_message";s:20:"revision_log_message";}s:15:" * static_cache";b:1;s:15:" * render_cache";b:1;s:19:" * persistent_cache";b:1;s:14:" * entity_keys";a:9:{s:2:"id";s:3:"mid";s:8:"revision";s:3:"vid";s:6:"bundle";s:6:"bundle";s:5:"label";s:4:"name";s:8:"langcode";s:8:"langcode";s:4:"uuid";s:4:"uuid";s:9:"published";s:6:"status";s:16:"default_langcode";s:16:"default_langcode";s:29:"revision_translation_affected";s:29:"revision_translation_affected";}s:5:" * id";s:5:"media";s:16:" * originalClass";s:25:"Drupal\media\Entity\Media";s:11:" * handlers";a:8:{s:7:"storage";s:46:"Drupal\Core\Entity\Sql\SqlContentEntityStorage";s:12:"view_builder";s:36:"Drupal\Core\Entity\EntityViewBuilder";s:12:"list_builder";s:36:"Drupal\Core\Entity\EntityListBuilder";s:6:"access";s:38:"Drupal\media\MediaAccessControlHandler";s:4:"form";a:4:{s:7:"default";s:22:"Drupal\media\MediaForm";s:3:"add";s:22:"Drupal\media\MediaForm";s:4:"edit";s:22:"Drupal\media\MediaForm";s:6:"delete";s:42:"Drupal\Core\Entity\ContentEntityDeleteForm";}s:11:"translation";s:52:"Drupal\content_translation\ContentTranslationHandler";s:10:"views_data";s:27:"Drupal\media\MediaViewsData";s:14:"route_provider";a:1:{s:4:"html";s:49:"Drupal\Core\Entity\Routing\AdminHtmlRouteProvider";}}s:19:" * admin_permission";s:16:"administer media";s:25:" * permission_granularity";s:11:"entity_type";s:8:" * links";a:6:{s:8:"add-page";s:10:"/media/add";s:8:"add-form";s:23:"/media/add/{media_type}";s:9:"canonical";s:14:"/media/{media}";s:11:"delete-form";s:21:"/media/{media}/delete";s:9:"edit-form";s:19:"/media/{media}/edit";s:8:"revision";s:46:"/media/{media}/revisions/{media_revision}/view";}s:17:" * label_callback";N;s:21:" * bundle_entity_type";s:10:"media_type";s:12:" * bundle_of";N;s:15:" * bundle_label";O:48:"Drupal\Core\StringTranslation\TranslatableMarkup":3:{s:9:" * string";s:10:"Media type";s:12:" * arguments";a:0:{}s:10:" * options";a:0:{}}s:13:" * base_table";s:5:"media";s:22:" * revision_data_table";s:20:"media_field_revision";s:17:" * revision_table";s:14:"media_revision";s:13:" * data_table";s:16:"media_field_data";s:15:" * translatable";b:1;s:19:" * show_revision_ui";b:1;s:8:" * label";O:48:"Drupal\Core\StringTranslation\TranslatableMarkup":3:{s:9:" * string";s:5:"Media";s:12:" * arguments";a:0:{}s:10:" * options";a:0:{}}s:19:" * label_collection";s:0:"";s:17:" * label_singular";O:48:"Drupal\Core\StringTranslation\TranslatableMarkup":3:{s:9:" * string";s:10:"media item";s:12:" * arguments";a:0:{}s:10:" * options";a:0:{}}s:15:" * label_plural";O:48:"Drupal\Core\StringTranslation\TranslatableMarkup":3:{s:9:" * string";s:11:"media items";s:12:" * arguments";a:0:{}s:10:" * options";a:0:{}}s:14:" * label_count";a:3:{s:8:"singular";s:17:"@count media item";s:6:"plural";s:18:"@count media items";s:7:"context";N;}s:15:" * uri_callback";N;s:8:" * group";s:7:"content";s:14:" * group_label";O:48:"Drupal\Core\StringTranslation\TranslatableMarkup":3:{s:9:" * string";s:7:"Content";s:12:" * arguments";a:0:{}s:10:" * options";a:1:{s:7:"context";s:17:"Entity type group";}}s:22:" * field_ui_base_route";s:27:"entity.media_type.edit_form";s:26:" * common_reference_target";b:1;s:22:" * list_cache_contexts";a:0:{}s:18:" * list_cache_tags";a:1:{i:0;s:10:"media_list";}s:14:" * constraints";a:1:{s:13:"EntityChanged";N;}s:13:" * additional";a:0:{}s:8:" * class";s:25:"Drupal\media\Entity\Media";s:11:" * provider";s:5:"media";s:20:" * stringTranslation";N;}',
))
->values(array(
  'collection' => 'entity.definitions.installed',
  'name' => 'media.field_storage_definitions',
  'value' => "a:18:{s:3:\"mid\";O:37:\"Drupal\\Core\\Field\\BaseFieldDefinition\":5:{s:7:\" * type\";s:7:\"integer\";s:9:\" * schema\";a:4:{s:7:\"columns\";a:1:{s:5:\"value\";a:3:{s:4:\"type\";s:3:\"int\";s:8:\"unsigned\";b:1;s:4:\"size\";s:6:\"normal\";}}s:11:\"unique keys\";a:0:{}s:7:\"indexes\";a:0:{}s:12:\"foreign keys\";a:0:{}}s:10:\" * indexes\";a:0:{}s:17:\" * itemDefinition\";O:51:\"Drupal\\Core\\Field\\TypedData\\FieldItemDataDefinition\":2:{s:18:\" * fieldDefinition\";r:2;s:13:\" * definition\";a:2:{s:4:\"type\";s:18:\"field_item:integer\";s:8:\"settings\";a:6:{s:8:\"unsigned\";b:1;s:4:\"size\";s:6:\"normal\";s:3:\"min\";s:0:\"\";s:3:\"max\";s:0:\"\";s:6:\"prefix\";s:0:\"\";s:6:\"suffix\";s:0:\"\";}}}s:13:\" * definition\";a:6:{s:5:\"label\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\" * string\";s:2:\"ID\";s:12:\" * arguments\";a:0:{}s:10:\" * options\";a:0:{}}s:9:\"read-only\";b:1;s:8:\"provider\";s:5:\"media\";s:10:\"field_name\";s:3:\"mid\";s:11:\"entity_type\";s:5:\"media\";s:6:\"bundle\";N;}}s:4:\"uuid\";O:37:\"Drupal\\Core\\Field\\BaseFieldDefinition\":5:{s:7:\" * type\";s:4:\"uuid\";s:9:\" * schema\";a:4:{s:7:\"columns\";a:1:{s:5:\"value\";a:3:{s:4:\"type\";s:13:\"varchar_ascii\";s:6:\"length\";i:128;s:6:\"binary\";b:0;}}s:11:\"unique keys\";a:1:{s:5:\"value\";a:1:{i:0;s:5:\"value\";}}s:7:\"indexes\";a:0:{}s:12:\"foreign keys\";a:0:{}}s:10:\" * indexes\";a:0:{}s:17:\" * itemDefinition\";O:51:\"Drupal\\Core\\Field\\TypedData\\FieldItemDataDefinition\":2:{s:18:\" * fieldDefinition\";r:35;s:13:\" * definition\";a:2:{s:4:\"type\";s:15:\"field_item:uuid\";s:8:\"settings\";a:3:{s:10:\"max_length\";i:128;s:8:\"is_ascii\";b:1;s:14:\"case_sensitive\";b:0;}}}s:13:\" * definition\";a:6:{s:5:\"label\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\" * string\";s:4:\"UUID\";s:12:\" * arguments\";a:0:{}s:10:\" * options\";a:0:{}}s:9:\"read-only\";b:1;s:8:\"provider\";s:5:\"media\";s:10:\"field_name\";s:4:\"uuid\";s:11:\"entity_type\";s:5:\"media\";s:6:\"bundle\";N;}}s:3:\"vid\";O:37:\"Drupal\\Core\\Field\\BaseFieldDefinition\":5:{s:7:\" * type\";s:7:\"integer\";s:9:\" * schema\";a:4:{s:7:\"columns\";a:1:{s:5:\"value\";a:3:{s:4:\"type\";s:3:\"int\";s:8:\"unsigned\";b:1;s:4:\"size\";s:6:\"normal\";}}s:11:\"unique keys\";a:0:{}s:7:\"indexes\";a:0:{}s:12:\"foreign keys\";a:0:{}}s:10:\" * indexes\";a:0:{}s:17:\" * itemDefinition\";O:51:\"Drupal\\Core\\Field\\TypedData\\FieldItemDataDefinition\":2:{s:18:\" * fieldDefinition\";r:67;s:13:\" * definition\";a:2:{s:4:\"type\";s:18:\"field_item:integer\";s:8:\"settings\";a:6:{s:8:\"unsigned\";b:1;s:4:\"size\";s:6:\"normal\";s:3:\"min\";s:0:\"\";s:3:\"max\";s:0:\"\";s:6:\"prefix\";s:0:\"\";s:6:\"suffix\";s:0:\"\";}}}s:13:\" * definition\";a:6:{s:5:\"label\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\" * string\";s:11:\"Revision ID\";s:12:\" * arguments\";a:0:{}s:10:\" * options\";a:0:{}}s:9:\"read-only\";b:1;s:8:\"provider\";s:5:\"media\";s:10:\"field_name\";s:3:\"vid\";s:11:\"entity_type\";s:5:\"media\";s:6:\"bundle\";N;}}s:8:\"langcode\";O:37:\"Drupal\\Core\\Field\\BaseFieldDefinition\":5:{s:7:\" * type\";s:8:\"language\";s:9:\" * schema\";a:4:{s:7:\"columns\";a:1:{s:5:\"value\";a:2:{s:4:\"type\";s:13:\"varchar_ascii\";s:6:\"length\";i:12;}}s:11:\"unique keys\";a:0:{}s:7:\"indexes\";a:0:{}s:12:\"foreign keys\";a:0:{}}s:10:\" * indexes\";a:0:{}s:17:\" * itemDefinition\";O:51:\"Drupal\\Core\\Field\\TypedData\\FieldItemDataDefinition\":2:{s:18:\" * fieldDefinition\";r:100;s:13:\" * definition\";a:2:{s:4:\"type\";s:19:\"field_item:language\";s:8:\"settings\";a:0:{}}}s:13:\" * definition\";a:8:{s:5:\"label\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\" * string\";s:8:\"Language\";s:12:\" * arguments\";a:0:{}s:10:\" * options\";a:0:{}}s:7:\"display\";a:2:{s:4:\"view\";a:1:{s:7:\"options\";a:1:{s:6:\"region\";s:6:\"hidden\";}}s:4:\"form\";a:1:{s:7:\"options\";a:2:{s:4:\"type\";s:15:\"language_select\";s:6:\"weight\";i:2;}}}s:12:\"revisionable\";b:1;s:12:\"translatable\";b:1;s:8:\"provider\";s:5:\"media\";s:10:\"field_name\";s:8:\"langcode\";s:11:\"entity_type\";s:5:\"media\";s:6:\"bundle\";N;}}s:6:\"bundle\";O:37:\"Drupal\\Core\\Field\\BaseFieldDefinition\":5:{s:7:\" * type\";s:16:\"entity_reference\";s:9:\" * schema\";a:4:{s:7:\"columns\";a:1:{s:9:\"target_id\";a:3:{s:11:\"description\";s:28:\"The ID of the target entity.\";s:4:\"type\";s:13:\"varchar_ascii\";s:6:\"length\";i:32;}}s:7:\"indexes\";a:1:{s:9:\"target_id\";a:1:{i:0;s:9:\"target_id\";}}s:11:\"unique keys\";a:0:{}s:12:\"foreign keys\";a:0:{}}s:10:\" * indexes\";a:0:{}s:17:\" * itemDefinition\";O:51:\"Drupal\\Core\\Field\\TypedData\\FieldItemDataDefinition\":2:{s:18:\" * fieldDefinition\";r:135;s:13:\" * definition\";a:2:{s:4:\"type\";s:27:\"field_item:entity_reference\";s:8:\"settings\";a:3:{s:11:\"target_type\";s:10:\"media_type\";s:7:\"handler\";s:7:\"default\";s:16:\"handler_settings\";a:0:{}}}}s:13:\" * definition\";a:7:{s:5:\"label\";s:10:\"Media type\";s:8:\"required\";b:1;s:9:\"read-only\";b:1;s:8:\"provider\";s:5:\"media\";s:10:\"field_name\";s:6:\"bundle\";s:11:\"entity_type\";s:5:\"media\";s:6:\"bundle\";N;}}s:16:\"revision_created\";O:37:\"Drupal\\Core\\Field\\BaseFieldDefinition\":5:{s:7:\" * type\";s:7:\"created\";s:9:\" * schema\";a:4:{s:7:\"columns\";a:1:{s:5:\"value\";a:1:{s:4:\"type\";s:3:\"int\";}}s:11:\"unique keys\";a:0:{}s:7:\"indexes\";a:0:{}s:12:\"foreign keys\";a:0:{}}s:10:\" * indexes\";a:0:{}s:17:\" * itemDefinition\";O:51:\"Drupal\\Core\\Field\\TypedData\\FieldItemDataDefinition\":2:{s:18:\" * fieldDefinition\";r:165;s:13:\" * definition\";a:2:{s:4:\"type\";s:18:\"field_item:created\";s:8:\"settings\";a:0:{}}}s:13:\" * definition\";a:7:{s:5:\"label\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\" * string\";s:20:\"Revision create time\";s:12:\" * arguments\";a:0:{}s:10:\" * options\";a:0:{}}s:11:\"description\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\" * string\";s:47:\"The time that the current revision was created.\";s:12:\" * arguments\";a:0:{}s:10:\" * options\";a:0:{}}s:12:\"revisionable\";b:1;s:8:\"provider\";s:5:\"media\";s:10:\"field_name\";s:16:\"revision_created\";s:11:\"entity_type\";s:5:\"media\";s:6:\"bundle\";N;}}s:13:\"revision_user\";O:37:\"Drupal\\Core\\Field\\BaseFieldDefinition\":5:{s:7:\" * type\";s:16:\"entity_reference\";s:9:\" * schema\";a:4:{s:7:\"columns\";a:1:{s:9:\"target_id\";a:3:{s:11:\"description\";s:28:\"The ID of the target entity.\";s:4:\"type\";s:3:\"int\";s:8:\"unsigned\";b:1;}}s:7:\"indexes\";a:1:{s:9:\"target_id\";a:1:{i:0;s:9:\"target_id\";}}s:11:\"unique keys\";a:0:{}s:12:\"foreign keys\";a:0:{}}s:10:\" * indexes\";a:0:{}s:17:\" * itemDefinition\";O:51:\"Drupal\\Core\\Field\\TypedData\\FieldItemDataDefinition\":2:{s:18:\" * fieldDefinition\";r:194;s:13:\" * definition\";a:2:{s:4:\"type\";s:27:\"field_item:entity_reference\";s:8:\"settings\";a:3:{s:11:\"target_type\";s:4:\"user\";s:7:\"handler\";s:7:\"default\";s:16:\"handler_settings\";a:0:{}}}}s:13:\" * definition\";a:7:{s:5:\"label\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\" * string\";s:13:\"Revision user\";s:12:\" * arguments\";a:0:{}s:10:\" * options\";a:0:{}}s:11:\"description\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\" * string\";s:50:\"The user ID of the author of the current revision.\";s:12:\" * arguments\";a:0:{}s:10:\" * options\";a:0:{}}s:12:\"revisionable\";b:1;s:8:\"provider\";s:5:\"media\";s:10:\"field_name\";s:13:\"revision_user\";s:11:\"entity_type\";s:5:\"media\";s:6:\"bundle\";N;}}s:20:\"revision_log_message\";O:37:\"Drupal\\Core\\Field\\BaseFieldDefinition\":5:{s:7:\" * type\";s:11:\"string_long\";s:9:\" * schema\";a:4:{s:7:\"columns\";a:1:{s:5:\"value\";a:2:{s:4:\"type\";s:4:\"text\";s:4:\"size\";s:3:\"big\";}}s:11:\"unique keys\";a:0:{}s:7:\"indexes\";a:0:{}s:12:\"foreign keys\";a:0:{}}s:10:\" * indexes\";a:0:{}s:17:\" * itemDefinition\";O:51:\"Drupal\\Core\\Field\\TypedData\\FieldItemDataDefinition\":2:{s:18:\" * fieldDefinition\";r:230;s:13:\" * definition\";a:2:{s:4:\"type\";s:22:\"field_item:string_long\";s:8:\"settings\";a:1:{s:14:\"case_sensitive\";b:0;}}}s:13:\" * definition\";a:9:{s:5:\"label\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\" * string\";s:20:\"Revision log message\";s:12:\" * arguments\";a:0:{}s:10:\" * options\";a:0:{}}s:11:\"description\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\" * string\";s:43:\"Briefly describe the changes you have made.\";s:12:\" * arguments\";a:0:{}s:10:\" * options\";a:0:{}}s:12:\"revisionable\";b:1;s:13:\"default_value\";a:1:{i:0;a:1:{s:5:\"value\";s:0:\"\";}}s:7:\"display\";a:1:{s:4:\"form\";a:1:{s:7:\"options\";a:3:{s:4:\"type\";s:15:\"string_textarea\";s:6:\"weight\";i:25;s:8:\"settings\";a:1:{s:4:\"rows\";i:4;}}}}s:8:\"provider\";s:5:\"media\";s:10:\"field_name\";s:20:\"revision_log_message\";s:11:\"entity_type\";s:5:\"media\";s:6:\"bundle\";N;}}s:6:\"status\";O:37:\"Drupal\\Core\\Field\\BaseFieldDefinition\":5:{s:7:\" * type\";s:7:\"boolean\";s:9:\" * schema\";a:4:{s:7:\"columns\";a:1:{s:5:\"value\";a:2:{s:4:\"type\";s:3:\"int\";s:4:\"size\";s:4:\"tiny\";}}s:11:\"unique keys\";a:0:{}s:7:\"indexes\";a:0:{}s:12:\"foreign keys\";a:0:{}}s:10:\" * indexes\";a:0:{}s:17:\" * itemDefinition\";O:51:\"Drupal\\Core\\Field\\TypedData\\FieldItemDataDefinition\":2:{s:18:\" * fieldDefinition\";r:271;s:13:\" * definition\";a:2:{s:4:\"type\";s:18:\"field_item:boolean\";s:8:\"settings\";a:2:{s:8:\"on_label\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\" * string\";s:2:\"On\";s:12:\" * arguments\";a:0:{}s:10:\" * options\";a:0:{}}s:9:\"off_label\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\" * string\";s:3:\"Off\";s:12:\" * arguments\";a:0:{}s:10:\" * options\";a:0:{}}}}}s:13:\" * definition\";a:9:{s:5:\"label\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\" * string\";s:9:\"Published\";s:12:\" * arguments\";a:0:{}s:10:\" * options\";a:0:{}}s:12:\"revisionable\";b:1;s:12:\"translatable\";b:1;s:13:\"default_value\";a:1:{i:0;a:1:{s:5:\"value\";b:1;}}s:7:\"display\";a:1:{s:4:\"form\";a:2:{s:7:\"options\";a:3:{s:4:\"type\";s:16:\"boolean_checkbox\";s:8:\"settings\";a:1:{s:13:\"display_label\";b:1;}s:6:\"weight\";i:100;}s:12:\"configurable\";b:1;}}s:8:\"provider\";s:5:\"media\";s:10:\"field_name\";s:6:\"status\";s:11:\"entity_type\";s:5:\"media\";s:6:\"bundle\";N;}}s:4:\"name\";O:37:\"Drupal\\Core\\Field\\BaseFieldDefinition\":5:{s:7:\" * type\";s:6:\"string\";s:9:\" * schema\";a:4:{s:7:\"columns\";a:1:{s:5:\"value\";a:3:{s:4:\"type\";s:7:\"varchar\";s:6:\"length\";i:255;s:6:\"binary\";b:0;}}s:11:\"unique keys\";a:0:{}s:7:\"indexes\";a:0:{}s:12:\"foreign keys\";a:0:{}}s:10:\" * indexes\";a:0:{}s:17:\" * itemDefinition\";O:51:\"Drupal\\Core\\Field\\TypedData\\FieldItemDataDefinition\":2:{s:18:\" * fieldDefinition\";r:317;s:13:\" * definition\";a:2:{s:4:\"type\";s:17:\"field_item:string\";s:8:\"settings\";a:3:{s:10:\"max_length\";i:255;s:8:\"is_ascii\";b:0;s:14:\"case_sensitive\";b:0;}}}s:13:\" * definition\";a:10:{s:5:\"label\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\" * string\";s:4:\"Name\";s:12:\" * arguments\";a:0:{}s:10:\" * options\";a:0:{}}s:8:\"required\";b:1;s:12:\"translatable\";b:1;s:12:\"revisionable\";b:1;s:13:\"default_value\";a:1:{i:0;a:1:{s:5:\"value\";s:0:\"\";}}s:7:\"display\";a:2:{s:4:\"form\";a:2:{s:7:\"options\";a:2:{s:4:\"type\";s:16:\"string_textfield\";s:6:\"weight\";i:-5;}s:12:\"configurable\";b:1;}s:4:\"view\";a:1:{s:7:\"options\";a:3:{s:5:\"label\";s:6:\"hidden\";s:4:\"type\";s:6:\"string\";s:6:\"weight\";i:-5;}}}s:8:\"provider\";s:5:\"media\";s:10:\"field_name\";s:4:\"name\";s:11:\"entity_type\";s:5:\"media\";s:6:\"bundle\";N;}}s:9:\"thumbnail\";O:37:\"Drupal\\Core\\Field\\BaseFieldDefinition\":5:{s:7:\" * type\";s:5:\"image\";s:9:\" * schema\";a:4:{s:7:\"columns\";a:5:{s:9:\"target_id\";a:3:{s:11:\"description\";s:26:\"The ID of the file entity.\";s:4:\"type\";s:3:\"int\";s:8:\"unsigned\";b:1;}s:3:\"alt\";a:3:{s:11:\"description\";s:56:\"Alternative image text, for the image's 'alt' attribute.\";s:4:\"type\";s:7:\"varchar\";s:6:\"length\";i:512;}s:5:\"title\";a:3:{s:11:\"description\";s:52:\"Image title text, for the image's 'title' attribute.\";s:4:\"type\";s:7:\"varchar\";s:6:\"length\";i:1024;}s:5:\"width\";a:3:{s:11:\"description\";s:33:\"The width of the image in pixels.\";s:4:\"type\";s:3:\"int\";s:8:\"unsigned\";b:1;}s:6:\"height\";a:3:{s:11:\"description\";s:34:\"The height of the image in pixels.\";s:4:\"type\";s:3:\"int\";s:8:\"unsigned\";b:1;}}s:7:\"indexes\";a:1:{s:9:\"target_id\";a:1:{i:0;s:9:\"target_id\";}}s:12:\"foreign keys\";a:1:{s:9:\"target_id\";a:2:{s:5:\"table\";s:12:\"file_managed\";s:7:\"columns\";a:1:{s:9:\"target_id\";s:3:\"fid\";}}}s:11:\"unique keys\";a:0:{}}s:10:\" * indexes\";a:0:{}s:17:\" * itemDefinition\";O:51:\"Drupal\\Core\\Field\\TypedData\\FieldItemDataDefinition\":2:{s:18:\" * fieldDefinition\";r:363;s:13:\" * definition\";a:2:{s:4:\"type\";s:16:\"field_item:image\";s:8:\"settings\";a:16:{s:13:\"default_image\";a:5:{s:4:\"uuid\";N;s:3:\"alt\";s:0:\"\";s:5:\"title\";s:0:\"\";s:5:\"width\";N;s:6:\"height\";N;}s:11:\"target_type\";s:4:\"file\";s:13:\"display_field\";b:0;s:15:\"display_default\";b:0;s:10:\"uri_scheme\";s:6:\"public\";s:15:\"file_extensions\";s:16:\"png gif jpg jpeg\";s:9:\"alt_field\";i:1;s:18:\"alt_field_required\";i:1;s:11:\"title_field\";i:0;s:20:\"title_field_required\";i:0;s:14:\"max_resolution\";s:0:\"\";s:14:\"min_resolution\";s:0:\"\";s:14:\"file_directory\";s:31:\"[date:custom:Y]-[date:custom:m]\";s:12:\"max_filesize\";s:0:\"\";s:7:\"handler\";s:7:\"default\";s:16:\"handler_settings\";a:0:{}}}}s:13:\" * definition\";a:10:{s:5:\"label\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\" * string\";s:9:\"Thumbnail\";s:12:\" * arguments\";a:0:{}s:10:\" * options\";a:0:{}}s:11:\"description\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\" * string\";s:32:\"The thumbnail of the media item.\";s:12:\" * arguments\";a:0:{}s:10:\" * options\";a:0:{}}s:12:\"revisionable\";b:1;s:12:\"translatable\";b:1;s:7:\"display\";a:1:{s:4:\"view\";a:2:{s:7:\"options\";a:4:{s:4:\"type\";s:5:\"image\";s:6:\"weight\";i:5;s:5:\"label\";s:6:\"hidden\";s:8:\"settings\";a:1:{s:11:\"image_style\";s:9:\"thumbnail\";}}s:12:\"configurable\";b:1;}}s:9:\"read-only\";b:1;s:8:\"provider\";s:5:\"media\";s:10:\"field_name\";s:9:\"thumbnail\";s:11:\"entity_type\";s:5:\"media\";s:6:\"bundle\";N;}}s:3:\"uid\";O:37:\"Drupal\\Core\\Field\\BaseFieldDefinition\":5:{s:7:\" * type\";s:16:\"entity_reference\";s:9:\" * schema\";a:4:{s:7:\"columns\";a:1:{s:9:\"target_id\";a:3:{s:11:\"description\";s:28:\"The ID of the target entity.\";s:4:\"type\";s:3:\"int\";s:8:\"unsigned\";b:1;}}s:7:\"indexes\";a:1:{s:9:\"target_id\";a:1:{i:0;s:9:\"target_id\";}}s:11:\"unique keys\";a:0:{}s:12:\"foreign keys\";a:0:{}}s:10:\" * indexes\";a:0:{}s:17:\" * itemDefinition\";O:51:\"Drupal\\Core\\Field\\TypedData\\FieldItemDataDefinition\":2:{s:18:\" * fieldDefinition\";r:448;s:13:\" * definition\";a:2:{s:4:\"type\";s:27:\"field_item:entity_reference\";s:8:\"settings\";a:3:{s:11:\"target_type\";s:4:\"user\";s:7:\"handler\";s:7:\"default\";s:16:\"handler_settings\";a:0:{}}}}s:13:\" * definition\";a:10:{s:5:\"label\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\" * string\";s:11:\"Authored by\";s:12:\" * arguments\";a:0:{}s:10:\" * options\";a:0:{}}s:11:\"description\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\" * string\";s:26:\"The user ID of the author.\";s:12:\" * arguments\";a:0:{}s:10:\" * options\";a:0:{}}s:12:\"revisionable\";b:1;s:22:\"default_value_callback\";s:43:\"Drupal\\media\\Entity\\Media::getCurrentUserId\";s:12:\"translatable\";b:1;s:7:\"display\";a:2:{s:4:\"form\";a:2:{s:7:\"options\";a:3:{s:4:\"type\";s:29:\"entity_reference_autocomplete\";s:6:\"weight\";i:5;s:8:\"settings\";a:4:{s:14:\"match_operator\";s:8:\"CONTAINS\";s:4:\"size\";s:2:\"60\";s:17:\"autocomplete_type\";s:4:\"tags\";s:11:\"placeholder\";s:0:\"\";}}s:12:\"configurable\";b:1;}s:4:\"view\";a:2:{s:7:\"options\";a:3:{s:5:\"label\";s:6:\"hidden\";s:4:\"type\";s:6:\"author\";s:6:\"weight\";i:0;}s:12:\"configurable\";b:1;}}s:8:\"provider\";s:5:\"media\";s:10:\"field_name\";s:3:\"uid\";s:11:\"entity_type\";s:5:\"media\";s:6:\"bundle\";N;}}s:7:\"created\";O:37:\"Drupal\\Core\\Field\\BaseFieldDefinition\":5:{s:7:\" * type\";s:7:\"created\";s:9:\" * schema\";a:4:{s:7:\"columns\";a:1:{s:5:\"value\";a:1:{s:4:\"type\";s:3:\"int\";}}s:11:\"unique keys\";a:0:{}s:7:\"indexes\";a:0:{}s:12:\"foreign keys\";a:0:{}}s:10:\" * indexes\";a:0:{}s:17:\" * itemDefinition\";O:51:\"Drupal\\Core\\Field\\TypedData\\FieldItemDataDefinition\":2:{s:18:\" * fieldDefinition\";r:503;s:13:\" * definition\";a:2:{s:4:\"type\";s:18:\"field_item:created\";s:8:\"settings\";a:0:{}}}s:13:\" * definition\";a:10:{s:5:\"label\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\" * string\";s:11:\"Authored on\";s:12:\" * arguments\";a:0:{}s:10:\" * options\";a:0:{}}s:11:\"description\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\" * string\";s:36:\"The time the media item was created.\";s:12:\" * arguments\";a:0:{}s:10:\" * options\";a:0:{}}s:12:\"translatable\";b:1;s:12:\"revisionable\";b:1;s:22:\"default_value_callback\";s:41:\"Drupal\\media\\Entity\\Media::getRequestTime\";s:7:\"display\";a:2:{s:4:\"form\";a:2:{s:7:\"options\";a:2:{s:4:\"type\";s:18:\"datetime_timestamp\";s:6:\"weight\";i:10;}s:12:\"configurable\";b:1;}s:4:\"view\";a:2:{s:7:\"options\";a:3:{s:5:\"label\";s:6:\"hidden\";s:4:\"type\";s:9:\"timestamp\";s:6:\"weight\";i:0;}s:12:\"configurable\";b:1;}}s:8:\"provider\";s:5:\"media\";s:10:\"field_name\";s:7:\"created\";s:11:\"entity_type\";s:5:\"media\";s:6:\"bundle\";N;}}s:7:\"changed\";O:37:\"Drupal\\Core\\Field\\BaseFieldDefinition\":5:{s:7:\" * type\";s:7:\"changed\";s:9:\" * schema\";a:4:{s:7:\"columns\";a:1:{s:5:\"value\";a:1:{s:4:\"type\";s:3:\"int\";}}s:11:\"unique keys\";a:0:{}s:7:\"indexes\";a:0:{}s:12:\"foreign keys\";a:0:{}}s:10:\" * indexes\";a:0:{}s:17:\" * itemDefinition\";O:51:\"Drupal\\Core\\Field\\TypedData\\FieldItemDataDefinition\":2:{s:18:\" * fieldDefinition\";r:546;s:13:\" * definition\";a:2:{s:4:\"type\";s:18:\"field_item:changed\";s:8:\"settings\";a:0:{}}}s:13:\" * definition\";a:8:{s:5:\"label\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\" * string\";s:7:\"Changed\";s:12:\" * arguments\";a:0:{}s:10:\" * options\";a:0:{}}s:11:\"description\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\" * string\";s:40:\"The time the media item was last edited.\";s:12:\" * arguments\";a:0:{}s:10:\" * options\";a:0:{}}s:12:\"translatable\";b:1;s:12:\"revisionable\";b:1;s:8:\"provider\";s:5:\"media\";s:10:\"field_name\";s:7:\"changed\";s:11:\"entity_type\";s:5:\"media\";s:6:\"bundle\";N;}}s:16:\"default_langcode\";O:37:\"Drupal\\Core\\Field\\BaseFieldDefinition\":5:{s:7:\" * type\";s:7:\"boolean\";s:9:\" * schema\";a:4:{s:7:\"columns\";a:1:{s:5:\"value\";a:2:{s:4:\"type\";s:3:\"int\";s:4:\"size\";s:4:\"tiny\";}}s:11:\"unique keys\";a:0:{}s:7:\"indexes\";a:0:{}s:12:\"foreign keys\";a:0:{}}s:10:\" * indexes\";a:0:{}s:17:\" * itemDefinition\";O:51:\"Drupal\\Core\\Field\\TypedData\\FieldItemDataDefinition\":2:{s:18:\" * fieldDefinition\";r:576;s:13:\" * definition\";a:2:{s:4:\"type\";s:18:\"field_item:boolean\";s:8:\"settings\";a:2:{s:8:\"on_label\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\" * string\";s:2:\"On\";s:12:\" * arguments\";a:0:{}s:10:\" * options\";a:0:{}}s:9:\"off_label\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\" * string\";s:3:\"Off\";s:12:\" * arguments\";a:0:{}s:10:\" * options\";a:0:{}}}}}s:13:\" * definition\";a:9:{s:5:\"label\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\" * string\";s:19:\"Default translation\";s:12:\" * arguments\";a:0:{}s:10:\" * options\";a:0:{}}s:11:\"description\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\" * string\";s:58:\"A flag indicating whether this is the default translation.\";s:12:\" * arguments\";a:0:{}s:10:\" * options\";a:0:{}}s:12:\"translatable\";b:1;s:12:\"revisionable\";b:1;s:13:\"default_value\";a:1:{i:0;a:1:{s:5:\"value\";b:1;}}s:8:\"provider\";s:5:\"media\";s:10:\"field_name\";s:16:\"default_langcode\";s:11:\"entity_type\";s:5:\"media\";s:6:\"bundle\";N;}}s:29:\"revision_translation_affected\";O:37:\"Drupal\\Core\\Field\\BaseFieldDefinition\":5:{s:7:\" * type\";s:7:\"boolean\";s:9:\" * schema\";a:4:{s:7:\"columns\";a:1:{s:5:\"value\";a:2:{s:4:\"type\";s:3:\"int\";s:4:\"size\";s:4:\"tiny\";}}s:11:\"unique keys\";a:0:{}s:7:\"indexes\";a:0:{}s:12:\"foreign keys\";a:0:{}}s:10:\" * indexes\";a:0:{}s:17:\" * itemDefinition\";O:51:\"Drupal\\Core\\Field\\TypedData\\FieldItemDataDefinition\":2:{s:18:\" * fieldDefinition\";r:618;s:13:\" * definition\";a:2:{s:4:\"type\";s:18:\"field_item:boolean\";s:8:\"settings\";a:2:{s:8:\"on_label\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\" * string\";s:2:\"On\";s:12:\" * arguments\";a:0:{}s:10:\" * options\";a:0:{}}s:9:\"off_label\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\" * string\";s:3:\"Off\";s:12:\" * arguments\";a:0:{}s:10:\" * options\";a:0:{}}}}}s:13:\" * definition\";a:9:{s:5:\"label\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\" * string\";s:29:\"Revision translation affected\";s:12:\" * arguments\";a:0:{}s:10:\" * options\";a:0:{}}s:11:\"description\";O:48:\"Drupal\\Core\\StringTranslation\\TranslatableMarkup\":3:{s:9:\" * string\";s:72:\"Indicates if the last edit of a translation belongs to current revision.\";s:12:\" * arguments\";a:0:{}s:10:\" * options\";a:0:{}}s:9:\"read-only\";b:1;s:12:\"revisionable\";b:1;s:12:\"translatable\";b:1;s:8:\"provider\";s:5:\"media\";s:10:\"field_name\";s:29:\"revision_translation_affected\";s:11:\"entity_type\";s:5:\"media\";s:6:\"bundle\";N;}}s:17:\"field_media_image\";O:38:\"Drupal\\field\\Entity\\FieldStorageConfig\":28:{s:5:\" * id\";s:23:\"media.field_media_image\";s:13:\" * field_name\";s:17:\"field_media_image\";s:14:\" * entity_type\";s:5:\"media\";s:7:\" * type\";s:5:\"image\";s:9:\" * module\";s:5:\"image\";s:11:\" * settings\";a:5:{s:13:\"default_image\";a:5:{s:4:\"uuid\";N;s:3:\"alt\";s:0:\"\";s:5:\"title\";s:0:\"\";s:5:\"width\";N;s:6:\"height\";N;}s:11:\"target_type\";s:4:\"file\";s:13:\"display_field\";b:0;s:15:\"display_default\";b:0;s:10:\"uri_scheme\";s:6:\"public\";}s:14:\" * cardinality\";i:1;s:15:\" * translatable\";b:1;s:9:\" * locked\";b:0;s:25:\" * persist_with_no_fields\";b:0;s:14:\"custom_storage\";b:0;s:10:\" * indexes\";a:0:{}s:10:\" * deleted\";b:0;s:13:\" * originalId\";s:23:\"media.field_media_image\";s:9:\" * status\";b:1;s:7:\" * uuid\";s:36:\"d3fd56b6-fa0f-4690-9885-ccceb29a0ba3\";s:11:\" * langcode\";s:2:\"en\";s:23:\" * third_party_settings\";a:0:{}s:8:\" * _core\";a:1:{s:19:\"default_config_hash\";s:43:\"7ZBrcl87ZXaw42v952wwcw_9cQgTBq5_5tgyUkE-VV0\";}s:14:\" * trustedData\";b:1;s:15:\" * entityTypeId\";s:20:\"field_storage_config\";s:15:\" * enforceIsNew\";b:1;s:12:\" * typedData\";N;s:16:\" * cacheContexts\";a:0:{}s:12:\" * cacheTags\";a:0:{}s:14:\" * cacheMaxAge\";i:-1;s:14:\" * _serviceIds\";a:0:{}s:15:\" * dependencies\";a:2:{s:8:\"enforced\";a:1:{s:6:\"module\";a:1:{i:0;s:5:\"media\";}}s:6:\"module\";a:3:{i:0;s:4:\"file\";i:1;s:5:\"image\";i:2;s:5:\"media\";}}}s:16:\"field_media_file\";O:38:\"Drupal\\field\\Entity\\FieldStorageConfig\":28:{s:5:\" * id\";s:22:\"media.field_media_file\";s:13:\" * field_name\";s:16:\"field_media_file\";s:14:\" * entity_type\";s:5:\"media\";s:7:\" * type\";s:4:\"file\";s:9:\" * module\";s:4:\"file\";s:11:\" * settings\";a:4:{s:10:\"uri_scheme\";s:6:\"public\";s:11:\"target_type\";s:4:\"file\";s:13:\"display_field\";b:0;s:15:\"display_default\";b:0;}s:14:\" * cardinality\";i:1;s:15:\" * translatable\";b:1;s:9:\" * locked\";b:0;s:25:\" * persist_with_no_fields\";b:0;s:14:\"custom_storage\";b:0;s:10:\" * indexes\";a:0:{}s:10:\" * deleted\";b:0;s:13:\" * originalId\";s:22:\"media.field_media_file\";s:9:\" * status\";b:1;s:7:\" * uuid\";s:36:\"9a4345cc-dc31-47d6-8735-2ddad3e67677\";s:11:\" * langcode\";s:2:\"en\";s:23:\" * third_party_settings\";a:0:{}s:8:\" * _core\";a:1:{s:19:\"default_config_hash\";s:43:\"4GNilUMnj0opT050eZIkWhkfuzu69ClyEr-cHxofjQw\";}s:14:\" * trustedData\";b:1;s:15:\" * entityTypeId\";s:20:\"field_storage_config\";s:15:\" * enforceIsNew\";b:1;s:12:\" * typedData\";N;s:16:\" * cacheContexts\";a:0:{}s:12:\" * cacheTags\";a:0:{}s:14:\" * cacheMaxAge\";i:-1;s:14:\" * _serviceIds\";a:0:{}s:15:\" * dependencies\";a:2:{s:8:\"enforced\";a:1:{s:6:\"module\";a:1:{i:0;s:5:\"media\";}}s:6:\"module\";a:2:{i:0;s:4:\"file\";i:1;s:5:\"media\";}}}}",
))
->values(array(
  'collection' => 'entity.definitions.installed',
  'name' => 'media_type.entity_type',
  'value' => 'O:42:"Drupal\Core\Config\Entity\ConfigEntityType":41:{s:16:" * config_prefix";s:4:"type";s:15:" * static_cache";b:0;s:14:" * lookup_keys";a:1:{i:0;s:4:"uuid";}s:16:" * config_export";a:9:{i:0;s:2:"id";i:1;s:5:"label";i:2;s:11:"description";i:3;s:6:"source";i:4;s:25:"queue_thumbnail_downloads";i:5;s:12:"new_revision";i:6;s:20:"source_configuration";i:7;s:9:"field_map";i:8;s:6:"status";}s:21:" * mergedConfigExport";a:0:{}s:15:" * render_cache";b:1;s:19:" * persistent_cache";b:1;s:14:" * entity_keys";a:9:{s:2:"id";s:2:"id";s:5:"label";s:5:"label";s:6:"status";s:6:"status";s:8:"revision";s:0:"";s:6:"bundle";s:0:"";s:8:"langcode";s:8:"langcode";s:16:"default_langcode";s:16:"default_langcode";s:29:"revision_translation_affected";s:29:"revision_translation_affected";s:4:"uuid";s:4:"uuid";}s:5:" * id";s:10:"media_type";s:16:" * originalClass";s:29:"Drupal\media\Entity\MediaType";s:11:" * handlers";a:5:{s:4:"form";a:3:{s:3:"add";s:26:"Drupal\media\MediaTypeForm";s:4:"edit";s:26:"Drupal\media\MediaTypeForm";s:6:"delete";s:44:"Drupal\media\Form\MediaTypeDeleteConfirmForm";}s:12:"list_builder";s:33:"Drupal\media\MediaTypeListBuilder";s:14:"route_provider";a:1:{s:4:"html";s:51:"Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider";}s:6:"access";s:45:"Drupal\Core\Entity\EntityAccessControlHandler";s:7:"storage";s:45:"Drupal\Core\Config\Entity\ConfigEntityStorage";}s:19:" * admin_permission";s:22:"administer media types";s:25:" * permission_granularity";s:11:"entity_type";s:8:" * links";a:4:{s:8:"add-form";s:26:"/admin/structure/media/add";s:9:"edit-form";s:42:"/admin/structure/media/manage/{media_type}";s:11:"delete-form";s:49:"/admin/structure/media/manage/{media_type}/delete";s:10:"collection";s:22:"/admin/structure/media";}s:17:" * label_callback";N;s:21:" * bundle_entity_type";N;s:12:" * bundle_of";s:5:"media";s:15:" * bundle_label";N;s:13:" * base_table";N;s:22:" * revision_data_table";N;s:17:" * revision_table";N;s:13:" * data_table";N;s:15:" * translatable";b:0;s:19:" * show_revision_ui";b:0;s:8:" * label";O:48:"Drupal\Core\StringTranslation\TranslatableMarkup":3:{s:9:" * string";s:10:"Media type";s:12:" * arguments";a:0:{}s:10:" * options";a:0:{}}s:19:" * label_collection";O:48:"Drupal\Core\StringTranslation\TranslatableMarkup":3:{s:9:" * string";s:11:"Media types";s:12:" * arguments";a:0:{}s:10:" * options";a:0:{}}s:17:" * label_singular";O:48:"Drupal\Core\StringTranslation\TranslatableMarkup":3:{s:9:" * string";s:10:"media type";s:12:" * arguments";a:0:{}s:10:" * options";a:0:{}}s:15:" * label_plural";O:48:"Drupal\Core\StringTranslation\TranslatableMarkup":3:{s:9:" * string";s:11:"media types";s:12:" * arguments";a:0:{}s:10:" * options";a:0:{}}s:14:" * label_count";a:3:{s:8:"singular";s:17:"@count media type";s:6:"plural";s:18:"@count media types";s:7:"context";N;}s:15:" * uri_callback";N;s:8:" * group";s:13:"configuration";s:14:" * group_label";O:48:"Drupal\Core\StringTranslation\TranslatableMarkup":3:{s:9:" * string";s:13:"Configuration";s:12:" * arguments";a:0:{}s:10:" * options";a:1:{s:7:"context";s:17:"Entity type group";}}s:22:" * field_ui_base_route";N;s:26:" * common_reference_target";b:0;s:22:" * list_cache_contexts";a:0:{}s:18:" * list_cache_tags";a:1:{i:0;s:22:"config:media_type_list";}s:14:" * constraints";a:0:{}s:13:" * additional";a:0:{}s:8:" * class";s:29:"Drupal\media\Entity\MediaType";s:11:" * provider";s:5:"media";s:20:" * stringTranslation";N;}',
))
->values(array(
  'collection' => 'entity.storage_schema.sql',
  'name' => 'media.entity_schema_data',
  'value' => 'a:4:{s:5:"media";a:2:{s:11:"primary key";a:1:{i:0;s:3:"mid";}s:11:"unique keys";a:1:{s:10:"media__vid";a:1:{i:0;s:3:"vid";}}}s:14:"media_revision";a:2:{s:11:"primary key";a:1:{i:0;s:3:"vid";}s:7:"indexes";a:1:{s:10:"media__mid";a:1:{i:0;s:3:"mid";}}}s:16:"media_field_data";a:2:{s:11:"primary key";a:2:{i:0;s:3:"mid";i:1;s:8:"langcode";}s:7:"indexes";a:3:{s:37:"media__id__default_langcode__langcode";a:3:{i:0;s:3:"mid";i:1;s:16:"default_langcode";i:2;s:8:"langcode";}s:10:"media__vid";a:1:{i:0;s:3:"vid";}s:20:"media__status_bundle";a:3:{i:0;s:6:"status";i:1;s:6:"bundle";i:2;s:3:"mid";}}}s:20:"media_field_revision";a:2:{s:11:"primary key";a:2:{i:0;s:3:"vid";i:1;s:8:"langcode";}s:7:"indexes";a:1:{s:37:"media__id__default_langcode__langcode";a:3:{i:0;s:3:"mid";i:1;s:16:"default_langcode";i:2;s:8:"langcode";}}}}',
))
->values(array(
  'collection' => 'entity.storage_schema.sql',
  'name' => 'media.field_schema_data.bundle',
  'value' => 'a:2:{s:5:"media";a:2:{s:6:"fields";a:1:{s:6:"bundle";a:4:{s:11:"description";s:28:"The ID of the target entity.";s:4:"type";s:13:"varchar_ascii";s:6:"length";i:32;s:8:"not null";b:1;}}s:7:"indexes";a:1:{s:30:"media_field__bundle__target_id";a:1:{i:0;s:6:"bundle";}}}s:16:"media_field_data";a:2:{s:6:"fields";a:1:{s:6:"bundle";a:4:{s:11:"description";s:28:"The ID of the target entity.";s:4:"type";s:13:"varchar_ascii";s:6:"length";i:32;s:8:"not null";b:1;}}s:7:"indexes";a:1:{s:30:"media_field__bundle__target_id";a:1:{i:0;s:6:"bundle";}}}}',
))
->values(array(
  'collection' => 'entity.storage_schema.sql',
  'name' => 'media.field_schema_data.changed',
  'value' => 'a:2:{s:16:"media_field_data";a:1:{s:6:"fields";a:1:{s:7:"changed";a:2:{s:4:"type";s:3:"int";s:8:"not null";b:0;}}}s:20:"media_field_revision";a:1:{s:6:"fields";a:1:{s:7:"changed";a:2:{s:4:"type";s:3:"int";s:8:"not null";b:0;}}}}',
))
->values(array(
  'collection' => 'entity.storage_schema.sql',
  'name' => 'media.field_schema_data.created',
  'value' => 'a:2:{s:16:"media_field_data";a:1:{s:6:"fields";a:1:{s:7:"created";a:2:{s:4:"type";s:3:"int";s:8:"not null";b:0;}}}s:20:"media_field_revision";a:1:{s:6:"fields";a:1:{s:7:"created";a:2:{s:4:"type";s:3:"int";s:8:"not null";b:0;}}}}',
))
->values(array(
  'collection' => 'entity.storage_schema.sql',
  'name' => 'media.field_schema_data.default_langcode',
  'value' => 'a:2:{s:16:"media_field_data";a:1:{s:6:"fields";a:1:{s:16:"default_langcode";a:3:{s:4:"type";s:3:"int";s:4:"size";s:4:"tiny";s:8:"not null";b:1;}}}s:20:"media_field_revision";a:1:{s:6:"fields";a:1:{s:16:"default_langcode";a:3:{s:4:"type";s:3:"int";s:4:"size";s:4:"tiny";s:8:"not null";b:1;}}}}',
))
->values(array(
  'collection' => 'entity.storage_schema.sql',
  'name' => 'media.field_schema_data.field_media_file',
  'value' => 'a:2:{s:23:"media__field_media_file";a:5:{s:11:"description";s:46:"Data storage for media field field_media_file.";s:6:"fields";a:9:{s:6:"bundle";a:5:{s:4:"type";s:13:"varchar_ascii";s:6:"length";i:128;s:8:"not null";b:1;s:7:"default";s:0:"";s:11:"description";s:88:"The field instance bundle to which this row belongs, used when deleting a field instance";}s:7:"deleted";a:5:{s:4:"type";s:3:"int";s:4:"size";s:4:"tiny";s:8:"not null";b:1;s:7:"default";i:0;s:11:"description";s:60:"A boolean indicating whether this data item has been deleted";}s:9:"entity_id";a:4:{s:4:"type";s:3:"int";s:8:"unsigned";b:1;s:8:"not null";b:1;s:11:"description";s:38:"The entity id this data is attached to";}s:11:"revision_id";a:4:{s:4:"type";s:3:"int";s:8:"unsigned";b:1;s:8:"not null";b:1;s:11:"description";s:47:"The entity revision id this data is attached to";}s:8:"langcode";a:5:{s:4:"type";s:13:"varchar_ascii";s:6:"length";i:32;s:8:"not null";b:1;s:7:"default";s:0:"";s:11:"description";s:37:"The language code for this data item.";}s:5:"delta";a:4:{s:4:"type";s:3:"int";s:8:"unsigned";b:1;s:8:"not null";b:1;s:11:"description";s:67:"The sequence number for this data item, used for multi-value fields";}s:26:"field_media_file_target_id";a:4:{s:11:"description";s:26:"The ID of the file entity.";s:4:"type";s:3:"int";s:8:"unsigned";b:1;s:8:"not null";b:1;}s:24:"field_media_file_display";a:6:{s:11:"description";s:75:"Flag to control whether this file should be displayed when viewing content.";s:4:"type";s:3:"int";s:4:"size";s:4:"tiny";s:8:"unsigned";b:1;s:7:"default";i:1;s:8:"not null";b:0;}s:28:"field_media_file_description";a:3:{s:11:"description";s:26:"A description of the file.";s:4:"type";s:4:"text";s:8:"not null";b:0;}}s:11:"primary key";a:4:{i:0;s:9:"entity_id";i:1;s:7:"deleted";i:2;s:5:"delta";i:3;s:8:"langcode";}s:7:"indexes";a:3:{s:6:"bundle";a:1:{i:0;s:6:"bundle";}s:11:"revision_id";a:1:{i:0;s:11:"revision_id";}s:26:"field_media_file_target_id";a:1:{i:0;s:26:"field_media_file_target_id";}}s:12:"foreign keys";a:1:{s:26:"field_media_file_target_id";a:2:{s:5:"table";s:12:"file_managed";s:7:"columns";a:1:{s:26:"field_media_file_target_id";s:3:"fid";}}}}s:32:"media_revision__field_media_file";a:5:{s:11:"description";s:58:"Revision archive storage for media field field_media_file.";s:6:"fields";a:9:{s:6:"bundle";a:5:{s:4:"type";s:13:"varchar_ascii";s:6:"length";i:128;s:8:"not null";b:1;s:7:"default";s:0:"";s:11:"description";s:88:"The field instance bundle to which this row belongs, used when deleting a field instance";}s:7:"deleted";a:5:{s:4:"type";s:3:"int";s:4:"size";s:4:"tiny";s:8:"not null";b:1;s:7:"default";i:0;s:11:"description";s:60:"A boolean indicating whether this data item has been deleted";}s:9:"entity_id";a:4:{s:4:"type";s:3:"int";s:8:"unsigned";b:1;s:8:"not null";b:1;s:11:"description";s:38:"The entity id this data is attached to";}s:11:"revision_id";a:4:{s:4:"type";s:3:"int";s:8:"unsigned";b:1;s:8:"not null";b:1;s:11:"description";s:47:"The entity revision id this data is attached to";}s:8:"langcode";a:5:{s:4:"type";s:13:"varchar_ascii";s:6:"length";i:32;s:8:"not null";b:1;s:7:"default";s:0:"";s:11:"description";s:37:"The language code for this data item.";}s:5:"delta";a:4:{s:4:"type";s:3:"int";s:8:"unsigned";b:1;s:8:"not null";b:1;s:11:"description";s:67:"The sequence number for this data item, used for multi-value fields";}s:26:"field_media_file_target_id";a:4:{s:11:"description";s:26:"The ID of the file entity.";s:4:"type";s:3:"int";s:8:"unsigned";b:1;s:8:"not null";b:1;}s:24:"field_media_file_display";a:6:{s:11:"description";s:75:"Flag to control whether this file should be displayed when viewing content.";s:4:"type";s:3:"int";s:4:"size";s:4:"tiny";s:8:"unsigned";b:1;s:7:"default";i:1;s:8:"not null";b:0;}s:28:"field_media_file_description";a:3:{s:11:"description";s:26:"A description of the file.";s:4:"type";s:4:"text";s:8:"not null";b:0;}}s:11:"primary key";a:5:{i:0;s:9:"entity_id";i:1;s:11:"revision_id";i:2;s:7:"deleted";i:3;s:5:"delta";i:4;s:8:"langcode";}s:7:"indexes";a:3:{s:6:"bundle";a:1:{i:0;s:6:"bundle";}s:11:"revision_id";a:1:{i:0;s:11:"revision_id";}s:26:"field_media_file_target_id";a:1:{i:0;s:26:"field_media_file_target_id";}}s:12:"foreign keys";a:1:{s:26:"field_media_file_target_id";a:2:{s:5:"table";s:12:"file_managed";s:7:"columns";a:1:{s:26:"field_media_file_target_id";s:3:"fid";}}}}}',
))
->values(array(
  'collection' => 'entity.storage_schema.sql',
  'name' => 'media.field_schema_data.field_media_image',
  'value' => "a:2:{s:24:\"media__field_media_image\";a:5:{s:11:\"description\";s:47:\"Data storage for media field field_media_image.\";s:6:\"fields\";a:11:{s:6:\"bundle\";a:5:{s:4:\"type\";s:13:\"varchar_ascii\";s:6:\"length\";i:128;s:8:\"not null\";b:1;s:7:\"default\";s:0:\"\";s:11:\"description\";s:88:\"The field instance bundle to which this row belongs, used when deleting a field instance\";}s:7:\"deleted\";a:5:{s:4:\"type\";s:3:\"int\";s:4:\"size\";s:4:\"tiny\";s:8:\"not null\";b:1;s:7:\"default\";i:0;s:11:\"description\";s:60:\"A boolean indicating whether this data item has been deleted\";}s:9:\"entity_id\";a:4:{s:4:\"type\";s:3:\"int\";s:8:\"unsigned\";b:1;s:8:\"not null\";b:1;s:11:\"description\";s:38:\"The entity id this data is attached to\";}s:11:\"revision_id\";a:4:{s:4:\"type\";s:3:\"int\";s:8:\"unsigned\";b:1;s:8:\"not null\";b:1;s:11:\"description\";s:47:\"The entity revision id this data is attached to\";}s:8:\"langcode\";a:5:{s:4:\"type\";s:13:\"varchar_ascii\";s:6:\"length\";i:32;s:8:\"not null\";b:1;s:7:\"default\";s:0:\"\";s:11:\"description\";s:37:\"The language code for this data item.\";}s:5:\"delta\";a:4:{s:4:\"type\";s:3:\"int\";s:8:\"unsigned\";b:1;s:8:\"not null\";b:1;s:11:\"description\";s:67:\"The sequence number for this data item, used for multi-value fields\";}s:27:\"field_media_image_target_id\";a:4:{s:11:\"description\";s:26:\"The ID of the file entity.\";s:4:\"type\";s:3:\"int\";s:8:\"unsigned\";b:1;s:8:\"not null\";b:1;}s:21:\"field_media_image_alt\";a:4:{s:11:\"description\";s:56:\"Alternative image text, for the image's 'alt' attribute.\";s:4:\"type\";s:7:\"varchar\";s:6:\"length\";i:512;s:8:\"not null\";b:0;}s:23:\"field_media_image_title\";a:4:{s:11:\"description\";s:52:\"Image title text, for the image's 'title' attribute.\";s:4:\"type\";s:7:\"varchar\";s:6:\"length\";i:1024;s:8:\"not null\";b:0;}s:23:\"field_media_image_width\";a:4:{s:11:\"description\";s:33:\"The width of the image in pixels.\";s:4:\"type\";s:3:\"int\";s:8:\"unsigned\";b:1;s:8:\"not null\";b:0;}s:24:\"field_media_image_height\";a:4:{s:11:\"description\";s:34:\"The height of the image in pixels.\";s:4:\"type\";s:3:\"int\";s:8:\"unsigned\";b:1;s:8:\"not null\";b:0;}}s:11:\"primary key\";a:4:{i:0;s:9:\"entity_id\";i:1;s:7:\"deleted\";i:2;s:5:\"delta\";i:3;s:8:\"langcode\";}s:7:\"indexes\";a:3:{s:6:\"bundle\";a:1:{i:0;s:6:\"bundle\";}s:11:\"revision_id\";a:1:{i:0;s:11:\"revision_id\";}s:27:\"field_media_image_target_id\";a:1:{i:0;s:27:\"field_media_image_target_id\";}}s:12:\"foreign keys\";a:1:{s:27:\"field_media_image_target_id\";a:2:{s:5:\"table\";s:12:\"file_managed\";s:7:\"columns\";a:1:{s:27:\"field_media_image_target_id\";s:3:\"fid\";}}}}s:33:\"media_revision__field_media_image\";a:5:{s:11:\"description\";s:59:\"Revision archive storage for media field field_media_image.\";s:6:\"fields\";a:11:{s:6:\"bundle\";a:5:{s:4:\"type\";s:13:\"varchar_ascii\";s:6:\"length\";i:128;s:8:\"not null\";b:1;s:7:\"default\";s:0:\"\";s:11:\"description\";s:88:\"The field instance bundle to which this row belongs, used when deleting a field instance\";}s:7:\"deleted\";a:5:{s:4:\"type\";s:3:\"int\";s:4:\"size\";s:4:\"tiny\";s:8:\"not null\";b:1;s:7:\"default\";i:0;s:11:\"description\";s:60:\"A boolean indicating whether this data item has been deleted\";}s:9:\"entity_id\";a:4:{s:4:\"type\";s:3:\"int\";s:8:\"unsigned\";b:1;s:8:\"not null\";b:1;s:11:\"description\";s:38:\"The entity id this data is attached to\";}s:11:\"revision_id\";a:4:{s:4:\"type\";s:3:\"int\";s:8:\"unsigned\";b:1;s:8:\"not null\";b:1;s:11:\"description\";s:47:\"The entity revision id this data is attached to\";}s:8:\"langcode\";a:5:{s:4:\"type\";s:13:\"varchar_ascii\";s:6:\"length\";i:32;s:8:\"not null\";b:1;s:7:\"default\";s:0:\"\";s:11:\"description\";s:37:\"The language code for this data item.\";}s:5:\"delta\";a:4:{s:4:\"type\";s:3:\"int\";s:8:\"unsigned\";b:1;s:8:\"not null\";b:1;s:11:\"description\";s:67:\"The sequence number for this data item, used for multi-value fields\";}s:27:\"field_media_image_target_id\";a:4:{s:11:\"description\";s:26:\"The ID of the file entity.\";s:4:\"type\";s:3:\"int\";s:8:\"unsigned\";b:1;s:8:\"not null\";b:1;}s:21:\"field_media_image_alt\";a:4:{s:11:\"description\";s:56:\"Alternative image text, for the image's 'alt' attribute.\";s:4:\"type\";s:7:\"varchar\";s:6:\"length\";i:512;s:8:\"not null\";b:0;}s:23:\"field_media_image_title\";a:4:{s:11:\"description\";s:52:\"Image title text, for the image's 'title' attribute.\";s:4:\"type\";s:7:\"varchar\";s:6:\"length\";i:1024;s:8:\"not null\";b:0;}s:23:\"field_media_image_width\";a:4:{s:11:\"description\";s:33:\"The width of the image in pixels.\";s:4:\"type\";s:3:\"int\";s:8:\"unsigned\";b:1;s:8:\"not null\";b:0;}s:24:\"field_media_image_height\";a:4:{s:11:\"description\";s:34:\"The height of the image in pixels.\";s:4:\"type\";s:3:\"int\";s:8:\"unsigned\";b:1;s:8:\"not null\";b:0;}}s:11:\"primary key\";a:5:{i:0;s:9:\"entity_id\";i:1;s:11:\"revision_id\";i:2;s:7:\"deleted\";i:3;s:5:\"delta\";i:4;s:8:\"langcode\";}s:7:\"indexes\";a:3:{s:6:\"bundle\";a:1:{i:0;s:6:\"bundle\";}s:11:\"revision_id\";a:1:{i:0;s:11:\"revision_id\";}s:27:\"field_media_image_target_id\";a:1:{i:0;s:27:\"field_media_image_target_id\";}}s:12:\"foreign keys\";a:1:{s:27:\"field_media_image_target_id\";a:2:{s:5:\"table\";s:12:\"file_managed\";s:7:\"columns\";a:1:{s:27:\"field_media_image_target_id\";s:3:\"fid\";}}}}}",
))
->values(array(
  'collection' => 'entity.storage_schema.sql',
  'name' => 'media.field_schema_data.langcode',
  'value' => 'a:4:{s:5:"media";a:1:{s:6:"fields";a:1:{s:8:"langcode";a:3:{s:4:"type";s:13:"varchar_ascii";s:6:"length";i:12;s:8:"not null";b:1;}}}s:16:"media_field_data";a:1:{s:6:"fields";a:1:{s:8:"langcode";a:3:{s:4:"type";s:13:"varchar_ascii";s:6:"length";i:12;s:8:"not null";b:1;}}}s:14:"media_revision";a:1:{s:6:"fields";a:1:{s:8:"langcode";a:3:{s:4:"type";s:13:"varchar_ascii";s:6:"length";i:12;s:8:"not null";b:1;}}}s:20:"media_field_revision";a:1:{s:6:"fields";a:1:{s:8:"langcode";a:3:{s:4:"type";s:13:"varchar_ascii";s:6:"length";i:12;s:8:"not null";b:1;}}}}',
))
->values(array(
  'collection' => 'entity.storage_schema.sql',
  'name' => 'media.field_schema_data.mid',
  'value' => 'a:4:{s:5:"media";a:1:{s:6:"fields";a:1:{s:3:"mid";a:4:{s:4:"type";s:3:"int";s:8:"unsigned";b:1;s:4:"size";s:6:"normal";s:8:"not null";b:1;}}}s:16:"media_field_data";a:1:{s:6:"fields";a:1:{s:3:"mid";a:4:{s:4:"type";s:3:"int";s:8:"unsigned";b:1;s:4:"size";s:6:"normal";s:8:"not null";b:1;}}}s:14:"media_revision";a:1:{s:6:"fields";a:1:{s:3:"mid";a:4:{s:4:"type";s:3:"int";s:8:"unsigned";b:1;s:4:"size";s:6:"normal";s:8:"not null";b:1;}}}s:20:"media_field_revision";a:1:{s:6:"fields";a:1:{s:3:"mid";a:4:{s:4:"type";s:3:"int";s:8:"unsigned";b:1;s:4:"size";s:6:"normal";s:8:"not null";b:1;}}}}',
))
->values(array(
  'collection' => 'entity.storage_schema.sql',
  'name' => 'media.field_schema_data.name',
  'value' => 'a:2:{s:16:"media_field_data";a:1:{s:6:"fields";a:1:{s:4:"name";a:4:{s:4:"type";s:7:"varchar";s:6:"length";i:255;s:6:"binary";b:0;s:8:"not null";b:0;}}}s:20:"media_field_revision";a:1:{s:6:"fields";a:1:{s:4:"name";a:4:{s:4:"type";s:7:"varchar";s:6:"length";i:255;s:6:"binary";b:0;s:8:"not null";b:0;}}}}',
))
->values(array(
  'collection' => 'entity.storage_schema.sql',
  'name' => 'media.field_schema_data.revision_created',
  'value' => 'a:1:{s:14:"media_revision";a:1:{s:6:"fields";a:1:{s:16:"revision_created";a:2:{s:4:"type";s:3:"int";s:8:"not null";b:0;}}}}',
))
->values(array(
  'collection' => 'entity.storage_schema.sql',
  'name' => 'media.field_schema_data.revision_log_message',
  'value' => 'a:1:{s:14:"media_revision";a:1:{s:6:"fields";a:1:{s:20:"revision_log_message";a:3:{s:4:"type";s:4:"text";s:4:"size";s:3:"big";s:8:"not null";b:0;}}}}',
))
->values(array(
  'collection' => 'entity.storage_schema.sql',
  'name' => 'media.field_schema_data.revision_translation_affected',
  'value' => 'a:2:{s:16:"media_field_data";a:1:{s:6:"fields";a:1:{s:29:"revision_translation_affected";a:3:{s:4:"type";s:3:"int";s:4:"size";s:4:"tiny";s:8:"not null";b:0;}}}s:20:"media_field_revision";a:1:{s:6:"fields";a:1:{s:29:"revision_translation_affected";a:3:{s:4:"type";s:3:"int";s:4:"size";s:4:"tiny";s:8:"not null";b:0;}}}}',
))
->values(array(
  'collection' => 'entity.storage_schema.sql',
  'name' => 'media.field_schema_data.revision_user',
  'value' => 'a:1:{s:14:"media_revision";a:2:{s:6:"fields";a:1:{s:13:"revision_user";a:4:{s:11:"description";s:28:"The ID of the target entity.";s:4:"type";s:3:"int";s:8:"unsigned";b:1;s:8:"not null";b:0;}}s:7:"indexes";a:1:{s:37:"media_field__revision_user__target_id";a:1:{i:0;s:13:"revision_user";}}}}',
))
->values(array(
  'collection' => 'entity.storage_schema.sql',
  'name' => 'media.field_schema_data.status',
  'value' => 'a:2:{s:16:"media_field_data";a:1:{s:6:"fields";a:1:{s:6:"status";a:3:{s:4:"type";s:3:"int";s:4:"size";s:4:"tiny";s:8:"not null";b:1;}}}s:20:"media_field_revision";a:1:{s:6:"fields";a:1:{s:6:"status";a:3:{s:4:"type";s:3:"int";s:4:"size";s:4:"tiny";s:8:"not null";b:1;}}}}',
))
->values(array(
  'collection' => 'entity.storage_schema.sql',
  'name' => 'media.field_schema_data.thumbnail',
  'value' => "a:2:{s:16:\"media_field_data\";a:3:{s:6:\"fields\";a:5:{s:20:\"thumbnail__target_id\";a:4:{s:11:\"description\";s:26:\"The ID of the file entity.\";s:4:\"type\";s:3:\"int\";s:8:\"unsigned\";b:1;s:8:\"not null\";b:0;}s:14:\"thumbnail__alt\";a:4:{s:11:\"description\";s:56:\"Alternative image text, for the image's 'alt' attribute.\";s:4:\"type\";s:7:\"varchar\";s:6:\"length\";i:512;s:8:\"not null\";b:0;}s:16:\"thumbnail__title\";a:4:{s:11:\"description\";s:52:\"Image title text, for the image's 'title' attribute.\";s:4:\"type\";s:7:\"varchar\";s:6:\"length\";i:1024;s:8:\"not null\";b:0;}s:16:\"thumbnail__width\";a:4:{s:11:\"description\";s:33:\"The width of the image in pixels.\";s:4:\"type\";s:3:\"int\";s:8:\"unsigned\";b:1;s:8:\"not null\";b:0;}s:17:\"thumbnail__height\";a:4:{s:11:\"description\";s:34:\"The height of the image in pixels.\";s:4:\"type\";s:3:\"int\";s:8:\"unsigned\";b:1;s:8:\"not null\";b:0;}}s:7:\"indexes\";a:1:{s:33:\"media_field__thumbnail__target_id\";a:1:{i:0;s:20:\"thumbnail__target_id\";}}s:12:\"foreign keys\";a:1:{s:33:\"media_field__thumbnail__target_id\";a:2:{s:5:\"table\";s:12:\"file_managed\";s:7:\"columns\";a:1:{s:20:\"thumbnail__target_id\";s:3:\"fid\";}}}}s:20:\"media_field_revision\";a:3:{s:6:\"fields\";a:5:{s:20:\"thumbnail__target_id\";a:4:{s:11:\"description\";s:26:\"The ID of the file entity.\";s:4:\"type\";s:3:\"int\";s:8:\"unsigned\";b:1;s:8:\"not null\";b:0;}s:14:\"thumbnail__alt\";a:4:{s:11:\"description\";s:56:\"Alternative image text, for the image's 'alt' attribute.\";s:4:\"type\";s:7:\"varchar\";s:6:\"length\";i:512;s:8:\"not null\";b:0;}s:16:\"thumbnail__title\";a:4:{s:11:\"description\";s:52:\"Image title text, for the image's 'title' attribute.\";s:4:\"type\";s:7:\"varchar\";s:6:\"length\";i:1024;s:8:\"not null\";b:0;}s:16:\"thumbnail__width\";a:4:{s:11:\"description\";s:33:\"The width of the image in pixels.\";s:4:\"type\";s:3:\"int\";s:8:\"unsigned\";b:1;s:8:\"not null\";b:0;}s:17:\"thumbnail__height\";a:4:{s:11:\"description\";s:34:\"The height of the image in pixels.\";s:4:\"type\";s:3:\"int\";s:8:\"unsigned\";b:1;s:8:\"not null\";b:0;}}s:7:\"indexes\";a:1:{s:33:\"media_field__thumbnail__target_id\";a:1:{i:0;s:20:\"thumbnail__target_id\";}}s:12:\"foreign keys\";a:1:{s:33:\"media_field__thumbnail__target_id\";a:2:{s:5:\"table\";s:12:\"file_managed\";s:7:\"columns\";a:1:{s:20:\"thumbnail__target_id\";s:3:\"fid\";}}}}}",
))
->values(array(
  'collection' => 'entity.storage_schema.sql',
  'name' => 'media.field_schema_data.uid',
  'value' => 'a:2:{s:16:"media_field_data";a:2:{s:6:"fields";a:1:{s:3:"uid";a:4:{s:11:"description";s:28:"The ID of the target entity.";s:4:"type";s:3:"int";s:8:"unsigned";b:1;s:8:"not null";b:0;}}s:7:"indexes";a:1:{s:27:"media_field__uid__target_id";a:1:{i:0;s:3:"uid";}}}s:20:"media_field_revision";a:2:{s:6:"fields";a:1:{s:3:"uid";a:4:{s:11:"description";s:28:"The ID of the target entity.";s:4:"type";s:3:"int";s:8:"unsigned";b:1;s:8:"not null";b:0;}}s:7:"indexes";a:1:{s:27:"media_field__uid__target_id";a:1:{i:0;s:3:"uid";}}}}',
))
->values(array(
  'collection' => 'entity.storage_schema.sql',
  'name' => 'media.field_schema_data.uuid',
  'value' => 'a:1:{s:5:"media";a:2:{s:6:"fields";a:1:{s:4:"uuid";a:4:{s:4:"type";s:13:"varchar_ascii";s:6:"length";i:128;s:6:"binary";b:0;s:8:"not null";b:1;}}s:11:"unique keys";a:1:{s:24:"media_field__uuid__value";a:1:{i:0;s:4:"uuid";}}}}',
))
->values(array(
  'collection' => 'entity.storage_schema.sql',
  'name' => 'media.field_schema_data.vid',
  'value' => 'a:4:{s:5:"media";a:1:{s:6:"fields";a:1:{s:3:"vid";a:4:{s:4:"type";s:3:"int";s:8:"unsigned";b:1;s:4:"size";s:6:"normal";s:8:"not null";b:0;}}}s:16:"media_field_data";a:1:{s:6:"fields";a:1:{s:3:"vid";a:4:{s:4:"type";s:3:"int";s:8:"unsigned";b:1;s:4:"size";s:6:"normal";s:8:"not null";b:1;}}}s:14:"media_revision";a:1:{s:6:"fields";a:1:{s:3:"vid";a:4:{s:4:"type";s:3:"int";s:8:"unsigned";b:1;s:4:"size";s:6:"normal";s:8:"not null";b:1;}}}s:20:"media_field_revision";a:1:{s:6:"fields";a:1:{s:3:"vid";a:4:{s:4:"type";s:3:"int";s:8:"unsigned";b:1;s:4:"size";s:6:"normal";s:8:"not null";b:1;}}}}',
))
->execute();

$connection->merge('key_value')
->fields([
  'collection' => 'state',
  'name' => 'views.view_route_names',
  'value' => 'a:13:{s:13:"watchdog.page";s:14:"dblog.overview";s:24:"user_admin_people.page_1";s:22:"entity.user.collection";s:20:"taxonomy_term.page_1";s:30:"entity.taxonomy_term.canonical";s:14:"content.page_1";s:20:"system.admin_content";s:23:"comment.page_unapproved";s:22:"comment.admin_approval";s:22:"comment.page_published";s:13:"comment.admin";s:20:"block_content.page_1";s:31:"entity.block_content.collection";s:12:"files.page_1";s:17:"view.files.page_1";s:12:"files.page_2";s:17:"view.files.page_2";s:16:"frontpage.feed_1";s:21:"view.frontpage.feed_1";s:16:"frontpage.page_1";s:21:"view.frontpage.page_1";s:20:"taxonomy_term.feed_1";s:25:"view.taxonomy_term.feed_1";s:21:"media.media_page_list";s:26:"view.media.media_page_list";}',
])
->condition('collection', 'state')
->condition('name', 'views.view_route_names')
->execute();

$connection->schema()->createTable('media', array(
  'fields' => array(
    'mid' => array(
      'type' => 'serial',
      'not null' => TRUE,
      'size' => 'normal',
      'unsigned' => TRUE,
    ),
    'vid' => array(
      'type' => 'int',
      'not null' => FALSE,
      'size' => 'normal',
      'unsigned' => TRUE,
    ),
    'bundle' => array(
      'type' => 'varchar_ascii',
      'not null' => TRUE,
      'length' => '32',
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
    'mid',
  ),
  'unique keys' => array(
    'media_field__uuid__value' => array(
      'uuid',
    ),
    'media__vid' => array(
      'vid',
    ),
  ),
  'indexes' => array(
    'media_field__bundle__target_id' => array(
      'bundle',
    ),
  ),
  'mysql_character_set' => 'utf8mb4',
));

$connection->schema()->createTable('media__field_media_file', array(
  'fields' => array(
    'bundle' => array(
      'type' => 'varchar_ascii',
      'not null' => TRUE,
      'length' => '128',
      'default' => '',
    ),
    'deleted' => array(
      'type' => 'int',
      'not null' => TRUE,
      'size' => 'tiny',
      'default' => '0',
    ),
    'entity_id' => array(
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
      'length' => '32',
      'default' => '',
    ),
    'delta' => array(
      'type' => 'int',
      'not null' => TRUE,
      'size' => 'normal',
      'unsigned' => TRUE,
    ),
    'field_media_file_target_id' => array(
      'type' => 'int',
      'not null' => TRUE,
      'size' => 'normal',
      'unsigned' => TRUE,
    ),
    'field_media_file_display' => array(
      'type' => 'int',
      'not null' => FALSE,
      'size' => 'tiny',
      'default' => '1',
      'unsigned' => TRUE,
    ),
    'field_media_file_description' => array(
      'type' => 'text',
      'not null' => FALSE,
      'size' => 'normal',
    ),
  ),
  'primary key' => array(
    'entity_id',
    'deleted',
    'delta',
    'langcode',
  ),
  'indexes' => array(
    'bundle' => array(
      'bundle',
    ),
    'revision_id' => array(
      'revision_id',
    ),
    'field_media_file_target_id' => array(
      'field_media_file_target_id',
    ),
  ),
  'mysql_character_set' => 'utf8mb4',
));

$connection->schema()->createTable('media__field_media_image', array(
  'fields' => array(
    'bundle' => array(
      'type' => 'varchar_ascii',
      'not null' => TRUE,
      'length' => '128',
      'default' => '',
    ),
    'deleted' => array(
      'type' => 'int',
      'not null' => TRUE,
      'size' => 'tiny',
      'default' => '0',
    ),
    'entity_id' => array(
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
      'length' => '32',
      'default' => '',
    ),
    'delta' => array(
      'type' => 'int',
      'not null' => TRUE,
      'size' => 'normal',
      'unsigned' => TRUE,
    ),
    'field_media_image_target_id' => array(
      'type' => 'int',
      'not null' => TRUE,
      'size' => 'normal',
      'unsigned' => TRUE,
    ),
    'field_media_image_alt' => array(
      'type' => 'varchar',
      'not null' => FALSE,
      'length' => '512',
    ),
    'field_media_image_title' => array(
      'type' => 'varchar',
      'not null' => FALSE,
      'length' => '1024',
    ),
    'field_media_image_width' => array(
      'type' => 'int',
      'not null' => FALSE,
      'size' => 'normal',
      'unsigned' => TRUE,
    ),
    'field_media_image_height' => array(
      'type' => 'int',
      'not null' => FALSE,
      'size' => 'normal',
      'unsigned' => TRUE,
    ),
  ),
  'primary key' => array(
    'entity_id',
    'deleted',
    'delta',
    'langcode',
  ),
  'indexes' => array(
    'bundle' => array(
      'bundle',
    ),
    'revision_id' => array(
      'revision_id',
    ),
    'field_media_image_target_id' => array(
      'field_media_image_target_id',
    ),
  ),
  'mysql_character_set' => 'utf8mb4',
));

$connection->schema()->createTable('media_field_data', array(
  'fields' => array(
    'mid' => array(
      'type' => 'int',
      'not null' => TRUE,
      'size' => 'normal',
      'unsigned' => TRUE,
    ),
    'vid' => array(
      'type' => 'int',
      'not null' => TRUE,
      'size' => 'normal',
      'unsigned' => TRUE,
    ),
    'bundle' => array(
      'type' => 'varchar_ascii',
      'not null' => TRUE,
      'length' => '32',
    ),
    'langcode' => array(
      'type' => 'varchar_ascii',
      'not null' => TRUE,
      'length' => '12',
    ),
    'status' => array(
      'type' => 'int',
      'not null' => TRUE,
      'size' => 'tiny',
    ),
    'name' => array(
      'type' => 'varchar',
      'not null' => FALSE,
      'length' => '255',
    ),
    'thumbnail__target_id' => array(
      'type' => 'int',
      'not null' => FALSE,
      'size' => 'normal',
      'unsigned' => TRUE,
    ),
    'thumbnail__alt' => array(
      'type' => 'varchar',
      'not null' => FALSE,
      'length' => '512',
    ),
    'thumbnail__title' => array(
      'type' => 'varchar',
      'not null' => FALSE,
      'length' => '1024',
    ),
    'thumbnail__width' => array(
      'type' => 'int',
      'not null' => FALSE,
      'size' => 'normal',
      'unsigned' => TRUE,
    ),
    'thumbnail__height' => array(
      'type' => 'int',
      'not null' => FALSE,
      'size' => 'normal',
      'unsigned' => TRUE,
    ),
    'uid' => array(
      'type' => 'int',
      'not null' => FALSE,
      'size' => 'normal',
      'unsigned' => TRUE,
    ),
    'created' => array(
      'type' => 'int',
      'not null' => FALSE,
      'size' => 'normal',
    ),
    'changed' => array(
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
    'mid',
    'langcode',
  ),
  'indexes' => array(
    'media__id__default_langcode__langcode' => array(
      'mid',
      'default_langcode',
      'langcode',
    ),
    'media__vid' => array(
      'vid',
    ),
    'media_field__bundle__target_id' => array(
      'bundle',
    ),
    'media_field__thumbnail__target_id' => array(
      'thumbnail__target_id',
    ),
    'media_field__uid__target_id' => array(
      'uid',
    ),
    'media__status_bundle' => array(
      'status',
      'bundle',
      'mid',
    ),
  ),
  'mysql_character_set' => 'utf8mb4',
));

$connection->schema()->createTable('media_field_revision', array(
  'fields' => array(
    'mid' => array(
      'type' => 'int',
      'not null' => TRUE,
      'size' => 'normal',
      'unsigned' => TRUE,
    ),
    'vid' => array(
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
    'status' => array(
      'type' => 'int',
      'not null' => TRUE,
      'size' => 'tiny',
    ),
    'name' => array(
      'type' => 'varchar',
      'not null' => FALSE,
      'length' => '255',
    ),
    'thumbnail__target_id' => array(
      'type' => 'int',
      'not null' => FALSE,
      'size' => 'normal',
      'unsigned' => TRUE,
    ),
    'thumbnail__alt' => array(
      'type' => 'varchar',
      'not null' => FALSE,
      'length' => '512',
    ),
    'thumbnail__title' => array(
      'type' => 'varchar',
      'not null' => FALSE,
      'length' => '1024',
    ),
    'thumbnail__width' => array(
      'type' => 'int',
      'not null' => FALSE,
      'size' => 'normal',
      'unsigned' => TRUE,
    ),
    'thumbnail__height' => array(
      'type' => 'int',
      'not null' => FALSE,
      'size' => 'normal',
      'unsigned' => TRUE,
    ),
    'uid' => array(
      'type' => 'int',
      'not null' => FALSE,
      'size' => 'normal',
      'unsigned' => TRUE,
    ),
    'created' => array(
      'type' => 'int',
      'not null' => FALSE,
      'size' => 'normal',
    ),
    'changed' => array(
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
    'vid',
    'langcode',
  ),
  'indexes' => array(
    'media__id__default_langcode__langcode' => array(
      'mid',
      'default_langcode',
      'langcode',
    ),
    'media_field__thumbnail__target_id' => array(
      'thumbnail__target_id',
    ),
    'media_field__uid__target_id' => array(
      'uid',
    ),
  ),
  'mysql_character_set' => 'utf8mb4',
));

$connection->schema()->createTable('media_revision', array(
  'fields' => array(
    'mid' => array(
      'type' => 'int',
      'not null' => TRUE,
      'size' => 'normal',
      'unsigned' => TRUE,
    ),
    'vid' => array(
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
    'revision_user' => array(
      'type' => 'int',
      'not null' => FALSE,
      'size' => 'normal',
      'unsigned' => TRUE,
    ),
    'revision_created' => array(
      'type' => 'int',
      'not null' => FALSE,
      'size' => 'normal',
    ),
    'revision_log_message' => array(
      'type' => 'text',
      'not null' => FALSE,
      'size' => 'big',
    ),
  ),
  'primary key' => array(
    'vid',
  ),
  'indexes' => array(
    'media__mid' => array(
      'mid',
    ),
    'media_field__revision_user__target_id' => array(
      'revision_user',
    ),
  ),
  'mysql_character_set' => 'utf8mb4',
));

$connection->schema()->createTable('media_revision__field_media_file', array(
  'fields' => array(
    'bundle' => array(
      'type' => 'varchar_ascii',
      'not null' => TRUE,
      'length' => '128',
      'default' => '',
    ),
    'deleted' => array(
      'type' => 'int',
      'not null' => TRUE,
      'size' => 'tiny',
      'default' => '0',
    ),
    'entity_id' => array(
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
      'length' => '32',
      'default' => '',
    ),
    'delta' => array(
      'type' => 'int',
      'not null' => TRUE,
      'size' => 'normal',
      'unsigned' => TRUE,
    ),
    'field_media_file_target_id' => array(
      'type' => 'int',
      'not null' => TRUE,
      'size' => 'normal',
      'unsigned' => TRUE,
    ),
    'field_media_file_display' => array(
      'type' => 'int',
      'not null' => FALSE,
      'size' => 'tiny',
      'default' => '1',
      'unsigned' => TRUE,
    ),
    'field_media_file_description' => array(
      'type' => 'text',
      'not null' => FALSE,
      'size' => 'normal',
    ),
  ),
  'primary key' => array(
    'entity_id',
    'revision_id',
    'deleted',
    'delta',
    'langcode',
  ),
  'indexes' => array(
    'bundle' => array(
      'bundle',
    ),
    'revision_id' => array(
      'revision_id',
    ),
    'field_media_file_target_id' => array(
      'field_media_file_target_id',
    ),
  ),
  'mysql_character_set' => 'utf8mb4',
));

$connection->schema()->createTable('media_revision__field_media_image', array(
  'fields' => array(
    'bundle' => array(
      'type' => 'varchar_ascii',
      'not null' => TRUE,
      'length' => '128',
      'default' => '',
    ),
    'deleted' => array(
      'type' => 'int',
      'not null' => TRUE,
      'size' => 'tiny',
      'default' => '0',
    ),
    'entity_id' => array(
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
      'length' => '32',
      'default' => '',
    ),
    'delta' => array(
      'type' => 'int',
      'not null' => TRUE,
      'size' => 'normal',
      'unsigned' => TRUE,
    ),
    'field_media_image_target_id' => array(
      'type' => 'int',
      'not null' => TRUE,
      'size' => 'normal',
      'unsigned' => TRUE,
    ),
    'field_media_image_alt' => array(
      'type' => 'varchar',
      'not null' => FALSE,
      'length' => '512',
    ),
    'field_media_image_title' => array(
      'type' => 'varchar',
      'not null' => FALSE,
      'length' => '1024',
    ),
    'field_media_image_width' => array(
      'type' => 'int',
      'not null' => FALSE,
      'size' => 'normal',
      'unsigned' => TRUE,
    ),
    'field_media_image_height' => array(
      'type' => 'int',
      'not null' => FALSE,
      'size' => 'normal',
      'unsigned' => TRUE,
    ),
  ),
  'primary key' => array(
    'entity_id',
    'revision_id',
    'deleted',
    'delta',
    'langcode',
  ),
  'indexes' => array(
    'bundle' => array(
      'bundle',
    ),
    'revision_id' => array(
      'revision_id',
    ),
    'field_media_image_target_id' => array(
      'field_media_image_target_id',
    ),
  ),
  'mysql_character_set' => 'utf8mb4',
));
