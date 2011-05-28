<?php

/**
 * Database additions for forum tests.
 */

db_create_table('forum', array(
  'fields' => array(
    'nid' => array(
      'type' => 'int',
      'unsigned' => TRUE,
      'not null' => TRUE,
      'default' => 0,
    ),
    'vid' => array(
      'type' => 'int',
      'unsigned' => TRUE,
      'not null' => TRUE,
      'default' => 0,
    ),
    'tid' => array(
      'type' => 'int',
      'unsigned' => TRUE,
      'not null' => TRUE,
      'default' => 0,
    ),
  ),
  'indexes' => array(
    'nid' => array(
      'nid',
    ),
    'tid' => array(
      'tid',
    ),
  ),
  'primary key' => array(
    'vid',
  ),
  'module' => 'forum',
  'name' => 'forum',
));
db_insert('forum')->fields(array(
  'nid',
  'vid',
  'tid',
))
->values(array(
  'nid' => '51',
  'vid' => '61',
  'tid' => '81',
))
->execute();

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
  'nid' => '51',
  'vid' => '61',
  'type' => 'forum',
  'language' => '',
  'title' => 'Apples',
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
  'nid' => '51',
  'vid' => '61',
  'uid' => '1',
  'title' => 'Apples',
  'body' => 'A fruit.',
  'teaser' => 'A fruit.',
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
  'nid' => '51',
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
  'type' => 'forum',
  'name' => 'Forum topic',
  'module' => 'forum',
  'description' => 'A <em>forum topic</em> is the initial post to a new discussion thread within a forum.',
  'help' => '',
  'has_title' => '1',
  'title_label' => 'Subject',
  'has_body' => '1',
  'body_label' => 'Body',
  'min_word_count' => '0',
  'custom' => '0',
  'modified' => '0',
  'locked' => '1',
  'orig_type' => 'forum',
))
->execute();

db_update('system')->fields(array(
  'schema_version' => '6000',
  'status' => '1',
))
->condition('filename', 'modules/forum/forum.module')
->execute();

db_insert('term_data')->fields(array(
  'tid',
  'vid',
  'name',
  'description',
  'weight',
))
->values(array(
  'tid' => '81',
  'vid' => '101',
  'name' => 'Fruits',
  'description' => 'Fruits.',
  'weight' => '0',
))
->execute();

db_insert('term_hierarchy')->fields(array(
  'tid',
  'parent',
))
->values(array(
  'tid' => '81',
  'parent' => '0',
))
->execute();

db_insert('term_node')->fields(array(
  'nid',
  'vid',
  'tid',
))
->values(array(
  'nid' => '51',
  'vid' => '61',
  'tid' => '81',
))
->execute();

db_insert('variable')->fields(array(
  'name',
  'value',
))
->values(array(
  'name' => 'forum_nav_vocabulary',
  'value' => 's:3:"101";',
))
->values(array(
  'name' => 'forum_containers',
  'value' => 'a:1:{i:0;s:3:"101";}',
))
->execute();

db_insert('vocabulary')->fields(array(
  'vid',
  'name',
  'description',
  'help',
  'relations',
  'hierarchy',
  'multiple',
  'required',
  'tags',
  'module',
  'weight',
))
->values(array(
  'vid' => '101',
  'name' => 'Upgrade test for forums',
  'description' => 'Vocabulary used for Forums. The name is changed from the default "Forums" so that the upgrade path may be tested.',
  'help' => '',
  'relations' => '1',
  'hierarchy' => '1',
  'multiple' => '0',
  'required' => '0',
  'tags' => '0',
  'module' => 'forum',
  'weight' => '-10',
))
->execute();

db_insert('vocabulary_node_types')->fields(array(
  'vid',
  'type',
))
->values(array(
  'vid' => '101',
  'type' => 'forum',
))
->execute();

