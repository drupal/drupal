<?php

/**
 * @file
 * Database additions for user permissions tests. Used in
 * \Drupal\system\Tests\Upgrade\UserPermissionUpgradePathTest.
 *
 * This dump only contains data and schema components relevant for user data
 * permission upgrade tests. The drupal-7.bare.standard_all.database.php.gz
 * file is imported before this dump, so the two form the database
 * structure expected in tests altogether.
 */

db_insert('users_roles')->fields(array(
  'uid',
  'rid',
))
->values(array(
  'uid' => '2',
  'rid' => '3',
))
->execute();

db_insert('users')->fields(array(
  'uid',
  'name',
  'pass',
  'mail',
  'theme',
  'signature',
  'signature_format',
  'created',
  'access',
  'login',
  'status',
  'timezone',
  'language',
  'picture',
  'init',
  'data',
))
->values(array(
  'uid' => '2',
  'name' => 'user1',
  'pass' => '$S$D9JgycE33DawX/9Iv2SfAjkQEi5alDZhxycfan6dDkUKf9lH0Nfo',
  'mail' => 'user1@example.com',
  'theme' => '',
  'signature' => '',
  'signature_format' => NULL,
  'created' => '1376147347',
  'access' => '0',
  'login' => '0',
  'status' => '1',
  'timezone' => 'Europe/Berlin',
  'language' => '',
  'picture' => '0',
  'init' => 'user1@example.com',
  'data' => NULL,
))
->execute();
