<?php

/**
 * @file
 * Database additions for Field UI tests tests. Used in
 * \Drupal\system\Tests\Upgrade\FieldUIUpgradePathTest.
 *
 * This dump only contains data and schema components relevant for Field UI
 * upgrade tests. The drupal-7.filled.database.php.gz file is imported before
 * this dump, so the two form the database structure expected in tests
 * altogether.
 */

db_insert('role')->fields(array(
  'rid',
  'name',
  'weight',
))
->values(array(
  'rid' => '4',
  'name' => 'Normal role',
  'weight' => '3',
))
->values(array(
  'rid' => '5',
  'name' => 'Admin role',
  'weight' => '4',
))
->execute();

// Add the 'Administer comments', 'Administer content types',
// 'Administer users' and 'Administer vocabularies and terms' permissions
// to the Admin role.
db_insert('role_permission')->fields(array(
  'rid',
  'permission',
  'module',
))
->values(array(
  'rid' => '5',
  'permission' => 'administer comments',
  'module' => 'comment',
))
->values(array(
  'rid' => '5',
  'permission' => 'administer content types',
  'module' => 'node',
))
->values(array(
  'rid' => '5',
  'permission' => 'administer users',
  'module' => 'user',
))
->values(array(
  'rid' => '5',
  'permission' => 'administer taxonomy',
  'module' => 'taxonomy',
))
->execute();
