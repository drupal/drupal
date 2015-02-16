<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\Dump\Comments.
 *
 * THIS IS A GENERATED FILE. DO NOT EDIT.
 *
 * @see cores/scripts/dump-database-d6.sh
 * @see https://www.drupal.org/sandbox/benjy/2405029
 */

namespace Drupal\migrate_drupal\Tests\Table\d6;

use Drupal\migrate_drupal\Tests\Dump\DrupalDumpBase;

/**
 * Generated file to represent the comments table.
 */
class Comments extends DrupalDumpBase {

  public function load() {
    $this->createTable("comments", array(
      'primary key' => array(
        'cid',
      ),
      'fields' => array(
        'cid' => array(
          'type' => 'serial',
          'not null' => TRUE,
          'length' => '11',
        ),
        'pid' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '11',
          'default' => '0',
        ),
        'nid' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '11',
          'default' => '0',
        ),
        'uid' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '11',
          'default' => '0',
        ),
        'subject' => array(
          'type' => 'varchar',
          'not null' => TRUE,
          'length' => '64',
          'default' => '',
        ),
        'comment' => array(
          'type' => 'text',
          'not null' => TRUE,
          'length' => 100,
        ),
        'hostname' => array(
          'type' => 'varchar',
          'not null' => TRUE,
          'length' => '128',
          'default' => '',
        ),
        'timestamp' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '11',
          'default' => '0',
        ),
        'status' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '3',
          'default' => '0',
          'unsigned' => TRUE,
        ),
        'format' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '6',
          'default' => '0',
        ),
        'thread' => array(
          'type' => 'varchar',
          'not null' => TRUE,
          'length' => '255',
        ),
        'name' => array(
          'type' => 'varchar',
          'not null' => FALSE,
          'length' => '60',
        ),
        'mail' => array(
          'type' => 'varchar',
          'not null' => FALSE,
          'length' => '64',
        ),
        'homepage' => array(
          'type' => 'varchar',
          'not null' => FALSE,
          'length' => '255',
        ),
      ),
    ));
    $this->database->insert("comments")->fields(array(
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
    ->values(array(
      'cid' => '1',
      'pid' => '0',
      'nid' => '1',
      'uid' => '0',
      'subject' => 'The first comment.',
      'comment' => 'The first comment body.',
      'hostname' => '127.0.0.1',
      'timestamp' => '1390264918',
      'status' => '0',
      'format' => '1',
      'thread' => '01/',
      'name' => '1st comment author name',
      'mail' => 'comment1@example.com',
      'homepage' => 'http://drupal.org',
    ))->values(array(
      'cid' => '2',
      'pid' => '3',
      'nid' => '1',
      'uid' => '0',
      'subject' => 'The response to the second comment.',
      'comment' => 'The second comment response body.',
      'hostname' => '127.0.0.1',
      'timestamp' => '1390264938',
      'status' => '0',
      'format' => '1',
      'thread' => '02/01',
      'name' => '3rd comment author name',
      'mail' => 'comment3@example.com',
      'homepage' => 'http://drupal.org',
    ))->values(array(
      'cid' => '3',
      'pid' => '0',
      'nid' => '1',
      'uid' => '0',
      'subject' => 'The second comment.',
      'comment' => 'The second comment body.',
      'hostname' => '127.0.0.1',
      'timestamp' => '1390264948',
      'status' => '1',
      'format' => '1',
      'thread' => '02/',
      'name' => '3rd comment author name',
      'mail' => 'comment3@example.com',
      'homepage' => 'http://drupal.org',
    ))->execute();
  }

}
