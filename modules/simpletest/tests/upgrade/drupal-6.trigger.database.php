<?php
/**
 * @file
 * Test content for the trigger upgrade path.
 */
db_create_table('trigger_assignments', array(
  'fields' => array(
    'hook' => array(
      'type' => 'varchar',
      'length' => 32,
      'not null' => TRUE,
      'default' => '',
    ),
    'op' => array(
      'type' => 'varchar',
      'length' => 32,
      'not null' => TRUE,
      'default' => '',
    ),
    'aid' => array(
      'type' => 'varchar',
      'length' => 255,
      'not null' => TRUE,
      'default' => '',
    ),
    'weight' => array(
      'type' => 'int',
      'not null' => TRUE,
      'default' => 0,
    ),
  ),
  'primary key' => array('hook', 'op', 'aid'),
  'module' => 'trigger',
  'name' => 'trigger_assignments',
));


// Add several trigger configurations.
db_insert('trigger_assignments')->fields(array(
  'hook',
  'op',
  'aid',
  'weight',
))
->values(array(
  'hook' => 'node',
  'op' => 'presave',
  'aid' => 'node_publish_action',
  'weight' => '1',
))
->values(array(
  'hook' => 'comment',
  'op' => 'presave',
  'aid' => 'comment_publish_action',
  'weight' => '1',
))
->values(array(
  'hook' => 'comment_delete',
  'op' => 'presave',
  'aid' => 'node_save_action',
  'weight' => '1',
))
->values(array(
  'hook' => 'nodeapi',
  'op' => 'presave',
  'aid' => 'node_make_sticky_action',
  'weight' => '1',
))
->values(array(
  'hook' => 'nodeapi',
  'op' => 'somehow_nodeapi_got_a_very_long',
  'aid' => 'node_save_action',
  'weight' => '1',
))
->execute();

db_update('system')->fields(array(
  'schema_version' => '6000',
  'status' => '1',
))
->condition('filename', 'modules/trigger/trigger.module')
->execute();
