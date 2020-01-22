<?php

/**
 * @file
 * Contains database additions to drupal-8.filled.standard.php.gz for testing
 * the upgrade path of https://www.drupal.org/project/drupal/issues/2899923.
 */

use Drupal\Core\Database\Database;
use Drupal\Core\Serialization\Yaml;

$connection = Database::getConnection();

$taxonomy_form_display_file = __DIR__ . '/core.entity_form_display.taxonomy_term.tags.default.yml';
$taxonomy_form_display_config = Yaml::decode(file_get_contents($taxonomy_form_display_file));

$connection->insert('config')
  ->fields(['collection', 'name', 'data'])
  ->values([
    'collection' => '',
    'name' => 'core.entity_form_display.taxonomy_term.tags.default',
    'data' => serialize($taxonomy_form_display_config),
  ])
  ->execute();
