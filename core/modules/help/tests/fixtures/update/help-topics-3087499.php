<?php

/**
 * @file
 * Contains database additions for testing the upgrade path for help topics.
 *
 * @see https://www.drupal.org/node/3087499
 */

use Drupal\Core\Database\Database;
use Drupal\Core\Serialization\Yaml;

$connection = Database::getConnection();

// Enable experimental help_topics module.
$extensions = $connection->select('config')
  ->fields('config', ['data'])
  ->condition('collection', '')
  ->condition('name', 'core.extension')
  ->execute()
  ->fetchField();
$extensions = unserialize($extensions);
$extensions['module']['help_topics'] = 0;
$connection->update('config')
  ->fields([
    'data' => serialize($extensions),
  ])
  ->condition('collection', '')
  ->condition('name', 'core.extension')
  ->execute();

// Structure of configured block and search page.
$search_page = Yaml::decode(file_get_contents(__DIR__ . '/search.page.help_search.yml'));
$connection->insert('config')
  ->fields([
    'collection',
    'name',
    'data',
  ])
  ->values([
    'collection' => '',
    'name' => 'search.page.help_search',
    'data' => serialize($search_page),
  ])
  ->execute();
