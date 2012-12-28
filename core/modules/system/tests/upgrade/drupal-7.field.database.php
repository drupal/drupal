<?php

/**
 * @file
 * Database additions for field variables. Used in FieldUpgradePathTest.
 *
 * The drupal-7.bare.database.php file is imported before this dump, so the
 * two form the database structure expected in tests altogether.
 */

// Add a 'bundle settings' variable for article nodes.
$value = array(
  'view_modes' => array(
    'teaser' => array(
      'custom_settings' => 1,
    ),
    'full' => array(
      'custom_settings' => 0,
    ),
    'rss' => array(
      'custom_settings' => 0,
    ),
    'search_index' => array(
      'custom_settings' => 0,
    ),
    'search_result' => array(
      'custom_settings' => 0,
    ),
  ),
  'extra_fields' => array(
    'form' => array(),
    'display' => array(
      'language' => array(
        'default' => array(
          'weight' => -1,
          'visible' => 1,
        ),
        'teaser' => array(
          'weight' => 0,
          'visible' => 0,
        ),
      ),
    ),
  ),
);
db_insert('variable')
  ->fields(array(
    'name' => 'field_bundle_settings_node__article',
    'value' => serialize($value),
  ))
  ->execute();
