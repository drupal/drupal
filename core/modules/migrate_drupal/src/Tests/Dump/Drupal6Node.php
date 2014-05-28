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
        'field_test_three_value' => array(
          'description' => 'Test field column.',
          'type' => 'numeric',
          'precision' => 10,
          'scale' => 2,
          'not null' => FALSE
        ),
      ),
      'primary key' => array('vid'),
    ));

    $this->database->insert('content_type_story')->fields(
      array(
        'nid',
        'vid',
        'field_test_three_value',
      ))
      ->values(array(
        'nid' => 1,
        'vid' => 1,
        'field_test_three_value' => '42.42',
      ))
      ->execute();
    $this->setModuleVersion('content', 6001);
  }
}
