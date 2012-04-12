<?php

/**
 * @file
 * Test content for the field update path.
 */

db_insert('variable')->fields(array(
  'name',
  'value',
))
->values(array(
  'name' => 'field_bundle_settings',
  'value' => 'a:1:{s:4:"node";a:1:{s:4:"poll";a:1:{s:12:"extra_fields";a:1:{s:7:"display";a:2:{s:16:"poll_view_voting";a:1:{s:7:"default";a:2:{s:6:"weight";s:1:"0";s:7:"visible";b:1;}}s:17:"poll_view_results";a:1:{s:7:"default";a:2:{s:6:"weight";s:1:"0";s:7:"visible";b:0;}}}}}}}',
))
->execute();
