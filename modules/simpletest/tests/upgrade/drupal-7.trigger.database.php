<?php
/**
 * @file
 * Test content for the trigger upgrade path.
 */

// Add several trigger configurations.
db_insert('trigger_assignments')->fields(array(
  'hook',
  'aid',
  'weight',
))
->values(array(
  'hook' => 'node_presave',
  'aid' => 'node_publish_action',
  'weight' => '1',
))
->values(array(
  'hook' => 'comment_presave',
  'aid' => 'comment_publish_action',
  'weight' => '1',
))
->values(array(
  'hook' => 'comment_delete',
  'aid' => 'node_save_action',
  'weight' => '1',
))
->execute();
