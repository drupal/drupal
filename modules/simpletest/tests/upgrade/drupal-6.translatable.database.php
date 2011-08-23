<?php

/**
 * Database additions for translatable tests.
 */

db_insert('node')->fields(array(
  'nid',
  'vid',
  'type',
  'language',
  'title',
  'uid',
  'status',
  'created',
  'changed',
  'comment',
  'promote',
  'moderate',
  'sticky',
  'tnid',
  'translate',
))
->values(array(
  'nid' => '53',
  'vid' => '63',
  'type' => 'translatable_page',
  'language' => 'fr',
  'title' => 'First translatable page',
  'uid' => '1',
  'status' => '1',
  'created' => '1298363952',
  'changed' => '1298363952',
  'comment' => '2',
  'promote' => '0',
  'moderate' => '0',
  'sticky' => '0',
  'tnid' => '0',
  'translate' => '0',
))
->execute();

db_insert('node_revisions')->fields(array(
  'nid',
  'vid',
  'uid',
  'title',
  'body',
  'teaser',
  'log',
  'timestamp',
  'format',
))
->values(array(
  'nid' => '53',
  'vid' => '63',
  'uid' => '1',
  'title' => 'First translatable page',
  'body' => 'Body of the first translatable page.',
  'teaser' => 'Teaser of the first translatable page.',
  'log' => '',
  'timestamp' => '1298363952',
  'format' => '1',
))
->execute();

db_insert('node_comment_statistics')->fields(array(
  'nid',
  'last_comment_timestamp',
  'last_comment_name',
  'last_comment_uid',
  'comment_count',
))
->values(array(
  'nid' => '53',
  'last_comment_timestamp' => '1298363952',
  'last_comment_name' => NULL,
  'last_comment_uid' => '1',
  'comment_count' => '0',
))
->execute();

db_insert('node_type')->fields(array(
  'type',
  'name',
  'module',
  'description',
  'help',
  'has_title',
  'title_label',
  'has_body',
  'body_label',
  'min_word_count',
  'custom',
  'modified',
  'locked',
  'orig_type',
))
->values(array(
  'type' => 'translatable_page',
  'name' => 'Translatable page',
  'module' => 'node',
  'description' => 'A <em>translatable page</em> is like a normal page, but with multilanguage support.',
  'help' => '',
  'has_title' => '1',
  'title_label' => 'Title',
  'has_body' => '1',
  'body_label' => 'Body',
  'min_word_count' => '0',
  'custom' => '0',
  'modified' => '0',
  'locked' => '1',
  'orig_type' => '',
))
->execute();

db_insert('variable')->fields(array(
  'name',
  'value',
))
->values(array(
  'name' => 'language_content_type_translatable_page',
  'value' => 's:1:"1";',
))
->execute();
