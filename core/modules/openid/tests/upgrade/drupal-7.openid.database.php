<?php

/**
 * @file
 * Database additions for openid tests.
 *
 * This dump enables openid module, the drupal-7.bare.minimal.database.php.gz
 * file is imported before this dump, so the two form the database structure
 * expected in tests altogether.
 */

db_create_table('openid_association', array(
  'fields' => array(
    'idp_endpoint_uri' => array(
      'type' => 'varchar',
      'length' => 255,
    ),
    'assoc_handle' => array(
      'type' => 'varchar',
      'length' => 255,
      'not null' => TRUE,
    ),
    'assoc_type' => array(
      'type' => 'varchar',
      'length' => 32,
    ),
    'session_type' => array(
      'type' => 'varchar',
      'length' => 32,
    ),
    'mac_key' => array(
      'type' => 'varchar',
      'length' => 255,
    ),
    'created' => array(
      'type' => 'int',
      'not null' => TRUE,
      'default' => 0,
    ),
    'expires_in' => array(
      'type' => 'int',
      'not null' => TRUE,
      'default' => 0,
    ),
  ),
  'primary key' => array(
    'assoc_handle',
  ),
  'module' => 'openid',
  'name' => 'openid_association',
));

db_create_table('openid_nonce', array(
  'fields' => array(
    'idp_endpoint_uri' => array(
      'type' => 'varchar',
      'length' => 255,
    ),
    'nonce' => array(
      'type' => 'varchar',
      'length' => 255,
    ),
    'expires' => array(
      'type' => 'int',
      'not null' => TRUE,
      'default' => 0,
    ),
  ),
  'indexes' => array(
    'nonce' => array(
      'nonce',
    ),
    'expires' => array(
      'expires',
    ),
  ),
  'module' => 'openid',
  'name' => 'openid_nonce',
));

db_update('system')
  ->fields(array(
    'filename' => 'modules/openid/openid.module',
    'name' => 'openid',
    'type' => 'module',
    'owner' => '',
    'status' => '1',
    'bootstrap' => '0',
    'schema_version' => '6000',
    'weight' => '0',
    'info' => 'a:11:{s:4:"name";s:6:"OpenID";s:11:"description";s:48:"Allows users to log into your site using OpenID.";s:7:"version";s:11:"7.14+29-dev";s:7:"package";s:4:"Core";s:4:"core";s:3:"7.x";s:5:"files";a:1:{i:0;s:11:"openid.test";}s:7:"project";s:6:"drupal";s:9:"datestamp";s:10:"1338768537";s:12:"dependencies";a:0:{}s:3:"php";s:5:"5.2.4";s:9:"bootstrap";i:0;}',
  ))
  ->condition('filename', 'modules/openid/openid.module')
  ->execute();
