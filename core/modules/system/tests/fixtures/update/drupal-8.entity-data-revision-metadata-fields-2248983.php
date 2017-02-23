<?php

/**
 * @file
 * Contains database additions to
 * drupal-8.2.1.bare.standard_with_entity_test_enabled.php.gz for testing the
 * upgrade path of https://www.drupal.org/node/2248983.
 */

use Drupal\Core\Database\Database;

$connection = Database::getConnection();

// Data for entity type "entity_test_revlog"
$connection->insert('entity_test_revlog')
  ->fields([
    'id',
    'revision_id',
    'type',
    'uuid',
    'langcode',
    'revision_created',
    'revision_user',
    'revision_log_message',
    'name',
  ])
  ->values([
    'id' => '1',
    'revision_id' => '2',
    'type' => 'entity_test_revlog',
    'uuid' => 'f0b962b1-391b-441b-a664-2468ad520d96',
    'langcode' => 'en',
    'revision_created' => '1476268518',
    'revision_user' => '1',
    'revision_log_message' => 'second revision',
    'name' => 'entity 1',
  ])
  ->execute();

$connection->insert('entity_test_revlog_revision')
  ->fields([
    'id',
    'revision_id',
    'langcode',
    'revision_created',
    'revision_user',
    'revision_log_message',
    'name',
  ])
  ->values([
    'id' => '1',
    'revision_id' => '1',
    'langcode' => 'en',
    'revision_created' => '1476268517',
    'revision_user' => '1',
    'revision_log_message' => 'first revision',
    'name' => 'entity 1',
  ])
  ->values([
    'id' => '1',
    'revision_id' => '2',
    'langcode' => 'en',
    'revision_created' => '1476268518',
    'revision_user' => '1',
    'revision_log_message' => 'second revision',
    'name' => 'entity 1',
  ])
  ->execute();

// Data for entity type "entity_test_mul_revlog"
$connection->insert('entity_test_mul_revlog')
  ->fields([
    'id',
    'revision_id',
    'type',
    'uuid',
    'langcode',
  ])
  ->values([
    'id' => '1',
    'revision_id' => '2',
    'type' => 'entity_test_mul_revlog',
    'uuid' => '6f04027a-1cbd-46e3-a67e-72636b493d4f',
    'langcode' => 'en',
  ])
  ->execute();

$connection->insert('entity_test_mul_revlog_field_data')
  ->fields([
    'id',
    'revision_id',
    'type',
    'langcode',
    'revision_created',
    'revision_user',
    'revision_log_message',
    'name',
    'default_langcode',
  ])
  ->values([
    'id' => '1',
    'revision_id' => '2',
    'type' => 'entity_test_mul_revlog',
    'langcode' => 'en',
    'revision_created' => '1476268518',
    'revision_user' => '1',
    'revision_log_message' => 'second revision',
    'name' => 'entity 1',
    'default_langcode' => '1',
  ])
  ->execute();

$connection->insert('entity_test_mul_revlog_field_revision')
  ->fields([
    'id',
    'revision_id',
    'langcode',
    'revision_created',
    'revision_user',
    'revision_log_message',
    'name',
    'default_langcode',
  ])
  ->values([
    'id' => '1',
    'revision_id' => '1',
    'langcode' => 'en',
    'revision_created' => '1476268517',
    'revision_user' => '1',
    'revision_log_message' => 'first revision',
    'name' => 'entity 1',
    'default_langcode' => '1',
  ])
  ->values([
    'id' => '1',
    'revision_id' => '2',
    'langcode' => 'en',
    'revision_created' => '1476268518',
    'revision_user' => '1',
    'revision_log_message' => 'second revision',
    'name' => 'entity 1',
    'default_langcode' => '1',
  ])
  ->execute();

$connection->insert('entity_test_mul_revlog_revision')
  ->fields([
    'id',
    'revision_id',
    'langcode',
  ])
  ->values([
    'id' => '1',
    'revision_id' => '1',
    'langcode' => 'en',
  ])
  ->values([
    'id' => '1',
    'revision_id' => '2',
    'langcode' => 'en',
  ])
  ->execute();
