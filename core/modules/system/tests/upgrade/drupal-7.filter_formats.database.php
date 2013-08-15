<?php

/**
 * @file
 * Database additions filter format tests. Used in upgrade.filter_formats.test.
 *
 * This dump only contains data and schema components relevant for role
 * functionality. The drupal-7.bare.database.php file is imported before
 * this dump, so the two form the database structure expected in tests
 * altogether.
 */

db_insert('filter_format')->fields(array(
  'format',
  'name',
  'cache',
  'status',
  'weight',
))
// Adds some filters formats
->values(array(
  'format' => 'format_one',
  'name' => 'Format One',
  'cache' => '1',
  'weight' => '1',
  'status' => '1'
))
->values(array(
  'format' => 'format_two',
  'name' => 'Format Two',
  'cache' => '1',
  'weight' => '2',
  'status' => '1'
))
// Add a disabled filter format
->values(array(
  'format' => 'format_three',
  'name' => 'Format Three',
  'cache' => '1',
  'weight' => '3',
  'status' => '0'
))
->execute();

// Adds filters to the crated filter formats
db_insert('filter')->fields(array(
  'format',
  'module',
  'name',
  'weight',
  'status',
  'settings',
))
// Filters for: Format One
->values(array(
  'format' => 'format_one',
  'module' => 'filter',
  'name' => 'filter_autop',
  'weight' => '2',
  'status' => '1',
  'settings' => 'a:0:{}',
))
->values(array(
  'format' => 'format_one',
  'module' => 'filter',
  'name' => 'filter_html',
  'weight' => '-10',
  'status' => '0',
  'settings' => 'a:3:{s:12:"allowed_html";s:74:"<a> <em> <strong> <cite> <blockquote> <code> <ul> <ol> <li> <dl> <dt> <dd>";s:16:"filter_html_help";i:1;s:20:"filter_html_nofollow";i:0;}',
))
->values(array(
  'format' => 'format_one',
  'module' => 'filter',
  'name' => 'filter_htmlcorrector',
  'weight' => '10',
  'status' => '0',
  'settings' => 'a:0:{}',
))
->values(array(
  'format' => 'format_one',
  'module' => 'filter',
  'name' => 'filter_html_escape',
  'weight' => '0',
  'status' => '1',
  'settings' => 'a:0:{}',
))
->values(array(
  'format' => 'format_two',
  'module' => 'filter',
  'name' => 'filter_url',
  'weight' => '1',
  'status' => '1',
  'settings' => 'a:1:{s:17:"filter_url_length";i:72;}',
))
->values(array(
  'format' => 'format_two',
  'module' => 'filter',
  'name' => 'filter_autop',
  'weight' => '0',
  'status' => '0',
  'settings' => 'a:0:{}',
))
->values(array(
  'format' => 'format_three',
  'module' => 'filter',
  'name' => 'filter_html',
  'weight' => '-10',
  'status' => '1',
  'settings' => 'a:3:{s:12:"allowed_html";s:9:"<a> <em> ";s:16:"filter_html_help";i:1;s:20:"filter_html_nofollow";i:0;}',
))
->values(array(
  'format' => 'format_three',
  'module' => 'filter',
  'name' => 'filter_htmlcorrector',
  'weight' => '10',
  'status' => '0',
  'settings' => 'a:0:{}',
))
->values(array(
  'format' => 'format_three',
  'module' => 'filter',
  'name' => 'filter_html_escape',
  'weight' => '-10',
  'status' => '1',
  'settings' => 'a:0:{}',
))
->values(array(
  'format' => 'format_three',
  'module' => 'filter',
  'name' => 'filter_url',
  'weight' => '0',
  'status' => '1',
  'settings' => 'a:1:{s:17:"filter_url_length";s:2:"72";}',
))
->values(array(
  'format' => 'format_two',
  'module' => 'missing_module',
  'name' => 'missing_filter',
  'weight' => '0',
  'status' => '1',
  'settings' => 'a:0:{}',
))
->execute();

// Define which roles can use the text formats.
db_insert('role_permission')->fields(array(
  'rid',
  'permission',
  'module',
))
// Adds some filters formats
->values(array(
  'rid' => 1,
  'permission' => 'use text format format_one',
  'module' => 'filter',
))
->values(array(
  'rid' => 4,
  'permission' => 'use text format format_one',
  'module' => 'filter',
))
->values(array(
  'rid' => 2,
  'permission' => 'use text format format_two',
  'module' => 'filter',
))
->values(array(
  'rid' => 4,
  'permission' => 'use text format format_three',
  'module' => 'filter',
))
->execute();

db_insert('variable')->fields(array(
  'name',
  'value',
))
->values(array(
  'name' => 'format_fallback_format',
  'value' => 's:10:"plain_text";',
))
->execute();
