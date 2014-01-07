<?php

/**
 * @file
 * Database additions for user picture tests. Used in UserPictureUpgradePathTest.
 *
 * This dump only contains data and schema components relevant for user picture
 * functionality. The drupal-7.bare.database.php file is imported before
 * this dump, so the two form the database structure expected in tests
 * altogether.
 */

// Add an image and assign it to uid 1.
$fid = db_insert('file_managed')
  ->fields(array(
    'uri' => 'public://user_pictures_dir/faked_image.png',
    'uid' => 1,
    'status' => 1,
    'filename' => 'faked_image.png',
    'filesize' => 1000,
    'filemime' => 'image/png',
    'created' => 1353542634,
    'changed' => 1353542634,
  ))
  ->execute();

db_insert('file_usage')
  ->fields(array(
    'fid' => $fid,
    'module' => 'user',
    'type' => 'user',
    'id' => 1,
    'count' => 1,
  ))
  ->execute();

db_update('users')
  ->condition('uid', 1)
  ->fields(array(
    'picture' => $fid,
  ))
  ->execute();

// Set up variables needed for user picture support.
$deleted_variables = array(
  'user_pictures',
  'user_picture_default',
  'user_picture_dimensions',
  'user_picture_file_size',
  'user_picture_guidelines',
  'user_picture_path',
  'user_picture_style',
);
db_delete('variable')
  ->condition('name', $deleted_variables, 'IN')
  ->execute();

db_insert('variable')->fields(array(
  'name',
  'value',
))
->values(array(
  'name' => 'user_pictures',
  'value' => 'i:1;',
))
->values(array(
  'name' => 'user_picture_default',
  'value' => 's:51:"sites/default/files/user_pictures_dir/druplicon.png";',
))
->values(array(
  'name' => 'user_picture_dimensions',
  'value' => 's:7:"800x800";',
))
->values(array(
  'name' => 'user_picture_file_size',
  'value' => 's:3:"700";',
))
->values(array(
  'name' => 'user_picture_guidelines',
  'value' => 's:34:"These are user picture guidelines.";',
))
->values(array(
  'name' => 'user_picture_path',
  'value' => 's:17:"user_pictures_dir";',
))
->values(array(
  'name' => 'user_picture_style',
  'value' => 's:9:"thumbnail";',
))
->execute();
