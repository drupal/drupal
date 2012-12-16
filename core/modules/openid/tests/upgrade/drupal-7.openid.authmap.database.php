<?php

/**
 * @file
 * Database additions for authmap tests. Used in
 * \Drupal\openid\Tests\Upgrade\OpenIDAuthmapUpgradePathTest.
 *
 * This dump only contains data and schema components relevant for user authmap
 * upgrade tests.
 */

db_insert('authmap')->fields(array(
    'aid',
    'uid',
    'authname',
    'module',
  ))
  ->values(array(
    'aid' => 1,
    'uid' => 1,
    'authname' => 'userA@providerA',
    'module' => 'openid',
  ))
  ->values(array(
    'aid' => 2,
    'uid' => 1,
    'authname' => 'userB@providerA',
    'module' => 'openid',
  ))
  ->values(array(
    'aid' => 3,
    'uid' => 1,
    'authname' => 'userA@providerB',
    'module' => 'fancy',
  ))
  ->execute();
