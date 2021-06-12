<?php

/**
 * @file
 * Adds deprecated tour config for testing updates.
 *
 * @see https://www.drupal.org/node/3022401
 */

use Drupal\Core\Database\Database;
use Drupal\Core\Serialization\Yaml;

$connection = Database::getConnection();

$tour_with_location = Yaml::decode(file_get_contents(__DIR__ . '/legacy_config/tour.tour.tour-test-legacy-location.yml'));
$connection->insert('config')
  ->fields([
    'collection',
    'name',
    'data',
  ])
  ->values([
    'collection' => '',
    'name' => 'tour.tour.tour-test-legacy-location',
    'data' => serialize($tour_with_location),
  ])
  ->execute();
