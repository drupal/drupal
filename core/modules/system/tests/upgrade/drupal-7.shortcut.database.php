<?php

/**
 * @file
 * Database additions for shortcut tests. Used in ShortcutUpgradePathTest.
 *
 * This dump only contains data and schema components relevant for shortcut
 * functionality. The drupal-7.bare.standard_all.database.php file is imported
 * before this dump, so the two form the database structure expected in tests
 * altogether.
 */

// Add custom shortcut set.
db_insert('shortcut_set')
  ->fields(array(
    'set_name',
    'title',
  ))
  ->values(array(
    'set_name' => 'shortcut-set-2',
    'title' => 'Custom shortcut set',
  ))
  ->execute();
