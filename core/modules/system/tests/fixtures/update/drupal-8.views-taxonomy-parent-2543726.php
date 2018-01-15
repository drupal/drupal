<?php

/**
 * @file
 * Contains database additions to drupal-8.bare.standard.php.gz for testing the
 * upgrade path of https://www.drupal.org/node/2455125.
 */

use Drupal\Component\Uuid\Php;
use Drupal\Core\Database\Database;
use Drupal\Core\Serialization\Yaml;

$connection = Database::getConnection();

$view_file = __DIR__ . '/drupal-8.views-taxonomy-parent-2543726.yml';
$view_config = Yaml::decode(file_get_contents($view_file));

$connection->insert('config')
  ->fields(['collection', 'name', 'data'])
  ->values([
    'collection' => '',
    'name' => "views.view.test_taxonomy_parent",
    'data' => serialize($view_config),
  ])
  ->execute();

$uuid = new Php();

// The root tid.
$tids = [0];

for ($i = 0; $i < 4; $i++) {
  $name = $this->randomString();

  $tid = $connection->insert('taxonomy_term_data')
    ->fields(['vid', 'uuid', 'langcode'])
    ->values(['vid' => 'tags', 'uuid' => $uuid->generate(), 'langcode' => 'en'])
    ->execute();

  $connection->insert('taxonomy_term_field_data')
    ->fields(['tid', 'vid', 'langcode', 'name', 'weight', 'changed', 'default_langcode'])
    ->values(['tid' => $tid, 'vid' => 'tags', 'langcode' => 'en', 'name' => $name, 'weight' => 0, 'changed' => REQUEST_TIME, 'default_langcode' => 1])
    ->execute();

  $tids[] = $tid;
}

$hierarchy = [
  // Term with tid 1 has terms with tids 2 and 3 as parents.
  1 => [2, 3],
  2 => [3, 0],
  3 => [0],
];

$query = $connection->insert('taxonomy_term_hierarchy')->fields(['tid', 'parent']);

foreach ($hierarchy as $tid => $parents) {
  foreach ($parents as $parent) {
    $query->values(['tid' => $tids[$tid], 'parent' => $tids[$parent]]);
  }
}

$query->execute();
