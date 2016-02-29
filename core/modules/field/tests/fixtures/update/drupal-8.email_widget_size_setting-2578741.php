<?php

/**
 * @file
 * Contains SQL necessary to add a new component for an email field/widget to
 * the 'node.article.default' entity form display.
 */

use Drupal\Core\Database\Database;

$connection = Database::getConnection();

$config = $connection->select('config', 'c')
  ->fields('c')
  ->condition('collection', '')
  ->condition('name', 'core.entity_form_display.node.article.default')
  ->execute()
  ->fetchAssoc();

$data = unserialize($config['data']);

// Manually add a new component that simulates an email field using the default
// email widget.
$data['content']['field_email_2578741'] = [
  'weight' => 20,
  'settings' => [
    'placeholder' => '',
  ],
  'third_party_settings' => [],
  'type' => 'email_default',
];

$connection->update('config')
  ->fields(['data' => serialize($data)])
  ->condition('collection', '')
  ->condition('name', 'core.entity_form_display.node.article.default')
  ->execute();
