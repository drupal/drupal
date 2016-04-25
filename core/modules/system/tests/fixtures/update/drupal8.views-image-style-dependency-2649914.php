<?php

/**
 * @file
 * Contains database additions to drupal-8-rc1.bare.standard.php.gz for testing
 * the upgrade path of https://www.drupal.org/node/2649914.
 */

use Drupal\Component\Serialization\Yaml;
use Drupal\Core\Database\Database;

$connection = Database::getConnection();

$views_config = Yaml::decode(file_get_contents(__DIR__ . '/drupal8.views-image-style-dependency-2649914.yml'));

$connection->insert('config')
  ->fields(['collection', 'name', 'data'])
  ->values([
    'collection' => '',
    'name' => 'views.view.' . $views_config['id'],
    'data' => serialize($views_config),
  ])->execute();
