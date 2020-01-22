<?php

/**
 * @file
 * Contains database additions to drupal-8.bare.standard.php.gz for testing the
 * upgrade path of https://www.drupal.org/node/2455125.
 */

use Drupal\Component\Uuid\Php;
use Drupal\Core\Database\Database;

$connection = Database::getConnection();

$uuid = new Php();

$tids = [];

for ($i = 0; $i < 60; $i++) {
  $name = $this->randomString();

  $tid = $connection->insert('taxonomy_term_data')
    ->fields(['vid', 'uuid', 'langcode'])
    ->values(['vid' => 'tags', 'uuid' => $uuid->generate(), 'langcode' => 'es'])
    ->execute();

  $connection->insert('taxonomy_term_field_data')
    ->fields(['tid', 'vid', 'langcode', 'name', 'weight', 'changed', 'default_langcode'])
    ->values(['tid' => $tid, 'vid' => 'tags', 'langcode' => 'en', 'name' => $name, 'weight' => 0, 'changed' => REQUEST_TIME, 'default_langcode' => 1])
    ->execute();

  $connection->insert('taxonomy_term_field_data')
    ->fields(['tid', 'vid', 'langcode', 'name', 'weight', 'changed', 'default_langcode'])
    ->values(['tid' => $tid, 'vid' => 'tags', 'langcode' => 'es', 'name' => $name . ' es', 'weight' => 0, 'changed' => REQUEST_TIME, 'default_langcode' => 0])
    ->execute();

  $tids[] = $tid;
}

$query = $connection->insert('taxonomy_term_hierarchy')->fields(['tid', 'parent']);

$previous_tid = 0;
foreach ($tids as $tid) {
  $query->values(['tid' => $tid, 'parent' => $previous_tid]);
  $previous_tid = $tid;
}

// Insert an extra record with no corresponding term.
// See https://www.drupal.org/project/drupal/issues/2997982
$query->values(['tid' => max($tids) + 1, 'parent' => 0]);

$query->execute();
