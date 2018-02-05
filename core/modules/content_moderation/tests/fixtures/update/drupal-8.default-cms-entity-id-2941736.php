<?php
// @codingStandardsIgnoreFile
/**
 * @file
 * Content for the update path test in #2941736.
 *
 * @see \Drupal\Tests\content_moderation\Functional\DefaultContentModerationStateRevisionUpdateTest.
 */

use Drupal\Core\Database\Database;

$connection = Database::getConnection();

$connection->insert('block_content')
  ->fields(array(
    'id',
    'revision_id',
    'type',
    'uuid',
    'langcode',
  ))
  ->values(array(
    'id' => '1',
    'revision_id' => '1',
    'type' => 'test_block_content',
    'uuid' => '811fac6c-8184-4de5-99eb-9e70d28709f4',
    'langcode' => 'en',
  ))
  ->values(array(
    'id' => '2',
    'revision_id' => '3',
    'type' => 'test_block_content',
    'uuid' => 'b89f025c-0538-4075-bd8e-96acf74211c9',
    'langcode' => 'en',
  ))
  ->values(array(
    'id' => '3',
    'revision_id' => '5',
    'type' => 'test_block_content',
    'uuid' => '62e428e1-88a6-478c-a8c6-a554ca2332ae',
    'langcode' => 'en',
  ))
  ->execute();

$connection->insert('block_content_field_data')
  ->fields(array(
    'id',
    'revision_id',
    'type',
    'langcode',
    'info',
    'changed',
    'default_langcode',
    'revision_translation_affected',
  ))
  ->values(array(
    'id' => '1',
    'revision_id' => '1',
    'type' => 'test_block_content',
    'langcode' => 'en',
    'info' => 'draft pending revision',
    'changed' => '1517725800',
    'default_langcode' => '1',
    'revision_translation_affected' => '1',
  ))
  ->values(array(
    'id' => '2',
    'revision_id' => '3',
    'type' => 'test_block_content',
    'langcode' => 'en',
    'info' => 'published default revision',
    'changed' => '1517725800',
    'default_langcode' => '1',
    'revision_translation_affected' => '1',
  ))
  ->values(array(
    'id' => '3',
    'revision_id' => '5',
    'type' => 'test_block_content',
    'langcode' => 'en',
    'info' => 'archived default revision',
    'changed' => '1517725800',
    'default_langcode' => '1',
    'revision_translation_affected' => '1',
  ))
  ->execute();

$connection->insert('block_content_field_revision')
  ->fields(array(
    'id',
    'revision_id',
    'langcode',
    'info',
    'changed',
    'default_langcode',
    'revision_translation_affected',
  ))
  ->values(array(
    'id' => '1',
    'revision_id' => '1',
    'langcode' => 'en',
    'info' => 'draft pending revision',
    'changed' => '1517725800',
    'default_langcode' => '1',
    'revision_translation_affected' => '1',
  ))
  ->values(array(
    'id' => '1',
    'revision_id' => '2',
    'langcode' => 'en',
    'info' => 'draft pending revision',
    'changed' => '1517725800',
    'default_langcode' => '1',
    'revision_translation_affected' => '1',
  ))
  ->values(array(
    'id' => '2',
    'revision_id' => '3',
    'langcode' => 'en',
    'info' => 'published default revision',
    'changed' => '1517725800',
    'default_langcode' => '1',
    'revision_translation_affected' => '1',
  ))
  ->values(array(
    'id' => '3',
    'revision_id' => '4',
    'langcode' => 'en',
    'info' => 'archived default revision',
    'changed' => '1517725800',
    'default_langcode' => '1',
    'revision_translation_affected' => '1',
  ))
  ->values(array(
    'id' => '3',
    'revision_id' => '5',
    'langcode' => 'en',
    'info' => 'archived default revision',
    'changed' => '1517725800',
    'default_langcode' => '1',
    'revision_translation_affected' => '1',
  ))
  ->execute();

$connection->insert('block_content_revision')
  ->fields(array(
    'id',
    'revision_id',
    'langcode',
    'revision_user',
    'revision_created',
    'revision_log',
  ))
  ->values(array(
    'id' => '1',
    'revision_id' => '1',
    'langcode' => 'en',
    'revision_user' => NULL,
    'revision_created' => '1517725800',
    'revision_log' => NULL,
  ))
  ->values(array(
    'id' => '1',
    'revision_id' => '2',
    'langcode' => 'en',
    'revision_user' => NULL,
    'revision_created' => '1517725800',
    'revision_log' => NULL,
  ))
  ->values(array(
    'id' => '2',
    'revision_id' => '3',
    'langcode' => 'en',
    'revision_user' => NULL,
    'revision_created' => '1517725800',
    'revision_log' => NULL,
  ))
  ->values(array(
    'id' => '3',
    'revision_id' => '4',
    'langcode' => 'en',
    'revision_user' => NULL,
    'revision_created' => '1517725800',
    'revision_log' => NULL,
  ))
  ->values(array(
    'id' => '3',
    'revision_id' => '5',
    'langcode' => 'en',
    'revision_user' => NULL,
    'revision_created' => '1517725800',
    'revision_log' => NULL,
  ))
  ->execute();

$connection->delete('config')
  ->condition('name', ['workflows.workflow.editorial'], 'IN')
  ->execute();

$connection->insert('config')
  ->fields(array(
    'collection',
    'name',
    'data',
  ))
  ->values(array(
    'collection' => '',
    'name' => 'block_content.type.test_block_content',
    'data' => 'a:8:{s:4:"uuid";s:36:"966baba6-525e-48fe-b8c5-a5f131b1857f";s:8:"langcode";s:2:"en";s:6:"status";b:1;s:12:"dependencies";a:0:{}s:2:"id";s:18:"test_block_content";s:5:"label";s:18:"Test Block Content";s:8:"revision";N;s:11:"description";N;}',
  ))
  ->values(array(
    'collection' => '',
    'name' => 'workflows.workflow.editorial',
    'data' => 'a:9:{s:4:"uuid";s:36:"08b548c7-ff59-468b-9347-7d697680d035";s:8:"langcode";s:2:"en";s:6:"status";b:1;s:12:"dependencies";a:2:{s:6:"config";a:2:{i:0;s:37:"block_content.type.test_block_content";i:1;s:17:"node.type.article";}s:6:"module";a:1:{i:0;s:18:"content_moderation";}}s:5:"_core";a:1:{s:19:"default_config_hash";s:43:"T_JxNjYlfoRBi7Bj1zs5Xv9xv1btuBkKp5C1tNrjMhI";}s:2:"id";s:9:"editorial";s:5:"label";s:9:"Editorial";s:4:"type";s:18:"content_moderation";s:13:"type_settings";a:3:{s:6:"states";a:3:{s:8:"archived";a:4:{s:5:"label";s:8:"Archived";s:6:"weight";i:5;s:9:"published";b:0;s:16:"default_revision";b:1;}s:5:"draft";a:4:{s:5:"label";s:5:"Draft";s:9:"published";b:0;s:16:"default_revision";b:0;s:6:"weight";i:-5;}s:9:"published";a:4:{s:5:"label";s:9:"Published";s:9:"published";b:1;s:16:"default_revision";b:1;s:6:"weight";i:0;}}s:11:"transitions";a:5:{s:7:"archive";a:4:{s:5:"label";s:7:"Archive";s:4:"from";a:1:{i:0;s:9:"published";}s:2:"to";s:8:"archived";s:6:"weight";i:2;}s:14:"archived_draft";a:4:{s:5:"label";s:16:"Restore to Draft";s:4:"from";a:1:{i:0;s:8:"archived";}s:2:"to";s:5:"draft";s:6:"weight";i:3;}s:18:"archived_published";a:4:{s:5:"label";s:7:"Restore";s:4:"from";a:1:{i:0;s:8:"archived";}s:2:"to";s:9:"published";s:6:"weight";i:4;}s:16:"create_new_draft";a:4:{s:5:"label";s:16:"Create New Draft";s:2:"to";s:5:"draft";s:6:"weight";i:0;s:4:"from";a:2:{i:0;s:5:"draft";i:1;s:9:"published";}}s:7:"publish";a:4:{s:5:"label";s:7:"Publish";s:2:"to";s:9:"published";s:6:"weight";i:1;s:4:"from";a:2:{i:0;s:5:"draft";i:1;s:9:"published";}}}s:12:"entity_types";a:2:{s:13:"block_content";a:1:{i:0;s:18:"test_block_content";}s:4:"node";a:1:{i:0;s:7:"article";}}}}',
  ))
  ->execute();

$connection->insert('content_moderation_state')
  ->fields(array(
    'id',
    'revision_id',
    'uuid',
    'langcode',
  ))
  ->values(array(
    'id' => '1',
    'revision_id' => '2',
    'uuid' => '3ce04732-f65f-4937-aa49-821f5842ae06',
    'langcode' => 'en',
  ))
  ->values(array(
    'id' => '2',
    'revision_id' => '3',
    'uuid' => 'a6507b55-3001-4748-8d32-f4fa47319754',
    'langcode' => 'en',
  ))
  ->values(array(
    'id' => '3',
    'revision_id' => '5',
    'uuid' => '112d2bd2-552b-4e2f-9a6d-526740ba1b38',
    'langcode' => 'en',
  ))
  ->values(array(
    'id' => '4',
    'revision_id' => '7',
    'uuid' => 'a85d0d06-e046-4509-b9b4-75d78dcdd91e',
    'langcode' => 'en',
  ))
  ->values(array(
    'id' => '5',
    'revision_id' => '8',
    'uuid' => '3797f5de-116b-4d75-b7e3-5206e6f97c41',
    'langcode' => 'en',
  ))
  ->values(array(
    'id' => '6',
    'revision_id' => '10',
    'uuid' => '8d9b11c1-8ddf-4c61-bb8d-9ac724e28d9e',
    'langcode' => 'en',
  ))
  ->execute();

$connection->insert('content_moderation_state_field_data')
  ->fields(array(
    'id',
    'revision_id',
    'langcode',
    'uid',
    'workflow',
    'moderation_state',
    'content_entity_type_id',
    'content_entity_id',
    'content_entity_revision_id',
    'default_langcode',
    'revision_translation_affected',
  ))
  ->values(array(
    'id' => '1',
    'revision_id' => '2',
    'langcode' => 'en',
    'uid' => '0',
    'workflow' => 'editorial',
    'moderation_state' => 'draft',
    'content_entity_type_id' => 'node',
    'content_entity_id' => '1',
    'content_entity_revision_id' => '2',
    'default_langcode' => '1',
    'revision_translation_affected' => '1',
  ))
  ->values(array(
    'id' => '2',
    'revision_id' => '3',
    'langcode' => 'en',
    'uid' => '0',
    'workflow' => 'editorial',
    'moderation_state' => 'published',
    'content_entity_type_id' => 'node',
    'content_entity_id' => '2',
    'content_entity_revision_id' => '3',
    'default_langcode' => '1',
    'revision_translation_affected' => '1',
  ))
  ->values(array(
    'id' => '3',
    'revision_id' => '5',
    'langcode' => 'en',
    'uid' => '0',
    'workflow' => 'editorial',
    'moderation_state' => 'archived',
    'content_entity_type_id' => 'node',
    'content_entity_id' => '3',
    'content_entity_revision_id' => '5',
    'default_langcode' => '1',
    'revision_translation_affected' => '1',
  ))
  ->values(array(
    'id' => '4',
    'revision_id' => '7',
    'langcode' => 'en',
    'uid' => '0',
    'workflow' => 'editorial',
    'moderation_state' => 'draft',
    'content_entity_type_id' => 'block_content',
    'content_entity_id' => '1',
    'content_entity_revision_id' => '2',
    'default_langcode' => '1',
    'revision_translation_affected' => '1',
  ))
  ->values(array(
    'id' => '5',
    'revision_id' => '8',
    'langcode' => 'en',
    'uid' => '0',
    'workflow' => 'editorial',
    'moderation_state' => 'published',
    'content_entity_type_id' => 'block_content',
    'content_entity_id' => '2',
    'content_entity_revision_id' => '3',
    'default_langcode' => '1',
    'revision_translation_affected' => '1',
  ))
  ->values(array(
    'id' => '6',
    'revision_id' => '10',
    'langcode' => 'en',
    'uid' => '0',
    'workflow' => 'editorial',
    'moderation_state' => 'archived',
    'content_entity_type_id' => 'block_content',
    'content_entity_id' => '3',
    'content_entity_revision_id' => '5',
    'default_langcode' => '1',
    'revision_translation_affected' => '1',
  ))
  ->execute();

$connection->insert('content_moderation_state_field_revision')
  ->fields(array(
    'id',
    'revision_id',
    'langcode',
    'uid',
    'workflow',
    'moderation_state',
    'content_entity_type_id',
    'content_entity_id',
    'content_entity_revision_id',
    'default_langcode',
    'revision_translation_affected',
  ))
  ->values(array(
    'id' => '1',
    'revision_id' => '1',
    'langcode' => 'en',
    'uid' => '0',
    'workflow' => 'editorial',
    'moderation_state' => 'published',
    'content_entity_type_id' => 'node',
    'content_entity_id' => '1',
    'content_entity_revision_id' => '1',
    'default_langcode' => '1',
    'revision_translation_affected' => '1',
  ))
  ->values(array(
    'id' => '1',
    'revision_id' => '2',
    'langcode' => 'en',
    'uid' => '0',
    'workflow' => 'editorial',
    'moderation_state' => 'draft',
    'content_entity_type_id' => 'node',
    'content_entity_id' => '1',
    'content_entity_revision_id' => '2',
    'default_langcode' => '1',
    'revision_translation_affected' => '1',
  ))
  ->values(array(
    'id' => '2',
    'revision_id' => '3',
    'langcode' => 'en',
    'uid' => '0',
    'workflow' => 'editorial',
    'moderation_state' => 'published',
    'content_entity_type_id' => 'node',
    'content_entity_id' => '2',
    'content_entity_revision_id' => '3',
    'default_langcode' => '1',
    'revision_translation_affected' => '1',
  ))
  ->values(array(
    'id' => '3',
    'revision_id' => '4',
    'langcode' => 'en',
    'uid' => '0',
    'workflow' => 'editorial',
    'moderation_state' => 'published',
    'content_entity_type_id' => 'node',
    'content_entity_id' => '3',
    'content_entity_revision_id' => '4',
    'default_langcode' => '1',
    'revision_translation_affected' => '1',
  ))
  ->values(array(
    'id' => '3',
    'revision_id' => '5',
    'langcode' => 'en',
    'uid' => '0',
    'workflow' => 'editorial',
    'moderation_state' => 'archived',
    'content_entity_type_id' => 'node',
    'content_entity_id' => '3',
    'content_entity_revision_id' => '5',
    'default_langcode' => '1',
    'revision_translation_affected' => '1',
  ))
  ->values(array(
    'id' => '4',
    'revision_id' => '6',
    'langcode' => 'en',
    'uid' => '0',
    'workflow' => 'editorial',
    'moderation_state' => 'published',
    'content_entity_type_id' => 'block_content',
    'content_entity_id' => '1',
    'content_entity_revision_id' => '1',
    'default_langcode' => '1',
    'revision_translation_affected' => '1',
  ))
  ->values(array(
    'id' => '4',
    'revision_id' => '7',
    'langcode' => 'en',
    'uid' => '0',
    'workflow' => 'editorial',
    'moderation_state' => 'draft',
    'content_entity_type_id' => 'block_content',
    'content_entity_id' => '1',
    'content_entity_revision_id' => '2',
    'default_langcode' => '1',
    'revision_translation_affected' => '1',
  ))
  ->values(array(
    'id' => '5',
    'revision_id' => '8',
    'langcode' => 'en',
    'uid' => '0',
    'workflow' => 'editorial',
    'moderation_state' => 'published',
    'content_entity_type_id' => 'block_content',
    'content_entity_id' => '2',
    'content_entity_revision_id' => '3',
    'default_langcode' => '1',
    'revision_translation_affected' => '1',
  ))
  ->values(array(
    'id' => '6',
    'revision_id' => '9',
    'langcode' => 'en',
    'uid' => '0',
    'workflow' => 'editorial',
    'moderation_state' => 'published',
    'content_entity_type_id' => 'block_content',
    'content_entity_id' => '3',
    'content_entity_revision_id' => '4',
    'default_langcode' => '1',
    'revision_translation_affected' => '1',
  ))
  ->values(array(
    'id' => '6',
    'revision_id' => '10',
    'langcode' => 'en',
    'uid' => '0',
    'workflow' => 'editorial',
    'moderation_state' => 'archived',
    'content_entity_type_id' => 'block_content',
    'content_entity_id' => '3',
    'content_entity_revision_id' => '5',
    'default_langcode' => '1',
    'revision_translation_affected' => '1',
  ))
  ->execute();

$connection->insert('content_moderation_state_revision')
  ->fields(array(
    'id',
    'revision_id',
    'langcode',
  ))
  ->values(array(
    'id' => '1',
    'revision_id' => '1',
    'langcode' => 'en',
  ))
  ->values(array(
    'id' => '1',
    'revision_id' => '2',
    'langcode' => 'en',
  ))
  ->values(array(
    'id' => '2',
    'revision_id' => '3',
    'langcode' => 'en',
  ))
  ->values(array(
    'id' => '3',
    'revision_id' => '4',
    'langcode' => 'en',
  ))
  ->values(array(
    'id' => '3',
    'revision_id' => '5',
    'langcode' => 'en',
  ))
  ->values(array(
    'id' => '4',
    'revision_id' => '6',
    'langcode' => 'en',
  ))
  ->values(array(
    'id' => '4',
    'revision_id' => '7',
    'langcode' => 'en',
  ))
  ->values(array(
    'id' => '5',
    'revision_id' => '8',
    'langcode' => 'en',
  ))
  ->values(array(
    'id' => '6',
    'revision_id' => '9',
    'langcode' => 'en',
  ))
  ->values(array(
    'id' => '6',
    'revision_id' => '10',
    'langcode' => 'en',
  ))
  ->execute();

$connection->insert('key_value')
  ->fields(array(
    'collection',
    'name',
    'value',
  ))
  ->values(array(
    'collection' => 'config.entity.key_store.block_content_type',
    'name' => 'uuid:966baba6-525e-48fe-b8c5-a5f131b1857f',
    'value' => 'a:1:{i:0;s:37:"block_content.type.test_block_content";}',
  ))
  ->execute();

$connection->insert('node')
  ->fields(array(
    'nid',
    'vid',
    'type',
    'uuid',
    'langcode',
  ))
  ->values(array(
    'nid' => '1',
    'vid' => '1',
    'type' => 'article',
    'uuid' => '11143847-fe18-4808-a797-8b15966adf4c',
    'langcode' => 'en',
  ))
  ->values(array(
    'nid' => '2',
    'vid' => '3',
    'type' => 'article',
    'uuid' => '336e6941-9340-419e-a763-65d4c11ea031',
    'langcode' => 'en',
  ))
  ->values(array(
    'nid' => '3',
    'vid' => '5',
    'type' => 'article',
    'uuid' => '3eebe337-f977-4a32-94d2-4095947f125d',
    'langcode' => 'en',
  ))
  ->execute();

$connection->insert('node_field_data')
  ->fields(array(
    'nid',
    'vid',
    'type',
    'langcode',
    'status',
    'title',
    'uid',
    'created',
    'changed',
    'promote',
    'sticky',
    'default_langcode',
    'revision_translation_affected',
  ))
  ->values(array(
    'nid' => '1',
    'vid' => '1',
    'type' => 'article',
    'langcode' => 'en',
    'status' => '1',
    'title' => 'draft pending revision',
    'uid' => '0',
    'created' => '1517725800',
    'changed' => '1517725800',
    'promote' => '1',
    'sticky' => '0',
    'default_langcode' => '1',
    'revision_translation_affected' => '1',
  ))
  ->values(array(
    'nid' => '2',
    'vid' => '3',
    'type' => 'article',
    'langcode' => 'en',
    'status' => '1',
    'title' => 'published default revision',
    'uid' => '0',
    'created' => '1517725800',
    'changed' => '1517725800',
    'promote' => '1',
    'sticky' => '0',
    'default_langcode' => '1',
    'revision_translation_affected' => '1',
  ))
  ->values(array(
    'nid' => '3',
    'vid' => '5',
    'type' => 'article',
    'langcode' => 'en',
    'status' => '0',
    'title' => 'archived default revision',
    'uid' => '0',
    'created' => '1517725800',
    'changed' => '1517725800',
    'promote' => '1',
    'sticky' => '0',
    'default_langcode' => '1',
    'revision_translation_affected' => '1',
  ))
  ->execute();

$connection->insert('node_field_revision')
  ->fields(array(
    'nid',
    'vid',
    'langcode',
    'status',
    'title',
    'uid',
    'created',
    'changed',
    'promote',
    'sticky',
    'default_langcode',
    'revision_translation_affected',
  ))
  ->values(array(
    'nid' => '1',
    'vid' => '1',
    'langcode' => 'en',
    'status' => '1',
    'title' => 'draft pending revision',
    'uid' => '0',
    'created' => '1517725800',
    'changed' => '1517725800',
    'promote' => '1',
    'sticky' => '0',
    'default_langcode' => '1',
    'revision_translation_affected' => '1',
  ))
  ->values(array(
    'nid' => '1',
    'vid' => '2',
    'langcode' => 'en',
    'status' => '0',
    'title' => 'draft pending revision',
    'uid' => '0',
    'created' => '1517725800',
    'changed' => '1517725800',
    'promote' => '1',
    'sticky' => '0',
    'default_langcode' => '1',
    'revision_translation_affected' => '1',
  ))
  ->values(array(
    'nid' => '2',
    'vid' => '3',
    'langcode' => 'en',
    'status' => '1',
    'title' => 'published default revision',
    'uid' => '0',
    'created' => '1517725800',
    'changed' => '1517725800',
    'promote' => '1',
    'sticky' => '0',
    'default_langcode' => '1',
    'revision_translation_affected' => '1',
  ))
  ->values(array(
    'nid' => '3',
    'vid' => '4',
    'langcode' => 'en',
    'status' => '1',
    'title' => 'archived default revision',
    'uid' => '0',
    'created' => '1517725800',
    'changed' => '1517725800',
    'promote' => '1',
    'sticky' => '0',
    'default_langcode' => '1',
    'revision_translation_affected' => '1',
  ))
  ->values(array(
    'nid' => '3',
    'vid' => '5',
    'langcode' => 'en',
    'status' => '0',
    'title' => 'archived default revision',
    'uid' => '0',
    'created' => '1517725800',
    'changed' => '1517725800',
    'promote' => '1',
    'sticky' => '0',
    'default_langcode' => '1',
    'revision_translation_affected' => '1',
  ))
  ->execute();

$connection->insert('node_revision')
  ->fields(array(
    'nid',
    'vid',
    'langcode',
    'revision_uid',
    'revision_timestamp',
    'revision_log',
  ))
  ->values(array(
    'nid' => '1',
    'vid' => '1',
    'langcode' => 'en',
    'revision_uid' => '0',
    'revision_timestamp' => '1517725800',
    'revision_log' => NULL,
  ))
  ->values(array(
    'nid' => '1',
    'vid' => '2',
    'langcode' => 'en',
    'revision_uid' => '0',
    'revision_timestamp' => '1517725800',
    'revision_log' => NULL,
  ))
  ->values(array(
    'nid' => '2',
    'vid' => '3',
    'langcode' => 'en',
    'revision_uid' => '0',
    'revision_timestamp' => '1517725800',
    'revision_log' => NULL,
  ))
  ->values(array(
    'nid' => '3',
    'vid' => '4',
    'langcode' => 'en',
    'revision_uid' => '0',
    'revision_timestamp' => '1517725800',
    'revision_log' => NULL,
  ))
  ->values(array(
    'nid' => '3',
    'vid' => '5',
    'langcode' => 'en',
    'revision_uid' => '0',
    'revision_timestamp' => '1517725800',
    'revision_log' => NULL,
  ))
  ->execute();
