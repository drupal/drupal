<?php

/**
 * @file
 * Database additions for role tests. Used in upgrade.roles.test.
 *
 * This dump only contains data and schema components relevant for role
 * functionality. The drupal-7.bare.database.php file is imported before
 * this dump, so the two form the database structure expected in tests
 * altogether.
 */

db_insert('role')->fields(array(
  'rid',
  'name',
  'weight',
))
// Adds a role with an umlaut in it.
->values(array(
  'rid' => '4',
  'name' => 'gärtner',
  'weight' => '3',
))
// Adds a very long role name.
->values(array(
  'rid' => '5',
  'name' => 'very long role name that has exactly sixty-four characters in it',
  'weight' => '4',
))
// Adds a very similar role name to test edge cases.
->values(array(
  'rid' => '6',
  'name' => 'very_long role name that has exactly sixty-four characters in it',
  'weight' => '5',
))
->execute();

// Add the "Edit own comments" permission to the gärtner test role.
db_insert('role_permission')->fields(array(
  'rid',
  'permission',
  'module',
))
->values(array(
  'rid' => '4',
  'permission' => 'edit own comments',
  'module' => 'comment',
))
->execute();

// Adds some role visibility settings on the "Powered by" block for the long
// role.
db_insert('block_role')->fields(array(
  'module',
  'delta',
  'rid',
))
->values(array(
  'module' => 'system',
  'delta' => 'powered',
  'rid' => '5',
))
->execute();
