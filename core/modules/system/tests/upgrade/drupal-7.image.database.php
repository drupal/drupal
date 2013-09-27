<?php

/**
 * @file
 * Database additions for Drupal\system\Tests\Upgrade\ImageUpgradePathTest.
 *
 * This dump only contains data and schema components relevant for image
 * functionality. The drupal-7.filled.database.php file is imported before
 * this dump, so the two form the database structure expected in tests
 * altogether.
 */

// Add image styles.
db_insert('image_styles')->fields(array(
  'isid',
  'name',
  'label'
))
// Override thumbnail style.
->values(array(
  'isid' => '1',
  'name' => 'thumbnail',
  'label' => 'Thumbnail (100x100)',
))
// Custom style.
->values(array(
  'isid' => '2',
  'name' => 'test-custom',
  'label' => 'Test custom',
))
->execute();

// Add image effects.
db_insert('image_effects')->fields(array(
  'ieid',
  'isid',
  'weight',
  'name',
  'data',
))
->values(array(
  'ieid' => '1',
  'isid' => '1',
  'weight' => '0',
  'name' => 'image_scale',
  'data' => 'a:3:{s:5:"width";s:3:"177";s:6:"height";s:3:"177";s:7:"upscale";i:0;}',
))
->values(array(
  'ieid' => '3',
  'isid' => '2',
  'weight' => '1',
  'name' => 'image_rotate',
  'data' => 'a:3:{s:7:"degrees";s:2:"90";s:7:"bgcolor";s:7:"#FFFFFF";s:6:"random";i:1;}',
))
->values(array(
  'ieid' => '4',
  'isid' => '2',
  'weight' => '2',
  'name' => 'image_desaturate',
  'data' => 'a:0:{}',
))
->execute();
