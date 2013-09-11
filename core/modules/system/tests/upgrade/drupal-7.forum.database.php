<?php

/**
 * @file
 * Database additions for forum upgrade tests. Used in ForumUpgradePathTest.
 *
 * This dump only contains data and schema components relevant for forum module
 * functionality. The drupal-7.bare.database.php file is imported before
 * this dump, so the two form the database structure expected in tests
 * altogether.
 */

// Create two terms.
$vocabulary = db_select('taxonomy_vocabulary', 'tv')
  ->fields('tv', array('vid'))
  ->condition('name', 'forums')
  ->execute()
  ->fetchField();

$container = db_insert('taxonomy_term_data')
  ->fields(array(
    'vid' => $vocabulary,
    'name' => 'Container',
    'description' => 'Container',
    'format' => 'full_html',
    'weight' => 0,
  ))
  ->execute();

$forum = db_insert('taxonomy_term_data')
  ->fields(array(
    'vid' => $vocabulary,
    'name' => 'Forum',
    'description' => 'Forum',
    'format' => 'full_html',
    'weight' => 0,
  ))
  ->execute();

db_delete('variable')
  ->condition('name', 'forum_containers')
  ->execute();

db_insert('variable')->fields(array(
  'name' => 'forum_containers',
  'value' => serialize(array($container)),
))
->execute();
