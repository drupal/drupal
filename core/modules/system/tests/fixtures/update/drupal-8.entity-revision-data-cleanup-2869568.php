<?php

/**
 * @file
 * Contains database additions to drupal-8.filled.standard.php.gz for testing
 * the upgrade path of https://www.drupal.org/node/2869568.
 */

use Drupal\Core\Database\Database;
use Drupal\Core\Language\LanguageInterface;

$connection = Database::getConnection();

// Manually add a record to the node_revision
$connection->insert('node_field_revision')
  ->fields([
    'nid' => 8,
    'vid' => 8,
    'langcode' => 'en',
    'title' => 'Deleted revision',
    'uid' => 1,
    'status' => 1,
    'created' => 1439731773,
    'changed' => 1439732036,
    'promote' => 1,
    'sticky' => 0,
    'revision_translation_affected' => 1,
    'default_langcode' => 1,
    'content_translation_source' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
    'content_translation_outdated' => 0,
  ])
  ->execute();
