<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\Dump\Drupal6Comment.
 */

namespace Drupal\migrate_drupal\Tests\Dump;

class Drupal6Comment extends Drupal6DumpBase {

  /**
   * {@inheritdoc}
   */
  public function load() {
    $this->createTable('comments', array(
      'description' => 'Stores comments and associated data.',
      'fields' => array(
        'cid' => array(
          'type' => 'serial',
          'not null' => TRUE,
          'description' => 'Primary Key: Unique comment ID.',
        ),
        'pid' => array(
          'type' => 'int',
          'not null' => TRUE,
          'default' => 0,
          'description' => 'The {comments}.cid to which this comment is a reply. If set to 0, this comment is not a reply to an existing comment.',
        ),
        'nid' => array(
          'type' => 'int',
          'not null' => TRUE,
          'default' => 0,
          'description' => 'The {node}.nid to which this comment is a reply.',
        ),
        'uid' => array(
          'type' => 'int',
          'not null' => TRUE,
          'default' => 0,
          'description' => 'The {users}.uid who authored the comment. If set to 0, this comment was created by an anonymous user.',
        ),
        'subject' => array(
          'type' => 'varchar',
          'length' => 64,
          'not null' => TRUE,
          'default' => '',
          'description' => 'The comment title.',
        ),
        'comment' => array(
          'type' => 'text',
          'not null' => TRUE,
          'size' => 'big',
          'description' => 'The comment body.',
        ),
        'hostname' => array(
          'type' => 'varchar',
          'length' => 128,
          'not null' => TRUE,
          'default' => '',
          'description' => "The author's host name.",
        ),
        'timestamp' => array(
          'type' => 'int',
          'not null' => TRUE,
          'default' => 0,
          'description' => 'The time that the comment was created, or last edited by its author, as a Unix timestamp.',
        ),
        'status' => array(
          'type' => 'int',
          'unsigned' => TRUE,
          'not null' => TRUE,
          'default' => 0,
          'size' => 'tiny',
          'description' => 'The published status of a comment. (0 = Published, 1 = Not Published)',
        ),
        'format' => array(
          'type' => 'int',
          'size' => 'small',
          'not null' => TRUE,
          'default' => 0,
          'description' => 'The {filter_formats}.format of the comment body.',
        ),
        'thread' => array(
          'type' => 'varchar',
          'length' => 255,
          'not null' => TRUE,
          'description' => "The vancode representation of the comment's place in a thread.",
        ),
        'name' => array(
          'type' => 'varchar',
          'length' => 60,
          'not null' => FALSE,
          'description' => "The comment author's name. Uses {users}.name if the user is logged in, otherwise uses the value typed into the comment form.",
        ),
        'mail' => array(
          'type' => 'varchar',
          'length' => 64,
          'not null' => FALSE,
          'description' => "The comment author's e-mail address from the comment form, if user is anonymous, and the 'Anonymous users may/must leave their contact information' setting is turned on.",
        ),
        'homepage' => array(
          'type' => 'varchar',
          'length' => 255,
          'not null' => FALSE,
          'description' => "The comment author's home page address from the comment form, if user is anonymous, and the 'Anonymous users may/must leave their contact information' setting is turned on.",
        ),
      ),
      'indexes' => array(
        'pid'    => array('pid'),
        'nid'    => array('nid'),
        'comment_uid'    => array('uid'),
        'status' => array('status'), // This index is probably unused
      ),
      'primary key' => array('cid'),
    ));
    $this->database->insert('comments')->fields(array(
      'cid',
      'pid',
      'nid',
      'uid',
      'subject',
      'comment',
      'hostname',
      'timestamp',
      'status',
      'format',
      'thread',
      'name',
      'mail',
      'homepage',
    ))
    // The comment structure is:
    // -1
    // -3
    // --2
    ->values(array(
      'cid' => 1,
      'pid' => 0,
      'nid' => 1,
      'uid' => 0,
      'subject' => 'The first comment.',
      'comment' => 'The first comment body.',
      'hostname' => '127.0.0.1',
      'timestamp' => 1390264918,
      'status' => 0,
      'format' => 1,
      'thread' => '01/',
      'name' => '1st comment author name',
      'mail' => 'comment1@example.com',
      'homepage' => 'http://drupal.org',
    ))
    ->values(array(
      'cid' => 2,
      'pid' => 3,
      'nid' => 1,
      'uid' => 0,
      'subject' => 'The response to the second comment.',
      'comment' => 'The second comment response body.',
      'hostname' => '127.0.0.1',
      'timestamp' => 1390264938,
      'status' => 0,
      'format' => 1,
      'thread' => '02/01',
      'name' => '3rd comment author name',
      'mail' => 'comment3@example.com',
      'homepage' => 'http://drupal.org',
    ))
    ->values(array(
      'cid' => 3,
      'pid' => 0,
      'nid' => 1,
      'uid' => 0,
      'subject' => 'The second comment.',
      'comment' => 'The second comment body.',
      'hostname' => '127.0.0.1',
      'timestamp' => 1390264948,
      // This comment is unpublished.
      'status' => 1,
      'format' => 1,
      'thread' => '02/',
      'name' => '3rd comment author name',
      'mail' => 'comment3@example.com',
      'homepage' => 'http://drupal.org',
    ))
    ->execute();
    $this->setModuleVersion('comment', '6001');
  }

}
