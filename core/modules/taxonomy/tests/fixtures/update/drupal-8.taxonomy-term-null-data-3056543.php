<?php

/**
 * @file
 * Contains database additions to drupal-8.filled.standard.php.gz for testing
 * the upgrade path of https://www.drupal.org/project/drupal/issues/3056543.
 */

use Drupal\Core\Database\Database;

$connection = Database::getConnection();

$connection->insert('taxonomy_term_data')
  ->fields([
    'tid' => 997,
    'vid' => 'tags',
    'uuid' => 'ea32f399-a53b-416c-81a9-e66204236c97',
    'langcode' => 'en',
  ])
  ->execute();
$connection->insert('taxonomy_term_field_data')
  ->fields([
    'tid' => 997,
    'vid' => 'tags',
    'langcode' => 'en',
    'default_langcode' => 0,
    'name' => 'tag997',
    'weight' => 0,
    'changed' => 1579555997,
    'content_translation_status' => NULL,
  ])
  ->execute();
$connection->insert('taxonomy_term_hierarchy')
  ->fields([
    'tid' => 997,
    'parent' => 0,
  ])
  ->execute();

$connection->insert('taxonomy_term_data')
  ->fields([
    'tid' => 998,
    'vid' => 'tags',
    'uuid' => 'ea32f399-a53b-416c-81a9-e66204236c98',
    'langcode' => 'en',
  ])
  ->execute();
$connection->insert('taxonomy_term_field_data')
  ->fields([
    'tid' => 998,
    'vid' => 'tags',
    'langcode' => 'en',
    'default_langcode' => 0,
    'name' => 'tag998',
    'weight' => 0,
    'changed' => 1579555998,
    'content_translation_status' => NULL,
  ])
  ->execute();
$connection->insert('taxonomy_term_hierarchy')
  ->fields([
    'tid' => 998,
    'parent' => 0,
  ])
  ->execute();

$connection->insert('taxonomy_term_data')
  ->fields([
    'tid' => 999,
    'vid' => 'tags',
    'uuid' => 'ea32f399-a53b-416c-81a9-e66204236c99',
    'langcode' => 'en',
  ])
  ->execute();
$connection->insert('taxonomy_term_field_data')
  ->fields([
    'tid' => 999,
    'vid' => 'tags',
    'langcode' => 'en',
    'default_langcode' => 0,
    'name' => 'tag999-en',
    'weight' => 0,
    'changed' => 1579555999,
    'content_translation_status' => NULL,
  ])
  ->execute();
$connection->insert('taxonomy_term_field_data')
  ->fields([
    'tid' => 999,
    'vid' => 'tags',
    'langcode' => 'es',
    'default_langcode' => 1,
    'name' => 'tag999-es',
    'weight' => 0,
    'changed' => 1579555999,
    'content_translation_status' => NULL,
  ])
  ->execute();
$connection->insert('taxonomy_term_hierarchy')
  ->fields([
    'tid' => 999,
    'parent' => 0,
  ])
  ->execute();
