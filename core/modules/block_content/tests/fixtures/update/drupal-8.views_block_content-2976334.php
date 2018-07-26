<?php

/**
 * @file
 * Contains database additions to drupal-8.bare.standard.php.gz for testing the
 * upgrade path of https://www.drupal.org/project/drupal/issues/2976334.
 */

use Drupal\Core\Database\Database;
use Drupal\Core\Serialization\Yaml;

$connection = Database::getConnection();

// Override configuration for 'block_content' View with extra display with with
// overridden filters.
$config = Yaml::decode(file_get_contents(__DIR__ . '/views.view.block_content_2976334.yml'));
$connection->update('config')
  ->fields([
    'data' => serialize($config),
  ])
  ->condition('name', 'views.view.' . $config['id'])
  ->execute();
