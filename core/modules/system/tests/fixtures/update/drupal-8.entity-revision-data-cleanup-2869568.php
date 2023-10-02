<?php

/**
 * @file
 * Provides database changes for testing upgrade path of system_update_8404().
 *
 * @see \Drupal\Tests\system\Functional\Update\SqlContentEntityStorageRevisionDataCleanupTest
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

// Add 100 more rows to test the loop in system_update_10201.
for ($i = 1; $i <= 100; $i++) {
  // Ensure that the new nodes use a vid that is greater than the maximum vid
  // in drupal-9.4.0.filled.standard.php.gz for node id 8.
  $vid = 10 + $i;
  $connection->insert('node_field_revision')
    ->fields([
      'nid' => 8,
      'vid' => $vid,
      'langcode' => 'en',
      'title' => 'Deleted revision',
      'uid' => 1,
      'status' => 1,
      'created' => 1439732773,
      'changed' => 1439733036,
      'promote' => 1,
      'sticky' => 0,
      'revision_translation_affected' => 1,
      'default_langcode' => 1,
      'content_translation_source' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
      'content_translation_outdated' => 0,
    ])
    ->execute();
}
