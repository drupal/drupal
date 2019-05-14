<?php

/**
 * @file
 * Contains database additions to drupal-8-rc1.filled.standard.php.gz for the
 * upgrade path in https://www.drupal.org/project/drupal/issues/2885809.
 */

use Drupal\Core\Database\Database;

$connection = Database::getConnection();

$connection->insert('comment')
  ->fields([
    'cid',
    'comment_type',
    'uuid',
    'langcode',
  ])
  ->values([
    'cid' => '5',
    'comment_type' => 'comment',
    'uuid' => '2f0505ad-fdc7-49fc-9d39-571bfc3e0f88',
    'langcode' => 'en',
  ])
  ->values([
    'cid' => '6',
    'comment_type' => 'comment',
    'uuid' => '3be94e6b-4506-488a-a861-9742a18f0507',
    'langcode' => 'en',
  ])
  ->execute();

$connection->insert('comment__comment_body')
  ->fields([
    'bundle',
    'deleted',
    'entity_id',
    'revision_id',
    'langcode',
    'delta',
    'comment_body_value',
    'comment_body_format',
  ])
  ->values([
    'bundle' => 'comment',
    'deleted' => '0',
    'entity_id' => '5',
    'revision_id' => '5',
    'langcode' => 'en',
    'delta' => '0',
    'comment_body_value' => "<p>Comment body</p>\r\n",
    'comment_body_format' => 'basic_html',
  ])
  ->values([
    'bundle' => 'comment',
    'deleted' => '0',
    'entity_id' => '6',
    'revision_id' => '6',
    'langcode' => 'en',
    'delta' => '0',
    'comment_body_value' => "<p>Comment body</p>\r\n",
    'comment_body_format' => 'basic_html',
  ])
  ->execute();

$connection->insert('comment_field_data')
  ->fields([
    'cid',
    'comment_type',
    'langcode',
    'pid',
    'entity_id',
    'subject',
    'uid',
    'name',
    'mail',
    'homepage',
    'hostname',
    'created',
    'changed',
    'status',
    'thread',
    'entity_type',
    'field_name',
    'default_langcode',
  ])
  ->values([
    'cid' => '5',
    'comment_type' => 'comment',
    'langcode' => 'en',
    'pid' => NULL,
    'entity_id' => '8',
    'subject' => 'Comment with no entity_type',
    'uid' => '1',
    'name' => 'drupal',
    'mail' => NULL,
    'homepage' => NULL,
    'hostname' => '127.0.0.1',
    'created' => '1557218256',
    'changed' => '1557218256',
    'status' => '1',
    'thread' => '02/',
    'entity_type' => NULL,
    'field_name' => 'field_test_2',
    'default_langcode' => '1',
  ])
  ->values([
    'cid' => '6',
    'comment_type' => 'comment',
    'langcode' => 'en',
    'pid' => NULL,
    'entity_id' => '8',
    'subject' => 'Comment with no field_name',
    'uid' => '1',
    'name' => 'drupal',
    'mail' => NULL,
    'homepage' => NULL,
    'hostname' => '127.0.0.1',
    'created' => '1557218266',
    'changed' => '1557218266',
    'status' => '1',
    'thread' => '03/',
    'entity_type' => 'node',
    'field_name' => NULL,
    'default_langcode' => '1',
  ])
  ->execute();
