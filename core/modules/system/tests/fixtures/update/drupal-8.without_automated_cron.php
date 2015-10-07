<?php

$connection = Drupal\Core\Database\Database::getConnection();
$config = $connection;

$connection->merge('config')
  ->condition('name', 'system.cron')
  ->condition('collection', '')
  ->fields([
    'name' => 'system.cron',
    'collection' => '',
    'data' => serialize(['threshold' => ['autorun' => 0]]),
  ])
  ->execute();
