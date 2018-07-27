<?php

/**
 * @file
 * Contains database additions to drupal-8.filled.standard.php.gz for testing
 * the upgrade path of https://www.drupal.org/project/drupal/issues/2981887.
 */

use Drupal\Core\Database\Database;
use Drupal\Core\Serialization\Yaml;

$connection = Database::getConnection();

$view_file = __DIR__ . '/views.view.test_taxonomy_term_view_with_content_translation_status.yml';
$view_with_cts_config = Yaml::decode(file_get_contents($view_file));

$view_file = __DIR__ . '/views.view.test_taxonomy_term_view_without_content_translation_status.yml';
$view_without_cts_config = Yaml::decode(file_get_contents($view_file));

$connection->insert('config')
  ->fields(['collection', 'name', 'data'])
  ->values([
    'collection' => '',
    'name' => 'views.view.test_taxonomy_term_view_with_content_translation_status',
    'data' => serialize($view_with_cts_config),
  ])
  ->values([
    'collection' => '',
    'name' => 'views.view.test_taxonomy_term_view_without_content_translation_status',
    'data' => serialize($view_without_cts_config),
  ])
  ->execute();
