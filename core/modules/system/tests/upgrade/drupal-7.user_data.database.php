<?php

/**
 * @file
 * Database additions for role tests. Used in
 * \Drupal\system\Tests\Upgrade\FilledStandardUpgradePathTest.
 *
 * This dump only contains data and schema components relevant for user data
 * upgrade tests. The drupal-7.filed.database.php.gz file is imported before
 * this dump, so the two form the database structure expected in tests
 * altogether.
 */

db_update('users')
  ->condition('uid', 1)
  ->fields(array(
    'data' => serialize(array(
      'contact' => 1,
      'garbage' => 'data',
    )),
  ))
  ->execute();

db_update('users')
  ->condition('uid', 2)
  ->fields(array(
    'data' => serialize(array(
      'contact' => '0',
      'more' => array('garbage', 'data'),
    )),
  ))
  ->execute();
