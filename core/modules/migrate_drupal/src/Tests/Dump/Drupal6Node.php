<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\Dump\Drupal6Node.
 */

namespace Drupal\migrate_drupal\Tests\Dump;

/**
 * Database dump for testing the node migration.
 */
class Drupal6Node extends Drupal6DumpBase {

  /**
   * {@inheritdoc}
   */
  public function load() {
    $this->createTable('node', array(
      'description' => 'The base table for nodes.',
      'fields' => array(
        'nid' => array(
          'description' => 'The primary identifier for a node.',
          'type' => 'serial',
          'unsigned' => TRUE,
          'not null' => TRUE,
        ),
        'vid' => array(
          'description' => 'The current {node_revisions}.vid version identifier.',
          'type' => 'int',
          'unsigned' => TRUE,
          'not null' => TRUE,
          'default' => 0,
        ),
        'type' => array(
          'description' => 'The {node_type}.type of this node.',
          'type' => 'varchar',
          'length' => 32,
          'not null' => TRUE,
          'default' => '',
        ),
        'language' => array(
          'description' => 'The {languages}.language of this node.',
          'type' => 'varchar',
          'length' => 12,
          'not null' => TRUE,
          'default' => '',
        ),
        'title' => array(
          'description' => 'The title of this node, always treated as non-markup plain text.',
          'type' => 'varchar',
          'length' => 255,
          'not null' => TRUE,
          'default' => '',
        ),
        'uid' => array(
          'description' => 'The {users}.uid that owns this node; initially, this is the user that created it.',
          'type' => 'int',
          'not null' => TRUE,
          'default' => 0,
        ),
        'status' => array(
          'description' => 'Boolean indicating whether the node is published (visible to non-administrators).',
          'type' => 'int',
          'not null' => TRUE,
          'default' => 1,
        ),
        'created' => array(
          'description' => 'The Unix timestamp when the node was created.',
          'type' => 'int',
          'not null' => TRUE,
          'default' => 0,
        ),
        'changed' => array(
          'description' => 'The Unix timestamp when the node was most recently saved.',
          'type' => 'int',
          'not null' => TRUE,
          'default' => 0,
        ),
        'comment' => array(
          'description' => 'Whether comments are allowed on this node: 0 = no, 1 = read only, 2 = read/write.',
          'type' => 'int',
          'not null' => TRUE,
          'default' => 0,
        ),
        'promote' => array(
          'description' => 'Boolean indicating whether the node should be displayed on the front page.',
          'type' => 'int',
          'not null' => TRUE,
          'default' => 0,
        ),
        'moderate' => array(
          'description' => 'Previously, a boolean indicating whether the node was "in moderation"; mostly no longer used.',
          'type' => 'int',
          'not null' => TRUE,
          'default' => 0,
        ),
        'sticky' => array(
          'description' => 'Boolean indicating whether the node should be displayed at the top of lists in which it appears.',
          'type' => 'int',
          'not null' => TRUE,
          'default' => 0,
        ),
        'tnid' => array(
          'description' => 'The translation set id for this node, which equals the node id of the source post in each set.',
          'type' => 'int',
          'unsigned' => TRUE,
          'not null' => TRUE,
          'default' => 0,
        ),
        'translate' => array(
          'description' => 'A boolean indicating whether this translation page needs to be updated.',
          'type' => 'int',
          'not null' => TRUE,
          'default' => 0,
        ),
      ),
      'indexes' => array(
        'node_changed' => array('changed'),
        'node_created' => array('created'),
        'node_moderate' => array('moderate'),
        'node_promote_status' => array('promote', 'status'),
        'node_status_type' => array('status', 'type', 'nid'),
        'node_title_type' => array('title', array('type', 4)),
        'node_type' => array(array('type', 4)),
        'uid' => array('uid'),
        'tnid' => array('tnid'),
        'translate' => array('translate'),
      ),
      'unique keys' => array(
        'vid' => array('vid'),
      ),
      'primary key' => array('nid'),
    ));
    $this->database->insert('node')->fields(array(
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
        'nid' => 1,
        'vid' => 1,
        'type' => 'story',
        'language' => '',
        'title' => 'Test title',
        'uid' => 1,
        'status' => 1,
        'created' => 1388271197,
        'changed' => 1390095701,
        'comment' => 0,
        'promote' => 0,
        'moderate' => 0,
        'sticky' => 0,
        'tnid' => 0,
        'translate' => 0,
      ))
      ->values(array(
        'nid' => 2,
        'vid' => 3,
        'type' => 'story',
        'language' => '',
        'title' => 'Test title 2',
        'uid' => 1,
        'status' => 1,
        'created' => 1388271197,
        'changed' => 1390095701,
        'comment' => 0,
        'promote' => 0,
        'moderate' => 0,
        'sticky' => 0,
        'tnid' => 0,
        'translate' => 0,
      ))
      ->values(array(
        'nid' => 3,
        'vid' => 4,
        'type' => 'test_planet',
        'language' => '',
        'title' => 'Test planet title 3',
        'uid' => 1,
        'status' => 1,
        'created' => 1388271527,
        'changed' => 1390096401,
        'comment' => 0,
        'promote' => 0,
        'moderate' => 0,
        'sticky' => 0,
        'tnid' => 0,
        'translate' => 0,
      ))
      ->values(array(
        'nid' => 4,
        'vid' => 6,
        'type' => 'test_planet',
        'language' => '',
        'title' => '',
        'uid' => 1,
        'status' => 1,
        'created' => 1388271527,
        'changed' => 1390096401,
        'comment' => 0,
        'promote' => 0,
        'moderate' => 0,
        'sticky' => 0,
        'tnid' => 0,
        'translate' => 0,
      ))
      ->values(array(
        'nid' => 5,
        'vid' => 7,
        'type' => 'test_planet',
        'language' => '',
        'title' => '',
        'uid' => 1,
        'status' => 1,
        'created' => 1388271527,
        'changed' => 1390096401,
        'comment' => 0,
        'promote' => 0,
        'moderate' => 0,
        'sticky' => 0,
        'tnid' => 0,
        'translate' => 0,
      ))
      ->values(array(
        'nid' => 6,
        'vid' => 8,
        'type' => 'test_planet',
        'language' => '',
        'title' => '',
        'uid' => 1,
        'status' => 1,
        'created' => 1388271527,
        'changed' => 1390096401,
        'comment' => 0,
        'promote' => 0,
        'moderate' => 0,
        'sticky' => 0,
        'tnid' => 0,
        'translate' => 0,
      ))
      ->values(array(
        'nid' => 7,
        'vid' => 9,
        'type' => 'test_planet',
        'language' => '',
        'title' => '',
        'uid' => 1,
        'status' => 1,
        'created' => 1388271527,
        'changed' => 1390096401,
        'comment' => 0,
        'promote' => 0,
        'moderate' => 0,
        'sticky' => 0,
        'tnid' => 0,
        'translate' => 0,
      ))
      ->values(array(
        'nid' => 8,
        'vid' => 10,
        'type' => 'test_planet',
        'language' => '',
        'title' => '',
        'uid' => 1,
        'status' => 1,
        'created' => 1388271527,
        'changed' => 1390096401,
        'comment' => 0,
        'promote' => 0,
        'moderate' => 0,
        'sticky' => 0,
        'tnid' => 0,
        'translate' => 0,
      ))
      ->execute();
    $this->createTable('node_revisions', array(
      'description' => 'Stores information about each saved version of a {node}.',
      'fields' => array(
        'nid' => array(
          'description' => 'The {node} this version belongs to.',
          'type' => 'int',
          'unsigned' => TRUE,
          'not null' => TRUE,
          'default' => 0,
        ),
        'vid' => array(
          'description' => 'The primary identifier for this version.',
          'type' => 'serial',
          'unsigned' => TRUE,
          'not null' => TRUE,
        ),
        'uid' => array(
          'description' => 'The {users}.uid that created this version.',
          'type' => 'int',
          'not null' => TRUE,
          'default' => 0,
        ),
        'title' => array(
          'description' => 'The title of this version.',
          'type' => 'varchar',
          'length' => 255,
          'not null' => TRUE,
          'default' => '',
        ),
        'body' => array(
          'description' => 'The body of this version.',
          'type' => 'text',
          'not null' => TRUE,
          'size' => 'big',
        ),
        'teaser' => array(
          'description' => 'The teaser of this version.',
          'type' => 'text',
          'not null' => TRUE,
          'size' => 'big',
        ),
        'log' => array(
          'description' => 'The log entry explaining the changes in this version.',
          'type' => 'text',
          'not null' => TRUE,
          'size' => 'big',
        ),
        'timestamp' => array(
          'description' => 'A Unix timestamp indicating when this version was created.',
          'type' => 'int',
          'not null' => TRUE,
          'default' => 0,
        ),
        'format' => array(
          'description' => "The input format used by this version's body.",
          'type' => 'int',
          'not null' => TRUE,
          'default' => 0,
        ),
      ),
      'indexes' => array(
        'nid' => array('nid'),
        'uid' => array('uid'),
      ),
      'primary key' => array('vid'),
    ));

    $this->database->insert('node_revisions')->fields(
      array(
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
        'nid' => 1,
        'vid' => 1,
        'uid' => 1,
        'title' => 'Test title',
        'body' => 'test',
        'teaser' => 'test',
        'log' => '',
        'timestamp' => 1390095701,
        'format' => 1,
      ))
      ->values(array(
        'nid' => 2,
        'vid' => 3,
        'uid' => 1,
        'title' => 'Test title rev 3',
        'body' => 'test rev 3',
        'teaser' => 'test rev 3',
        'log' => '',
        'timestamp' => 1390095701,
        'format' => 1,
      ))
      ->values(array(
        'nid' => 3,
        'vid' => 4,
        'uid' => 1,
        'title' => 'Test page title rev 4',
        'body' => 'test page body rev 4',
        'teaser' => 'test page teaser rev 4',
        'log' => '',
        'timestamp' => 1390095701,
        'format' => 0,
      ))
      ->values(array(
        'nid' => 4,
        'vid' => 6,
        'uid' => 1,
        'title' => 'Node 4',
        'body' => 'Node 4 body',
        'teaser' => 'test for node 4',
        'log' => '',
        'timestamp' => 1390095701,
        'format' => 1,
      ))
      ->values(array(
        'nid' => 5,
        'vid' => 7,
        'uid' => 1,
        'title' => 'Node 5',
        'body' => 'Node 5 body',
        'teaser' => 'test for node 5',
        'log' => '',
        'timestamp' => 1390095701,
        'format' => 1,
      ))
      ->values(array(
        'nid' => 6,
        'vid' => 8,
        'uid' => 1,
        'title' => 'Node 6',
        'body' => 'Node 6 body',
        'teaser' => 'test for node 6',
        'log' => '',
        'timestamp' => 1390095701,
        'format' => 1,
      ))
      ->values(array(
        'nid' => 7,
        'vid' => 9,
        'uid' => 1,
        'title' => 'Node 7',
        'body' => 'Node 7 body',
        'teaser' => 'test for node 7',
        'log' => '',
        'timestamp' => 1390095701,
        'format' => 1,
      ))
      ->values(array(
        'nid' => 8,
        'vid' => 10,
        'uid' => 1,
        'title' => 'Node 8',
        'body' => 'Node 8 body',
        'teaser' => 'test for node 8',
        'log' => '',
        'timestamp' => 1390095701,
        'format' => 1,
      ))
      ->values(array(
        'nid' => 9,
        'vid' => 11,
        'uid' => 1,
        'title' => 'Node 9',
        'body' => 'Node 9 body',
        'teaser' => 'test for node 9',
        'log' => '',
        'timestamp' => 1390095701,
        'format' => 1,
      ))
      ->execute();

    $this->createTable('content_type_story', array(
      'description' => 'The content type join table.',
      'fields' => array(
        'nid' => array(
          'description' => 'The {node} this version belongs to.',
          'type' => 'int',
          'unsigned' => TRUE,
          'not null' => TRUE,
          'default' => 0,
        ),
        'vid' => array(
          'description' => 'The primary identifier for this version.',
          'type' => 'serial',
          'unsigned' => TRUE,
          'not null' => TRUE,
        ),
        'uid' => array(
          'description' => 'The author of the node.',
          'type' => 'int',
          'unsigned' => TRUE,
          'not null' => TRUE,
        ),
        'field_test_three_value' => array(
          'description' => 'Test field column.',
          'type' => 'numeric',
          'precision' => 10,
          'scale' => 2,
          'not null' => FALSE
        ),
        'field_test_integer_selectlist_value' => array(
          'description' => 'Test integer select field column.',
          'type' => 'int',
          'unsigned' => FALSE,
          'not null' => FALSE
        ),
        'field_test_identical1_value' => array(
          'description' => 'Test field column.',
          'type' => 'int',
          'unsigned' => TRUE,
          'not null' => FALSE
        ),
        'field_test_identical2_value' => array(
          'description' => 'Test field column.',
          'type' => 'int',
          'unsigned' => TRUE,
          'not null' => FALSE
        ),
        'field_test_link_url' => array(
          'description' => 'The link field',
          'type' => 'varchar',
          'length' => 2048,
          'not null' => FALSE,
        ),
        'field_test_link_title' => array(
          'description' => 'The link field',
          'type' => 'varchar',
          'length' => 255,
          'not null' => FALSE,
        ),
        'field_test_link_attributes' => array(
          'description' => 'The link attributes',
          'type' => 'text',
          'not null' => FALSE,
        ),
        'field_test_filefield_fid' => array(
          'description' => 'The file id.',
          'type' => 'int',
          'unsigned' => TRUE,
          'not null' => TRUE,
        ),
        'field_test_filefield_list' => array(
          'description' => 'File list field.',
          'type' => 'int',
          'unsigned' => TRUE,
          'not null' => TRUE,
        ),
        'field_test_filefield_data' => array(
          'description' => 'The file meta.',
          'type' => 'varchar',
          'length' => 255,
          'not null' => TRUE,
        ),
      ),
      'primary key' => array('vid'),
    ));

    $this->database->insert('content_type_story')->fields(
      array(
        'nid',
        'vid',
        'uid',
        'field_test_three_value',
        'field_test_integer_selectlist_value',
        'field_test_identical1_value',
        'field_test_identical2_value',
        'field_test_link_url',
        'field_test_link_title',
        'field_test_link_attributes',
        'field_test_filefield_fid',
        'field_test_filefield_list',
        'field_test_filefield_data'
      ))
      ->values(array(
        'nid' => 1,
        'vid' => 1,
        'uid' => 1,
        'field_test_three_value' => '42.42',
        'field_test_integer_selectlist_value' => '3412',
        'field_test_identical1_value' => 1,
        'field_test_identical2_value' => 1,
        'field_test_link_url' => 'http://drupal.org/project/drupal',
        'field_test_link_title' => 'Drupal project page',
        'field_test_link_attributes' => 's:32:"a:1:{s:6:"target";s:6:"_blank";}";";',
        'field_test_filefield_fid' => 1,
        'field_test_filefield_list' => 1,
        'field_test_filefield_data' => 'a:1:{s:11:"description";s:4:"desc";}'
      ))
      ->values(array(
        'nid' => 1,
        'vid' => 2,
        'uid' => 1,
        'field_test_three_value' => '42.42',
        'field_test_integer_selectlist_value' => '3412',
        'field_test_identical1_value' => 1,
        'field_test_identical2_value' => 1,
        'field_test_link_url' => 'http://drupal.org/project/drupal',
        'field_test_link_title' => 'Drupal project page',
        'field_test_link_attributes' => 's:32:"a:1:{s:6:"target";s:6:"_blank";}";',
        'field_test_filefield_fid' => 1,
        'field_test_filefield_list' => 1,
        'field_test_filefield_data' => 'a:1:{s:11:"description";s:4:"desc";}'
      ))
      ->values(array(
        'nid' => 2,
        'vid' => 3,
        'uid' => 1,
        'field_test_three_value' => '23.2',
        'field_test_integer_selectlist_value' => '1244',
        'field_test_identical1_value' => 1,
        'field_test_identical2_value' => 1,
        'field_test_link_url' => 'http://groups.drupal.org/',
        'field_test_link_title' => 'Drupal Groups',
        'field_test_link_attributes' => 's:6:"a:0:{}";',
        'field_test_filefield_fid' => 2,
        'field_test_filefield_list' => 1,
        'field_test_filefield_data' => 'a:1:{s:11:"description";s:4:"desc";}'
      ))
      ->values(array(
        'nid' => 2,
        'vid' => 5,
        'uid' => 1,
        'field_test_three_value' => '23.2',
        'field_test_integer_selectlist_value' => '1244',
        'field_test_identical1_value' => 1,
        'field_test_identical2_value' => 1,
        'field_test_link_url' => 'http://groups.drupal.org/',
        'field_test_link_title' => 'Drupal Groups',
        'field_test_link_attributes' => 's:6:"a:0:{}";',
        'field_test_filefield_fid' => 2,
        'field_test_filefield_list' => 1,
        'field_test_filefield_data' => 'a:1:{s:11:"description";s:4:"desc";}'
      ))
      ->execute();
    $this->setModuleVersion('content', 6001);

    $this->createTable('content_type_test_planet', array(
      'description' => 'The content type join table.',
      'fields' => array(
        'nid' => array(
          'description' => 'The {node} this version belongs to.',
          'type' => 'int',
          'unsigned' => TRUE,
          'not null' => TRUE,
          'default' => 0,
        ),
        'vid' => array(
          'description' => 'The primary identifier for this version.',
          'type' => 'serial',
          'unsigned' => TRUE,
          'not null' => TRUE,
        ),
      ),
      'primary key' => array('vid'),
    ));

    $this->database->insert('content_type_test_planet')->fields(
      array(
        'nid',
        'vid',
      ))
      ->values(array(
        'nid' => 3,
        'vid' => 4,
      ))
      ->values(array(
        'nid' => 4,
        'vid' => 6,
      ))
      ->values(array(
        'nid' => 5,
        'vid' => 7,
      ))
      ->values(array(
        'nid' => 6,
        'vid' => 8,
      ))
      ->values(array(
        'nid' => 7,
        'vid' => 9,
      ))
      ->values(array(
        'nid' => 8,
        'vid' => 10,
      ))
      ->values(array(
        'nid' => 9,
        'vid' => 11,
      ))
      ->execute();
  }
}
