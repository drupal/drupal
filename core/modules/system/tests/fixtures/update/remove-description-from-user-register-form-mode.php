<?php

/**
 * @file
 * Empties the description of the user register form mode.
 */

use Drupal\Core\Database\Database;

$connection = Database::getConnection();

$data = $connection->select('config')
  ->condition('name', 'core.entity_form_mode.user.register')
  ->fields('config', ['data'])
  ->execute()
  ->fetchField();
$data = unserialize($data);
// Change description from null to new line to confirm that the update hook calls trim().
// @see system_post_update_convert_empty_string_entity_form_modes_to_null().
$data['description'] = "\n";
$connection->update('config')
  ->condition('name', 'core.entity_form_mode.user.register')
  ->fields([
    'data' => serialize($data),
  ])
  ->execute();
