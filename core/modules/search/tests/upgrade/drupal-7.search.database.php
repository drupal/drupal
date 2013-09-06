<?php

/**
 * @file
 * Database additions for \Drupal\search\Tests\SearchUpgradePathTest.
 *
 * This dump only contains data and schema components relevant for search
 * functionality. The bare.standard_all.database.php file is imported before
 * this dump, so the two form the database structure expected in tests.
 */

// Set user as the only active and default search.
db_insert('variable')
  ->fields(array(
    'name',
    'value',
  ))
  ->values(array(
    'name' => 'search_active_modules',
    'value'=> 'a:1:{s:4:"user";s:4:"user";}',
  ))
  ->values(array(
    'name' => 'search_default_module',
    'value'=> 's:4:"user";',
  ))
  ->execute();
